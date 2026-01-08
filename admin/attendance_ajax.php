<?php
/*
KP34903: Attendance ajax
Group 7
JiranHub Web
*/

session_start();
require '../db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'organizer')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_id'])) {
    $reg_id = intval($_POST['reg_id']);
    $check = $conn->query("SELECT r.registration_id, u.full_name, r.attended FROM registrations r JOIN users u ON r.user_id = u.user_id WHERE r.registration_id=$reg_id");
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ($row['attended'] == 1) {
             echo json_encode(['success' => false, 'message' => $row['full_name'] . ' is already marked present.']);
        } else {
            $conn->query("UPDATE registrations SET attended=1 WHERE registration_id=$reg_id");
            echo json_encode(['success' => true, 'message' => 'Marked ' . $row['full_name'] . ' as Present!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Ticket ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>