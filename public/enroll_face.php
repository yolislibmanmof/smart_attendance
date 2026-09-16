<?php
$pageTitle = "Absen Wajah";
require_once '../includes/public_header.php';

$userId = $_SESSION['user_id'];
$st = $pdo->prepare("SELECT face_descriptor, face_enrolled_at FROM users WHERE id = ?");
$st->execute([$userId]);
$me = $st->fetch();
$enrolled = !empty($me['face_descriptor']);
?>

<div class="absen-container">
    <div class="absen-header">
        <h1>😺 Absen dengan Wajah</h1>
        <p>Biometrik + Liveness Detection (anti-foto)</p>
    </div>

    <div class="absen-box">
        <?php if ($enrolled): ?>
            <h2>✅ Wajah Sudah Terdaftar</h2>
            <div class="absen-info">
                <p><strong>Terdaftar:</strong> <?= date('d M Y, H:i', strtotime($me['face_enrolled_at'])) ?> WIB</p>
                <p><strong>Proteksi:</strong> Setiap absen masuk wajib verifikasi wajah + kedipan mata</p>
            </div>
            <p style="font-size:13px; color:#7f8c8d; margin-bottom:16px;">
                Foto wajah Anda disimpan lokal di server ini saja (folder <code>uploads/faces/</code>) dan tidak pernah dikirim ke pihak ketiga.
            </p>
            <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                <a href="enroll_face.php?re=1" class="btn-absen btn-masuk" style="width:auto; padding:10px 20px; text-decoration:none; display:inline-block;">🔄 Daftar Ulang</a>
                <button type="button" id="btn-delete-face" class="btn-absen btn-pulang" style="width:auto; padding:10px 20px;">🗑 Hapus Data Wajah</button>
            </div>
        <?php else: ?>
            <h2>Daftarkan Wajah Anda</h2>
            <div class="face-wrap" style="width:300px;">
                <video id="face-video" data-mode="enroll" autoplay muted playsinline></video>
                <div class="face-oval"></div>
            </div>
            <div id="face-status" class="face-status">Menyiapkan kamera & model AI...</div>
            <p style="font-size:12px; color:#95a5a6; margin-top:12px;">
                Tips: cahaya cukup • lepas masker/kacamata gelap • wajah memenuhi bingkai oval
            </p>
        <?php endif; ?>
    </div>

    <div class="back-link"><a href="dashboard.php">← Kembali ke Dashboard</a></div>
</div>

<!-- Library face-api.js (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<?php require_once '../includes/public_footer.php'; ?>