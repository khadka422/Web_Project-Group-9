<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Set upload directory
$upload_dir = "uploads/programmes/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch Programme Leaders
$stmt = $conn->query("SELECT * FROM staff WHERE role = 'Programme Leader'");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$programme_to_edit = null;
$leader_id = null;

// Handle Programme Creation & Editing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    [$programme_id, $name, $desc, $level, $published, $leader_id] = [
        $_POST['programme_id'] ?? null,
        trim($_POST['name']),
        $_POST['description'],
        $_POST['level'],
        isset($_POST['published']) ? 1 : 0,
        $_POST['programme_leader'] ?? null,
    ];



    // Handle Image Upload
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $upload_dir . $image_name;

        // Check if the file is an image and its size is within the limit
        if ($_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png'];
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

    if (!empty($programme_id)) {
        // Check if new name already exists (excluding current programme)
        $stmt = $conn->prepare("SELECT id FROM programmes WHERE name = ? AND id != ?");
        $stmt->execute([$name, $programme_id]);
        $existing_programme = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_programme) {
            $_SESSION['error'] = "A programme with this name already exists.";
        } else {
            // Fetch existing image
            $stmt = $conn->prepare("SELECT image_path FROM programmes WHERE id = ?");
            $stmt->execute([$programme_id]);
            $old_image = $stmt->fetch(PDO::FETCH_ASSOC);

            // If new image uploaded, delete old image
            if ($image_path && $old_image && file_exists($old_image['image_path'])) {
                unlink($old_image['image_path']);
            } else {
                $image_path = $old_image['image_path']; // Keep old image if not changed
            }

            // Update Programme
            $stmt = $conn->prepare("UPDATE programmes SET name = ?, description = ?, level = ?, image_path = ?, published = ? WHERE id = ?");
            $stmt->execute([$name, $desc, $level, $image_path, $published, $programme_id]);

            // Remove existing Programme Leader
            $stmt = $conn->prepare("DELETE FROM programme_staff WHERE programme_id = ? AND role = 'Programme Leader'");
            $stmt->execute([$programme_id]);

            // Add new Programme Leader if selected
            if (!empty($leader_id)) {
                $stmt = $conn->prepare("INSERT INTO programme_staff (programme_id, staff_id, role) VALUES (?, ?, 'Programme Leader')");
                $stmt->execute([$programme_id, $leader_id]);
            }

            $_SESSION['success'] = "Programme updated successfully.";
        }
    } else {
        // Check if programme with the same name exists
        $stmt = $conn->prepare("SELECT id FROM programmes WHERE name = ?");
        $stmt->execute([$name]);
        $existing_programme = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_programme) {
            $_SESSION['error'] = "A programme with this name already exists.";
        } else {
            // Insert new programme
            $stmt = $conn->prepare("INSERT INTO programmes (name, description, level, image_path, published) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $level, $image_path, $published]);
            $programme_id = $conn->lastInsertId();

            // Insert Programme Leader if selected
            if (!empty($leader_id)) {
                $stmt = $conn->prepare("INSERT INTO programme_staff (programme_id, staff_id, role) VALUES (?, ?, 'Programme Leader')");
                $stmt->execute([$programme_id, $leader_id]);
            }
            // if (!empty($leader_id)) {
            //     $stmt = $conn->prepare("INSERT INTO programme_staff (programme_id, staff_id, role) VALUES (:programme_id, :staff_id, 'Programme Leader')");
            //     $stmt->bindParam(':programme_id', $programme_id);
            //     $stmt->bindParam(':staff_id', $leader_id);
            //     $stmt->execute();
            // }

            $_SESSION['success'] = "Programme added successfully.";
        }
    }

    // Redirect to refresh the page and display messages
    header("Location: manage_programmes.php");
    exit();
}

