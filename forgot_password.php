<?php
/*
KP34903: Forgot password
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
$msg = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $stmt_upd = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
        $stmt_upd->bind_param("sss", $token, $expiry, $email);
        $stmt_upd->execute();
        $reset_link = "reset_password.php?token=" . $token;
        $msg = "Click here to reset: <a href='$reset_link' style='text-decoration:underline; font-weight:bold;'>Reset Password Link</a>";
    } else {
        $error = "Email not found in our records.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="login-wrapper">
        <div class="form-box">
            <h2 style="margin-bottom: 10px;">Reset Password</h2>
            <p style="margin-bottom: 30px; font-size:0.95rem;">Enter your email to receive a reset link.</p>
            <?php if($error): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; font-size:0.9rem; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if($msg): ?>
                <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:6px; font-size:0.9rem; margin-bottom:20px; text-align:left; word-break:break-all;">
                    <?php echo $msg; ?>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="you@example.com">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Link</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
