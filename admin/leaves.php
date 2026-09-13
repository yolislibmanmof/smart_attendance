<?php
$pageTitle = "Pengajuan Cuti";
require_once '../config/database.php';
require_once '../includes/admin_header.php';

$msg = $_SESSION['admin_leave_msg'] ?? '';
unset($_SESSION['admin_leave_msg']);

$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'approved', 'rejected'])) $filter = 'all';

$sql = "SELECT l.*, u.name, u.nip FROM leaves l JOIN users u ON l.user_id = u.id";
$params = [];
if ($filter !== 'all') { $sql .= " WHERE l.status = ?"; $params[] = $filter; }
$sql .= " ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

$pendingCount  = (int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$approvedCount = (int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'approved'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'rejected'")->fetchColumn();
$totalCount    = count($leaves);

$typeLabel   = ['cuti' => 'Cuti', 'izin' => 'Izin', 'sakit' => 'Sakit'];
$typeEmoji   = ['cuti' => '🏖️', 'izin' => '📝', 'sakit' => '🤒'];
$typeGrad    = [
    'cuti'  => 'linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%)',
    'izin'  => 'linear-gradient(135deg, #f59e0b 0%, #f43f5e 100%)',
    'sakit' => 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)',
];
$statusLabel = [
    'pending'  => ['warning', '⏳ Menunggu Review', 'rgba(245,158,11,0.15)', '#fbbf24', 'rgba(245,158,11,0.3)'],
    'approved' => ['success', '✅ Disetujui',      'rgba(16,185,129,0.15)', '#34d399', 'rgba(16,185,129,0.3)'],
    'rejected' => ['danger',  '❌ Ditolak',        'rgba(244,63,94,0.15)',  '#fb7185', 'rgba(244,63,94,0.3)'],
];
?>

<style>
    .nx-tab-bar { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
    .nx-tab {
        padding: 10px 18px;
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        color: var(--nx-text-dim);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .nx-tab:hover { background: var(--nx-surface-2); color: #fff; transform: translateY(-1px); }
    .nx-tab.active {
        background: var(--nx-gradient-1);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 18px rgba(99,102,241,0.4);
    }
    .nx-tab .count {
        padding: 2px 8px;
        background: rgba(255,255,255,0.18);
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        min-width: 22px;
        text-align: center;
    }
    .nx-tab.active .count { background: rgba(255,255,255,0.25); }

    .nx-leave-card {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius-lg);
        margin-bottom: 16px;
        overflow: hidden;
        transition: all 0.25s;
    }
    .nx-leave-card:hover { border-color: var(--nx-border-strong); transform: translateY(-2px); box-shadow: var(--nx-shadow-lg); }

    .nx-leave-head {
        display: flex;
        gap: 14px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--nx-border);
        align-items: center;
        flex-wrap: wrap;
    }
    .nx-leave-avatar {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; font-weight: 800; color: #fff;
        flex-shrink: 0;
        box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    }
    .nx-leave-info { flex: 1; min-width: 180px; }
    .nx-leave-info strong { display: block; font-size: 14px; color: #fff; font-weight: 700; }
    .nx-leave-info small { font-size: 11px; color: var(--nx-text-muted); font-family: var(--nx-mono); }
    .nx-leave-type-pill {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .nx-leave-body { padding: 18px 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 680px) { .nx-leave-body { grid-template-columns: 1fr; } }
    .nx-leave-body .col { display: flex; flex-direction: column; gap: 8px; }
    .nx-info-chip {
        padding: 10px 14px;
        background: var(--nx-bg-2);
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        font-size: 12.5px;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .nx-info-chip .lbl { color: var(--nx-text-muted); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; margin-bottom: 2px; display: block; }
    .nx-info-chip .val { color: var(--nx-text); font-weight: 500; line-height: 1.4; }
    .nx-info-chip .val.mono { font-family: var(--nx-mono); color: var(--nx-cyan); }

    .nx-leave-reason {
        grid-column: 1 / -1;
        padding: 14px 16px;
        background: var(--nx-bg-2);
        border-left: 3px solid var(--nx-accent);
        border-radius: 8px;
        font-size: 13px;
        color: var(--nx-text);
        line-height: 1.5;
    }
    .nx-leave-reason::before { content: '"'; font-size: 28px; color: var(--nx-accent); font-weight: 800; line-height: 0; vertical-align: -8px; margin-right: 4px; }

    .nx-leave-foot {
        padding: 16px 22px;
        background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, transparent 100%);
        border-top: 1px solid var(--nx-border);
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .nx-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid;
    }

    .nx-approval-form {
        flex: 1;
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        min-width: 260px;
    }
    .nx-approval-form input[type="text"] {
        flex: 1;
        min-width: 140px;
        padding: 8px 12px;
        background: var(--nx-bg-2);
        border: 1px solid var(--nx-border-strong);
        border-radius: 8px;
        color: var(--nx-text);
        font-size: 12.5px;
        font-family: var(--nx-font);
    }
    .nx-approval-form input[type="text"]:focus { outline: none; border-color: var(--nx-accent); }

    .nx-admin-note {
        flex: 1;
        padding: 10px 14px;
        background: var(--nx-bg-2);
        border-radius: 8px;
        font-size: 12.5px;
        color: var(--nx-text-dim);
        border-left: 3px solid var(--nx-accent);
        min-width: 160px;
    }
    .nx-admin-note strong { color: #fff; display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 3px; }

    .nx-attachment-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        background: var(--nx-gradient-2);
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .nx-attachment-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(6,182,212,0.4); }
</style>

<?php if ($msg): ?>
    <div class="alert success">✨ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ===== TAB FILTER ===== -->
<div class="nx-tab-bar">
    <a href="leaves.php?status=all" class="nx-tab <?= $filter === 'all' ? 'active' : '' ?>">
        📋 Semua <span class="count"><?= $totalCount ?></span>
    </a>
    <a href="leaves.php?status=pending" class="nx-tab <?= $filter === 'pending' ? 'active' : '' ?>">
        ⏳ Menunggu Review <span class="count"><?= $pendingCount ?></span>
    </a>
    <a href="leaves.php?status=approved" class="nx-tab <?= $filter === 'approved' ? 'active' : '' ?>">
        ✅ Disetujui <span class="count"><?= $approvedCount ?></span>
    </a>
    <a href="leaves.php?status=rejected" class="nx-tab <?= $filter === 'rejected' ? 'active' : '' ?>">
        ❌ Ditolak <span class="count"><?= $rejectedCount ?></span>
    </a>
</div>

<!-- ===== CARD LIST ===== -->
<?php if (empty($leaves)): ?>
    <div class="panel">
        <div class="empty-row">
            <span class="nx-empty-icon">📭</span>
            <div><strong>Tidak ada pengajuan pada filter ini</strong></div>
            <small style="color:var(--nx-text-muted);">Pengajuan baru akan muncul di sini saat karyawan mengirimkan permohonan cuti, izin, atau sakit.</small>
        </div>
    </div>
<?php else: foreach ($leaves as $lv):
    $st = $statusLabel[$lv['status']] ?? ['info', $lv['status'], 'rgba(99,102,241,0.15)', '#a5b4fc', 'rgba(99,102,241,0.3)'];
    $parts = explode(' ', $lv['name']);
    $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
    $grad = $typeGrad[$lv['leave_type']] ?? 'var(--nx-gradient-1)';
    $days = (strtotime($lv['end_date']) - strtotime($lv['start_date'])) / 86400 + 1;
?>
<div class="nx-leave-card">
    <div class="nx-leave-head">
        <div class="nx-leave-avatar" style="background: <?= $grad ?>;"><?= htmlspecialchars($initials) ?></div>
        <div class="nx-leave-info">
            <strong><?= htmlspecialchars($lv['name']) ?></strong>
            <small>NIP: <?= htmlspecialchars($lv['nip']) ?> • diajukan <?= date('d M Y, H:i', strtotime($lv['created_at'])) ?></small>
        </div>
        <span class="nx-leave-type-pill" style="background: <?= $grad ?>;">
            <?= $typeEmoji[$lv['leave_type']] ?? '📄' ?> <?= $typeLabel[$lv['leave_type']] ?? $lv['leave_type'] ?>
        </span>
    </div>

    <div class="nx-leave-body">
        <div class="col">
            <div class="nx-info-chip">
                <div>
                    <span class="lbl">📅 Tanggal Mulai</span>
                    <span class="val mono"><?= date('d M Y', strtotime($lv['start_date'])) ?></span>
                </div>
            </div>
            <div class="nx-info-chip">
                <div>
                    <span class="lbl">🏁 Tanggal Selesai</span>
                    <span class="val mono"><?= date('d M Y', strtotime($lv['end_date'])) ?></span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="nx-info-chip">
                <div>
                    <span class="lbl">⏱️ Durasi</span>
                    <span class="val"><?= (int)$days ?> hari</span>
                </div>
            </div>
            <div class="nx-info-chip">
                <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; width:100%;">
                    <div>
                        <span class="lbl">📎 Lampiran</span>
                        <span class="val"><?= !empty($lv['attachment']) ? 'Tersedia' : 'Tidak ada' ?></span>
                    </div>
                    <?php if (!empty($lv['attachment'])): ?>
                        <a href="../uploads/<?= htmlspecialchars($lv['attachment']) ?>" target="_blank" class="nx-attachment-btn">📎 Lihat</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="nx-leave-reason"><?= nl2br(htmlspecialchars($lv['reason'])) ?></div>
    </div>

    <div class="nx-leave-foot">
        <span class="nx-status-pill" style="background: <?= $st[2] ?>; color: <?= $st[3] ?>; border-color: <?= $st[4] ?>;">
            <span class="nx-dot"></span> <?= $st[1] ?>
        </span>

        <?php if ($lv['status'] === 'pending'): ?>
            <form method="POST" action="../api/leave_action.php" class="nx-approval-form">
                <input type="hidden" name="leave_id" value="<?= $lv['id'] ?>">
                <input type="text" name="admin_notes" placeholder="✍️ Catatan untuk karyawan (opsional)">
                <button name="action" value="approve" class="btn btn-success btn-sm" onclick="return confirmAction('Setujui pengajuan ini?')">✔ Setujui</button>
                <button name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirmAction('Tolak pengajuan ini?')">✖ Tolak</button>
            </form>
        <?php else: ?>
            <div class="nx-admin-note">
                <strong>📝 Catatan Admin</strong>
                <?= htmlspecialchars($lv['admin_notes'] ?: '—') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>