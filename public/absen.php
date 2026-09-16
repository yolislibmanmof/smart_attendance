<?php
$pageTitle = "Absensi";
require_once '../includes/public_header.php';

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Cek apakah sudah absen masuk hari ini
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE user_id = ? AND date = ?");
$stmt->execute([$userId, $today]);
$todayAttendance = $stmt->fetch();

// Ambil lokasi aktif
$stmt = $pdo->prepare("SELECT * FROM locations WHERE is_active = TRUE LIMIT 1");
$stmt->execute();
$location = $stmt->fetch();

// [V3] Cek apakah user sudah enroll wajah
$stFace = $pdo->prepare("SELECT face_descriptor IS NOT NULL AS enrolled FROM users WHERE id = ?");
$stFace->execute([$userId]);
$faceEnrolled = (bool)$stFace->fetchColumn();
?>

<div class="absen-container">
    <div class="absen-header">
        <h1>Absensi Hari Ini</h1>
        <p><?= date('l, d F Y') ?></p>
    </div>

    <?php if ($location): ?>
    <div class="location-info">
        <strong>📍 Lokasi:</strong> <?= htmlspecialchars($location['name']) ?>
        <br>
        <small>Radius: <?= $location['radius_meter'] ?> meter</small>
        <?php if ($location['wifi_ssid']): ?>
            <br><strong>📶 Wi-Fi:</strong> <?= htmlspecialchars($location['wifi_ssid']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="status-message"></div>

    <?php if (!$todayAttendance): ?>
        <!-- Form Absen Masuk -->
        <div class="absen-box" id="absen-masuk-box">
            <h2>Absen Masuk</h2>
            <div class="location-status" id="location-status">
                <p>🔄 Mendeteksi lokasi Anda...</p>
            </div>
            
            <div class="qr-section">
                <p><strong>Scan QR Code untuk absen:</strong></p>
                <div id="qr-reader" style="width: 100%"></div>
            </div>

            <!-- [V3] FACE VERIFICATION PANEL -->
            <?php if ($faceEnrolled): ?>
            <div class="face-panel">
                <p><strong>🔐 Verifikasi Wajah (Liveness):</strong></p>
                <div class="face-wrap">
                    <video id="face-video" data-mode="verify" autoplay muted playsinline></video>
                    <div class="face-oval"></div>
                </div>
                <div id="face-status" class="face-status">Menyiapkan...</div>
                <span id="face-verified-badge" class="badge success" style="display:none; margin-top:8px;">✅ Wajah Terverifikasi</span>
            </div>
            <?php else: ?>
            <div class="face-panel muted">
                <p>😺 <strong>Absen Wajah</strong> belum aktif di akun Anda.</p>
                <a href="enroll_face.php" class="face-enroll-link">Aktifkan sekarang →</a>
            </div>
            <?php endif; ?>

            <form id="form-absen-masuk" style="display: none;">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="wifi_bssid" id="wifi_bssid">
                <input type="hidden" name="qr_token" id="qr_token">
                <!-- [V3] Face inputs -->
                <input type="hidden" name="face_descriptor_live" value="">
                <input type="hidden" name="face_snapshot" value="">
                
                <div class="form-info">
                    <p><strong>Lokasi:</strong> <span id="lokasi-info">-</span></p>
                    <p><strong>Akurasi:</strong> <span id="akurasi-info">-</span></p>
                </div>

                <button type="submit" class="btn-absen btn-masuk">
                    <span>✅</span> Konfirmasi Absen Masuk
                </button>
            </form>
        </div>

    <?php elseif (!$todayAttendance['clock_out_time']): ?>
        <!-- Form Absen Pulang -->
        <div class="absen-box" id="absen-pulang-box">
            <h2>Absen Pulang</h2>
            <div class="absen-info">
                <p><strong>Jam Masuk:</strong> <?= date('H:i', strtotime($todayAttendance['clock_in_time'])) ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge badge-<?= $todayAttendance['status'] ?>">
                        <?= $todayAttendance['status'] == 'present' ? 'Hadir' : 'Terlambat' ?>
                    </span>
                </p>
            </div>

            <div class="location-status" id="location-status">
                <p>🔄 Mendeteksi lokasi Anda...</p>
            </div>

            <form id="form-absen-pulang" style="display: none;">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="wifi_bssid" id="wifi_bssid">
                
                <div class="form-info">
                    <p><strong>Lokasi:</strong> <span id="lokasi-info">-</span></p>
                    <p><strong>Akurasi:</strong> <span id="akurasi-info">-</span></p>
                </div>

                <button type="submit" class="btn-absen btn-pulang">
                    <span></span> Konfirmasi Absen Pulang
                </button>
            </form>
        </div>

    <?php else: ?>
        <div class="absen-box completed">
            <h2>✅ Absensi Hari Ini Selesai</h2>
            <div class="absen-info">
                <p><strong>Jam Masuk:</strong> <?= date('H:i', strtotime($todayAttendance['clock_in_time'])) ?></p>
                <p><strong>Jam Pulang:</strong> <?= date('H:i', strtotime($todayAttendance['clock_out_time'])) ?></p>
            </div>
            <p class="thank-you">Terima kasih, semoga harimu menyenangkan!</p>

            <?php if ($todayAttendance['mood'] === null): ?>
            <div class="mood-box">
                <p><strong>Bagaimana perasaanmu bekerja hari ini?</strong></p>
                <div class="mood-options">
                    <?php foreach ([1=>'😞',2=>'😐',3=>'😊',4=>'😄',5=>'🤩'] as $val=>$emoji): ?>
                    <button type="button" class="mood-btn" data-mood="<?= $val ?>" title="Mood <?= $val ?>"><?= $emoji ?></button>
                    <?php endforeach; ?>
                </div>
                <small style="color:#7f8c8d;">+2 poin untuk berbagi mood 😊</small>
            </div>
            <?php else: ?>
            <p style="margin-top:10px; font-size:24px;"><?= [1=>'😞',2=>'😐',3=>'😊',4=>'😄',5=>'🤩'][$todayAttendance['mood']] ?> <small style="font-size:13px; color:#7f8c8d;">mood hari ini</small></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="back-link">
        <a href="dashboard.php">← Kembali ke Dashboard</a>
    </div>
</div>

<!-- Library QR Code Scanner -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<!-- [V3] Library Face Recognition -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<?php require_once '../includes/public_footer.php'; ?>