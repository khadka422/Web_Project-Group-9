<?php
session_start();
require_once 'admin/config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid program ID.";
    exit;
}

$programme_id = $_GET['id'];

// Handle interest registration
if (isset($_POST['register_interest'])) {
    $programme_id = $_POST['programme_id'];
    $student_name = isset($_POST['student_name']) ? htmlspecialchars(strip_tags(trim($_POST['student_name'])), ENT_QUOTES, 'UTF-8') : '';
    $email = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email'])), ENT_QUOTES, 'UTF-8') : '';
    

    if (!empty($programme_id) && !empty($student_name) && !empty($email)) {
        $sql_check = "SELECT COUNT(*) as count FROM student_interests WHERE programme_id = ? AND email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$programme_id, $email]);
        $count = $stmt_check->fetchColumn();

        if ($count == 0) {
            $sql = "INSERT INTO student_interests (programme_id, student_name, email) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$programme_id, $student_name, $email]);

            $_SESSION['intrest_success'] = "Your interest has been registered successfully!";
        } else {
            $_SESSION['intrest_error'] = "You have already registered your interest in this programme!";
        }
    } else {
        $_SESSION['intrest_error'] = "All fields are required!";
    }
}

// Handle interest withdrawal
if (isset($_POST['withdraw_interest'])) {
    $withdraw_email = isset($_POST['withdraw_email']) ? htmlspecialchars(strip_tags(trim($_POST['withdraw_email'])), ENT_QUOTES, 'UTF-8') : '';


    if (!empty($programme_id) && !empty($withdraw_email)) {
        $sql_delete = "DELETE FROM student_interests WHERE programme_id = ? AND email = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->execute([$programme_id, $withdraw_email]);

        if ($stmt_delete->rowCount() > 0) {
            $_SESSION['interest_success'] = "Your interest has been successfully withdrawn.";
        } else {
            $_SESSION['interest_error'] = "No interest record found with this email!";
        }
    } else {
        $_SESSION['interest_error'] = "Email is required to withdraw interest!";
    }
}

// Fetch programme details
$stmt = $conn->prepare("SELECT * FROM programmes WHERE id = ?");
$stmt->execute([$programme_id]);
$programme = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$programme) {
    echo "Programme not found.";
    exit;
}

// Fetch programme leader
$stmt = $conn->prepare("SELECT s.name, s.email, s.image_path 
                        FROM programme_staff ps
                        JOIN staff s ON ps.staff_id = s.id
                        WHERE ps.programme_id = ? AND ps.role = 'Programme Leader'");
$stmt->execute([$programme_id]);
$programme_leader = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch modules in the programme, ordered by year
$stmt = $conn->prepare("SELECT m.id, m.module_name, m.year, m.image_path 
                        FROM modules m 
                        WHERE m.programme_id = ? 
                        ORDER BY m.year");
$stmt->execute([$programme_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize modules by year
$modules_by_year = [];
foreach ($modules as $module) {
    $modules_by_year[$module['year']][] = $module;
}

// Fetch module leaders
$module_leaders = [];
foreach ($modules as $module) {
    $stmt = $conn->prepare("SELECT s.name, s.email, s.image_path 
                            FROM module_staff ms
                            JOIN staff s ON ms.staff_id = s.id
                            WHERE ms.module_id = ? AND ms.role = 'Module Leader'");
    $stmt->execute([$module['id']]);
    $module_leaders[$module['id']] = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($programme['name']); ?> - Programme Details</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1><?php echo htmlspecialchars($programme['name']); ?></h1>
        <img src="
        admin/<?php echo htmlspecialchars($programme['image_path']); ?>" alt="Programme Image"
            class="programme-image">
        <p><?php echo nl2br(htmlspecialchars($programme['description'])); ?></p>
        <p class="level"><strong>Level:</strong> <?php echo htmlspecialchars($programme['level']); ?></p>

        <?php if ($programme_leader): ?>
            <div class="programme-leader">
                <h2>Programme Leader</h2>
                <div class="leader-info">
                    <img src="admin/<?php echo htmlspecialchars($programme_leader['image_path']); ?>"
                        alt="Programme Leader Image">
                    <div>
                        <p><strong><?php echo htmlspecialchars($programme_leader['name']); ?></strong></p>
                        <p>Email: <?php echo htmlspecialchars($programme_leader['email']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h2>Modules</h2>
        <?php if (!empty($modules_by_year)): ?>
            <?php foreach ($modules_by_year as $year => $modules): ?>
                <h3>Year <?php echo $year; ?></h3>
                <table class="module-table">
                    <thead>
                        <tr>
                            <th> Image</th>
                            <th>Module Name</th>
                            <th>Module Leader</th>
                            <th> Image</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td><img src="admin/<?php echo htmlspecialchars($module['image_path']); ?>" alt="Module Image">
                                </td>
                                <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                                <td>
                                    <?php if (!empty($module_leaders[$module['id']])): ?>
                                        <?php echo htmlspecialchars($module_leaders[$module['id']]['name']); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($module_leaders[$module['id']]['image_path'])): ?>
                                        <img src="admin/<?php echo htmlspecialchars($module_leaders[$module['id']]['image_path']); ?>"
                                            alt="Module Leader Image">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($module_leaders[$module['id']])): ?>
                                        <?php echo htmlspecialchars($module_leaders[$module['id']]['email']); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No modules found for this programme.</p>
        <?php endif; ?>

        <div class="students">
            <h3>Register Your Interest</h3>
            <form method="post">
                <input type="hidden" name="programme_id" value="<?php echo htmlspecialchars($programme['id']); ?>" />
                <label for="student_name">Name:</label>
                <input type="text" id="student_name" name="student_name" required /><br>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required /><br>

                <input type="submit" name="register_interest" value="Register Interest" class="cta-button" />
            </form>

            <!-- Withdraw Interest Link -->
            <p>
                <a href="#" id="withdrawInterestLink">Withdraw Interest</a>
            </p>

            <!-- Hidden Withdraw Form -->
            <div id="withdrawForm" style="display: none;">
                <h3>Withdraw Your Interest</h3>
                <form method="post">
                    <input type="hidden" name="programme_id"
                        value="<?php echo htmlspecialchars($programme['id']); ?>" />
                    <label for="withdraw_email">Email:</label>
                    <input type="email" id="withdraw_email" name="withdraw_email" required /><br>
                    <input type="submit" name="withdraw_interest" value="Withdraw Interest" class="cta-button" />
                </form>
            </div>
            <?php if (isset($_SESSION['intrest_success'])): ?>
                <p class="success-message">
                    <?php echo $_SESSION['intrest_success'];
                    unset($_SESSION['intrest_success']); ?>
                </p>
            <?php elseif (isset($_SESSION['intrest_error'])): ?>
                <p class="error-message"><?php echo $_SESSION['intrest_error'];
                unset($_SESSION['intrest_error']); ?></p>
            <?php endif; ?>

        </div>
        <a href="index.php" class="cta-button">Back to Programmes</a>

    </div>
    <?php include 'footer.php'; ?>
    <script>
        document.getElementById("withdrawInterestLink").addEventListener("click", function (event) {
            event.preventDefault();
            document.getElementById("withdrawForm").style.display = "block";
        });
    </script>
</body>

</html>
