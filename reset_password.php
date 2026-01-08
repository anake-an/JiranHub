<?php
/*
KP34903: Reset password
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
$token = isset($_GET['token']) ? trim($_GET['token']) : "";
$msg = "";
$error = "";
$valid_token = false;
if ($token) {
    $now = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token=? AND reset_expiry > ?");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $valid_token = true;
    } else {
        $error = "Invalid or expired token.";
    }
} else {
    header("Location: login.php"); exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters long!";
    } elseif (!preg_match("/[A-Z]/", $pass) || !preg_match("/[a-z]/", $pass) || !preg_match("/[0-9]/", $pass) || !preg_match("/[\W]/", $pass)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt_upd = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE reset_token=?");
        $stmt_upd->bind_param("ss", $hashed, $token);
        $stmt_upd->execute();
        $msg = "Password updated successfully! <a href='login.php' style='text-decoration:underline; font-weight:bold;'>Login Now</a>";
        $valid_token = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="login-wrapper">
        <div class="form-box">
            <h2 style="margin-bottom: 10px;">New Password</h2>
            <?php if($msg): ?>
                <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:6px; font-size:0.9rem; margin-bottom:20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; font-size:0.9rem; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if($valid_token): ?>
                <p style="margin-bottom: 20px;">Enter your new password below.</p>
                <form method="POST">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" required minlength="8" placeholder="••••••••">
                        <small style="color:var(--slate); font-size:0.85rem; display:block; margin-top:5px; margin-bottom:10px;">
                            Min 8 chars, with Upper, Lower, Number & Special Character.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Change Password</button>
                </form>
            <?php elseif(!$msg): ?>
                <p>This link is invalid or has expired.</p>
                <a href="forgot_password.php" class="btn btn-secondary">Request New Link</a>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
