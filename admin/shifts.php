<?php
$pageTitle = "Shift & Jam Kerja";
require_once '../config/database.php';
require_once '../includes/admin_header.php';

$msg = $_SESSION['admin_shift_msg'] ?? '';
unset($_SESSION['admin_shift_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $name      = trim($_POST['name'] ?? '');
        $start     = $_POST['start_time'] ?? '';
        $end       = $_POST['end_time'] ?? '';
        $tolerance = (int)($_POST['late_tolerance_minutes'] ?? 15);

        if ($name === '' || $start === '' || $end === '') {
            $_SESSION['admin_shift_msg'] = 'Semua field wajib diisi!';
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO shifts (name, start_time, end_time, late_tolerance_minutes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $start, $end, $tolerance]);
            $_SESSION['admin_shift_msg'] = 'Shift berhasil ditambahkan!';
        } else {
            $id = (int)($_POST['shift_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE shifts SET name = ?, start_time = ?, end_time = ?, late_tolerance_minutes = ? WHERE id = ?");
            $stmt->execute([$name, $start, $end, $tolerance, $id]);
            $_SESSION['admin_shift_msg'] = 'Shift berhasil diperbarui!';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['shift_id'] ?? 0);
        $pdo->prepare("UPDATE users SET shift_id = NULL WHERE shift_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM shifts WHERE id = ?")->execute([$id]);
        $_SESSION['admin_shift_msg'] = 'Shift dihapus. Karyawan terkait kini tanpa shift.';
    }

    header('Location: shifts.php');
    exit;
}

$editId = (int)($_GET['edit'] ?? 0);
$editShift = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
    $stmt->execute([$editId]);
    $editShift = $stmt->fetch();
}

$shifts = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM users u WHERE u.shift_id = s.id) AS total_users FROM shifts s ORDER BY s.start_time")->fetchAll();

// Gradients untuk kartu shift (bergilir)
$shiftGrads = [
    'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
    'linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%)',
    'linear-gradient(135deg, #10b981 0%, #06b6d4 100%)',
    'linear-gradient(135deg, #f59e0b 0%, #f43f5e 100%)',
    'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)',
    'linear-gradient(135deg, #84cc16 0%, #10b981 100%)',
];

// Hitung total karyawan aktif untuk progress bar
$totalEmp = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
?>

