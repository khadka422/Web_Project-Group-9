<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Set upload directory
$upload_dir = "uploads/staff_images/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch Staff Members
$stmt = $conn->query("SELECT * FROM staff");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$staff_to_edit = null;
$staff_id = null;

// Handle Staff Creation & Editing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    [$staff_id, $name, $email, $role, $image_path] = [
        $_POST['staff_id'] ?? null,
        trim($_POST['name']),
        trim($_POST['email']),
        $_POST['role'],
        null,
    ];

    // Handle Image Upload
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $upload_dir . $image_name;

        // Check if the file is an image and its size is within the limit
        if ($_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/svg+xml'];
            if (in_array($_FILES['image']['type'], $allowed_types)) {
                if ($_FILES['image']['size'] < 5000000) { // 5MB limit
                    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
                    $image_path = $target_file;
                } else {
                    $_SESSION['error'] = "File size exceeds 5MB.";
                }
            } else {
                
                $_SESSION['error'] = "Invalid file type.";
                header("Location: manage_staff.php");
                exit();
            }
        }
    }

    if (!empty($staff_id)) {
        // Check if new email already exists (excluding current staff)
        $stmt = $conn->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
        $stmt->execute([$email, $staff_id]);
        $existing_staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_staff) {
            $_SESSION['error'] = "A staff member with this email already exists.";
        } else {
            // Fetch existing image if no new one uploaded
            $stmt = $conn->prepare("SELECT image_path FROM staff WHERE id = ?");
            $stmt->execute([$staff_id]);
            $old_image = $stmt->fetch(PDO::FETCH_ASSOC);

            // If new image uploaded, delete old image
            if ($image_path && $old_image && file_exists($old_image['image_path'])) {
                unlink($old_image['image_path']);
            } else {
                $image_path = $old_image['image_path']; // Keep old image if not changed
            }

            // Update Staff
            $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, role = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $image_path, $staff_id]);

            $_SESSION['success'] = "Staff updated successfully.";
        }
    } else {
        // Check if staff with the same email exists
        $stmt = $conn->prepare("SELECT id FROM staff WHERE email = ?");
        $stmt->execute([$email]);
        $existing_staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_staff) {
            $_SESSION['error'] = "A staff member with this email already exists.";
        } else {
            // Insert new staff
            $stmt = $conn->prepare("INSERT INTO staff (name, email, role, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $role, $image_path]);
            $_SESSION['success'] = "Staff added successfully.";
        }
    }

    // Redirect to refresh the page and display messages
    header("Location: manage_staff.php");
    exit();
}

// Handle Staff Deletion
if (isset($_GET['delete'])) {
    $staff_id = $_GET['delete'];

    // Delete Image from Storage
    $stmt = $conn->prepare("SELECT image_path FROM staff WHERE id = ?");
    $stmt->execute([$staff_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($image && file_exists($image['image_path'])) {
        unlink($image['image_path']);
    }

    // Delete Staff
    $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->execute([$staff_id]);

    $_SESSION['success'] = "Staff deleted successfully.";

    header("Location: manage_staff.php");
    exit();
}

// Fetch Staff
$stmt = $conn->query("SELECT * FROM staff");
$staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If Editing an Existing Staff
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $staff_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="home-section">
        <?php include 'navbar.php'; ?>

        <div class="management-body">
            <!-- Existing Staff Table -->
            <div class="management-table">
                <h3>Our Staff</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_members as $row): ?>
                            <tr>
                                <td><img src="<?= $row['image_path'] ?>" width="50" alt="Staff Image"></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['role']) ?></td>
                                <td>
                                    <div class="edit-delete-actions">
                                        <a href="?id=<?= $row['id'] ?>" class="edit-btn">
                                            <i class='bx bx-edit bx-sm'></i>
                                        </a>
                                        <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Delete staff member?')">
                                            <i class='bx bx-trash bx-sm'></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="management-heading">Manage Staff</h2>
            <!-- Add/Edit Form Section -->
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="management-form">
                    <div class="form-group">
                        <label for="staff_name">Staff Name</label>
                        <input type="text" id="staff_name" name="name" placeholder="Enter Staff Name" required value="<?= htmlspecialchars($staff_to_edit['name'] ?? '') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="staff_email">Email</label>
                        <input type="email" id="staff_email" name="email" placeholder="Enter Staff Email" required value="<?= htmlspecialchars($staff_to_edit['email'] ?? '') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="staff_role">Role</label>
                        <select id="staff_role" name="role">
                            <option value="Programme Leader" <?= isset($staff_to_edit['role']) && $staff_to_edit['role'] == 'Programme Leader' ? 'selected' : '' ?>>Programme Leader</option>
                            <option value="Module Leader" <?= isset($staff_to_edit['role']) && $staff_to_edit['role'] == 'Module Leader' ? 'selected' : '' ?>>Module Leader</option>
                            <!-- Add more roles as needed -->
                        </select>
                    </div>

                    <div class="form-group image-upload">
                        <label for="staff_image">Upload Image</label>
                        <input type="file" id="staff_image" name="image" accept=".jpg, .jpeg, .svg, .png" <?= isset($staff_to_edit) ? '' : 'required' ?> />
                        <?php if (!empty($staff_to_edit['image_path'])): ?>
                            <div class="image-preview">
                                <img src="<?= $staff_to_edit['image_path'] ?>" alt="Staff Image" width="100">
                            </div>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="staff_id" value="<?= htmlspecialchars($staff_to_edit['id'] ?? '') ?>" />
                    <button type="submit" class="submit-btn"><?= isset($staff_to_edit) ? "Update Staff" : "Add Staff" ?></button>
                    <?php if (isset($staff_to_edit)): ?>
                        <a href="manage_staff.php" class="cancel-btn">Cancel Edit</a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="error-message" style="color: red; margin-top: 10px; font-weight: normal;">
                            <?= $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="success-message" style="color: green; margin-top: 10px; font-weight: normal;">
                            <?= $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
