<?php
session_start();
include 'config.php'; // Ensure this file connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Hash input password using MD5

    // Securely fetch user
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) { 
        $_SESSION['admin'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error_message = "Invalid login credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Education Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login_style.css">
   
</head>
<body>
    <div class="login-container">
        <h2>Login to Education Management</h2>
        <div class="line-wrapper">
            <div class="line"></div>
        </div>
        <p>Enter your credentials to access the admin panel</p>

        <form method="POST">
            <input type="text" name="username" class="input-field" placeholder="Admin Username" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <button type="submit" class="login-btn">Login</button>
        </form>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="login-footer">
            <p>Forget Password? <a href="#">Contact Your Service Provider? </a></p>
        </div>
        <div class="login-footer">
            <p><a href="../index.php">Back to Home Page</a></p>
        </div>
    </div>

</body>
</html>
