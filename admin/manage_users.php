<?php
/*
KP34903: Manage users
Group 7
JiranHub Web
*/

session_start();
require '../db.php';
requireSuperAdmin();

$sid_uid = $_SESSION['user_id'];
$sid_user = $conn->query("SELECT full_name, role FROM users WHERE user_id=$sid_uid")->fetch_assoc();
if (isset($_SESSION['role']) && $_SESSION['role'] == 'organizer') {
    $pending_nav = $conn->query("SELECT count(*) as c FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.status='pending_payment' AND e.organizer_id=$sid_uid")->fetch_assoc()['c'];
} else {
    $pending_nav = $conn->query("SELECT count(*) as c FROM registrations WHERE status='pending_payment'")->fetch_assoc()['c'];
}
$pending_reports = $conn->query("SELECT count(*) as c FROM reports WHERE status='pending'")->fetch_assoc()['c'];
if (isset($_GET['action']) && isset($_GET['id'])) {
    $uid = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action == 'promote') {
        $conn->query("UPDATE users SET role='organizer' WHERE user_id=$uid");
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ($uid, 'Congratulations! You have been promoted to Community Organizer (AJK).')");
    } elseif ($action == 'demote') {
        $conn->query("UPDATE users SET role='resident' WHERE user_id=$uid");
    } elseif ($action == 'delete') {
        if ($uid != $_SESSION['user_id']) $conn->query("DELETE FROM users WHERE user_id=$uid");
    }
    header("Location: manage_users.php?msg=updated"); exit();
}
$current_id = $_SESSION['user_id'];
$users = $conn->query("SELECT * FROM users ORDER BY role ASC, full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
        <a href="manage_users.php" class="active"><i class="fa-solid fa-user-gear w-25px"></i> Users (AJK)</a>
        <a href="manage_events.php"><i class="fa-solid fa-calendar w-25px"></i> Events</a>
        <a href="manage_registrations.php"><i class="fa-solid fa-users w-25px"></i> Registrations
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
        <h1>Manage Community Members</h1>
        <p class="text-slate mb-30">Promote trusted residents to AJK (Organizer) status.</p>
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-open p-10 rounded mb-20">User role updated successfully.</div>
        <?php endif; ?>
        <table class="admin-table">
            <thead><tr><th>User Info</th><th>Current Role</th><th>Contact</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><span class="text-sm text-slate"><?php echo htmlspecialchars($row['email']); ?></span></td>
                    <td>
                        <?php if($row['role'] == 'admin'): ?><span class="badge bg-admin">Admin</span>
                        <?php elseif($row['role'] == 'organizer'): ?><span class="badge bg-organizer">Organizer (AJK)</span>
                        <?php else: ?><span class="badge bg-closed">Resident</span><?php endif; ?>
                    </td>
                    <td><?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : '-'; ?></td>
                            <td>
                                <?php if($row['user_id'] == $current_id): ?>
                                    <span class="text-sm text-slate">(Current User)</span>
                                <?php else: ?>
                                    <?php if($row['role'] == 'resident'): ?>
                                        <a href="manage_users.php?action=promote&id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Promote to Organizer?')">Promote to AJK</a>
                                    <?php elseif($row['role'] == 'organizer'): ?>
                                        <a href="manage_users.php?action=demote&id=<?php echo $row['user_id']; ?>" class="btn btn-secondary btn-sm btn-warning" onclick="return confirm('Demote to Resident?')">Demote</a>
                                    <?php endif; ?>
                                    <?php if($row['role'] != 'admin'): ?>
                                        <a href="manage_users.php?action=delete&id=<?php echo $row['user_id']; ?>" class="btn btn-secondary btn-sm btn-danger" onclick="return confirm('Delete this user?');"><i class="fa-solid fa-trash"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
