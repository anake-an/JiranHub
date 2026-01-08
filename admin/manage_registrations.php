<?php
/*
KP34903: Manage registrations
Group 7
JiranHub Web
*/

session_start();
require '../db.php';
requireAdmin();

$sid_uid = $_SESSION['user_id'];
$sid_user = $conn->query("SELECT full_name, role FROM users WHERE user_id=$sid_uid")->fetch_assoc();
if ($_SESSION['role'] == 'organizer') {
    $pending_nav = $conn->query("SELECT count(*) as c FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.status='pending_payment' AND e.organizer_id=$sid_uid")->fetch_assoc()['c'];
} else {
    $pending_nav = $conn->query("SELECT count(*) as c FROM registrations WHERE status='pending_payment'")->fetch_assoc()['c'];
}
$pending_reports = $conn->query("SELECT count(*) as c FROM reports WHERE status='pending'")->fetch_assoc()['c'];
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reg_id = intval($_GET['id']);
    $action = $_GET['action'];
    $stmt_info = $conn->prepare("SELECT r.user_id, e.title FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.registration_id=?");
    $stmt_info->bind_param("i", $reg_id);
    $stmt_info->execute();
    $res = $stmt_info->get_result();
    if($res->num_rows > 0) {
        $info = $res->fetch_assoc();
        $uid = $info['user_id'];
        $evt_title = $info['title'];
        if ($action == 'approve') {
            $conn->query("UPDATE registrations SET status='confirmed' WHERE registration_id=$reg_id");
            $msg = "Great news! Your registration for '$evt_title' has been APPROVED.";
        } elseif ($action == 'reject') {
            $conn->query("UPDATE registrations SET status='cancelled' WHERE registration_id=$reg_id");
            $msg = "Update: Your registration for '$evt_title' was cancelled. Please contact admin.";
        }
        $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt_notif->bind_param("is", $uid, $msg);
        $stmt_notif->execute();
        header("Location: manage_registrations.php?msg=updated"); exit();
    }
}
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sql = "SELECT r.*, u.full_name, e.title, e.price FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN events e ON r.event_id = e.event_id WHERE 1=1 ";
if ($_SESSION['role'] == 'organizer') {
    $sql .= "AND e.organizer_id = $sid_uid ";
}
if ($filter == 'pending') { $sql .= "AND r.status = 'pending_payment' "; }
elseif ($filter == 'confirmed') { $sql .= "AND r.status = 'confirmed' "; }
$sql .= "ORDER BY r.created_at DESC";
$registrations = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registrations - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-primary-50">
<div class="admin-grid">
    <div class="mobile-admin-header">
        <i class="fa-solid fa-bars cursor-pointer text-dark" onclick="document.querySelector('.sidebar').classList.toggle('active')" style="font-size:1.5rem;"></i>
        <div class="font-bold text-lg text-primary"><i class="fa-solid fa-shield-halved"></i> AdminPanel</div>
    </div>
    <div class="sidebar">
        <div class="sidebar-user-block">
            <a href="dashboard.php" class="font-bold text-xl text-dark p-0">
                <i class="fa-solid fa-shield-halved text-primary"></i> AdminPanel
            </a>
            <div class="sidebar-user-info">
                <small class="text-slate uppercase text-xs">Logged in as</small><br>
                <span class="font-semibold text-dark"><?php echo htmlspecialchars($sid_user['full_name']); ?></span>
            </div>
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-chart-line w-25px"></i> Dashboard</a>
        <?php if($_SESSION['role'] == 'admin'): ?><a href="manage_users.php"><i class="fa-solid fa-user-gear w-25px"></i> Users (AJK)</a><?php endif; ?>
        <a href="manage_events.php"><i class="fa-solid fa-calendar w-25px"></i> Events</a>
        <a href="manage_registrations.php" class="active"><i class="fa-solid fa-users w-25px"></i> Registrations
            <?php if(isset($pending_nav) && $pending_nav > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_nav; ?></span><?php endif; ?>
        </a>
        <a href="attendance.php"><i class="fa-solid fa-clipboard-user w-25px"></i> Attendance</a>
        <?php if($_SESSION['role'] == 'admin'): ?><a href="manage_reports.php"><i class="fa-solid fa-flag w-25px"></i> Reports
            <?php if(isset($pending_reports) && $pending_reports > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_reports; ?></span><?php endif; ?>
        </a><?php endif; ?>
        <div class="sidebar-bottom">
            <a href="../index.php" target="_blank"><i class="fa-solid fa-external-link w-25px"></i> View Live Site</a>
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket w-25px"></i> Logout</a>
        </div>
    </div>
    <div class="content">
        <div class="d-flex justify-between align-center mb-30">
            <h1>Manage Registrations</h1>
            <div>
                <a href="?status=all" class="btn <?php echo ($filter=='all')?'btn-admin':'btn-secondary'; ?> btn-sm">All</a>
                <a href="?status=pending" class="btn <?php echo ($filter=='pending')?'btn-admin':'btn-secondary'; ?> btn-sm">Pending</a>
                <a href="?status=confirmed" class="btn <?php echo ($filter=='confirmed')?'btn-admin':'btn-secondary'; ?> btn-sm">Confirmed</a>
            </div>
        </div>
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-open p-10 rounded mb-20">Status updated successfully.</div>
        <?php endif; ?>
        <table class="admin-table">
            <thead><tr><th>User</th><th>Event</th><th>Payment Proof</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php if($registrations->num_rows > 0): ?>
                    <?php while($row = $registrations->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><span class="text-sm text-slate">ID: <?php echo $row['user_id']; ?></span></td>
                        <td><?php echo htmlspecialchars($row['title']); ?><br><span class="text-xs font-bold"><?php echo ($row['price'] > 0) ? 'RM '.$row['price'] : 'Free'; ?></span></td>
                        <td>
                            <?php if(!empty($row['payment_proof'])): ?>
                                <a href="../uploads/payments/<?php echo $row['payment_proof']; ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fa-regular fa-eye"></i> View</a>
                            <?php elseif($row['price'] > 0): ?><span class="text-danger text-sm">Not Uploaded</span>
                            <?php else: ?><span class="text-slate text-sm">N/A</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['status'] == 'pending_payment'): ?><span class="badge bg-orange">Pending</span>
                            <?php elseif($row['status'] == 'confirmed'): ?><span class="badge bg-open">Confirmed</span>
                            <?php else: ?><span class="badge bg-cancelled">Cancelled</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['status'] == 'pending_payment'): ?>
                                <a href="manage_registrations.php?action=approve&id=<?php echo $row['registration_id']; ?>" class="btn btn-primary btn-sm bg-success border-0" onclick="return confirm('Approve this payment?')"><i class="fa-solid fa-check"></i></a>
                                <a href="manage_registrations.php?action=reject&id=<?php echo $row['registration_id']; ?>" class="btn btn-secondary btn-sm text-danger" onclick="return confirm('Reject registration?')"><i class="fa-solid fa-xmark"></i></a>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center p-25 text-slate">No registrations found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
