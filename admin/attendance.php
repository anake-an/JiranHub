<?php
/*
KP34903: Attendance
Group 7
JiranHub Web
*/

session_start();
require '../db.php';
requireAdmin();


$sid_uid = $_SESSION['user_id'];
$sid_user = $conn->query("SELECT full_name, role FROM users WHERE user_id=$sid_uid")->fetch_assoc();
if (isset($_SESSION['role']) && $_SESSION['role'] == 'organizer') {
    $pending_nav = $conn->query("SELECT count(*) as c FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.status='pending_payment' AND e.organizer_id=$sid_uid")->fetch_assoc()['c'];
} else {
    $pending_nav = $conn->query("SELECT count(*) as c FROM registrations WHERE status='pending_payment'")->fetch_assoc()['c'];
}

$pending_reports = $conn->query("SELECT count(*) as c FROM reports WHERE status='pending'")->fetch_assoc()['c'];
if (isset($_GET['toggle']) && isset($_GET['rid'])) {
    $reg_id = intval($_GET['rid']);
    $status = intval($_GET['toggle']);
    $conn->query("UPDATE registrations SET attended=$status WHERE registration_id=$reg_id");
    $evt_id = intval($_GET['event_id']);
    header("Location: attendance.php?event_id=$evt_id&msg=updated");
    exit();
}
$my_uid = $_SESSION['user_id'];
$events = $conn->query("SELECT * FROM events WHERE status != 'cancelled' AND organizer_id=$my_uid ORDER BY event_date ASC");
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$participants = null;
$stats = ['total' => 0, 'present' => 0];
if ($selected_event_id > 0) {
    $sql = "SELECT r.*, u.full_name, u.phone, u.email
            FROM registrations r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.event_id = $selected_event_id AND r.status = 'confirmed'
            ORDER BY u.full_name ASC";
    $participants = $conn->query($sql);
    while ($p = $participants->fetch_assoc()) {
        $rows[] = $p;
        $stats['total']++;
        if ($p['attended'] == 1) $stats['present']++;
    }
    $participants = $rows ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-primary-50">
<div class="admin-grid">
    <div class="mobile-admin-header">
        <i class="fa-solid fa-bars cursor-pointer text-dark" onclick="document.querySelector('.sidebar').classList.toggle('active')" style="font-size:1.5rem;"></i>
        <div class="font-bold text-lg text-primary"><i class="fa-solid fa-shield-halved"></i> AdminPanel</div>
    </div>
    <div class="sidebar">
        <div class="sidebar-user-block">
            <a href="dashboard.php" class="font-bold text-xl text-dark p-0">
                <i class="fa-solid fa-shield-halved text-primary"></i> AdminPanel
            </a>
            <div class="sidebar-user-info">
                <small class="text-slate uppercase text-xs">Logged in as</small><br>
                <span class="font-semibold text-dark"><?php echo htmlspecialchars($sid_user['full_name']); ?></span>
            </div>
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-chart-line w-25px"></i> Dashboard</a>
        <?php if($_SESSION['role'] == 'admin'): ?><a href="manage_users.php"><i class="fa-solid fa-user-gear w-25px"></i> Users (AJK)</a><?php endif; ?>
        <a href="manage_events.php"><i class="fa-solid fa-calendar w-25px"></i> Events</a>
        <a href="manage_registrations.php"><i class="fa-solid fa-users w-25px"></i> Registrations
            <?php if(isset($pending_nav) && $pending_nav > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_nav; ?></span><?php endif; ?>
        </a>
        <a href="attendance.php" class="active"><i class="fa-solid fa-clipboard-user w-25px"></i> Attendance</a>
        <?php if($_SESSION['role'] == 'admin'): ?><a href="manage_reports.php"><i class="fa-solid fa-flag w-25px"></i> Reports
            <?php if(isset($pending_reports) && $pending_reports > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_reports; ?></span><?php endif; ?>
        </a><?php endif; ?>
        <div class="sidebar-bottom">
            <a href="../index.php" target="_blank"><i class="fa-solid fa-external-link w-25px"></i> View Live Site</a>
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket w-25px"></i> Logout</a>
        </div>
    </div>
    <div class="content">
        <div class="d-flex justify-between align-center mb-30">
            <h1>Attendance Tracking</h1>
        </div>
        <div class="card admin-card-header">
            <form method="GET" style="flex:1;">
                <label>Select Event to Mark Attendance:</label>
                <div class="d-flex gap-10">
                    <select name="event_id" style="flex:1;" onchange="this.form.submit()">
                        <option value="">-- Choose Event --</option>
                        <?php while($evt = $events->fetch_assoc()): ?>
                            <option value="<?php echo $evt['event_id']; ?>" <?php echo ($selected_event_id == $evt['event_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($evt['title']); ?> (<?php echo date('d M', strtotime($evt['event_date'])); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Go</button>
                </div>
                </form>
                <div style="margin-left:auto; margin-top: 10px;">
                    <button onclick="openScanner()" class="btn btn-primary d-flex align-center gap-10 bg-dark border-0">
                        <i class="fa-solid fa-qrcode"></i> Open Scanner
                    </button>
                </div>
            </div>
        <?php if ($selected_event_id > 0): ?>
            <div class="grid-2 mb-30">
                <div class="stat-card bg-open border-0">
                    <div class="stat-label text-success">Present</div>
                    <div class="stat-num text-success"><?php echo $stats['present']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Confirmed</div>
                    <div class="stat-num"><?php echo $stats['total']; ?></div>
                </div>
            </div>
            <div class="card card-nopad">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($participants)): ?>
                            <?php foreach($participants as $row): ?>
                            <tr style="background: <?php echo ($row['attended'] == 1) ? '#f0fdf4' : 'white'; ?>;">
                                <td>
                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                    <span class="text-sm text-slate"><?php echo htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td>
                                    <?php if($row['attended'] == 1): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-closed">Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['attended'] == 0): ?>
                                        <a href="attendance.php?event_id=<?php echo $selected_event_id; ?>&toggle=1&rid=<?php echo $row['registration_id']; ?>" class="btn btn-primary btn-sm px-15">Mark Present</a>
                                    <?php else: ?>
                                        <a href="attendance.php?event_id=<?php echo $selected_event_id; ?>&toggle=0&rid=<?php echo $row['registration_id']; ?>" class="btn btn-secondary btn-sm px-15 text-danger">Undo</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center p-30">No confirmed participants found for this event.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
<div id="scannerModal" class="modal">
    <div class="modal-content">
        <h3 class="text-center mb-15">Scan Participant Ticket</h3>
        <div id="reader" class="w-full"></div>
        <div id="scanned-result" class="text-center mt-15 min-h-30 text-slate">Waiting for scan...</div>
        <div class="scanner-manual-box">
            <p class="text-sm text-slate mb-10">Camera issue? Enter Ticket ID manually:</p>
            <div class="scanner-submit">
                <input type="number" id="manual_id" placeholder="Ticket ID" class="scanner-input">
                <button onclick="manualSubmit()" class="btn btn-primary py-8 px-15">Submit</button>
            </div>
        </div>
        <button onclick="closeScanner()" class="btn btn-secondary w-full mt-20">Close Scanner</button>
    </div>
</div>
<script>
    let html5QrcodeScanner = null;
    function openScanner() {
        document.getElementById('scannerModal').style.display = 'block';
        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: {width: 250, height: 250} },
            false
        );
        html5QrcodeScanner.render(onScanSuccess);
    }
    function closeScanner() {
        document.getElementById('scannerModal').style.display = 'none';
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(error => {
                console.error("Failed to clear scanner check.", error);
            });
        }
    }
    function manualSubmit() {
        const id = document.getElementById('manual_id').value;
        if(id) {
            onScanSuccess(JSON.stringify({type:'attendance', reg_id: id}));
        }
    }
    function onScanSuccess(decodedText, decodedResult) {
        const resultContainer = document.getElementById('scanned-result');
        try {
            const data = JSON.parse(decodedText);
            if (data.type && data.type === 'attendance' && data.reg_id) {
                if(html5QrcodeScanner) html5QrcodeScanner.pause();
                resultContainer.innerHTML = '<span style="color:#f59e0b; font-weight:bold;">Processing...</span>';
                fetch('attendance_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'reg_id=' + data.reg_id
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        resultContainer.innerHTML = `<span class="success-msg">✅ ${res.message}</span>`;
                        new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3').play().catch(()=>{});
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        resultContainer.innerHTML = `<span class="error-msg">⚠️ ${res.message}</span>`;
                        setTimeout(() => {
                            if(html5QrcodeScanner) html5QrcodeScanner.resume();
                            resultContainer.innerHTML = 'Ready for next scan...';
                        }, 2000);
                    }
                })
                .catch(err => {
                    console.error(err);
                    resultContainer.innerHTML = '<span class="error-msg">Network Error</span>';
                    setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 2000);
                });
            } else {
                 console.log("Ignored non-attendance QR");
            }
        } catch (e) {
        }
    }
</script>
</html>
