<?php
$pageTitle = "Login";
require_once '../includes/public_header.php';

$errorMsg = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<div class="auth-container">
    <!-- ===== ILUSTRASI KIRI ===== -->
    <div class="nx-auth-illustration">
        <div class="nx-auth-illustration-content">
            <div class="nx-logo">⏰</div>
            <h2>Selamat Datang di Smart Attendance</h2>
            <p>Aplikasi absensi modern dengan verifikasi multi-faktor: GPS, Wi-Fi, QR Code, dan Anti-Fake GPS.</p>
            <div class="nx-auth-features">
                <div class="nx-auth-feature">
                    <div class="ico">📍</div>
                    <span>Verifikasi GPS Geofencing akurat</span>
                </div>
                <div class="nx-auth-feature">
                    <div class="ico">🎫</div>
                    <span>QR Code berputar anti-foto</span>
                </div>
                <div class="nx-auth-feature">
                    <div class="ico">📴</div>
                    <span>Offline mode — absen tanpa sinyal</span>
                </div>
                <div class="nx-auth-feature">
                    <div class="ico">🏆</div>
                    <span>Poin & badge untuk kehadiran rajin</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FORM KANAN ===== -->
    <div class="auth-box">
        <div class="nx-auth-header">
            <span class="nx-welcome">👋 Selamat datang kembali</span>
            <h2>Masuk ke Akun Anda</h2>
            <p class="subtitle">Silakan masuk untuk melanjutkan ke Portal Karyawan</p>
        </div>
        
        <?php if ($errorMsg): ?>
            <div class="alert error">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form action="../api/auth.php?action=login" method="POST">
            <div class="form-group">
                <label><span class="ico">📧</span> Email</label>
                <input type="email" name="email" placeholder="nama@kantor.com" required autocomplete="email">
            </div>
            <div class="form-group">
                <label><span class="ico">🔒</span> Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary">✨ Masuk Sekarang</button>
        </form>
        <p class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
    </div>
</div>

<?php require_once '../includes/public_footer.php'; ?>