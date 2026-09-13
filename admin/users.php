<?php
$pageTitle = "Data Karyawan";
require_once '../config/database.php';
require_once '../includes/admin_header.php';

$msg = $_SESSION['admin_users_msg'] ?? '';
unset($_SESSION['admin_users_msg']);

// ===== Proses POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nip      = trim($_POST['nip'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin', 'employee']) ? $_POST['role'] : 'employee';
        $shiftId  = !empty($_POST['shift_id']) ? (int)$_POST['shift_id'] : null;

        if ($nip === '' || $name === '' || $email === '' || strlen($password) < 6) {
            $_SESSION['admin_users_msg'] = 'Data tidak valid! Password minimal 6 karakter.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nip = ?");
            $chk->execute([$email, $nip]);
            if ($chk->fetch()) {
                $_SESSION['admin_users_msg'] = 'Email atau NIP sudah terdaftar!';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nip, name, email, password, role, shift_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$nip, $name, $email, $hash, $role, $shiftId]);
                $_SESSION['admin_users_msg'] = 'Karyawan berhasil ditambahkan!';
            }
        }
    }

    if ($action === 'assign_shift') {
        $uid     = (int)($_POST['user_id'] ?? 0);
        $shiftId = !empty($_POST['shift_id']) ? (int)$_POST['shift_id'] : null;
        $stmt = $pdo->prepare("UPDATE users SET shift_id = ? WHERE id = ?");
        $stmt->execute([$shiftId, $uid]);
        $_SESSION['admin_users_msg'] = 'Shift karyawan diperbarui!';
    }

    if ($action === 'toggle_status') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$uid]);
        $_SESSION['admin_users_msg'] = 'Status akun diperbarui!';
    }

    if ($action === 'reset_password') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $uid]);
        $_SESSION['admin_users_msg'] = 'Password direset menjadi: password123';
    }

    if ($action === 'update_telegram') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $tid = trim($_POST['telegram_id'] ?? '');
        $stmt = $pdo->prepare("UPDATE users SET telegram_id = ? WHERE id = ?");
        $stmt->execute([$tid !== '' ? $tid : null, $uid]);
        $_SESSION['admin_users_msg'] = 'Telegram ID diperbarui!';
    }

    header('Location: users.php');
    exit;
}

$shifts = $pdo->query("SELECT * FROM shifts ORDER BY name")->fetchAll();
$users  = $pdo->query("SELECT u.*, s.name AS shift_name FROM users u LEFT JOIN shifts s ON u.shift_id = s.id ORDER BY u.role DESC, u.name ASC")->fetchAll();

// Ringkasan untuk stat card
$totalAll    = count($users);
$totalActive = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$totalAdmin  = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$totalEmp    = $totalAll - $totalAdmin;
?>