<style>
    .nx-shifts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }

    .nx-shift-card {
        position: relative;
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius-lg);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .nx-shift-card:hover {
        transform: translateY(-4px);
        border-color: var(--nx-border-strong);
        box-shadow: var(--nx-shadow-lg);
    }

    .nx-shift-clock {
        position: relative;
        padding: 26px 22px 20px;
        color: #fff;
        text-align: center;
    }
    .nx-shift-clock::before {
        content: '';
        position: absolute;
        top: -50%; right: -20%;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        pointer-events: none;
    }
    .nx-shift-name {
        font-size: 17px;
        font-weight: 800;
        margin: 0 0 14px;
        letter-spacing: -0.3px;
        position: relative;
    }
    .nx-shift-time {
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 14px;
        font-family: var(--nx-mono);
        position: relative;
    }
    .nx-shift-time .t {
        font-size: 34px;
        font-weight: 700;
        letter-spacing: -1px;
        line-height: 1;
    }
    .nx-shift-time .arr { font-size: 22px; opacity: 0.6; }
    .nx-shift-tolerance {
        margin-top: 12px;
        padding: 4px 12px;
        background: rgba(255,255,255,0.18);
        border-radius: 50px;
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        position: relative;
    }

    .nx-shift-body { padding: 18px 22px; border-top: 1px solid var(--nx-border); }
    .nx-shift-stat { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 12.5px; }
    .nx-shift-stat .label { color: var(--nx-text-dim); }
    .nx-shift-stat .val { color: #fff; font-weight: 700; }

    .nx-shift-progress {
        height: 6px;
        background: var(--nx-bg-2);
        border-radius: 3px;
        overflow: hidden;
        margin: 6px 0 16px;
    }
    .nx-shift-progress-bar { height: 100%; border-radius: 3px; transition: width 1s ease; }

    .nx-shift-actions { display: flex; gap: 8px; }
    .nx-shift-actions .btn { flex: 1; justify-content: center; }
</style>

<?php if ($msg): ?>
    <div class="alert success">✨ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ===== FORM SHIFT ===== -->
<div class="panel" style="margin-bottom: 28px;">
    <div class="nx-form-card-header">
        <div class="nx-ico-big">🕐</div>
        <div>
            <h2><?= $editShift ? 'Edit Shift Kerja' : 'Buat Shift Kerja Baru' ?></h2>
            <p><?= $editShift ? 'Perbarui jam kerja untuk shift yang dipilih' : 'Atur jadwal masuk-pulang & toleransi keterlambatan' ?></p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editShift ? 'update' : 'add' ?>">
            <?php if ($editShift): ?><input type="hidden" name="shift_id" value="<?= $editShift['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Shift</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editShift['name'] ?? '') ?>" placeholder="Contoh: Shift Pagi" required>
                </div>
                <div class="form-group">
                    <label>Toleransi Keterlambatan (menit)</label>
                    <input type="number" name="late_tolerance_minutes" min="0" value="<?= $editShift['late_tolerance_minutes'] ?? 15 ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Jam Masuk</label>
                    <input type="time" name="start_time" value="<?= htmlspecialchars($editShift['start_time'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Jam Pulang</label>
                    <input type="time" name="end_time" value="<?= htmlspecialchars($editShift['end_time'] ?? '') ?>" required>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 8px;">
                <button type="submit" class="btn btn-primary">✨ <?= $editShift ? 'Perbarui Shift' : 'Simpan Shift' ?></button>
                <?php if ($editShift): ?><a href="shifts.php" class="btn btn-light">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ===== GRID SHIFT ===== -->
<div class="panel">
    <div class="panel-header">
        <h2>Daftar Shift Kerja</h2>
        <span class="badge info"><?= count($shifts) ?> shift aktif</span>
    </div>
    <div style="padding: 22px;">
        <?php if (empty($shifts)): ?>
            <div class="empty-row">
                <span class="nx-empty-icon">🕐</span>
                <div><strong>Belum ada shift kerja</strong></div>
                <small style="color:var(--nx-text-muted);">Buat shift pertama Anda melalui form di atas.</small>
            </div>
        <?php else: ?>
            <div class="nx-shifts-grid">
                <?php foreach ($shifts as $i => $s):
                    $grad = $shiftGrads[$i % count($shiftGrads)];
                    $pct = $totalEmp > 0 ? round(($s['total_users'] / $totalEmp) * 100) : 0;
                ?>
                <div class="nx-shift-card">
                    <div class="nx-shift-clock" style="background: <?= $grad ?>;">
                        <div class="nx-shift-name"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="nx-shift-time">
                            <span class="t"><?= substr($s['start_time'], 0, 5) ?></span>
                            <span class="arr">→</span>
                            <span class="t"><?= substr($s['end_time'], 0, 5) ?></span>
                        </div>
                        <div class="nx-shift-tolerance">⏱️ Toleransi <?= $s['late_tolerance_minutes'] ?> menit</div>
                    </div>
                    <div class="nx-shift-body">
                        <div class="nx-shift-stat">
                            <span class="label">Karyawan Terdaftar</span>
                            <span class="val"><?= $s['total_users'] ?> <span style="color:var(--nx-text-muted); font-weight:500;">/ <?= $totalEmp ?></span></span>
                        </div>
                        <div class="nx-shift-progress">
                            <div class="nx-shift-progress-bar" style="width: <?= $pct ?>%; background: <?= $grad ?>;"></div>
                        </div>
                        <div class="nx-shift-actions">
                            <a href="shifts.php?edit=<?= $s['id'] ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                <button class="btn btn-danger btn-sm" style="width: 100%;" onclick="return confirmAction('Hapus shift ini? Karyawan terkait akan kehilangan shift.')">🗑 Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>