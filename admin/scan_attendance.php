<?php
/*
KP34903: Scan attendance
Group 7
JiranHub Web
*/

session_start();
require '../db.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Attendance - JiranHub Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { background: #000; color: white; display: flex; flex-direction: column; height: 100vh; margin: 0; }
        .scanner-container { flex: 1; display: flex; justify-content: center; align-items: center; position: relative; }
        #reader { width: 100%; max-width: 500px; }
        .controls { padding: 20px; background: #1e293b; text-align: center; border-radius: 20px 20px 0 0; }
        .back-btn { display: inline-block; color: white; margin-bottom: 5px; text-decoration: none; }
        #scanned-result { margin-top: 10px; font-weight: bold; min-height: 24px; }
        .success-msg { color: #4ade80; }
        .error-msg { color: #f87171; }
    </style>
</head>
<body>
    <div class="scanner-container">
        <div id="reader"></div>
    </div>
    <div class="controls">
        <a href="attendance.php" class="back-btn">&larr; Back to Attendance</a>
        <h3>Scan QR Code</h3>
        <p style="color: #94a3b8; font-size: 0.9rem;">Point camera at participant's ticket</p>
        <div id="scanned-result">Waiting for scan...</div>
    </div>
    <script>
        const resultContainer = document.getElementById('scanned-result');
        let isScanning = true;
        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return;
            try {
                const data = JSON.parse(decodedText);
                if (data.type && data.type === 'attendance' && data.reg_id) {
                    isScanning = false;
                    resultContainer.innerHTML = '<span style="color:#fbbf24">Processing...</span>';
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
                        } else {
                            resultContainer.innerHTML = `<span class="error-msg">❌ ${res.message}</span>`;
                        }
                        setTimeout(() => { isScanning = true; resultContainer.innerHTML = 'Ready to scan next...'; }, 2000);
                    })
                    .catch(err => {
                        console.error(err);
                        isScanning = true;
                        resultContainer.innerHTML = '<span class="error-msg">Network Error</span>';
                    });
                }
            } catch (e) {
                console.log("Invalid QR");
            }
        }
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: {width: 250, height: 250} },
            /* verbose= */ false
        );
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>