// Handle Programme Deletion
if (isset($_GET['delete'])) {
    $programme_id = $_GET['delete'];

    // Delete Image from Storage
    $stmt = $conn->prepare("SELECT image_path FROM programmes WHERE id = ?");
    $stmt->execute([$programme_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($image && file_exists($image['image_path'])) {
        unlink($image['image_path']);
    }

    // Delete Programme Leader Entry
    $stmt = $conn->prepare("DELETE FROM programme_staff WHERE programme_id = ?");
    $stmt->execute([$programme_id]);

    // Delete Programme
    $stmt = $conn->prepare("DELETE FROM programmes WHERE id = ?");
    $stmt->execute([$programme_id]);

    $_SESSION['success'] = "Programme deleted successfully.";

    header("Location: manage_programmes.php");
    exit();
}

// Fetch all programmes
$stmt = $conn->query("SELECT * FROM programmes");
$programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all staff who can be Programme Leaders
$stmt = $conn->query("SELECT * FROM staff WHERE role = 'Programme Leader'");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch programme leaders from programme_staff
$stmt = $conn->query("SELECT programme_id, staff_id FROM programme_staff WHERE role = 'Programme Leader'");
$programme_leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map programme leaders to their respective programmes
$leaders_map = [];
foreach ($programme_leaders as $leader) {
    $leaders_map[$leader['programme_id']] = $leader['staff_id'];
}


// If Editing an Existing Programme
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM programmes WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $programme_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch existing Programme Leader
    $stmt = $conn->prepare("SELECT staff_id FROM programme_staff WHERE programme_id = ? AND role = 'Programme Leader'");
    $stmt->execute([$_GET['id']]);
    $leader_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($leader_data) {
        $leader_id = $leader_data['staff_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Programmes</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="home-section">
        <?php include 'navbar.php'; ?>

        <div class="management-body">
            <!-- Existing Programmes Table -->
            <div class="management-table">
                <h3>Our Programmes</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Programme Name</th>
                            <th>Level</th>
                            <th>Programme Leader</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programmes as $row): ?>
                            <tr>
                                <td><img src="<?= $row['image_path'] ?>" width="50" alt="Programme Image"></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['level']) ?></td>
                                <td>

                                    <?php
                                    $leader_id = $leaders_map[$row['id']] ?? null;
                                    if ($leader_id) {
                                        foreach ($staff as $s) {
                                            if ($s['id'] == $leader_id) {
                                                echo htmlspecialchars($s['name']);
                                                break;
                                            }
                                        }
                                    } else {
                                        echo "Not Assigned";
                                    }
                                    ?>
                                </td>
                                <td><?= $row['published'] ? 'Published' : 'Unpublished' ?></td>
                                <td>
                                    <div class="edit-delete-actions">
                                        <a href="?id=<?= $row['id'] ?>" class="edit-btn">
                                            <i class='bx bx-edit bx-sm'></i>
                                        </a>
                                        <a href="?delete=<?= $row['id'] ?>" class="delete-btn"
                                            onclick="return confirm('Delete programme?')">
                                            <i class='bx bx-trash bx-sm'></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="management-heading">Manage Programmes</h2>
            <!-- Add/Edit Form Section -->
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="management-form">
                    <div class="form-group">
                        <label for="programme_name">Programme Name</label>
                        <input type="text" id="programme_name" name="name" placeholder="Enter Programme Name" required
                            value="<?= htmlspecialchars($programme_to_edit['name'] ?? '') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="programme_description">Description</label>
                        <textarea id="programme_description" name="description" required
                            placeholder="Enter Programme Description"><?= htmlspecialchars($programme_to_edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="programme_level">Level</label>
                        <select id="programme_level" name="level">
                            <option value="Undergraduate" <?= isset($programme_to_edit['level']) && $programme_to_edit['level'] == 'Undergraduate' ? 'selected' : '' ?>>Undergraduate</option>
                            <option value="Postgraduate" <?= isset($programme_to_edit['level']) && $programme_to_edit['level'] == 'Postgraduate' ? 'selected' : '' ?>>Postgraduate</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="programme_leader">Programme Leader</label>
                        <select id="programme_leader" name="programme_leader">
                            <option value="">No Leader</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= isset($leader_id) && $leader_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group image-upload">
                        <label for="programme_image">Upload Image</label>
                        <input type="file" id="programme_image" name="image" accept="image/*"
                            <?= isset($programme_to_edit) ? '' : 'required' ?> />
                        <?php if (!empty($programme_to_edit['image_path'])): ?>
                            <div class="image-preview">
                                <img src="<?= $programme_to_edit['image_path'] ?>" alt="Programme Image" width="100">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group checkbox">
                        <label class="checkbox-label">
                            <span>Published</span><input type="checkbox" name="published"
                                <?= isset($programme_to_edit['published']) && $programme_to_edit['published'] ? 'checked' : '' ?>>
                        </label>
                    </div>

                    <input type="hidden" name="programme_id"
                        value="<?= htmlspecialchars($programme_to_edit['id'] ?? '') ?>" />
                    <button type="submit"
                        class="submit-btn"><?= isset($programme_to_edit) ? "Update Programme" : "Add Programme" ?></button>
                    <?php if (isset($programme_to_edit)): ?>
                        <a href="manage_programmes.php" class="cancel-btn">Cancel Edit</a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="error-message" style="color: red; margin-top: 10px; font-weight: normal; ">
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
    <script src="script.js"></script>

</body>

</html>