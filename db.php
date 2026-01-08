<?php
/*
KP34903: Database Connection
Group 7
JiranHub Web
*/

//Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "jiranhub_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("CRITICAL ERROR: Database connection failed. " . $conn->connect_error);
}

//Send notification function
function sendNotification($conn, $user_id, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}

//Require login function
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Security: Require Admin OR Organizer
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'organizer')) {
        header("Location: ../login.php");
        exit();
    }
}

// Security: Require Super Admin ONLY
function requireSuperAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}
?>
