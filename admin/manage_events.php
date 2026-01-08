<?php
/*
KP34903: Manage events
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $max_slots = intval($_POST['max_slots']);
    $price = floatval($_POST['price']);
    $bank_details = $_POST['bank_details'];
    $status = $_POST['status'];
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $organizer_id = $_SESSION['user_id'];
    $filename = "";
    $update_image = false;
    if (!empty($_FILES['banner_image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $target_dir = "../uploads/banners/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = time() . "_" . basename($_FILES["banner_image"]["name"]);
            if (move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_dir . $filename)) {
                $update_image = true;
            }
        }
    }
    if ($event_id > 0) {
        if ($update_image) {
            $stmt = $conn->prepare("UPDATE events SET title=?, description=?, location=?, event_date=?, event_time=?, max_slots=?, price=?, bank_details=?, status=?, banner_image=? WHERE event_id=? AND organizer_id=?");
            $stmt->bind_param("sssssidsssii", $title, $description, $location, $event_date, $event_time, $max_slots, $price, $bank_details, $status, $filename, $event_id, $organizer_id);
        } else {
            $stmt = $conn->prepare("UPDATE events SET title=?, description=?, location=?, event_date=?, event_time=?, max_slots=?, price=?, bank_details=?, status=? WHERE event_id=? AND organizer_id=?");
            $stmt->bind_param("sssssidssii", $title, $description, $location, $event_date, $event_time, $max_slots, $price, $bank_details, $status, $event_id, $organizer_id);
        }
    } else {
        if ($update_image) {
        } else { $filename = "default.jpg"; }
        $stmt = $conn->prepare("INSERT INTO events (organizer_id, title, description, location, event_date, event_time, max_slots, price, bank_details, status, banner_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssidsss", $organizer_id, $title, $description, $location, $event_date, $event_time, $max_slots, $price, $bank_details, $status, $filename);
    }
    if ($stmt->execute()) {
        header("Location: manage_events.php?msg=saved"); exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $oid = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id=? AND organizer_id=?");
    $stmt->bind_param("ii", $id, $oid);
    $stmt->execute();
    header("Location: manage_events.php?msg=deleted"); exit();
}
if ($_SESSION['role'] == 'admin') {
    $events = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
} else {
    $my_id = $_SESSION['user_id'];
    $events = $conn->query("SELECT * FROM events WHERE organizer_id=$my_id ORDER BY event_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
        <a href="manage_events.php" class="active"><i class="fa-solid fa-calendar w-25px"></i> Events</a>
        <a href="manage_registrations.php"><i class="fa-solid fa-users w-25px"></i> Registrations
            <?php if(isset($pending_nav) && $pending_nav > 0): ?><span class="badge bg-danger text-white ml-5 text-xs"><?php echo $pending_nav; ?></span><?php endif; ?>
        </a>
        <a href="attendance.php"><i class="fa-solid fa-clipboard-user w-25px"></i> Attendance</a>
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
            <h1>Manage Events</h1>
            <button onclick="openModal()" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Event</button>
        </div>
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-open p-10 rounded mb-20">Operation successful!</div>
        <?php endif; ?>
        <table class="admin-table">
            <thead><tr><th>Image</th><th>Title</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while($row = $events->fetch_assoc()): ?>
                <tr>
                    <td><?php $img = !empty($row['banner_image']) ? "../uploads/banners/".$row['banner_image'] : "../assets/images/default.jpg"; ?>
                        <img src="<?php echo $img; ?>" class="table-thumb">
                    </td>
                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong><br><span class="text-sm text-slate"><?php echo htmlspecialchars($row['location']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                    <td>
                        <?php if($row['status']=='open'): ?><span class="badge bg-open">Open</span>
                        <?php elseif($row['status']=='closed'): ?><span class="badge bg-closed">Closed</span>
                        <?php else: ?><span class="badge bg-cancelled">Cancelled</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['organizer_id'] == $_SESSION['user_id']): ?>
                            <button onclick='openModal(<?php echo json_encode($row); ?>)' class="btn btn-secondary btn-sm">Edit</button>
                            <a href="manage_events.php?delete=<?php echo $row['event_id']; ?>" class="btn btn-secondary btn-sm text-danger" onclick="return confirm('Delete event?')">Delete</a>
                        <?php else: ?>
                            <span class="text-xs-gray">View Only</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="eventModal" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-between mb-20">
            <h2 id="modalTitle">Create New Event</h2>
            <span onclick="document.getElementById('eventModal').style.display='none'" class="cursor-pointer text-xl">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="event_id" id="event_id" value="">
            <div class="mb-15"><label>Title</label><input type="text" name="title" id="title" required></div>
            <div class="mb-15"><label>Description</label><textarea name="description" id="description" rows="4" required></textarea></div>
            <div class="grid-2 mb-15">
                <div><label>Date</label><input type="date" name="event_date" id="event_date" required></div>
                <div><label>Time</label><input type="time" name="event_time" id="event_time" required></div>
            </div>
            <div class="mb-15"><label>Location</label><input type="text" name="location" id="location" required></div>
            <div class="grid-3-form mb-15">
                <div><label>Max Slots</label><input type="number" name="max_slots" id="max_slots" value="50" required></div>
                <div><label>Price (RM)</label><input type="number" step="0.01" name="price" id="price" value="0.00" required></div>
                <div><label>Status</label><select name="status" id="status"><option value="open">Open</option><option value="closed">Closed</option><option value="cancelled">Cancelled</option></select></div>
            </div>
            <div class="mb-15">
                <label>Bank Details (Instructions for payment)</label>
                <textarea name="bank_details" id="bank_details" rows="2" placeholder="e.g. Maybank 123456789 (JiranHub Admin)"></textarea>
                <small class="text-slate">Required if price > 0.</small>
            </div>
            <div class="mb-50"><label>Banner Image (Optional)</label><input type="file" name="banner_image"><small class="text-slate">Leave empty to keep current image when editing.</small></div>
            <button type="submit" class="btn btn-primary w-full">Save Event</button>
        </form>
    </div>
</div>
<script>
    function openModal(data = null) {
        document.getElementById('eventModal').style.display = 'block';
        if (data) {
            document.getElementById('modalTitle').innerText = 'Edit Event';
            document.getElementById('event_id').value = data.event_id;
            document.getElementById('title').value = data.title;
            document.getElementById('description').value = data.description;
            document.getElementById('location').value = data.location;
            document.getElementById('event_date').value = data.event_date;
            document.getElementById('event_time').value = data.event_time;
            document.getElementById('max_slots').value = data.max_slots;
            document.getElementById('price').value = data.price;
            document.getElementById('bank_details').value = data.bank_details || '';
            document.getElementById('status').value = data.status;
        } else {
            document.getElementById('modalTitle').innerText = 'Create New Event';
            document.getElementById('event_id').value = '';
            document.querySelector('form').reset();
        }
    }
    window.onclick = function(event) { if (event.target == document.getElementById('eventModal')) document.getElementById('eventModal').style.display = "none"; }
</script>
</body>
</html>
