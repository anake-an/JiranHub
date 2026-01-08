<?php
/*
KP34903: Notifications
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$uid = $_SESSION['user_id'];
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'read_all') {
        $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
        header("Location: notifications.php");
        exit();
    }
    elseif ($_GET['action'] == 'clear_all') {
        $conn->query("DELETE FROM notifications WHERE user_id=$uid");
        header("Location: notifications.php");
        exit();
    }
}
$sql = "SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC";
$notifs = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Navbar Mobile Layout Fixes */
        .nav-inner { position: relative; display: flex; justify-content: space-between; align-items: center; }
        .nav-bell { position: relative; color: var(--slate); font-size: 1.2rem; margin-right: 15px; text-decoration: none; display: flex; align-items: center; }
        .nav-bell:hover { color: var(--primary); }
        .badge-count { position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 5px; border-radius: 50%; border: 2px solid white; font-weight: 700; }
        /* Mobile Menu */
        @media (max-width: 768px) {
            .mobile-right-elements { display: flex; align-items: center; }
            .nav-links { display: none; flex-direction: column; position: absolute; top: 70px; left: 0; width: 100%; background: white; border-bottom: 1px solid #e2e8f0; padding: 10px 0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); z-index: 1000; }
            .nav-links .nav-link { width: 100%; text-align: left; padding: 15px 25px; border-bottom: 1px solid #f1f5f9; margin: 0; }
            .nav-links .btn { margin: 15px 25px; width: auto; display: block; text-align: center; }
            .desktop-bell { display: none; }
        }
        @media (min-width: 769px) { .mobile-bell { display: none; } }
        /* Notification Item Styles */
        .notif-item { background: white; border: 1px solid var(--silver); padding: 20px; border-radius: var(--radius); margin-bottom: 15px; display: flex; align-items: flex-start; gap: 15px; transition: 0.2s; }
        .notif-item.unread { background: #f0f9ff; border-left: 4px solid var(--primary); }
        .notif-icon { width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); border: 1px solid var(--silver); flex-shrink: 0; }
        .unread .notif-icon { background: var(--primary); color: white; border: none; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="main-wrapper">
        <div class="container" style="padding: 60px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h1 style="margin-bottom: 5px;">Notifications</h1>
                    <p style="color: var(--slate); margin: 0;">Updates on your events and community status.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="notifications.php?action=read_all" class="btn btn-secondary" style="height:36px; font-size:0.85rem; padding:0 15px;">
                        <i class="fa-regular fa-envelope-open" style="margin-right:8px;"></i> Mark all Read
                    </a>
                    <a href="notifications.php?action=clear_all" class="btn btn-secondary" style="height:36px; font-size:0.85rem; padding:0 15px; color:#ef4444; border-color:#fee2e2;" onclick="return confirm('Clear all notifications?')">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
            </div>
            <?php if ($notifs->num_rows > 0): ?>
                <?php while($row = $notifs->fetch_assoc()): ?>
                    <div class="notif-item <?php echo ($row['is_read'] == 0) ? 'unread' : ''; ?>">
                        <div class="notif-icon">
                            <?php if(strpos($row['message'], 'approved') !== false || strpos($row['message'], 'confirmed') !== false): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php elseif(strpos($row['message'], 'cancelled') !== false || strpos($row['message'], 'rejected') !== false): ?>
                                <i class="fa-solid fa-xmark"></i>
                            <?php else: ?>
                                <i class="fa-regular fa-bell"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p style="margin: 0 0 5px 0; color: var(--dark); font-weight: <?php echo ($row['is_read'] == 0) ? '600' : '400'; ?>;">
                                <?php echo htmlspecialchars($row['message']); ?>
                            </p>
                            <small style="color: var(--slate); font-size: 0.8rem;">
                                <?php echo date('M d, h:i A', strtotime($row['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; border: 2px dashed var(--silver); border-radius: var(--radius);">
                    <i class="fa-regular fa-bell-slash" style="font-size: 3rem; color: var(--silver); margin-bottom: 20px;"></i>
                    <p style="color: var(--slate);">No notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
