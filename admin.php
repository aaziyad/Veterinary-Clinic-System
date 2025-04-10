<?php
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit;
}

// Database connection configuration
$host = 'localhost';
$db = 'pawdopter_care';
$user = 'root';
$pass = ''; // Replace with your database password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['add_doctor'])) {
            // Add doctor
            $doctor_name = $_POST['doctor_name'];
            $available_date = $_POST['available_date'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $fees = $_POST['fees'];

            $sql = "INSERT INTO doctors (doctor_name, available_date, start_time, end_time, fees) 
                    VALUES (:doctor_name, :available_date, :start_time, :end_time, :fees)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':doctor_name' => $doctor_name,
                ':available_date' => $available_date,
                ':start_time' => $start_time,
                ':end_time' => $end_time,
                ':fees' => $fees
            ]);
            $success_message = "Doctor added successfully!";
        }

        if (isset($_POST['approve_adoption'])) {
            // Approve adoption request
            $request_id = $_POST['request_id'];

            $sql = "UPDATE adoption_requests SET status = 'approved' WHERE id = :request_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':request_id' => $request_id]);
            $success_message = "Adoption request approved successfully!";
        }

        if (isset($_POST['remove_doctor'])) {
            // Remove doctor
            $doctor_id = $_POST['doctor_id'];

            $sql = "DELETE FROM doctors WHERE id = :doctor_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':doctor_id' => $doctor_id]);
            $success_message = "Doctor removed successfully!";
        }

        if (isset($_POST['add_pet'])) {
            // Add pet
            $name = $_POST['name'];
            $breed = $_POST['breed'];
            $age = $_POST['age'];
            $description = $_POST['description'];

            $sql = "INSERT INTO pets (name, breed, age, description, available) 
                    VALUES (:name, :breed, :age, :description, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':breed' => $breed,
                ':age' => $age,
                ':description' => $description
            ]);
            $success_message = "Pet added successfully!";
        }
    }

    // Fetch all necessary data
    $doctors = $pdo->query("SELECT * FROM doctors")->fetchAll(PDO::FETCH_ASSOC);
    $adoption_requests = $pdo->query("SELECT ar.id, p.name AS pet_name, u.username, ar.status 
                                      FROM adoption_requests ar
                                      JOIN pets p ON ar.pet_id = p.id
                                      JOIN users u ON ar.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
    $pets = $pdo->query("SELECT * FROM pets")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors
    echo "Error: " . $e->getMessage();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css"> <!-- Main CSS -->
    <link rel="stylesheet" href="logout.css"> <!-- Logout Button CSS -->
    <link rel="stylesheet" href="admin.css">
 
</head>
<body>
    <div class="header-area">
        <div class="bottom-header">
            <h2>Admin Panel</h2>
            <ul class="navigation">
                <li><a href="index.html">Home</a></li>
                <li><a href="logout.php" class="logout-button">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Success Message -->
    <?php if (isset($success_message)) : ?>
        <div style="text-align: center; color: green;">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>

    <!-- Add Doctor -->
    <div class="admin-section">
        <h1>Add Doctor</h1>
        <form method="POST" action="">
            <label for="doctor_name">Doctor Name:</label>
            <input type="text" id="doctor_name" name="doctor_name" required>

            <label for="available_date">Available Date:</label>
            <input type="date" id="available_date" name="available_date" required>

            <label for="start_time">Start Time:</label>
            <input type="time" id="start_time" name="start_time" required>

            <label for="end_time">End Time:</label>
            <input type="time" id="end_time" name="end_time" required>

            <label for="fees">Fees:</label>
            <input type="number" id="fees" name="fees" step="0.01" required>

            <button type="submit" name="add_doctor">Add Doctor</button>
        </form>
    </div>

    <!-- Adoption Requests -->
    <div class="admin-section">
        <h1>Adoption Requests</h1>
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Pet Name</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($adoption_requests as $request) : ?>
                    <tr>
                        <td><?php echo $request['id']; ?></td>
                        <td><?php echo $request['pet_name']; ?></td>
                        <td><?php echo $request['username']; ?></td>
                        <td><?php echo $request['status']; ?></td>
                        <td>
                            <?php if ($request['status'] === 'pending') : ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="approve_adoption">Approve</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Available Doctors -->
    <div class="admin-section">
        <h1>Available Doctors</h1>
        <table>
            <thead>
                <tr>
                    <th>Doctor ID</th>
                    <th>Name</th>
                    <th>Available Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Fees</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doctor) : ?>
                    <tr>
                        <td><?php echo $doctor['id']; ?></td>
                        <td><?php echo $doctor['doctor_name']; ?></td>
                        <td><?php echo $doctor['available_date']; ?></td>
                        <td><?php echo $doctor['start_time']; ?></td>
                        <td><?php echo $doctor['end_time']; ?></td>
                        <td><?php echo $doctor['fees']; ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                <button type="submit" name="remove_doctor">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Manage Pets -->
    <div class="admin-section">
        <h1>Manage Pets</h1>
        <form method="POST" action="">
            <label for="name">Pet Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="breed">Breed:</label>
            <input type="text" id="breed" name="breed" required>

            <label for="age">Age:</label>
            <input type="number" id="age" name="age" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <button type="submit" name="add_pet">Add Pet</button>
        </form>

        <h2>Available Pets</h2>
        <table>
            <thead>
                <tr>
                    <th>Pet ID</th>
                    <th>Name</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pets as $pet) : ?>
                    <tr>
                        <td><?php echo $pet['id']; ?></td>
                        <td><?php echo $pet['name']; ?></td>
                        <td><?php echo $pet['breed']; ?></td>
                        <td><?php echo $pet['age']; ?></td>
                        <td><?php echo $pet['description']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
