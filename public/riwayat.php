<?php
$pageTitle = "Riwayat Kehadiran";
require_once '../includes/public_header.php';

$userId = $_SESSION['user_id'];

// Filter bulan (format YYYY-MM), default bulan ini
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

function statusBadge($status) {
    switch ($status) {
        case 'present':    return '<span class="badge success">Hadir</span>';
        case 'late':       return '<span class="badge warning">Terlambat</span>';
        case 'absent':     return '<span class="badge danger">Tanpa Keterangan</span>';
        case 'leave':      return '<span class="badge info">Cuti</span>';
        case 'permission': return '<span class="badge info">Izin</span>';
        default:           return '<span class="badge info">' . htmlspecialchars($status) . '</span>';
    }
}

// Data absensi bulan terpilih
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ? ORDER BY date DESC");
$stmt->execute([$userId, $month]);
$records = $stmt->fetchAll();

// Ringkasan bulan terpilih
$stmt = $pdo->prepare("SELECT
        SUM(status = 'present') AS hadir,
        SUM(status = 'late')    AS terlambat,
        SUM(status = 'absent')  AS alpa
    FROM attendances WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->execute([$userId, $month]);
$summary = $stmt->fetch();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Riwayat Kehadiran</h1>
        <p>Catatan absensi Anda</p>
    </div>

    <div class="content-box">
        <form method="GET" class="filter-bar">
            <label for="month"><strong>Filter Bulan:</strong></label>
            <input type="month" id="month" name="month" value="<?= htmlspecialchars($month) ?>" max="<?= date('Y-m') ?>">
            <button type="submit" class="btn-primary" style="width:auto; padding:10px 20px;">Tampilkan</button>
        </form>

        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon">✅</div>
                <div class="stat-info"><h3><?= (int)$summary['hadir'] ?></h3><p>Hadir</p></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info"><h3><?= (int)$summary['terlambat'] ?></h3><p>Terlambat</p></div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">❌</div>
                <div class="stat-info"><h3><?= (int)$summary['alpa'] ?></h3><p>Tanpa Keterangan</p></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Metode Verifikasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:25px; color:#95a5a6;">Tidak ada data pada bulan ini</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                            <td><?= $r['clock_in_time'] ? date('H:i', strtotime($r['clock_in_time'])) : '-' ?></td>
                            <td><?= $r['clock_out_time'] ? date('H:i', strtotime($r['clock_out_time'])) : '-' ?></td>
                            <td><small><?= htmlspecialchars($r['notes'] ?? '-') ?></small></td>
                            <td><?= statusBadge($r['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="back-link"><a href="dashboard.php">← Kembali ke Dashboard</a></div>
</div>

<?php require_once '../includes/public_footer.php'; ?>