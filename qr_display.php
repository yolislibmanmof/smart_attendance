<?php
require_once __DIR__ . '/config/database.php';
$loc = $pdo->query("SELECT * FROM locations WHERE is_active = TRUE LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>QR Absen - Kiosk</title>
<style>
    body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
           background:linear-gradient(135deg,#2c3e50,#3498db); font-family:'Segoe UI',sans-serif; color:#fff; }
    .kiosk { text-align:center; background:rgba(255,255,255,.08); padding:40px 60px; border-radius:20px; backdrop-filter:blur(6px); }
    h1 { font-size:26px; letter-spacing:2px; margin:0 0 5px; }
    #loc { opacity:.8; font-size:14px; }
    #qr { background:#fff; padding:15px; border-radius:15px; margin:25px 0 15px; width:300px; height:300px; }
    #code { font-family:monospace; font-size:22px; letter-spacing:4px; color:#7ef9ff; }
    #timer { font-size:44px; font-weight:800; margin-top:10px; }
    small { opacity:.7; }
</style>
</head>
<body>
<div class="kiosk">
    <h1>⏰ SCAN UNTUK ABSEN</h1>
    <div id="loc"><?= htmlspecialchars($loc['name'] ?? 'Lokasi belum diatur') ?></div>
    <img id="qr" src="" alt="QR Code">
    <div id="code">-</div>
    <div id="timer">60</div>
    <small>kode berganti otomatis tiap 60 detik</small>
</div>
<script>
let currentCode = '';
async function tick() {
    try {
        const r = await fetch('api/qr_action.php');
        const d = await r.json();
        if (d.success) {
            document.getElementById('timer').textContent = d.seconds_left;
            if (d.code !== currentCode) {
                currentCode = d.code;
                document.getElementById('code').textContent = d.code;
                document.getElementById('qr').src = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(d.code);
            }
        }
    } catch (e) { /* server sebentar offline, coba lagi detik depan */ }
}
tick();
setInterval(tick, 1000);
</script>
</body>
</html>