<?php
/*
KP34903: Forum
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_topic'])) {
    if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $cat = $_POST['category'];
    $uid = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    if ($cat == 'announcement' && $role != 'admin') $cat = 'general';
    $stmt = $conn->prepare("INSERT INTO forum_topics (user_id, title, content, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $uid, $title, $content, $cat);
    if($stmt->execute()) {
        header("Location: forum.php?msg=created"); exit();
    } else {
    }
}
$current_uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$total_sql = "SELECT count(*) as c FROM forum_topics";
$total_rows = $conn->query($total_sql)->fetch_assoc()['c'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT t.*, u.full_name, (SELECT COUNT(*) FROM forum_replies r WHERE r.topic_id = t.topic_id) as reply_count
        FROM forum_topics t JOIN users u ON t.user_id = u.user_id
        ORDER BY CASE WHEN t.category = 'announcement' THEN 1 ELSE 2 END, t.created_at DESC LIMIT $offset, $limit";
$topics = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="main-wrapper">
        <div class="container" style="padding: 60px 24px;">
            <div class="d-flex justify-between align-center mb-30">
                <div>
                    <h1 class="mb-10">Community Forum</h1>
                    <p class="text-slate m-0">Discuss, share, and stay updated.</p>
                </div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <button onclick="document.getElementById('topicModal').style.display='block'" class="btn btn-primary">New Topic</button>
                <?php endif; ?>
            </div>
            <?php if(isset($_GET['msg'])): ?>
                <div class="bg-open p-10 rounded mb-20 text-success font-bold">Topic posted successfully!</div>
            <?php endif; ?>
            <div class="d-flex flex-col gap-15">
                <?php if ($topics->num_rows > 0): ?>
                    <?php while($row = $topics->fetch_assoc()): ?>
                        <?php
                            $is_mine = ($row['user_id'] == $current_uid);
                            $card_class = "card forum-card";
                            if($row['category']=='announcement') {
                                $card_class .= " border-l-danger";
                            } elseif($is_mine) {
                                $card_class .= " border-l-primary";
                            } else {
                                $card_class .= " border-l-transparent";
                            }
                        ?>
                        <a href="view_topic.php?id=<?php echo $row['topic_id']; ?>" class="<?php echo $card_class; ?>">
                            <div class="forum-icon-box">
                                <?php if($row['category']=='announcement'): ?><i class="fa-solid fa-bullhorn text-danger"></i>
                                <?php elseif($row['category']=='safety'): ?><i class="fa-solid fa-shield-halved text-warning" style="color:#f59e0b;"></i>
                                <?php elseif($row['category']=='complaint'): ?><i class="fa-solid fa-triangle-exclamation text-orange" style="color:#ea580c;"></i>
                                <?php elseif($row['category']=='events'): ?><i class="fa-solid fa-calendar-day text-primary"></i>
                                <?php elseif($row['category']=='marketplace'): ?><i class="fa-solid fa-store text-success"></i>
                                <?php else: ?><i class="fa-regular fa-comments"></i><?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <?php if($row['category']=='announcement'): ?><span class="badge bg-cancelled">Announcement</span><?php endif; ?>
                                <h3 class="m-0 mb-10 text-dark"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <small class="text-slate">By <?php echo htmlspecialchars($row['full_name']); ?> &bull; <?php echo date('M d', strtotime($row['created_at'])); ?></small>
                            </div>
                            <div class="text-center">
                                <div class="forum-meta-large"><?php echo $row['reply_count']; ?></div>
                                <small class="text-slate">Replies</small>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-comments empty-icon"></i>
                        <h3 class="text-slate">No topics yet.</h3>
                        <p>Be the first to start a discussion!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination Controls -->
            <?php if($total_pages > 1): ?>
            <div class="d-flex justify-center gap-10 mt-30">
                <?php if($page > 1): ?>
                    <a href="forum.php?page=<?php echo $page-1; ?>" class="btn btn-secondary btn-sm">&larr; Previous</a>
                <?php endif; ?>
                <span class="d-flex align-center font-bold text-slate">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                <?php if($page < $total_pages): ?>
                    <a href="forum.php?page=<?php echo $page+1; ?>" class="btn btn-secondary btn-sm">Next &rarr;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
    <div id="topicModal" class="modal">
        <div class="modal-content">
            <div class="d-flex justify-between mb-20">
                <h2 class="m-0">Start Discussion</h2>
                <span onclick="document.getElementById('topicModal').style.display='none'" class="cursor-pointer text-lg">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="create_topic" value="1">
                <div class="mb-15">
                    <label class="font-bold block mb-10">Topic Title</label>
                    <input type="text" name="title" required class="w-full p-10 rounded border-silver">
                </div>
                <div class="mb-15">
                    <label class="font-bold block mb-10">Category</label>
                    <select name="category" class="w-full p-10 rounded border-silver">
                        <option value="general">General Discussion</option>
                        <option value="safety">Safety Alert</option>
                        <option value="complaint">Complaint / Aduan</option>
                        <option value="events">Community Events</option>
                        <option value="marketplace">Marketplace / Jualan</option>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role']=='admin'): ?>
                            <option value="announcement">Official Announcement</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-15">
                    <label class="font-bold block mb-10">Content</label>
                    <textarea name="content" rows="5" required class="w-full p-10 rounded border-silver"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-full">Post Topic</button>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        window.onclick = function(event) {
            if (event.target == document.getElementById('topicModal')) {
                document.getElementById('topicModal').style.display = "none";
            }
        }
    </script>
</body>
</html>
