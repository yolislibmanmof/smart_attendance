<?php
$pageTitle = "Dashboard Karyawan";
require_once '../includes/public_header.php';

$userId = $_SESSION['user_id'];

// Statistik bulan ini
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendances 
    WHERE user_id = ? 
    AND MONTH(date) = MONTH(CURRENT_DATE()) 
    AND YEAR(date) = YEAR(CURRENT_DATE())
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Riwayat 5 absensi terakhir
$stmt = $pdo->prepare("
    SELECT * FROM attendances 
    WHERE user_id = ? 
    ORDER BY date DESC, created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recentAttendances = $stmt->fetchAll();

// ===== V2: GAMIFICATION =====
$stmt = $pdo->prepare("SELECT status, mood FROM attendances WHERE user_id = ? AND date >= ? ORDER BY date DESC");
$stmt->execute([$userId, date('Y-m-01')]);
$monthRows = $stmt->fetchAll();

$points = 0; $onTime = 0; $moodCount = 0; $streak = 0;
foreach ($monthRows as $r) {
    if ($r['status'] === 'present') { $points += 10; $onTime++; }
    elseif ($r['status'] === 'late') { $points += 5; }
    if ($r['mood'] !== null) { $points += 2; $moodCount++; }
}
// Hitung streak on-time (beruntun dari terbaru)
foreach ($monthRows as $r) { if ($r['status'] === 'present') $streak++; else break; }

$badges = [];
if ($streak >= 3)   $badges[] = "🔥 On-Time Streak {$streak} hari";
if ($onTime >= 3)   $badges[] = "🐦 Early Bird ({$onTime}x tepat waktu)";
if ($moodCount >= 5) $badges[] = "😊 Mood Master";
if ($points >= 100) $badges[] = "💯 Century Club";
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
        <p><?= date('l, d F Y') ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <h3><?= $stats['total_days'] ?? 0 ?></h3>
                <p>Total Hari</p>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <h3><?= $stats['present_days'] ?? 0 ?></h3>
                <p>Hadir</p>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <h3><?= $stats['late_days'] ?? 0 ?></h3>
                <p>Terlambat</p>
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <h3><?= $stats['absent_days'] ?? 0 ?></h3>
                <p>Tanpa Keterangan</p>
            </div>
        </div>
    </div>

    <!-- V2: GAMIFICATION CARD -->
    <div class="content-box" style="display:flex; gap:20px; align-items:center; flex-wrap:wrap; margin-bottom: 25px;">
        <div style="text-align:center; min-width:90px;">
            <div style="font-size:34px; font-weight:800; color:#3498db;"><?= $points ?></div>
            <small style="color:#7f8c8d;">Poin Kehadiran</small>
        </div>
        <div style="flex:1; display:flex; gap:8px; flex-wrap:wrap;">
            <?php if (empty($badges)): ?>
                <small style="color:#95a5a6;">Kumpulkan badge: absen tepat waktu & isi mood tiap hari! 😊</small>
            <?php else: foreach ($badges as $b): ?>
                <span class="badge info" style="font-size:13px; padding:8px 14px;"><?= $b ?></span>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="action-buttons">
        <a href="absen.php" class="btn-action primary">
            <span class="btn-icon">📍</span>
            <div>
                <strong>Absen Sekarang</strong>
                <small>Check-in / Check-out</small>
            </div>
        </a>
        <a href="riwayat.php" class="btn-action">
            <span class="btn-icon">📋</span>
            <div>
                <strong>Riwayat Absensi</strong>
                <small>Lihat semua catatan</small>
            </div>
        </a>
        <a href="cuti.php" class="btn-action">
            <span class="btn-icon">🏖️</span>
            <div>
                <strong>Ajukan Cuti/Izin</strong>
                <small>Permohonan cuti</small>
            </div>
        </a>
    </div>

    <div class="recent-activity">
        <h2>5 Absensi Terakhir</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentAttendances)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">Belum ada data absensi</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentAttendances as $att): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($att['date'])) ?></td>
                            <td><?= $att['clock_in_time'] ? date('H:i', strtotime($att['clock_in_time'])) : '-' ?></td>
                            <td><?= $att['clock_out_time'] ? date('H:i', strtotime($att['clock_out_time'])) : '-' ?></td>
                            <td>
                                <?php
                                $statusClass = 'badge ';
                                $statusText = $att['status'];
                                switch($att['status']) {
                                    case 'present': $statusClass .= 'success'; $statusText = 'Hadir'; break;
                                    case 'late': $statusClass .= 'warning'; $statusText = 'Terlambat'; break;
                                    case 'absent': $statusClass .= 'danger'; $statusText = 'Tidak Hadir'; break;
                                    case 'leave': $statusClass .= 'info'; $statusText = 'Cuti'; break;
                                    case 'permission': $statusClass .= 'info'; $statusText = 'Izin'; break;
                                }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/public_footer.php'; ?>