<?php
/*
KP34903: Manage reports
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
    $rid = intval($_GET['id']);
    $action = $_GET['action'];
    $stmt_fetch = $conn->prepare("SELECT * FROM reports WHERE report_id=?");
    $stmt_fetch->bind_param("i", $rid);
    $stmt_fetch->execute();
    $res = $stmt_fetch->get_result();
    $rep = $res->fetch_assoc();
    if ($rep) {
        if ($action == 'delete_content') {
            $tid = $rep['target_id'];
            if ($rep['target_type'] == 'event') {
                $stmt_del = $conn->prepare("DELETE FROM events WHERE event_id=?");
                $stmt_del->bind_param("i", $tid);
                $stmt_del->execute();
            } elseif ($rep['target_type'] == 'topic') {
                $stmt_del = $conn->prepare("DELETE FROM forum_topics WHERE topic_id=?");
                $stmt_del->bind_param("i", $tid);
                $stmt_del->execute();
            } elseif ($rep['target_type'] == 'reply') {
                $stmt_del = $conn->prepare("DELETE FROM forum_replies WHERE reply_id=?");
                $stmt_del->bind_param("i", $tid);
                $stmt_del->execute();
            }
            $conn->query("UPDATE reports SET status='resolved' WHERE report_id=$rid");
            $msg = "Content deleted and report resolved.";
        } elseif ($action == 'dismiss') {
            $conn->query("UPDATE reports SET status='resolved' WHERE report_id=$rid");
            $msg = "Report dismissed.";
        }
    }
    header("Location: manage_reports.php?msg=updated"); exit();
}
$reports = $conn->query("SELECT r.*, u.full_name FROM reports r JOIN users u ON r.user_id = u.user_id WHERE r.status='pending' ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Admin</title>
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
        <a href="manage_users.php"><i class="fa-solid fa-user-gear w-25px"></i> Users (AJK)</a>
        <a href="manage_events.php"><i class="fa-solid fa-calendar w-25px"></i> Events</a>
        <a href="manage_registrations.php"><i class="fa-solid fa-users w-25px"></i> Registrations
            <?php if(isset($pending_nav) && $pending_nav > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_nav; ?></span><?php endif; ?>
        </a>
        <a href="attendance.php"><i class="fa-solid fa-clipboard-user w-25px"></i> Attendance</a>
        <?php if($_SESSION['role'] == 'admin'): ?><a href="manage_reports.php" class="active"><i class="fa-solid fa-flag w-25px"></i> Reports
            <?php if(isset($pending_reports) && $pending_reports > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_reports; ?></span><?php endif; ?>
        </a><?php endif; ?>
        <div class="sidebar-bottom">
            <a href="../index.php" target="_blank"><i class="fa-solid fa-external-link w-25px"></i> View Live Site</a>
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket w-25px"></i> Logout</a>
        </div>
    </div>
    <div class="content">
        <h1>Content Moderation</h1>
        <p class="text-slate mb-30">Review flagged content from the community.</p>
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-open p-10 rounded mb-20 text-success font-bold">Action completed successfully.</div>
        <?php endif; ?>
        <table class="admin-table">
            <thead><tr><th>Reporter</th><th>Type</th><th>Reason</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if($reports->num_rows > 0): ?>
                    <?php while($row = $reports->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><span class="text-sm text-slate"><?php echo date('d M Y', strtotime($row['created_at'])); ?></span></td>
                        <?php
                            $tid = $row['target_id'];
                            $preview = "<em>Content not found</em>";
                            $link = "#";
                            if ($row['target_type'] == 'event') {
                                $res_c = $conn->query("SELECT title FROM events WHERE event_id=$tid");
                                if ($res_c->num_rows > 0) {
                                    $preview = htmlspecialchars($res_c->fetch_assoc()['title']);
                                    $link = "../event_details.php?id=$tid";
                                }
                            } elseif ($row['target_type'] == 'topic') {
                                $res_c = $conn->query("SELECT title FROM forum_topics WHERE topic_id=$tid");
                                if ($res_c->num_rows > 0) {
                                    $preview = htmlspecialchars($res_c->fetch_assoc()['title']);
                                    $link = "../view_topic.php?id=$tid";
                                }
                            } elseif ($row['target_type'] == 'reply') {
                                $res_c = $conn->query("SELECT content, topic_id FROM forum_replies WHERE reply_id=$tid");
                                if ($res_c->num_rows > 0) {
                                    $rd = $res_c->fetch_assoc();
                                    $preview = htmlspecialchars(substr($rd['content'], 0, 50)) . "...";
                                    $link = "../view_topic.php?id=" . $rd['topic_id'];
                                }
                            }
                        ?>
                        <td>
                            <span class="badge bg-cancelled text-xs"><?php echo strtoupper($row['target_type']); ?></span>
                            <div class="mt-10 font-semibold"><?php echo $preview; ?></div>
                            <?php if($link != '#'): ?>
                                <a href="<?php echo $link; ?>" target="_blank" class="text-sm text-primary underline">View Content</a>
                            <?php endif; ?>
                        </td>
                        <td class="max-w-300"><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td>
                            <a href="manage_reports.php?action=delete_content&id=<?php echo $row['report_id']; ?>" class="btn btn-primary btn-sm btn-danger" onclick="return confirm('Delete the flagged content permanently?')">Delete Content</a>
                            <a href="manage_reports.php?action=dismiss&id=<?php echo $row['report_id']; ?>" class="btn btn-secondary btn-sm">Dismiss</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center p-30 text-slate">No pending reports.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
