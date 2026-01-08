<?php
/*
KP34903: Profile
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];
$msg = ""; $error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $img_sql = "";
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $target = "uploads/profiles/" . time() . "_" . basename($_FILES['profile_image']['name']);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
                $img_name = basename($target);
                $stmt_img = $conn->prepare("UPDATE users SET profile_image=? WHERE user_id=?");
                $stmt_img->bind_param("si", $img_name, $user_id);
                $stmt_img->execute();
            }
        } else { $error = "Invalid image type. JPG, PNG, GIF only."; }
    }
    $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE user_id=?");
    $stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
    if($stmt->execute()) {
        $msg = "Profile updated!";
    } else {
        $error = "Error updating profile.";
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_payment'])) {
    $reg_id = intval($_POST['reg_id']);
    $stmt_check = $conn->prepare("SELECT registration_id FROM registrations WHERE registration_id=? AND user_id=?");
    $stmt_check->bind_param("ii", $reg_id, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0 && !empty($_FILES['late_receipt']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['late_receipt']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $target = "uploads/payments/" . time() . "_late_" . basename($_FILES['late_receipt']['name']);
            if (move_uploaded_file($_FILES['late_receipt']['tmp_name'], $target)) {
                $fname = basename($target);
                $stmt_pay = $conn->prepare("UPDATE registrations SET payment_proof=? WHERE registration_id=?");
                $stmt_pay->bind_param("si", $fname, $reg_id);
                $stmt_pay->execute();
                sendNotification($conn, $user_id, 'Late payment receipt uploaded. Waiting approval.');
                $msg = "Receipt uploaded successfully.";
            } else {
                $error = "File upload failed.";
            }
        } else { $error = "Invalid file type. Only JPG, PNG, GIF allowed."; }
    }
}
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_res = $stmt_user->get_result();
if ($user_res->num_rows > 0) {
    $user = $user_res->fetch_assoc();
} else {
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt_events = $conn->prepare("SELECT r.*, e.title, e.event_date, e.event_time, e.price, e.banner_image
                        FROM registrations r JOIN events e ON r.event_id=e.event_id
                        WHERE r.user_id=? ORDER BY r.created_at DESC");
$stmt_events->bind_param("i", $user_id);
$stmt_events->execute();
$events = $stmt_events->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="main-wrapper">
        <div class="container py-60">
            <?php if($msg) echo "<div class='bg-open p-10 rounded mb-20 font-bold'>$msg</div>"; ?>
            <?php if($error) echo "<div class='bg-cancelled p-10 rounded mb-20 font-bold'>$error</div>"; ?>
            <div class="card p-30">
                <div class="d-flex justify-between align-center mb-30">
                    <h2 class="m-0 text-dark">Profile Settings</h2>
                    <?php if($user['role'] == 'admin'): ?>
                        <span class="profile-header-badge">Admin Account</span>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" class="d-flex gap-40 flex-wrap">
                    <div class="profile-img-container">
                        <?php $pic = !empty($user['profile_image']) ? "uploads/profiles/".$user['profile_image'] : "assets/images/default_user.png"; ?>
                        <img src="<?php echo $pic; ?>" class="profile-img shadow-sm">
                        <label class="btn btn-secondary profile-img-btn mt-10">Upload New Photo <input type="file" name="profile_image" style="display:none;" onchange="this.form.submit()"></label>
                        <input type="hidden" name="update_profile" value="1">
                    </div>
                    <div class="profile-form-grid">
                        <div class="profile-input-grid">
                            <div>
                                <label class="input-label">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required class="modern-input">
                            </div>
                            <div>
                                <label class="input-label">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="modern-input">
                            </div>
                        </div>
                        <div class="mb-20">
                            <label class="input-label">Email Address <span class="text-xs text-slate font-normal">(Cannot be changed)</span></label>
                            <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="modern-input">
                        </div>
                        <div class="mb-30">
                            <label class="input-label">Home Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="modern-input">
                        </div>
                        <div class="text-right">
                            <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 12px 25px;">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section-title-row">
                <h2 class="m-0 text-dark">My Activity</h2>
                <?php if ($events->num_rows <= 0): ?>
                <a href="events.php" class="link-primary-bold">Browse Events &rarr;</a>
                <?php endif; ?>
            </div>
            <?php if ($events->num_rows > 0): ?>
                <div class="d-flex flex-col gap-20">
                    <?php while($row = $events->fetch_assoc()): ?>
                        <div class="card activity-card">
                            <?php $eimg = !empty($row['banner_image']) ? "uploads/banners/".$row['banner_image'] : "assets/images/default.jpg"; ?>
                            <img src="<?php echo $eimg; ?>" class="activity-img">
                            <div style="flex:1;">
                                <h3 class="m-0 mb-10"><a href="event_details.php?id=<?php echo $row['event_id']; ?>" class="text-dark"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                                <p class="m-0 text-sm text-slate"><?php echo date('d M Y', strtotime($row['event_date'])); ?></p>
                            </div>
                            <div style="flex:1; text-align:right;">
                                <!-- STATUS BADGE -->
                                <?php if($row['status']=='confirmed'): ?>
                                    <span class="badge status-badge-confirmed">Confirmed</span>
                                    <small class="ticket-num">Ticket #<?php echo $row['registration_id']; ?></small>
                                <?php elseif($row['status']=='cancelled'): ?>
                                    <span class="badge bg-cancelled">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge status-badge-pending">Pending</span>
                                <?php endif; ?>
                                <!-- PAYMENT ACTIONS -->
                                <?php if($row['price'] > 0): ?>
                                    <div class="mt-10 text-sm">
                                        <?php if(!empty($row['payment_proof'])): ?>
                                            <a href="uploads/payments/<?php echo $row['payment_proof']; ?>" target="_blank" class="text-primary underline">View Receipt</a>
                                        <?php elseif($row['status'] == 'pending_payment'): ?>
                                            <form method="POST" enctype="multipart/form-data" class="mt-10">
                                                <input type="hidden" name="upload_payment" value="1">
                                                <input type="hidden" name="reg_id" value="<?php echo $row['registration_id']; ?>">
                                                <label class="upload-label">
                                                    <i class="fa-solid fa-upload"></i> Upload Pay
                                                    <input type="file" name="late_receipt" style="display:none;" onchange="this.form.submit()">
                                                </label>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-right"></div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
