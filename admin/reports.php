<?php
require_once '../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../public/index.php');
    exit;
}

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

// ===== EXPORT CSV =====
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_kehadiran_' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIP', 'Nama', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Metode Verifikasi']);
    $stmt = $pdo->prepare("SELECT a.*, u.name, u.nip FROM attendances a JOIN users u ON a.user_id = u.id WHERE DATE_FORMAT(a.date, '%Y-%m') = ? ORDER BY a.date, u.name");
    $stmt->execute([$month]);
    foreach ($stmt->fetchAll() as $r) {
        fputcsv($out, [$r['nip'], $r['name'], $r['date'], $r['clock_in_time'] ?? '-', $r['clock_out_time'] ?? '-', $r['status'], $r['notes'] ?? '-']);
    }
    fclose($out);
    exit;
}

require_once '../includes/admin_header.php';

// Ringkasan bulan
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status = 'present') AS hadir, SUM(status = 'late') AS terlambat, SUM(status = 'absent') AS alpa FROM attendances WHERE DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->execute([$month]);
$sum = $stmt->fetch();
$rate = $sum['total'] > 0 ? round((($sum['hadir'] + $sum['terlambat']) / $sum['total']) * 100) : 0;

// Rekap per karyawan
$stmt = $pdo->prepare("SELECT u.nip, u.name, SUM(a.status = 'present') AS hadir, SUM(a.status = 'late') AS terlambat, SUM(a.status = 'absent') AS alpa FROM attendances a JOIN users u ON a.user_id = u.id WHERE DATE_FORMAT(a.date, '%Y-%m') = ? GROUP BY u.id ORDER BY u.name");
$stmt->execute([$month]);
$perUser = $stmt->fetchAll();

// Detail absensi
$stmt = $pdo->prepare("SELECT a.*, u.name, u.nip FROM attendances a JOIN users u ON a.user_id = u.id WHERE DATE_FORMAT(a.date, '%Y-%m') = ? ORDER BY a.date DESC, u.name");
$stmt->execute([$month]);
$details = $stmt->fetchAll();
?>

