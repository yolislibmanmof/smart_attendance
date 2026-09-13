<?php
$pageTitle = "Pengajuan Cuti/Izin";
require_once '../includes/public_header.php';

$userId = $_SESSION['user_id'];
$successMsg = $_SESSION['cuti_success'] ?? '';
$errorMsg   = $_SESSION['cuti_error'] ?? '';
unset($_SESSION['cuti_success'], $_SESSION['cuti_error']);

$stmt = $pdo->prepare("SELECT * FROM leaves WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$myLeaves = $stmt->fetchAll();

$typeLabel   = ['cuti' => 'Cuti', 'izin' => 'Izin', 'sakit' => 'Sakit'];
$statusLabel = [
    'pending'  => ['warning', 'Menunggu'],
    'approved' => ['success', 'Disetujui'],
    'rejected' => ['danger',  'Ditolak'],
];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Pengajuan Cuti / Izin</h1>
        <p>Ajukan permohonan dan pantau statusnya</p>
    </div>

    <?php if ($successMsg): ?><div class="alert success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

    <div class="content-box">
        <h2>Form Pengajuan</h2>
        <form action="../api/leave_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit">
            <div class="form-group">
                <label>Jenis Pengajuan</label>
                <select name="leave_type" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="cuti">Cuti</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tanggal Mulai</label>
                <input type="date" name="start_date" required>
            </div>
            <div class="form-group">
                <label>Tanggal Selesai</label>
                <input type="date" name="end_date" required>
            </div>
            <div class="form-group">
                <label>Alasan</label>
                <textarea name="reason" placeholder="Tuliskan alasan pengajuan Anda..." required></textarea>
            </div>
            <div class="form-group">
                <label>Lampiran (opsional, JPG/PNG/PDF maks 2MB)</label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <button type="submit" class="btn-primary">Kirim Pengajuan</button>
        </form>
    </div>

    <div class="content-box">
        <h2>Riwayat Pengajuan Saya</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Jenis</th><th>Periode</th><th>Alasan</th><th>Status</th><th>Catatan Admin</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($myLeaves)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:25px; color:#95a5a6;">Belum ada pengajuan</td></tr>
                    <?php else: ?>
                        <?php foreach ($myLeaves as $lv): ?>
                        <?php $st = $statusLabel[$lv['status']] ?? ['info', $lv['status']]; ?>
                        <tr>
                            <td><?= $typeLabel[$lv['leave_type']] ?? $lv['leave_type'] ?></td>
                            <td><?= date('d/m/Y', strtotime($lv['start_date'])) ?> - <?= date('d/m/Y', strtotime($lv['end_date'])) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($lv['reason'], 0, 50, '...')) ?></td>
                            <td><span class="badge <?= $st[0] ?>"><?= $st[1] ?></span></td>
                            <td><?= htmlspecialchars($lv['admin_notes'] ?? '-') ?></td>
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