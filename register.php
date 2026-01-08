<?php
/*
KP34903: Register
Group 7
JiranHub Web
*/

session_start();
require 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$error = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'resident';
    if ($password !== $confirm_password) {
    $error = "Passwords do not match!";
    }
    elseif (!preg_match("/^[a-zA-Z  -]+$/", $full_name)) {
    $error = "Invalid Name! Only letters and spaces are allowed.";
    }
    elseif (strlen($password) < 8) {
    $error = "Password must be at least 8 characters long!";
    }
    elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\W]/", $password)) {
    $error = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }
    else {
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $phone, $hashed_password, $role);
            if ($stmt->execute()) {
                $success = "Registration successful! <a href='login.php'>Login here</a>";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join - JiranHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="login-wrapper">
        <div class="form-box" style="max-width: 480px;">
            <h2 style="margin-bottom: 10px;">Join the Community</h2>
            <p style="margin-bottom: 30px;">Create your resident account today.</p>
            <?php if($error): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; margin-bottom:20px; font-size:0.9rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if($success): ?>
                <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:6px; margin-bottom:20px; font-size:0.9rem;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name <span style="color:var(--error)">*</span></label>
                    <input type="text" name="full_name" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Email Address <span style="color:var(--error)">*</span></label>
                    <input type="email" name="email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Phone Number <span style="color:var(--error)">*</span></label>
                    <input type="text" name="phone" required placeholder="012-3456789">
                </div>
                <div class="form-group">
                    <label>Password <span style="color:var(--error)">*</span></label>
                    <input type="password" name="password" required placeholder="••••••••" minlength="8">
                    <small style="color:var(--slate); font-size:0.85rem; display:block; margin-top:-1px; margin-bottom:10px;">
                        Min 8 chars, with Upper, Lower, Number & Special Character.
                    </small>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span style="color:var(--error)">*</span></label>
                    <input type="password" name="confirm_password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Create Account</button>
            </form>
            <p style="margin-top: 25px; font-size:0.9rem;">
                Already have an account? <a href="login.php" style="color:var(--primary); font-weight:700;">Log In</a>
            </p>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
