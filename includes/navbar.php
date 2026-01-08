<?php
if (isset($conn) && isset($_SESSION['user_id'])) {
    $uid_nav = $_SESSION['user_id'];
    $notif_nav = 0;
    $res_nav = $conn->query("SELECT count(*) as c FROM notifications WHERE user_id=$uid_nav AND is_read=0");
    if($res_nav) $notif_nav = $res_nav->fetch_assoc()['c'];
}
?>
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-house"></i> JiranHub
            </a>
            <div class="mobile-right-elements">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="notifications.php" class="nav-bell mobile-bell">
                        <i class="fa-regular fa-bell"></i>
                        <?php if(isset($notif_nav) && $notif_nav > 0): ?><span class="badge-count"><?php echo $notif_nav; ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
                <div class="hamburger" onclick="toggleMenu()"><i class="fa-solid fa-bars"></i></div>
                <div class="nav-links" id="navLinks">
                    <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a>
                    <a href="events.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'events.php') ? 'active' : ''; ?>">Events</a>
                    <a href="forum.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'forum.php') ? 'active' : ''; ?>">Forum</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'organizer'): ?>
                            <a href="admin/dashboard.php" class="nav-link">Dashboard</a>
                        <?php endif; ?>
                        <a href="notifications.php" class="nav-bell desktop-bell" style="margin: 0 15px;">
                            <i class="fa-regular fa-bell"></i>
                            <?php if(isset($notif_nav) && $notif_nav > 0): ?><span class="badge-count"><?php echo $notif_nav; ?></span><?php endif; ?>
                        </a>
                        <a href="profile.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">Profile</a>
                        <a href="logout.php" class="btn btn-secondary" style="height:36px; padding:0 15px;" onclick="return confirm('Logout?')">Sign Out</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary" style="height:36px; padding:0 15px;">Log In</a>
                        <a href="register.php" class="btn btn-primary" style="height:36px; padding:0 15px;">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <script>
        function toggleMenu() {
            const nav = document.getElementById('navLinks');
            if (nav.style.display === 'flex') {
                nav.style.display = 'none';
            } else {
                nav.style.display = 'flex';
            }
        }
    </script>
