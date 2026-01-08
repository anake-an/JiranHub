<?php
/*
KP34903: View topic
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if (!isset($_GET['id'])) { header("Location: forum.php"); exit(); }
$tid = intval($_GET['id']);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
    $uid = $_SESSION['user_id'];
    if (isset($_POST['content'])) {
        $content = trim($_POST['content']);
        $stmt = $conn->prepare("INSERT INTO forum_replies (topic_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $tid, $uid, $content);
        $stmt->execute();


        $stmt_owner = $conn->prepare("SELECT user_id, title FROM forum_topics WHERE topic_id=?");
        $stmt_owner->bind_param("i", $tid);
        $stmt_owner->execute();
        $topic_data = $stmt_owner->get_result()->fetch_assoc();

        if ($topic_data && $topic_data['user_id'] != $uid) {
            $owner_id = $topic_data['user_id'];
            $notif_msg = "New reply on your topic: " . substr($topic_data['title'], 0, 30) . "...";
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt_notif->bind_param("is", $owner_id, $notif_msg);
            $stmt_notif->execute();
        }
        header("Location: view_topic.php?id=$tid"); exit();
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'close_topic') {
        $stmt = $conn->prepare("UPDATE forum_topics SET status='closed' WHERE topic_id=? AND user_id=?");
        $stmt->bind_param("ii", $tid, $uid);
        $stmt->execute();
        header("Location: view_topic.php?id=$tid"); exit();
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'delete_topic') {
        $stmt = $conn->prepare("DELETE FROM forum_topics WHERE topic_id=? AND user_id=?");
        $stmt->bind_param("ii", $tid, $uid);
        $stmt->execute();
        header("Location: forum.php"); exit();
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'report_content') {
        $reason = trim($_POST['reason']);
        $target_type = $_POST['target_type'];
        $target_id = intval($_POST['target_id']);

        if (($target_type == 'topic' || $target_type == 'reply') && $target_id > 0) {
            $stmt = $conn->prepare("INSERT INTO reports (user_id, target_type, target_id, reason) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $uid, $target_type, $target_id, $reason);
            $stmt->execute();
        }
        header("Location: view_topic.php?id=$tid&msg=reported"); exit();
    }
}
$stmt_t = $conn->prepare("SELECT t.*, u.full_name, u.role, u.profile_image FROM forum_topics t JOIN users u ON t.user_id=u.user_id WHERE t.topic_id=?");
$stmt_t->bind_param("i", $tid);
$stmt_t->execute();
$topic = $stmt_t->get_result()->fetch_assoc();
if(!$topic) { header("Location: forum.php"); exit(); }
$stmt_r = $conn->prepare("SELECT r.*, u.full_name, u.role, u.profile_image FROM forum_replies r JOIN users u ON r.user_id=u.user_id WHERE r.topic_id=? ORDER BY r.created_at ASC");
$stmt_r->bind_param("i", $tid);
$stmt_r->execute();
$replies = $stmt_r->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Forum</title>
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
        .post-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid var(--silver); }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="main-wrapper">
        <div class="container" style="padding: 60px 24px;">
            <a href="forum.php" style="display:inline-block; margin-bottom:20px; color:var(--primary); font-weight:600; text-decoration:none;">&larr; Back to Forum</a>
            <?php if(isset($_GET['msg']) && $_GET['msg']=='reported'): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:10px; border-radius:6px; margin-bottom:20px;">Report submitted. Admins will review it.</div>
            <?php endif; ?>
            <div class="card" style="padding: 30px; margin-bottom: 30px; position:relative;">
                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $topic['user_id']): ?>
                    <div style="position:absolute; top:20px; right:20px;">
                        <?php if($topic['status'] == 'open'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Close this topic? No more replies allowed.')">
                                <input type="hidden" name="action" value="close_topic">
                                <button class="btn btn-secondary" style="height:30px; font-size:0.8rem; padding:0 10px;">Close Topic</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this topic?')">
                                <input type="hidden" name="action" value="delete_topic">
                                <button class="btn btn-secondary" style="height:30px; font-size:0.8rem; padding:0 10px; color:#b91c1c; border-color:#fee2e2;">Delete Topic</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif(isset($_SESSION['user_id'])): ?>
                    <div style="position:absolute; top:20px; right:20px;">
                        <button onclick="openReportModal('topic', <?php echo $tid; ?>)" style="border:none; background:none; color:#94a3b8; cursor:pointer;" title="Report this post">
                            <i class="fa-solid fa-flag"></i>
                        </button>
                    </div>
                <?php endif; ?>
                <div>
                    <?php
                        $cat = $topic['category'];
                        $bg = '#f1f5f9'; $col = '#64748b'; $txt = 'General';
                        if($cat=='safety') { $bg='#ffedd5'; $col='#c2410c'; $txt='Safety Alert'; }
                        elseif($cat=='announcement') { $bg='#fee2e2'; $col='#b91c1c'; $txt='Announcement'; }
                        elseif($cat=='complaint') { $bg='#ffedd5'; $col='#ea580c'; $txt='Complaint'; }
                        elseif($cat=='events') { $bg='#dbeafe'; $col='#1d4ed8'; $txt='Event'; }
                        elseif($cat=='marketplace') { $bg='#d1fae5'; $col='#047857'; $txt='Marketplace'; }
                    ?>
                    <span class="badge" style="background:<?php echo $bg; ?>; color:<?php echo $col; ?>; margin-bottom:10px;"><?php echo $txt; ?></span>
                </div>
                <h1 style="margin-bottom: 10px; padding-right: 40px; margin-top:5px;"><?php echo htmlspecialchars($topic['title']); ?></h1>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                    <?php $p = !empty($topic['profile_image']) ? "uploads/profiles/".$topic['profile_image'] : "assets/images/default_user.png"; ?>
                    <img src="<?php echo $p; ?>" class="post-avatar">
                    <div>
                        <strong><?php echo htmlspecialchars($topic['full_name']); ?></strong>
                        <?php if($topic['role']=='admin') echo "<span class='badge' style='background:#0f172a; color:white; font-size:0.7rem;'>Admin</span>"; ?>
                        <div style="font-size:0.8rem; color:var(--slate);"><?php echo date('d M Y', strtotime($topic['created_at'])); ?></div>
                    </div>
                </div>
                <div style="line-height:1.6; white-space:pre-line;"><?php echo nl2br(htmlspecialchars($topic['content'])); ?></div>
                <?php if($topic['status'] == 'closed'): ?>
                    <div style="margin-top:20px; padding:10px; background:#f1f5f9; color:#64748b; font-weight:700; text-align:center; border-radius:6px;">
                        <i class="fa-solid fa-lock"></i> Topic Closed by Owner
                    </div>
                <?php endif; ?>
            </div>
            <h3 style="margin-bottom:20px;"><?php echo $replies->num_rows; ?> Replies</h3>
            <?php while($row = $replies->fetch_assoc()): ?>
                <div class="card" style="padding:20px; margin-bottom:15px; background:#f8fafc; position:relative;">
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $row['user_id']): ?>
                        <div style="position:absolute; top:15px; right:15px;">
                            <button onclick="openReportModal('reply', <?php echo $row['reply_id']; ?>)" style="border:none; background:none; color:#cbd5e1; cursor:pointer;" title="Report this reply">
                                <i class="fa-solid fa-flag" style="font-size:0.8rem;"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <?php $rp = !empty($row['profile_image']) ? "uploads/profiles/".$row['profile_image'] : "assets/images/default_user.png"; ?>
                        <img src="<?php echo $rp; ?>" class="post-avatar" style="width:30px; height:30px;">
                        <div>
                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                            <?php if($row['role']=='admin') echo "<span class='badge' style='background:#0f172a; color:white; font-size:0.7rem;'>Admin</span>"; ?>
                            <span style="color:var(--slate); font-size:0.8rem; margin-left:10px;"><?php echo date('d M, h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                    <div style="padding-left:40px; line-height:1.6;"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                </div>
            <?php endwhile; ?>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($topic['status'] == 'open'): ?>
                    <div class="card" style="padding:20px; margin-top:40px; border-top:4px solid var(--primary);">
                        <h4>Leave a Reply</h4>
                        <form method="POST">
                            <textarea name="content" rows="3" required style="width:100%; padding:10px; border:1px solid #ddd; margin:10px 0; border-radius:6px;"></textarea>
                            <button type="submit" class="btn btn-primary">Reply</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align:center; margin-top:40px;"><a href="login.php?redirect=view_topic.php?id=<?php echo $tid; ?>" class="btn btn-secondary">Login to Reply</a></div>
            <?php endif; ?>
        </div>
    </main>
    <div id="reportModal" class="modal">
        <div class="modal-content" style="max-width:400px;">
            <h3>Report Topic</h3>
            <form method="POST">
                <input type="hidden" name="action" value="report_content">
                <input type="hidden" name="target_type" id="report_target_type" value="topic">
                <input type="hidden" name="target_id" id="report_target_id" value="<?php echo $tid; ?>">
                <div style="margin-bottom:15px;">
                    <label>Reason</label>
                    <select name="reason" style="width:100%; padding:8px;">
                        <option value="Spam">Spam</option>
                        <option value="Harassment">Harassment</option>
                        <option value="Inappropriate Content">Inappropriate Content</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#b91c1c; width:100%;">Submit Report</button>
                <button type="button" onclick="document.getElementById('reportModal').style.display='none'" class="btn btn-secondary" style="width:100%; margin-top:10px;">Cancel</button>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        function openReportModal(type, id) {
            document.getElementById('report_target_type').value = type;
            document.getElementById('report_target_id').value = id;
            document.getElementById('reportModal').style.display = 'block';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('reportModal')) {
                document.getElementById('reportModal').style.display = "none";
            }
        }
    </script>
</body>
</html>