<style>
    .nx-user-grid-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .nx-mini-stat { background: var(--nx-surface); border: 1px solid var(--nx-border); border-radius: var(--nx-radius); padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
    .nx-mini-stat .ico { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .nx-mini-stat .ico.a { background: var(--nx-gradient-2); }
    .nx-mini-stat .ico.b { background: var(--nx-gradient-3); }
    .nx-mini-stat .ico.c { background: var(--nx-gradient-4); }
    .nx-mini-stat .ico.d { background: var(--nx-gradient-5); }
    .nx-mini-stat h4 { margin: 0; font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
    .nx-mini-stat p { margin: 0; font-size: 11px; color: var(--nx-text-muted); text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; }

    .nx-form-card-header { padding: 22px 24px; background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(236,72,153,0.06) 100%); border-bottom: 1px solid var(--nx-border); display: flex; align-items: center; gap: 14px; }
    .nx-form-card-header .nx-ico-big { width: 44px; height: 44px; border-radius: 12px; background: var(--nx-gradient-1); display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 6px 18px rgba(99,102,241,0.35); }
    .nx-form-card-header h2 { margin: 0; font-size: 16px; color: #fff; font-weight: 700; letter-spacing: -0.2px; }
    .nx-form-card-header p { margin: 2px 0 0; font-size: 12px; color: var(--nx-text-dim); }
    .nx-form-card-header::before { display: none; }

    .nx-shift-select { background: var(--nx-bg-2); color: var(--nx-text); border: 1px solid var(--nx-border-strong); border-radius: 6px; padding: 6px 10px; font-size: 12px; font-family: var(--nx-font); cursor: pointer; min-width: 130px; transition: all 0.2s; }
    .nx-shift-select:focus { outline: none; border-color: var(--nx-accent); box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }

    .nx-tg-input { background: var(--nx-bg-2); color: var(--nx-cyan); border: 1px solid var(--nx-border-strong); border-radius: 6px; padding: 6px 10px; font-size: 11px; font-family: var(--nx-mono); width: 100px; transition: all 0.2s; }
    .nx-tg-input:focus { outline: none; border-color: var(--nx-cyan); box-shadow: 0 0 0 3px rgba(6,182,212,0.15); }
    .nx-tg-input::placeholder { color: var(--nx-text-muted); }

    .nx-action-bar { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .nx-action-bar form { display: inline-block; }
</style>

<?php if ($msg): ?>
    <div class="alert success">✨ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ===== MINI STATS ===== -->
<div class="nx-user-grid-summary">
    <div class="nx-mini-stat">
        <div class="ico a">👥</div>
        <div><h4><?= $totalAll ?></h4><p>Total Akun</p></div>
    </div>
    <div class="nx-mini-stat">
        <div class="ico b">✅</div>
        <div><h4><?= $totalActive ?></h4><p>Aktif</p></div>
    </div>
    <div class="nx-mini-stat">
        <div class="ico c">🛡️</div>
        <div><h4><?= $totalAdmin ?></h4><p>Admin</p></div>
    </div>
    <div class="nx-mini-stat">
        <div class="ico d">💼</div>
        <div><h4><?= $totalEmp ?></h4><p>Karyawan</p></div>
    </div>
</div>

<!-- ===== FORM TAMBAH KARYAWAN ===== -->
<div class="panel" style="margin-bottom: 28px;">
    <div class="nx-form-card-header">
        <div class="nx-ico-big">➕</div>
        <div>
            <h2>Tambah Karyawan / Admin Baru</h2>
            <p>Registrasi akun baru untuk tim Anda</p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label>NIP (Nomor Induk Pegawai)</label>
                    <input type="text" name="nip" placeholder="Contoh: EMP003" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" placeholder="Nama lengkap" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@kantor.com" required>
                </div>
                <div class="form-group">
                    <label>Password (min. 6 karakter)</label>
                    <input type="text" name="password" placeholder="Password awal" required minlength="6">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Role Akses</label>
                    <select name="role">
                        <option value="employee">💼 Karyawan</option>
                        <option value="admin">🛡️ Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Shift Kerja</label>
                    <select name="shift_id">
                        <option value="">-- Tanpa Shift --</option>
                        <?php foreach ($shifts as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 8px;">
                <button type="submit" class="btn btn-primary">✨ Simpan Akun Baru</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== DAFTAR PENGGUNA ===== -->
<div class="panel">
    <div class="panel-header">
        <h2>Daftar Pengguna</h2>
        <span class="badge info"><?= count($users) ?> akun terdaftar</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th>Telegram</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="empty-row"><span class="nx-empty-icon">👥</span><div><strong>Belum ada pengguna</strong></div><small style="color:var(--nx-text-muted);">Tambahkan karyawan pertama Anda melalui form di atas.</small></td></tr>
                <?php else: foreach ($users as $u):
                    $parts = explode(' ', $u['name']);
                    $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
                ?>
                <tr>
                    <td>
                        <div class="nx-user-cell">
                            <div class="nx-avatar"><?= htmlspecialchars($initials) ?></div>
                            <div class="nx-user-cell-info">
                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                <small><?= htmlspecialchars($u['nip']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="nx-mono" style="color: var(--nx-text-dim); font-size: 12px;"><?= htmlspecialchars($u['email']) ?></span></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge info">🛡️ Admin</span>
                        <?php else: ?>
                            <span class="badge success">💼 Karyawan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="assign_shift">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="shift_id" onchange="this.form.submit()" class="nx-shift-select">
                                <option value="">— Tanpa Shift —</option>
                                <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $u['shift_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <span class="badge success"><span class="nx-dot"></span>Aktif</span>
                        <?php else: ?>
                            <span class="badge danger"><span class="nx-dot"></span>Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:flex; gap:4px; align-items:center;">
                            <input type="hidden" name="action" value="update_telegram">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="text" name="telegram_id" value="<?= htmlspecialchars($u['telegram_id'] ?? '') ?>" placeholder="chat_id" class="nx-tg-input">
                            <button class="btn btn-light btn-sm" title="Simpan">💾</button>
                        </form>
                    </td>
                    <td>
                        <div class="nx-action-bar">
                            <form method="POST">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button class="btn btn-primary btn-sm" onclick="return confirmAction('Reset password menjadi password123?')" title="Reset password">🔑</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button class="btn <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-success' ?> btn-sm" onclick="return confirmAction('Ubah status akun ini?')" title="<?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                    <?= $u['status'] === 'active' ? '⛔' : '✅' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>