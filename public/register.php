<?php
$pageTitle = "Registrasi";
require_once '../includes/public_header.php';

$successMsg = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);
?>

<div class="auth-container">
    <!-- ===== ILUSTRASI KIRI ===== -->
    <div class="nx-auth-illustration" style="background: linear-gradient(135deg, #10b981 0%, #06b6d4 50%, #3b82f6 100%);">
        <div class="nx-auth-illustration-content">
            <div class="nx-logo">🎉</div>
            <h2>Bergabunglah dengan Kami!</h2>
            <p>Daftarkan diri Anda untuk mulai menggunakan Smart Attendance dan nikmati kemudahan absensi modern.</p>
            <div class="nx-auth-features">
                <div class="nx-auth-feature">
                    <div class="ico">⚡</div>
                    <span>Proses pendaftaran cepat & mudah</span>
                </div>
                <div class="nx-auth-feature">
                    <div class="ico">📱</div>
                    <span>Akses dari mana saja, kapan saja</span>
                </div>
                <div class="nx-auth-feature">
                    <div class="ico">🔔</div>
                    <span>Notifikasi Telegram terintegrasi</span>
                </div>
                <div class="nx-auth-feature">
                    <div class="ico">😊</div>
                    <span>Mood check-in & gamifikasi seru</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FORM KANAN ===== -->
    <div class="auth-box">
        <div class="nx-auth-header">
            <span class="nx-welcome" style="background: var(--nx-emerald-bg); color: var(--nx-emerald);">🎊 Buat akun baru</span>
            <h2>Daftar Akun Karyawan</h2>
            <p class="subtitle">Isi data diri Anda untuk memulai perjalanan di Smart Attendance</p>
        </div>
        
        <?php if ($successMsg): ?>
            <div class="alert success">✅ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <form action="../api/auth.php?action=register" method="POST">
            <div class="form-group">
                <label><span class="ico">🆔</span> NIP (Nomor Induk Pegawai)</label>
                <input type="text" name="nip" placeholder="Contoh: EMP002" required autocomplete="username">
            </div>
            <div class="form-group">
                <label><span class="ico">👤</span> Nama Lengkap</label>
                <input type="text" name="name" placeholder="Nama lengkap sesuai KTP" required autocomplete="name">
            </div>
            <div class="form-group">
                <label><span class="ico">📧</span> Email</label>
                <input type="email" name="email" placeholder="nama@kantor.com" required autocomplete="email">
            </div>
            <div class="form-group">
                <label><span class="ico">🔒</span> Password</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password">
            </div>
            <button type="submit" class="btn-primary" style="background: var(--nx-grad-success); box-shadow: 0 8px 20px rgba(16,185,129,0.3);">🎉 Daftar Sekarang</button>
        </form>
        <p class="auth-footer">
            Sudah punya akun? <a href="index.php">Login di sini</a>
        </p>
    </div>
</div>

<?php require_once '../includes/public_footer.php'; ?>