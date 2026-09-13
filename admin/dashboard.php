<?php
$pageTitle = "Dashboard";
require_once '../config/database.php';
require_once '../includes/admin_header.php';

// ===== Statistik =====
$totalEmployees = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
$todayPresent   = (int)$pdo->query("SELECT COUNT(*) FROM attendances WHERE date = CURDATE() AND clock_in_time IS NOT NULL")->fetchColumn();
$todayLate      = (int)$pdo->query("SELECT COUNT(*) FROM attendances WHERE date = CURDATE() AND status = 'late'")->fetchColumn();
$pendingLeaves  = (int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();

// Kemarin (untuk trend)
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yestPresent = (int)$pdo->query("SELECT COUNT(*) FROM attendances WHERE date = '$yesterday' AND clock_in_time IS NOT NULL")->fetchColumn();

// Progress kehadiran hari ini
$presenceRate = $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100) : 0;

// ===== Absensi hari ini =====
$todayAttendances = $pdo->query("
    SELECT a.*, u.name, u.nip 
    FROM attendances a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.date = CURDATE() 
    ORDER BY a.clock_in_time DESC
")->fetchAll();

// Jam sekarang untuk greeting
$hour = (int)date('H');
$greet = $hour < 12 ? 'Selamat Pagi' : ($hour < 18 ? 'Selamat Siang' : 'Selamat Malam');
?>

<!-- WELCOME BANNER -->
<div class="nx-welcome">
    <div class="nx-welcome-content">
        <h2><?= $greet ?>, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>! 👋</h2>
        <p>Berikut ringkasan aktivitas karyawan Anda hari ini, <?= date('l, d F Y') ?>.</p>
        <div class="nx-welcome-chips">
            <span class="nx-chip">📅 <?= date('d M Y') ?></span>
            <span class="nx-chip">⏰ <?= date('H:i') ?> WIB</span>
            <span class="nx-chip">🎯 Kehadiran <?= $presenceRate ?>%</span>
            <?php if ($pendingLeaves > 0): ?>
                <span class="nx-chip" style="background:rgba(245,158,11,0.2); border-color:rgba(245,158,11,0.4);">📬 <?= $pendingLeaves ?> cuti menunggu</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3><?= number_format($totalEmployees) ?></h3>
            <p>Total Karyawan Aktif</p>
            <?php if ($totalEmployees > 0): ?>
                <div class="nx-stat-trend">⚡ Tim Anda</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
            <h3><?= $todayPresent ?></h3>
            <p>Hadir Hari Ini</p>
            <div class="nx-progress">
                <div class="nx-progress-bar" style="width: <?= $presenceRate ?>%;"></div>
            </div>
            <?php if ($todayPresent > $yestPresent): ?>
                <div class="nx-stat-trend">↑ +<?= $todayPresent - $yestPresent ?> vs kemarin</div>
            <?php elseif ($todayPresent < $yestPresent): ?>
                <div class="nx-stat-trend down">↓ <?= $todayPresent - $yestPresent ?> vs kemarin</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon">⚠️</div>
        <div class="stat-info">
            <h3><?= $todayLate ?></h3>
            <p>Terlambat Hari Ini</p>
            <?php if ($todayLate > 0): ?>
                <div class="nx-stat-trend down">⚡ Perlu perhatian</div>
            <?php else: ?>
                <div class="nx-stat-trend">🎉 Semua tepat waktu</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon">🏖️</div>
        <div class="stat-info">
            <h3><?= $pendingLeaves ?></h3>
            <p>Cuti Menunggu Approval</p>
            <?php if ($pendingLeaves > 0): ?>
                <div class="nx-stat-trend down">📬 Perlu review</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- LIVE ATTENDANCE PANEL -->
<div class="panel">
    <div class="panel-header">
        <h2>Absensi Hari Ini — Live Feed</h2>
        <span class="nx-live">LIVE <?= date('H:i') ?></span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Metode Verifikasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($todayAttendances)): ?>
                    <tr>
                        <td colspan="5" class="empty-row">
                            <span class="nx-empty-icon">🌙</span>
                            <div><strong>Belum ada yang absen hari ini</strong></div>
                            <small style="color:var(--nx-text-muted);">Data akan muncul otomatis saat karyawan mulai absen.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todayAttendances as $att):
                        $parts = explode(' ', $att['name']);
                        $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
                    ?>
                    <tr>
                        <td>
                            <div class="nx-user-cell">
                                <div class="nx-avatar"><?= htmlspecialchars($initials) ?></div>
                                <div class="nx-user-cell-info">
                                    <strong><?= htmlspecialchars($att['name']) ?></strong>
                                    <small><?= htmlspecialchars($att['nip']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($att['clock_in_time']): ?>
                                <span class="nx-mono"><?= date('H:i:s', strtotime($att['clock_in_time'])) ?></span>
                            <?php else: ?>
                                <span style="color:var(--nx-text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($att['clock_out_time']): ?>
                                <span class="nx-mono"><?= date('H:i:s', strtotime($att['clock_out_time'])) ?></span>
                            <?php else: ?>
                                <span style="color:var(--nx-text-muted);">Masih bekerja</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $methods = [];
                            if ($att['is_qr_verified']) $methods[] = ['QR Code', 'info'];
                            else $methods[] = ['GPS / Wi-Fi', 'info'];
                            if ($att['is_mock_location']) $methods[] = ['⚠️ Fake GPS', 'danger'];
                            ?>
                            <?php foreach ($methods as $m): ?>
                                <span class="badge <?= $m[1] ?>"><span class="nx-dot"></span><?= $m[0] ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php
                            $badge = 'success'; $text = '✓ Hadir';
                            if ($att['status'] == 'late') { $badge = 'warning'; $text = '⏰ Terlambat'; }
                            if ($att['status'] == 'absent') { $badge = 'danger'; $text = '✕ Tanpa Ket.'; }
                            ?>
                            <span class="badge <?= $badge ?>"><?= $text ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>