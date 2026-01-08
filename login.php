<?php
/*
KP34903: Login
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: " . ($user['role'] == 'admin' ? 'admin/dashboard.php' : 'index.php'));
            exit();
        } else { $error = "Incorrect email or password."; }
    } else { $error = "Incorrect email or password."; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="login-wrapper">
        <div class="form-box">
            <h2 style="margin-bottom: 10px;">Welcome Back</h2>
            <p style="margin-bottom: 30px; font-size:0.95rem;">Login to your account.</p>
            <?php if($error): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; font-size:0.9rem; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <div style="text-align:right; margin-bottom:15px;">
                    <a href="forgot_password.php" style="color:var(--primary); font-size:0.9rem;">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
            </form>
            <p style="margin-top: 25px; font-size:0.9rem;">
                Don't have an account? <a href="register.php" style="color:var(--primary); font-weight:700;">Sign Up</a>
            </p>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
