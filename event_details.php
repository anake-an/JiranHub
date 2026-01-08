<?php
/*
KP34903: Event details
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if (!isset($_GET['id'])) { header("Location: events.php"); exit(); }
$event_id = intval($_GET['id']);
$msg = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) { header("Location: login.php?redirect=event_details.php?id=$event_id"); exit(); }
    $user_id = $_SESSION['user_id'];
    if (isset($_POST['action']) && $_POST['action'] == 'join_event') {
        $check = $conn->query("SELECT registration_id FROM registrations WHERE user_id=$user_id AND event_id=$event_id");
        if ($check->num_rows > 0) {
            $error = "You are already registered for this event.";
        } else {
            $evt_check = $conn->query("SELECT price, max_slots, status, (SELECT count(*) FROM registrations WHERE event_id=$event_id AND status!='cancelled') as current_count FROM events WHERE event_id=$event_id")->fetch_assoc();
            if ($evt_check['status'] != 'open') {
                $error = "Cannot register. Event is " . $evt_check['status'] . ".";
            } elseif ($evt_check['current_count'] >= $evt_check['max_slots']) {
                $error = "Sorry, this event is fully booked.";
            } else {
                $status = 'confirmed';
                $proof = "";
                if ($evt_check['price'] > 0) {
                    $status = 'pending_payment';
                    if (!empty($_FILES['payment_proof']['name'])) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            $target = "uploads/payments/" . time() . "_" . basename($_FILES['payment_proof']['name']);
                            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target)) {
                                $proof = basename($target);
                            } else { $error = "Failed to upload receipt."; }
                        } else { $error = "Invalid file type. Only JPG, PNG, GIF allowed."; }
                    } else { $error = "Payment proof is required."; }
                }
                if (!$error) {
                    $stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id, status, payment_proof) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $user_id, $event_id, $status, $proof);
                    if ($stmt->execute()) {
                        $notif_msg = ($status=='confirmed') ? "You joined the event successfully." : "Registration received. Waiting for payment approval.";
                        $conn->query("INSERT INTO notifications (user_id, message) VALUES ($user_id, \"$notif_msg\")");
                        header("Location: event_details.php?id=$event_id&success=1"); exit();
                    } else { $error = "Database error."; }
                }
            }
        }
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'submit_review') {
        $rating = intval($_POST['rating']);
        $comment = trim($_POST['comment']);
        $dup = $conn->query("SELECT review_id FROM event_reviews WHERE user_id=$user_id AND event_id=$event_id");
        if ($dup->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO event_reviews (event_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $event_id, $user_id, $rating, $comment);
            $stmt->execute();
            header("Location: event_details.php?id=$event_id&msg=reviewed"); exit();
        }
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'report_event') {
        $reason = trim($_POST['reason']);
        $type = 'event';
        $stmt = $conn->prepare("INSERT INTO reports (user_id, target_type, target_id, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $user_id, $type, $event_id, $reason);
        $stmt->execute();
        header("Location: event_details.php?id=$event_id&msg=reported"); exit();
    }
}
$sql = "SELECT e.*, u.full_name as organizer_name FROM events e JOIN users u ON e.organizer_id = u.user_id WHERE e.event_id = $event_id";
$event = $conn->query($sql)->fetch_assoc();
$avg_res = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM event_reviews WHERE event_id=$event_id");
$rating_data = $avg_res->fetch_assoc();
$avg_rating = is_numeric($rating_data['avg_rating']) ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['total_reviews'];
$is_registered = false;
$reg_status = "";
$can_review = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $r_check = $conn->query("SELECT status FROM registrations WHERE user_id=$uid AND event_id=$event_id");
    if ($r_check->num_rows > 0) {
        $is_registered = true;
        $reg_status = $r_check->fetch_assoc()['status'];
        $today = date("Y-m-d");
        if ($reg_status == 'confirmed' && $event['event_date'] < $today) {
            $rev_check = $conn->query("SELECT review_id FROM event_reviews WHERE user_id=$uid AND event_id=$event_id");
            if ($rev_check->num_rows == 0) {
                $can_review = true;
            }
        }
    }
}
$reviews = $conn->query("SELECT r.*, u.full_name, u.profile_image FROM event_reviews r JOIN users u ON r.user_id=u.user_id WHERE r.event_id=$event_id ORDER BY r.created_at DESC");
$curr = $conn->query("SELECT count(*) as c FROM registrations WHERE event_id=$event_id AND status!='cancelled'")->fetch_assoc()['c'];
$slots_left = $event['max_slots'] - $curr;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .nav-inner { position: relative; display: flex; justify-content: space-between; align-items: center; }
        .nav-bell { position: relative; color: var(--slate); font-size: 1.2rem; margin-right: 15px; text-decoration: none; display: flex; align-items: center; }
        .nav-bell:hover { color: var(--primary); }
        .badge-count { position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 5px; border-radius: 50%; border: 2px solid white; font-weight: 700; }
        @media (max-width: 768px) {
            .mobile-right-elements { display: flex; align-items: center; }
            .nav-links { display: none; flex-direction: column; position: absolute; top: 70px; left: 0; width: 100%; background: white; border-bottom: 1px solid #e2e8f0; padding: 10px 0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); z-index: 1000; }
            .nav-links .nav-link { width: 100%; text-align: left; padding: 15px 25px; border-bottom: 1px solid #f1f5f9; margin: 0; }
            .nav-links .btn { margin: 15px 25px; width: auto; display: block; text-align: center; }
            .desktop-bell { display: none; }
            .event-layout { flex-direction: column; } .sidebar-col { width: 100%; }
        }
        @media (min-width: 769px) { .mobile-bell { display: none; } }
        .event-banner-wide { width: 100%; height: 350px; object-fit: cover; border-radius: var(--radius); margin-bottom: 40px; }
        .event-layout { display: flex; gap: 60px; position: relative; align-items: flex-start; }
        .main-col { flex: 2; }
        .sidebar-col { flex: 1; position: sticky; top: 100px; min-width: 320px; }
    </style>
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container" style="padding: 40px 24px;">
        <p style="font-size: 0.9rem; margin-bottom: 20px;"><a href="events.php" style="text-decoration: underline;">Events</a> &rsaquo; Details</p>
        <?php if(isset($_GET['msg']) && $_GET['msg']=='reported'): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:10px; border-radius:6px; margin-bottom:20px;">Event reported to admins.</div>
        <?php endif; ?>
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <div>
                <h1 style="font-size: 2.5rem; margin-bottom: 10px;">
                    <?php echo htmlspecialchars($event['title']); ?>
                    <?php if($event['status']=='open'): ?>
                        <span class="badge" style="background:#dcfce7; color:#166534; font-size:1rem; vertical-align:middle;">OPEN</span>
                    <?php elseif($event['status']=='closed'): ?>
                        <span class="badge" style="background:#f1f5f9; color:#64748b; font-size:1rem; vertical-align:middle;">CLOSED</span>
                    <?php else: ?>
                        <span class="badge" style="background:#fee2e2; color:#991b1b; font-size:1rem; vertical-align:middle;">CANCELLED</span>
                    <?php endif; ?>
                </h1>
                <p style="color: var(--slate); margin-bottom: 20px;">Organized by <strong><?php echo htmlspecialchars($event['organizer_name']); ?></strong></p>
                <div style="display:flex; align-items:center; gap:5px; margin-bottom:30px;">
                    <?php if($total_reviews > 0): ?>
                        <span style="color:#f59e0b; font-size:1.1rem;"><i class="fa-solid fa-star"></i> <strong><?php echo $avg_rating; ?></strong></span>
                        <span style="color:var(--slate); font-size:0.9rem;">(<?php echo $total_reviews; ?> reviews)</span>
                    <?php else: ?>
                        <span style="color:var(--slate); font-size:0.9rem;"><i class="fa-regular fa-star"></i> No reviews yet</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <button onclick="document.getElementById('reportModal').style.display='block'" style="border:none; background:none; color:#94a3b8; cursor:pointer;" title="Report Event"><i class="fa-solid fa-flag"></i></button>
            <?php endif; ?>
        </div>
        <?php if($error): echo "<div style='background:#fee2e2; color:#b91c1c; padding:15px; border-radius:6px; margin-bottom:20px;'>$error</div>"; endif; ?>
        <?php if(isset($_GET['success'])): echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:6px; margin-bottom:20px;'>Success! You have joined this event.</div>"; endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg']=='reviewed'): echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:6px; margin-bottom:20px;'>Thank you! Your review has been posted.</div>"; endif; ?>
        <div class="event-layout">
            <div class="main-col">
                <?php $img = !empty($event['banner_image']) ? "uploads/banners/".$event['banner_image'] : "assets/images/default.jpg"; ?>
                <img src="<?php echo $img; ?>" class="event-banner-wide">
                <h3>About this event</h3>
                <div style="line-height: 1.8; color: var(--dark); white-space: pre-line; margin-bottom: 40px;"><?php echo nl2br(htmlspecialchars($event['description'])); ?></div>
                <div style="padding: 30px; background: var(--snow); border-radius: var(--radius); border: 1px solid var(--silver); margin-bottom: 60px;">
                    <h3>Details</h3>
                    <p><i class="fa-regular fa-calendar"></i> <?php echo date('d F Y', strtotime($event['event_date'])); ?> at <?php echo date('h:i A', strtotime($event['event_time'])); ?></p>
                    <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                </div>
                <div class="review-section">
                    <h3 style="margin-bottom:20px;">Participant Reviews</h3>
                    <?php if($can_review): ?>
                        <div class="review-card" style="background:#f8fafc; border:2px dashed var(--primary);">
                            <h4 style="margin-bottom:10px;">Leave a Review</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="submit_review">
                                <div style="margin-bottom:15px;">
                                    <label style="display:block; margin-bottom:5px;">Your Rating</label>
                                    <div class="rating-input">
                                        <input type="radio" name="rating" id="star5" value="5" required><label for="star5"><i class="fa-solid fa-star"></i></label>
                                        <input type="radio" name="rating" id="star4" value="4"><label for="star4"><i class="fa-solid fa-star"></i></label>
                                        <input type="radio" name="rating" id="star3" value="3"><label for="star3"><i class="fa-solid fa-star"></i></label>
                                        <input type="radio" name="rating" id="star2" value="2"><label for="star2"><i class="fa-solid fa-star"></i></label>
                                        <input type="radio" name="rating" id="star1" value="1"><label for="star1"><i class="fa-solid fa-star"></i></label>
                                    </div>
                                </div>
                                <div style="margin-bottom:15px;"><label>Comment</label><textarea name="comment" rows="3" required placeholder="How was the event?"></textarea></div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if($reviews->num_rows > 0): ?>
                        <?php while($row = $reviews->fetch_assoc()): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php $p = !empty($row['profile_image']) ? "uploads/profiles/".$row['profile_image'] : "assets/images/default_user.png"; ?>
                                        <img src="<?php echo $p; ?>" class="post-avatar">
                                        <div><strong style="display:block; line-height:1.2;"><?php echo htmlspecialchars($row['full_name']); ?></strong><small style="color:var(--slate);"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small></div>
                                    </div>
                                    <div class="star-rating">
                                        <?php for($i=0; $i<$row['rating']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?>
                                        <?php for($i=$row['rating']; $i<5; $i++) echo '<i class="fa-regular fa-star" style="color:#cbd5e1;"></i>'; ?>
                                    </div>
                                </div>
                                <p style="margin:0;"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: echo "<p style='color:var(--slate);'>No reviews yet.</p>"; endif; ?>
                </div>
            </div>
            <div class="sidebar-col">
                <div class="form-box" style="margin: 0; text-align: left; border-top: 4px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <span style="color: var(--slate);">Price</span>
                        <span style="font-size: 1.5rem; font-weight: 800;"><?php echo ($event['price'] > 0) ? 'RM '.$event['price'] : 'Free'; ?></span>
                    </div>
                    <hr style="border:0; border-top:1px solid var(--silver); margin-bottom:20px;">
                    <?php if ($is_registered && $reg_status == 'confirmed'): ?>
                        <?php
                            $ticket_info = $conn->query("SELECT registration_id FROM registrations WHERE user_id=$uid AND event_id=$event_id")->fetch_assoc();
                            $ticket_id = $ticket_info['registration_id'];
                        ?>
                        <div style="background: white; border: 2px dashed var(--primary); padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px;">
                            <h4 style="margin-top: 0; color: var(--primary);">My Ticket</h4>
                            <div id="qrcode" style="margin: 15px auto; display: flex; justify-content: center;"></div>
                            <p style="font-weight: 800; font-size: 1.1rem; margin: 10px 0;">Ticket ID: #<?php echo $ticket_id; ?></p>
                            <small style="color: var(--slate); display: block;">Show QR or ID to organizer</small>
                        </div>
                    <?php endif; ?>
                    <?php if ($event['status'] == 'cancelled'): ?>
                        <div style="background:#fee2e2; color:#b91c1c; padding:15px; text-align:center; font-weight:700; border-radius:6px;">Cancelled</div>
                    <?php elseif ($is_registered): ?>
                        <div style="background:#dcfce7; color:#166534; padding:15px; text-align:center; font-radius:6px; margin-bottom:10px;"><i class="fa-solid fa-check-circle"></i> Registered</div>
                        <p class="text-center" style="font-size:0.85rem;">Status: <strong><?php echo ucfirst($reg_status); ?></strong></p>
                    <?php elseif ($slots_left <= 0): ?>
                        <button class="btn btn-secondary" disabled style="width:100%;">Sold Out</button>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="join_event">
                            <?php if ($event['price'] > 0): ?>
                                <div style="background:#fefce8; border:1px solid #fde047; padding:15px; border-radius:8px; margin-bottom:15px;">
                                    <strong style="color:#854d0e; font-size:0.9rem;">Payment Instructions</strong>
                                    <p style="margin:5px 0 0 0; font-size:0.85rem; color:#854d0e; white-space: pre-line;">
                                        <?php echo !empty($event['bank_details']) ? htmlspecialchars($event['bank_details']) : "Please contact admin for bank details."; ?>
                                    </p>
                                </div>
                                <div style="background:#eff6ff; padding:15px; border-radius:8px; margin-bottom:15px; border:1px dashed var(--primary);">
                                    <label style="font-size:0.85rem; font-weight:700; color:var(--primary); display:block; margin-bottom:8px;">Upload Payment Proof</label>
                                    <input type="file" name="payment_proof" required style="font-size:0.85rem; width:100%;">
                                    <small style="display:block; margin-top:5px; color:var(--slate);">Transfer RM<?php echo $event['price']; ?> and upload receipt.</small>
                                </div>
                            <?php endif; ?>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button type="submit" class="btn btn-primary" style="width:100%;" onclick="return confirm('Confirm registration?')"><?php echo ($event['price'] > 0) ? 'Pay & Join' : 'Reserve Spot'; ?></button>
                            <?php else: ?>
                                <a href="login.php?redirect=event_details.php?id=<?php echo $event_id; ?>" class="btn btn-primary" style="width:100%;">Login to Join</a>
                            <?php endif; ?>
                        </form>
                        <p class="text-center" style="font-size:0.85rem; margin-top:15px; color:var(--slate);"><?php echo $slots_left; ?> spots left</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div id="reportModal" class="modal">
        <div class="modal-content" style="max-width:400px;">
            <h3>Report Event</h3>
            <form method="POST">
                <input type="hidden" name="action" value="report_event">
                <div style="margin-bottom:15px;">
                    <label>Reason</label>
                    <select name="reason" style="width:100%; padding:8px;">
                        <option value="Spam/Scam">Spam or Scam</option>
                        <option value="Inappropriate">Inappropriate Content</option>
                        <option value="Misleading">Misleading Information</option>
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
        window.onclick = function(event) { if (event.target == document.getElementById('reportModal')) document.getElementById('reportModal').style.display = "none"; }
        <?php
        if($is_registered && $reg_status == 'confirmed') {
            $uid = $_SESSION['user_id'];
            $reg_data = $conn->query("SELECT registration_id FROM registrations WHERE user_id=$uid AND event_id=$event_id")->fetch_assoc();
            $qr_payload = json_encode(["reg_id" => $reg_data['registration_id'], "type" => "attendance"]);
            echo "
            new QRCode(document.getElementById('qrcode'), {
                text: '$qr_payload',
                width: 128,
                height: 128
            });";
        }
        ?>
    </script>
</body>
</html>
