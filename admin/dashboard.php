<?php
/*
KP34903: Dashboard
Group 7
JiranHub Web
*/

session_start();
require '../db.php';
requireAdmin();

$sid_uid = $_SESSION['user_id'];
$sid_user = $conn->query("SELECT full_name, role FROM users WHERE user_id=$sid_uid")->fetch_assoc();
$pending_reports = $conn->query("SELECT count(*) as c FROM reports WHERE status='pending'")->fetch_assoc()['c'];

$stats = [];
if ($_SESSION['role'] == 'organizer') {
    $stats['users'] = $conn->query("SELECT count(*) as c FROM users")->fetch_assoc()['c'];
    $stats['events'] = $conn->query("SELECT count(*) as c FROM events WHERE status='open' AND organizer_id=$sid_uid")->fetch_assoc()['c'];
    $stats['pending'] = $conn->query("SELECT count(*) as c FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.status='pending_payment' AND e.organizer_id=$sid_uid")->fetch_assoc()['c'];
    $stats['income'] = $conn->query("SELECT sum(e.price) as total FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.status='confirmed' AND e.organizer_id=$sid_uid")->fetch_assoc()['total'];

    $recents = $conn->query("SELECT r.*, u.full_name, e.title FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN events e ON r.event_id = e.event_id WHERE e.organizer_id=$sid_uid ORDER BY r.created_at DESC LIMIT 5");
} else {
    $stats['users'] = $conn->query("SELECT count(*) as c FROM users")->fetch_assoc()['c'];
    $stats['events'] = $conn->query("SELECT count(*) as c FROM events WHERE status='open'")->fetch_assoc()['c'];
    $stats['pending'] = $conn->query("SELECT count(*) as c FROM registrations WHERE status='pending_payment'")->fetch_assoc()['c'];
    $stats['income'] = $conn->query("SELECT sum(e.price) as total FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.status='confirmed'")->fetch_assoc()['total'];

    $recents = $conn->query("SELECT r.*, u.full_name, e.title FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN events e ON r.event_id = e.event_id ORDER BY r.created_at DESC LIMIT 5");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JiranHub Admin</title>
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
        <a href="dashboard.php" class="active"><i class="fa-solid fa-chart-line w-25px"></i> Dashboard</a>
        <?php if($_SESSION['role'] == 'admin'): ?><a href="manage_users.php"><i class="fa-solid fa-user-gear w-25px"></i> Users (AJK)</a><?php endif; ?>
        <a href="manage_events.php"><i class="fa-solid fa-calendar w-25px"></i> Events</a>
        <a href="manage_registrations.php"><i class="fa-solid fa-users w-25px"></i> Registrations
            <?php if($stats['pending'] > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $stats['pending']; ?></span><?php endif; ?>
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
        <h1 class="mb-30">Dashboard Overview</h1>
        <div class="admin-stats-grid">
            <div class="stat-card"><div class="stat-label">Pending Payments</div><div class="stat-num text-danger"><?php echo $stats['pending']; ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Residents</div><div class="stat-num"><?php echo $stats['users']; ?></div></div>
            <div class="stat-card"><div class="stat-label">Active Events</div><div class="stat-num"><?php echo $stats['events']; ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-num text-success">RM <?php echo number_format($stats['income'] ?? 0, 2); ?></div></div>
        </div>
        <div class="bg-white rounded p-25 border">
            <h3>Recent Registrations</h3>
            <table class="admin-table">
                <thead><tr><th>User</th><th>Event</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php while($row = $recents->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td>
                            <?php if($row['status'] == 'pending_payment'): ?><span class="badge bg-orange">Pending</span>
                            <?php elseif($row['status'] == 'confirmed'): ?><span class="badge bg-open">Confirmed</span>
                            <?php else: ?><span class="badge bg-cancelled">Cancelled</span><?php endif; ?>
                        </td>
                        <td><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
