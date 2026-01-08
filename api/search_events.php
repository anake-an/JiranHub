<?php
/*
KP34903: Search events
Group 7
JiranHub Web
*/

require '../db.php';
$search = "";
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search = trim($_GET['q']);
    $searchTerm = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT * FROM events WHERE title LIKE ? OR location LIKE ? ORDER BY CASE WHEN status = 'open' THEN 1 ELSE 2 END, event_date ASC");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    $events = $conn->query("SELECT * FROM events ORDER BY CASE WHEN status = 'open' THEN 1 ELSE 2 END, event_date ASC");
}
if ($events->num_rows > 0) {
    while($row = $events->fetch_assoc()) {
        $img = !empty($row['banner_image']) ? "uploads/banners/".$row['banner_image'] : "assets/images/default.jpg";
        $date = date('d M Y', strtotime($row['event_date']));
        $title = htmlspecialchars($row['title']);
        $loc = htmlspecialchars($row['location']);
        $id = $row['event_id'];
        $status_badge = "";
        if ($row['status'] == 'open') {
            $status_badge = '<span class="status-badge status-open">Open</span>';
        } elseif ($row['status'] == 'closed') {
            $status_badge = '<span class="status-badge status-closed">Closed</span>';
        } else {
            $status_badge = '<span class="status-badge status-cancelled">Cancelled</span>';
        }
        echo "
        <a href='event_details.php?id=$id' class='card event-card-style'>
            <div class='event-card-pos'>
                <img src='$img' class='card-img' alt='Event'>
                <div class='event-price-badge'>
                    <span class='price-tag'>" . (($row['price'] > 0) ? 'RM '.$row['price'] : 'Free') . "</span>
                </div>
            </div>
            <div class='card-content'>
                <div class='d-flex justify-between mb-10 align-center'>
                    <span class='text-sm font-bold text-primary'>" . strtoupper(date('M d', strtotime($row['event_date']))) . "</span>
                    " . (($row['status'] == 'open') ? '<div class="event-status-dot" title="Open"></div>' :
                        (($row['status'] == 'cancelled') ? '<span class="badge bg-cancelled text-xs px-10">Cancelled</span>' :
                        '<span class="badge bg-closed text-xs px-10">Closed</span>')) . "
                </div>
                <h3 class='text-lg mb-10'>$title</h3>
                <p class='text-sm m-0 text-slate'>
                    <i class='fa-solid fa-location-dot mr-5'></i> $loc
                </p>
            </div>
        </a>";
    }
} else {
    echo "<div class='empty-state'>
            <i class='fa-regular fa-calendar-xmark empty-icon'></i>
            <h3 class='text-slate'>No events found matching \"$search\"</h3>
            <a href='events.php' class='btn btn-secondary mt-15'>Clear Search</a>
          </div>";
}
?>
