<?php
/*
KP34903: Events
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
$search = "";
$conn->set_charset("utf8mb4");
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search = trim($_GET['q']);
    $searchTerm = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT * FROM events WHERE title LIKE ? OR location LIKE ? ORDER BY CASE WHEN status = 'open' THEN 1 ELSE 2 END, event_date ASC");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    $sql = "SELECT * FROM events ORDER BY CASE WHEN status = 'open' THEN 1 ELSE 2 END, event_date ASC";
    $events = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Events - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="main-wrapper">
        <div class="container py-60">
            <div class="search-container">
                <h1 class="mb-10">Explore Activities</h1>
                <p class="text-slate mb-30">Find out what's happening in your neighbourhood.</p>
                <form method="GET" class="search-form">
                    <input type="text" name="q" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search events or locations..."
                           onkeyup="searchEvents(this.value)"
                           class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>
            <div class="grid-3" id="eventGrid">
                <?php if ($events->num_rows > 0): ?>
                    <?php while($row = $events->fetch_assoc()): ?>
                        <a href="event_details.php?id=<?php echo $row['event_id']; ?>" class="card event-card-style">
                            <?php
                                $img = !empty($row['banner_image']) ? "uploads/banners/".$row['banner_image'] : "assets/images/default.jpg";
                                if(!file_exists($img)) $img = "assets/images/default.jpg";
                            ?>
                            <div class="event-card-pos">
                                <img src="<?php echo $img; ?>" class="card-img" alt="Event">
                                <div class="event-price-badge">
                                    <span class="price-tag">
                                        <?php echo ($row['price'] > 0) ? 'RM '.$row['price'] : 'Free'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="d-flex justify-between mb-10 align-center">
                                    <span class="text-sm font-bold text-primary">
                                        <?php echo strtoupper(date('M d', strtotime($row['event_date']))); ?>
                                    </span>
                                    <?php if($row['status']=='open'): ?>
                                        <div class="event-status-dot" title="Open"></div>
                                    <?php elseif($row['status']=='cancelled'): ?>
                                        <span class="badge bg-cancelled text-xs px-10">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-closed text-xs px-10">Closed</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg mb-10"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="text-sm m-0 text-slate">
                                    <i class="fa-solid fa-location-dot mr-5"></i>
                                    <?php echo htmlspecialchars($row['location']); ?>
                                </p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-calendar-xmark empty-icon"></i>
                        <h3 class="text-slate">No events found matching "<?php echo htmlspecialchars($search); ?>"</h3>
                        <a href="events.php" class="btn btn-secondary mt-15">Clear Search</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script>
        function searchEvents(query) {
            const grid = document.getElementById('eventGrid');
            fetch('api/search_events.php?q=' + encodeURIComponent(query))
                .then(response => response.text())
                .then(data => {
                    grid.innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        }
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            searchEvents(document.getElementById('searchInput').value);
        });
    </script>
</body>
</html>
