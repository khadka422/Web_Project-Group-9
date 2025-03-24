<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Set upload directory
$upload_dir = "uploads/modules/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch Programmes
$stmt = $conn->query("SELECT * FROM programmes");
$programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Staff (Eligible to be Module Leaders)
$stmt = $conn->query("SELECT * FROM staff WHERE role IN ('Module Leader', 'Lecturer')");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$module_to_edit = null;
$leader_id = null;

// Handle Module Creation & Editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_module'])) {
    [$module_id, $programme_id, $module_name, $year, $leader_id] = [
        $_POST['module_id'] ?? null,
        $_POST['programme_id'],
        trim($_POST['module_name']),
        $_POST['year'],
        $_POST['module_leader'] ?? null,
    ];

    // Handle Image Upload
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $upload_dir . $image_name;

        // Check file type and size
        if ($_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/jpg',];
            if (in_array($_FILES['image']['type'], $allowed_types)) {
                if ($_FILES['image']['size'] < 5000000) { // 5MB limit
                    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
                    $image_path = $target_file;
                } else {
                    $_SESSION['error'] = "File size exceeds 5MB.";
                }
            } else {
                $_SESSION['error'] = "Invalid file type.";
            }
        }
    }

    if (!empty($module_id)) {
        // Check if module name already exists (excluding current module)
        $stmt = $conn->prepare("SELECT id FROM modules WHERE module_name = ? AND id != ?");
        $stmt->execute([$module_name, $module_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['error'] = "A module with this name already exists.";
        } else {
            // Fetch existing image
            $stmt = $conn->prepare("SELECT image_path FROM modules WHERE id = ?");
            $stmt->execute([$module_id]);
            $old_image = $stmt->fetch(PDO::FETCH_ASSOC);

            // If new image uploaded, delete old image
            if ($image_path && $old_image && file_exists($old_image['image_path'])) {
                unlink($old_image['image_path']);
            } else {
                $image_path = $old_image['image_path']; // Keep old image if not changed
            }

            // Update Module
            $stmt = $conn->prepare("UPDATE modules SET programme_id = ?, module_name = ?, year = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$programme_id, $module_name, $year, $image_path, $module_id]);

            // Remove existing Module Leader
            $stmt = $conn->prepare("DELETE FROM module_staff WHERE module_id = ? AND role = 'Module Leader'");
            $stmt->execute([$module_id]);

            // Add new Module Leader if selected
            if (!empty($leader_id)) {
                $stmt = $conn->prepare("INSERT INTO module_staff (module_id, staff_id, role) VALUES (?, ?, 'Module Leader')");
                $stmt->execute([$module_id, $leader_id]);
            }

            $_SESSION['success'] = "Module updated successfully.";
        }
    } else {
        // Check if module name exists
        $stmt = $conn->prepare("SELECT id FROM modules WHERE module_name = ?");
        $stmt->execute([$module_name]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['error'] = "A module with this name already exists.";
        } else {
            // Insert new module
            $stmt = $conn->prepare("INSERT INTO modules (programme_id, module_name, year, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$programme_id, $module_name, $year, $image_path]);
            $module_id = $conn->lastInsertId();

            // Insert Module Leader if selected
            if (!empty($leader_id)) {
                $stmt = $conn->prepare("INSERT INTO module_staff (module_id, staff_id, role) VALUES (?, ?, 'Module Leader')");
                $stmt->execute([$module_id, $leader_id]);
            }

            $_SESSION['success'] = "Module added successfully.";
        }
    }

    header("Location: manage_modules.php");
    exit();
}

// Handle Module Deletion
if (isset($_GET['delete'])) {
    $module_id = $_GET['delete'];

    // Delete Image from Storage
    $stmt = $conn->prepare("SELECT image_path FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($image && file_exists($image['image_path'])) {
        unlink($image['image_path']);
    }

    // Delete Module Leader Entry
    $stmt = $conn->prepare("DELETE FROM module_staff WHERE module_id = ?");
    $stmt->execute([$module_id]);

    // Delete Module
    $stmt = $conn->prepare("DELETE FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);

    $_SESSION['success'] = "Module deleted successfully.";
    header("Location: manage_modules.php");
    exit();
}

// Fetch all modules with details
$stmt = $conn->query("
    SELECT m.id, m.module_name, m.year, m.image_path, p.name AS programme_name, s.name AS leader_name 
    FROM modules m
    JOIN programmes p ON m.programme_id = p.id
    LEFT JOIN module_staff ms ON m.id = ms.module_id AND ms.role = 'Module Leader'
    LEFT JOIN staff s ON ms.staff_id = s.id
");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If Editing an Existing Module
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $module_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch existing Module Leader
    $stmt = $conn->prepare("SELECT staff_id FROM module_staff WHERE module_id = ? AND role = 'Module Leader'");
    $stmt->execute([$_GET['id']]);
    $leader_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $leader_id = $leader_data ? $leader_data['staff_id'] : null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="home-section">
        <?php include 'navbar.php'; ?>

        <div class="management-body">
            <!-- Existing Modules Table -->
            <div class="management-table">
                <h3>Our Modules</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Module Name</th>
                            <th>Programme</th>
                            <th>Year</th>
                            <th>Module Leader</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $mod): ?>
                            <tr>
                                <td><img src="<?= $mod['image_path'] ?>" width="50" alt="Module Image"></td>
                                <td><?= htmlspecialchars($mod['module_name']) ?></td>
                                <td><?= htmlspecialchars($mod['programme_name']) ?></td>
                                <td><?= htmlspecialchars($mod['year']) ?></td>
                                <td><?= $mod['leader_name'] ? htmlspecialchars($mod['leader_name']) : 'Not Assigned' ?></td>
                                <td>
                                    <div class="edit-delete-actions">
                                        <a href="?id=<?= $mod['id'] ?>" class="edit-btn">
                                            <i class='bx bx-edit bx-sm'></i>
                                        </a>
                                        <a href="?delete=<?= $mod['id'] ?>" class="delete-btn" 
                                           onclick="return confirm('Delete module?')">
                                            <i class='bx bx-trash bx-sm'></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="management-heading">Manage Modules</h2>
            <!-- Add/Edit Form Section -->
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="management-form">
                    <input type="hidden" name="module_id" value="<?= htmlspecialchars($module_to_edit['id'] ?? '') ?>">
                    
                    <div class="form-group">
                        <label for="programme_id">Programme</label>
                        <select id="programme_id" name="programme_id" required>
                            <option value="" disabled>Select Programme</option>
                            <?php foreach ($programmes as $prog): ?>
                                <option value="<?= $prog['id'] ?>" 
                                    <?= isset($module_to_edit['programme_id']) && $prog['id'] == $module_to_edit['programme_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prog['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="module_name">Module Name</label>
                        <input type="text" id="module_name" name="module_name" placeholder="Enter Module Name" required
                            value="<?= htmlspecialchars($module_to_edit['module_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="year">Year of Study</label>
                        <input type="number" id="year" name="year" placeholder="Year" required min="1" max="4"
                            value="<?= htmlspecialchars($module_to_edit['year'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="module_leader">Module Leader</label>
                        <select id="module_leader" name="module_leader">
                            <option value="">No Leader</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" 
                                    <?= isset($leader_id) && $leader_id == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group image-upload">
                        <label for="image">Upload Image</label>
                        <input type="file" id="image" name="image" accept=".jpeg, .png, .svg, .jpg"
                            <?= isset($module_to_edit) ? '' : 'required' ?>>
                        <?php if (!empty($module_to_edit['image_path'])): ?>
                            <div class="image-preview">
                                <img src="<?= $module_to_edit['image_path'] ?>" alt="Module Image" width="100">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="save_module" class="submit-btn">
                        <?= isset($module_to_edit) ? "Update Module" : "Add Module" ?>
                    </button>
                    <?php if (isset($module_to_edit)): ?>
                        <a href="manage_modules.php" class="cancel-btn">Cancel Edit</a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="error-message" style="color: red; margin-top: 10px; font-weight: normal;">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="success-message" style="color: green; margin-top: 10px; font-weight: normal;">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <script src="script.js"></script>

</body>
</html>