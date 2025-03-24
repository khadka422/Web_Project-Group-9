<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}


// Handle CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_interests.csv"');

    $output = fopen('php://output', 'w');
    // Adding header row
    fputcsv($output, ['Name', 'Email', 'Program', 'Date Registered'], ',', '"', '\\');

    $programme_filter = $_GET['programme'] ?? 'all';
    $sql = "SELECT si.student_name, si.email, p.name AS programme_name, si.created_at 
            FROM student_interests si
            JOIN programmes p ON si.programme_id = p.id";

    $params = [];
    if ($programme_filter !== 'all') {
        $sql .= " WHERE si.programme_id = ?";
        $params[] = $programme_filter;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Fetch and output the data for each student
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Add the row data to the CSV output
        fputcsv($output, [
            $row['student_name'],
            $row['email'],
            $row['programme_name'],
            $row['created_at']
        ], ',', '"', '\\');
    }

    fclose($output);
    exit();
}





// Fetch programmes for dropdown
$programmes = $conn->query("SELECT id, name FROM programmes")->fetchAll(PDO::FETCH_ASSOC);

// Get filter from URL
$programme_filter = $_GET['programme'] ?? 'all';

// Build SQL Query for displaying student interests
$sql = "SELECT si.student_name, si.email, p.name AS programme_name, si.created_at 
        FROM student_interests si
        JOIN programmes p ON si.programme_id = p.id";

$params = [];
if ($programme_filter !== 'all') {
    $sql .= " WHERE si.programme_id = ?";
    $params[] = $programme_filter;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$student_interests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Interests</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="home-section">
        <?php include 'navbar.php'; ?>


    <div class="management-body">

        <div class="management-table">
            <h3>Student Interests</h3>

            <!-- Filter and Export Section -->
            <div class="filter-export-container">
                <!-- Filter Form -->
                <form method="GET" class="filter-form">
                    <label for="programme" class="filter-label">Filter by Programme:</label>
                    <select name="programme" id="programme" class="filter-select">
                        <option value="all">All</option>
                        <?php foreach ($programmes as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($programme_filter == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="filter-btn">Apply Filter</button>
                </form>

                <!-- Export Button -->
                <a href="?programme=<?= $programme_filter ?>&export=true" class="export-btn">Export Mailing Lists</a>
            </div>

            <!-- Student Interests Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Program</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_interests as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['programme_name']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($student_interests)): ?>
                        <tr><td colspan="4">No student interests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>


</body>
</html>
