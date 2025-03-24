<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function OpenCon()
{
    $dbhost = "127.0.0.1"; // Use 127.0.0.1 instead of localhost to avoid socket issues
    $dbuser = "root";
    $dbpass = ""; // Default is empty for XAMPP
    $dbname = "student_hub";

    try {
        $conn = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Open connection globally
$conn = OpenCon();
