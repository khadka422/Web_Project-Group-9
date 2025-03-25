<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch data (Example: number of programs, modules, staff)
$stmt = $conn->query("SELECT COUNT(*) AS total_programmes FROM programmes");
$total_programmes = $stmt->fetch(PDO::FETCH_ASSOC)['total_programmes'];

$stmt = $conn->query("SELECT COUNT(*) AS total_modules FROM modules");
$total_modules = $stmt->fetch(PDO::FETCH_ASSOC)['total_modules'];

$stmt = $conn->query("SELECT COUNT(*) AS total_staff FROM staff");
$total_staff = $stmt->fetch(PDO::FETCH_ASSOC)['total_staff'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Education Management</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="home-section">
        <?php include 'navbar.php'; ?>

        <div class="management-body">
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h1>Welcome to Education Management Dashboard</h1>
                    <p>Manage your education system efficiently and access key metrics</p>
                </div>

                <!-- Dashboard Cards (Summary Stats) -->
                <div class="dashboard-cards">
                    <div class="card">
                        <i class="bx bxs-school bx-lg"></i>
                        <h2><?= $total_programmes ?></h2>
                        <p>Programmes</p>
                    </div>
                    <div class="card">
                        <i class="bx bx-book bx-lg"></i>
                        <h2><?= $total_modules ?></h2>
                        <p>Modules</p>
                    </div>
                    <div class="card">
                        <i class="bx bx-user bx-lg"></i>
                        <h2><?= $total_staff ?></h2>
                        <p>Staff</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>

</html>