<style>
    .nx-report-toolbar {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius-lg);
        padding: 18px 22px;
        margin-bottom: 24px;
        display: flex;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
    }
    .nx-month-picker {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .nx-month-picker label {
        font-size: 12px;
        color: var(--nx-text-dim);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    .nx-month-picker input {
        background: var(--nx-bg-2);
        border: 1px solid var(--nx-border-strong);
        color: #fff;
        padding: 9px 14px;
        border-radius: 8px;
        font-family: var(--nx-mono);
        font-size: 13px;
        color-scheme: dark;
    }
    .nx-month-picker input:focus { outline: none; border-color: var(--nx-accent); box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    .nx-export-btn {
        margin-left: auto;
        background: var(--nx-gradient-3);
        color: #fff;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 6px 18px rgba(16,185,129,0.35);
        transition: all 0.2s;
    }
    .nx-export-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16,185,129,0.5); }

    .nx-report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 26px; }

    .nx-stat-card-mini {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius);
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s;
    }
    .nx-stat-card-mini:hover { transform: translateY(-3px); border-color: var(--nx-border-strong); }
    .nx-stat-card-mini::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 3px;
    }
    .nx-stat-card-mini.a::before { background: var(--nx-gradient-2); }
    .nx-stat-card-mini.b::before { background: var(--nx-gradient-3); }
    .nx-stat-card-mini.c::before { background: var(--nx-gradient-4); }
    .nx-stat-card-mini.d::before { background: var(--nx-gradient-5); }
    .nx-stat-card-mini .ico {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        margin-bottom: 12px;
    }
    .nx-stat-card-mini.a .ico { background: var(--nx-gradient-2); box-shadow: 0 6px 14px rgba(6,182,212,0.3); }
    .nx-stat-card-mini.b .ico { background: var(--nx-gradient-3); box-shadow: 0 6px 14px rgba(16,185,129,0.3); }
    .nx-stat-card-mini.c .ico { background: var(--nx-gradient-4); box-shadow: 0 6px 14px rgba(245,158,11,0.3); }
    .nx-stat-card-mini.d .ico { background: var(--nx-gradient-5); box-shadow: 0 6px 14px rgba(236,72,153,0.3); }
    .nx-stat-card-mini h3 {
        font-size: 28px;
        font-weight: 800;
        color: #fff;
        margin: 0 0 4px;
        letter-spacing: -0.6px;
        line-height: 1;
    }
    .nx-stat-card-mini p { margin: 0; font-size: 12px; color: var(--nx-text-dim); font-weight: 500; }

    .nx-recap-row {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 14px;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--nx-border);
    }
    .nx-recap-row:last-child { border-bottom: none; }
    .nx-recap-bars { display: flex; gap: 4px; align-items: center; }
    .nx-bar { height: 24px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 700; min-width: 30px; transition: all 0.3s; }
    .nx-bar.h { background: linear-gradient(90deg, #10b981, #34d399); }
    .nx-bar.l { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .nx-bar.a { background: linear-gradient(90deg, #f43f5e, #fb7185); }

    .nx-bar-legend {
        display: flex;
        gap: 14px;
        margin-top: 10px;
        margin-bottom: 18px;
        font-size: 11px;
        color: var(--nx-text-dim);
    }
    .nx-bar-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .nx-bar-legend .dot { width: 10px; height: 10px; border-radius: 3px; }
</style>

<!-- ===== TOOLBAR ===== -->
<div class="nx-report-toolbar">
    <form method="GET" class="nx-month-picker">
        <label>📅 Bulan</label>
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        <button class="btn btn-primary">Terapkan</button>
    </form>
    <a href="reports.php?month=<?= htmlspecialchars($month) ?>&export=csv" class="nx-export-btn">
        ⬇️ Export CSV
    </a>
</div>

<!-- ===== STATS GRID ===== -->
<div class="nx-report-grid">
    <div class="nx-stat-card-mini a">
        <div class="ico">📋</div>
        <h3><?= (int)$sum['total'] ?></h3>
        <p>Total Catatan Absensi</p>
    </div>
    <div class="nx-stat-card-mini b">
        <div class="ico">✅</div>
        <h3><?= (int)$sum['hadir'] ?></h3>
        <p>Hadir Tepat Waktu</p>
    </div>
    <div class="nx-stat-card-mini c">
        <div class="ico">⚠️</div>
        <h3><?= (int)$sum['terlambat'] ?></h3>
        <p>Terlambat</p>
    </div>
    <div class="nx-stat-card-mini d">
        <div class="ico">📈</div>
        <h3><?= $rate ?>%</h3>
        <p>Tingkat Kehadiran</p>
    </div>
</div>

<!-- ===== REKAP PER KARYAWAN ===== -->
<div class="panel" style="margin-bottom: 26px;">
    <div class="panel-header">
        <h2>Rekap Visual per Karyawan</h2>
        <span class="badge info"><?= htmlspecialchars($month) ?></span>
    </div>
    <div style="padding: 22px;">
        <?php if (empty($perUser)): ?>
            <div class="empty-row">
                <span class="nx-empty-icon">📊</span>
                <div><strong>Belum ada data rekap bulan ini</strong></div>
                <small style="color:var(--nx-text-muted);">Data akan muncul otomatis setelah karyawan melakukan absensi.</small>
            </div>
        <?php else:
            $barLegend = '<div class="nx-bar-legend">
                <span><span class="dot" style="background:linear-gradient(90deg,#10b981,#34d399);"></span> Hadir</span>
                <span><span class="dot" style="background:linear-gradient(90deg,#f59e0b,#fbbf24);"></span> Terlambat</span>
                <span><span class="dot" style="background:linear-gradient(90deg,#f43f5e,#fb7185);"></span> Tanpa Ket.</span>
            </div>';
            echo $barLegend;
        ?>
            <?php foreach ($perUser as $pu):
                $parts = explode(' ', $pu['name']);
                $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
                $total = (int)$pu['hadir'] + (int)$pu['terlambat'] + (int)$pu['alpa'];
                $maxTotal = max(array_map(fn($p) => (int)$p['hadir'] + (int)$p['terlambat'] + (int)$p['alpa'], $perUser));
                $scale = $maxTotal > 0 ? 100 / $maxTotal : 1;
                $wH = (int)$pu['hadir'] * $scale;
                $wL = (int)$pu['terlambat'] * $scale;
                $wA = (int)$pu['alpa'] * $scale;
            ?>
            <div class="nx-recap-row">
                <div class="nx-user-cell" style="min-width: 200px;">
                    <div class="nx-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="nx-user-cell-info">
                        <strong><?= htmlspecialchars($pu['name']) ?></strong>
                        <small><?= htmlspecialchars($pu['nip']) ?></small>
                    </div>
                </div>
                <div class="nx-recap-bars">
                    <?php if ($pu['hadir'] > 0): ?><div class="nx-bar h" style="width: <?= max($wH, 6) ?>%;" title="Hadir: <?= (int)$pu['hadir'] ?>"><?= (int)$pu['hadir'] ?></div><?php endif; ?>
                    <?php if ($pu['terlambat'] > 0): ?><div class="nx-bar l" style="width: <?= max($wL, 6) ?>%;" title="Terlambat: <?= (int)$pu['terlambat'] ?>"><?= (int)$pu['terlambat'] ?></div><?php endif; ?>
                    <?php if ($pu['alpa'] > 0): ?><div class="nx-bar a" style="width: <?= max($wA, 6) ?>%;" title="Alpa: <?= (int)$pu['alpa'] ?>"><?= (int)$pu['alpa'] ?></div><?php endif; ?>
                </div>
                <div style="font-size: 11px; color: var(--nx-text-muted); font-weight: 600; min-width: 60px; text-align: right;">
                    Total: <span style="color: #fff;"><?= $total ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===== DETAIL TABLE ===== -->
<div class="panel">
    <div class="panel-header">
        <h2>Detail Absensi</h2>
        <span class="badge info"><?= count($details) ?> record</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Karyawan</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Metode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($details)): ?>
                    <tr><td colspan="6" class="empty-row"><span class="nx-empty-icon">📭</span><div><strong>Belum ada detail absensi</strong></div></td></tr>
                <?php else: foreach ($details as $d):
                    $parts = explode(' ', $d['name']);
                    $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
                ?>
                <tr>
                    <td><span class="nx-mono"><?= date('d M Y', strtotime($d['date'])) ?></span></td>
                    <td>
                        <div class="nx-user-cell">
                            <div class="nx-avatar"><?= htmlspecialchars($initials) ?></div>
                            <div class="nx-user-cell-info">
                                <strong><?= htmlspecialchars($d['name']) ?></strong>
                                <small><?= htmlspecialchars($d['nip']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="nx-mono"><?= $d['clock_in_time'] ? date('H:i', strtotime($d['clock_in_time'])) : '—' ?></span></td>
                    <td><span class="nx-mono"><?= $d['clock_out_time'] ? date('H:i', strtotime($d['clock_out_time'])) : '—' ?></span></td>
                    <td><small style="color: var(--nx-text-dim);"><?= htmlspecialchars($d['notes'] ?? '—') ?></small></td>
                    <td>
                        <?php if ($d['status'] === 'present'): ?><span class="badge success"><span class="nx-dot"></span> Hadir</span>
                        <?php elseif ($d['status'] === 'late'): ?><span class="badge warning"><span class="nx-dot"></span> Terlambat</span>
                        <?php else: ?><span class="badge danger"><?= htmlspecialchars($d['status']) ?></span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>