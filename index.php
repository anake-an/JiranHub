<?php
/*
KP34903: Home Page
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
$news = $conn->query("SELECT * FROM forum_topics WHERE category='announcement' ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
$events = $conn->query("SELECT * FROM events ORDER BY event_date ASC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="main-wrapper">
        <div class="hero-section">
            <div class="container hero-wrapper">
                <div class="hero-text">
                    <span class="badge badge-accent mb-20">Residents Portal</span>
                    <h1>Welcome to Our JiranHub.</h1>
                    <p>Your central place for community news, safety alerts, and local events. Stay connected with your neighbours and keep our area safe.</p>
                    <div class="d-flex gap-10">
                        <a href="events.php" class="btn btn-primary">Join Activities</a>
                        <a href="forum.php" class="btn btn-secondary">Resident Forum</a>
                    </div>
                </div>
                <div style="flex:1;">
                    <img src="assets/images/hero_bg.jpg" alt="Community Area" class="hero-img">
                </div>
            </div>
        </div>
        <?php if($news): ?>
        <div class="bg-snow text-center" style="border-bottom:1px solid var(--silver); padding:12px;">
            <span class="badge badge-accent">News</span>
            <strong class="m-0 text-dark font-bold text-sm" style="margin:0 10px;"><?php echo htmlspecialchars($news['title']); ?></strong>
            <a href="view_topic.php?id=<?php echo $news['topic_id']; ?>" class="text-primary text-sm" style="text-decoration:underline;">Read more</a>
        </div>
        <?php endif; ?>
        <div class="container py-60">
            <div class="d-flex justify-between" style="align-items: flex-end; margin-bottom: 40px;">
                <div>
                    <h2>What's Happening</h2>
                    <p class="m-0">Upcoming events and activities in the area.</p>
                </div>
                <a href="events.php" class="btn btn-secondary" style="height:36px;">View All</a>
            </div>
            <div class="grid-3">
                <?php if ($events && $events->num_rows > 0): ?>
                    <?php while($row = $events->fetch_assoc()): ?>
                        <a href="event_details.php?id=<?php echo $row['event_id']; ?>" class="card">
                            <?php
                                $img = !empty($row['banner_image']) ? "uploads/banners/".$row['banner_image'] : "assets/images/default.jpg";
                                if(!file_exists($img)) $img = "assets/images/default.jpg";
                            ?>
                            <div style="position:relative;">
                                <img src="<?php echo $img; ?>" class="card-img" alt="Event">
                                <div style="position:absolute; top:15px; right:15px;">
                                    <span class="badge bg-white text-dark shadow-sm">
                                        <?php echo ($row['price'] > 0) ? 'RM '.$row['price'] : 'Free'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="d-flex justify-between align-center mb-10">
                                    <span class="text-primary font-bold text-sm">
                                        <?php echo strtoupper(date('M d', strtotime($row['event_date']))); ?>
                                    </span>
                                    <?php if($row['status']=='open'): ?>
                                        <span class="badge bg-open text-xs">OPEN</span>
                                    <?php elseif($row['status']=='closed'): ?>
                                        <span class="badge bg-closed text-xs">CLOSED</span>
                                    <?php else: ?>
                                        <span class="badge bg-cancelled text-xs">CANCELLED</span>
                                    <?php endif; ?>
                                </div>
                                <h3 style="font-size:1.15rem; margin-bottom:5px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="m-0 text-sm">
                                    <i class="fa-solid fa-location-dot text-slate" style="margin-right:5px;"></i>
                                    <?php echo htmlspecialchars($row['location']); ?>
                                </p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center" style="grid-column:1/-1; padding:60px; border:2px dashed var(--silver); border-radius:var(--radius);">
                        <i class="fa-regular fa-calendar text-slate mb-20" style="font-size:2.5rem; color:var(--silver) !important;"></i>
                        <p>No active events found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
