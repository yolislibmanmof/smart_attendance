<?php
// admin/locations.php
$pageTitle = "Lokasi & Wi-Fi";
require_once '../config/database.php';
require_once '../includes/admin_header.php';

$msg = $_SESSION['admin_loc_msg'] ?? '';
unset($_SESSION['admin_loc_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $name     = trim($_POST['name'] ?? '');
        $lat      = (float)($_POST['latitude'] ?? 0);
        $lon      = (float)($_POST['longitude'] ?? 0);
        $radius   = (int)($_POST['radius_meter'] ?? 50);
        $ssid     = trim($_POST['wifi_ssid'] ?? '');
        $bssid    = trim($_POST['wifi_bssid'] ?? '');
        $officeIp = trim($_POST['office_ip'] ?? '');
        $active   = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $lat == 0 || $lon == 0) {
            $_SESSION['admin_loc_msg'] = 'Nama dan koordinat wajib diisi!';
        } elseif ($action === 'add') {
            $token = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $pdo->prepare("INSERT INTO locations (name, latitude, longitude, radius_meter, wifi_ssid, wifi_bssid, office_ip, qr_token, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $lat, $lon, $radius, $ssid, $bssid, $officeIp, $token, $active]);
            $_SESSION['admin_loc_msg'] = 'Lokasi ditambahkan dengan token QR: ' . $token;
        } else {
            $id = (int)($_POST['location_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE locations SET name = ?, latitude = ?, longitude = ?, radius_meter = ?, wifi_ssid = ?, wifi_bssid = ?, office_ip = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $lat, $lon, $radius, $ssid, $bssid, $officeIp, $active, $id]);
            $_SESSION['admin_loc_msg'] = 'Lokasi berhasil diperbarui!';
        }
    }

    if ($action === 'regen_qr') {
        $id = (int)($_POST['location_id'] ?? 0);
        $token = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
        $pdo->prepare("UPDATE locations SET qr_token = ? WHERE id = ?")->execute([$token, $id]);
        $_SESSION['admin_loc_msg'] = 'Token dasar diputar: ' . $token . '. Layar kiosk menyesuaikan otomatis.';
    }

    header('Location: locations.php');
    exit;
}

$editId = (int)($_GET['edit'] ?? 0);
$editLoc = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$editId]);
    $editLoc = $stmt->fetch();
}

$locations = $pdo->query("SELECT * FROM locations ORDER BY id")->fetchAll();
?>

<style>
    .nx-loc-card {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius-lg);
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.3s;
    }
    .nx-loc-card:hover { border-color: var(--nx-border-strong); box-shadow: var(--nx-shadow-lg); }

    .nx-loc-header {
        padding: 18px 22px;
        background: linear-gradient(135deg, rgba(6,182,212,0.08) 0%, rgba(99,102,241,0.06) 100%);
        border-bottom: 1px solid var(--nx-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .nx-loc-title { display: flex; align-items: center; gap: 12px; }
    .nx-loc-pin {
        width: 42px; height: 42px;
        border-radius: 12px;
        background: var(--nx-gradient-2);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        box-shadow: 0 6px 16px rgba(6,182,212,0.35);
        flex-shrink: 0;
    }
    .nx-loc-title h3 { margin: 0; font-size: 16px; color: #fff; font-weight: 700; letter-spacing: -0.2px; }
    .nx-loc-title p { margin: 2px 0 0; font-size: 11px; color: var(--nx-text-muted); font-family: var(--nx-mono); }

    .nx-loc-body { padding: 22px; display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: center; }
    @media (max-width: 720px) { .nx-loc-body { grid-template-columns: 1fr; } }

    .nx-loc-info { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 520px) { .nx-loc-info { grid-template-columns: 1fr; } }
    .nx-loc-info-item { background: var(--nx-bg-2); border: 1px solid var(--nx-border); border-radius: 10px; padding: 12px 14px; }
    .nx-loc-info-item .label { font-size: 10.5px; color: var(--nx-text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
    .nx-loc-info-item .val { font-size: 13px; color: #fff; font-weight: 600; word-break: break-all; }
    .nx-loc-info-item .val.mono { font-family: var(--nx-mono); color: var(--nx-cyan); font-size: 12px; }

    .nx-loc-qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        min-width: 200px;
    }
    .nx-qr-frame {
        position: relative;
        padding: 14px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .nx-qr-frame img { display: block; width: 160px; height: 160px; }
    .nx-qr-badge {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: var(--nx-gradient-1);
        color: #fff;
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(99,102,241,0.5);
    }
    .nx-qr-caption {
        text-align: center;
        font-size: 11px;
        color: var(--nx-text-dim);
        line-height: 1.4;
    }
    .nx-qr-caption strong { color: var(--nx-emerald); }
    .nx-loc-qr-actions { display: flex; gap: 8px; width: 100%; }
    .nx-loc-qr-actions .btn { flex: 1; justify-content: center; }

    .nx-live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        background: rgba(16,185,129,0.15);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 50px;
        font-size: 10px;
        font-weight: 700;
        color: var(--nx-emerald);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .nx-live-indicator::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--nx-emerald);
        box-shadow: 0 0 6px var(--nx-emerald);
        animation: nxPulse 1.5s infinite;
    }
    .nx-live-indicator.offline { background: rgba(244,63,94,0.15); border-color: rgba(244,63,94,0.3); color: var(--nx-rose); }
    .nx-live-indicator.offline::before { background: var(--nx-rose); box-shadow: 0 0 6px var(--nx-rose); }
</style>

<?php if ($msg): ?>
    <div class="alert success">✨ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ===== FORM TAMBAH LOKASI ===== -->
<div class="panel" style="margin-bottom: 28px;">
    <div class="nx-form-card-header">
        <div class="nx-ico-big">📍</div>
        <div>
            <h2><?= $editLoc ? 'Edit Lokasi Kantor' : 'Tambah Lokasi Kantor Baru' ?></h2>
            <p>Atur koordinat GPS, Wi-Fi fingerprint, dan IP jaringan untuk verifikasi absensi</p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editLoc ? 'update' : 'add' ?>">
            <?php if ($editLoc): ?><input type="hidden" name="location_id" value="<?= $editLoc['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editLoc['name'] ?? '') ?>" placeholder="Contoh: Kantor Pusat" required>
                </div>
                <div class="form-group">
                    <label>Radius Geofencing (meter)</label>
                    <input type="number" name="radius_meter" min="10" value="<?= $editLoc['radius_meter'] ?? 50 ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="text" name="latitude" value="<?= htmlspecialchars($editLoc['latitude'] ?? '') ?>" placeholder="Contoh: -6.20000000" required>
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" name="longitude" value="<?= htmlspecialchars($editLoc['longitude'] ?? '') ?>" placeholder="Contoh: 106.81666600" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Wi-Fi SSID (informasi)</label>
                    <input type="text" name="wifi_ssid" value="<?= htmlspecialchars($editLoc['wifi_ssid'] ?? '') ?>" placeholder="Nama Wi-Fi kantor">
                </div>
                <div class="form-group">
                    <label>Wi-Fi BSSID / MAC (opsional)</label>
                    <input type="text" name="wifi_bssid" value="<?= htmlspecialchars($editLoc['wifi_bssid'] ?? '') ?>" placeholder="00:1A:2B:3C:4D:5E">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>IP Jaringan Kantor (verifikasi Wi-Fi)</label>
                    <input type="text" name="office_ip" value="<?= htmlspecialchars($editLoc['office_ip'] ?? '') ?>" placeholder="Contoh: 127.0.0.1 / IP publik kantor">
                </div>
                <div class="form-group">
                    <label>Status Lokasi</label>
                    <label style="display:flex; align-items:center; gap:10px; padding: 10px 14px; background: var(--nx-bg-2); border: 1px solid var(--nx-border-strong); border-radius: 8px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="is_active" value="1" style="width:auto; accent-color: var(--nx-accent);" <?= ($editLoc === null || $editLoc['is_active']) ? 'checked' : '' ?>>
                        <span style="font-weight: 600; font-size: 13px; color: var(--nx-text);">Aktif untuk absensi</span>
                    </label>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 8px;">
                <button type="submit" class="btn btn-primary">✨ <?= $editLoc ? 'Perbarui Lokasi' : 'Simpan Lokasi' ?></button>
                <?php if ($editLoc): ?><a href="locations.php" class="btn btn-light">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ===== DAFTAR LOKASI ===== -->
<div class="panel">
    <div class="panel-header">
        <h2>Lokasi Kantor Terdaftar</h2>
        <span class="badge info"><?= count($locations) ?> lokasi</span>
    </div>
    <div style="padding: 22px;">
        <?php if (empty($locations)): ?>
            <div class="empty-row">
                <span class="nx-empty-icon">📍</span>
                <div><strong>Belum ada lokasi kantor</strong></div>
                <small style="color:var(--nx-text-muted);">Tambahkan lokasi pertama agar karyawan bisa absen via GPS/Wi-Fi/QR.</small>
            </div>
        <?php else: foreach ($locations as $loc): ?>
        <div class="nx-loc-card">
            <div class="nx-loc-header">
                <div class="nx-loc-title">
                    <div class="nx-loc-pin">📍</div>
                    <div>
                        <h3><?= htmlspecialchars($loc['name']) ?></h3>
                        <p>ID: #<?= $loc['id'] ?></p>
                    </div>
                </div>
                <?php if ($loc['is_active']): ?>
                    <span class="nx-live-indicator">● AKTIF</span>
                <?php else: ?>
                    <span class="nx-live-indicator offline">● NONAKTIF</span>
                <?php endif; ?>
                <a href="locations.php?edit=<?= $loc['id'] ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
            </div>

            <div class="nx-loc-body">
                <div class="nx-loc-info">
                    <div class="nx-loc-info-item">
                        <div class="label">🧭 Koordinat</div>
                        <div class="val mono"><?= $loc['latitude'] ?>, <?= $loc['longitude'] ?></div>
                    </div>
                    <div class="nx-loc-info-item">
                        <div class="label">🎯 Radius</div>
                        <div class="val"><?= $loc['radius_meter'] ?> meter</div>
                    </div>
                    <div class="nx-loc-info-item">
                        <div class="label">📶 Wi-Fi SSID</div>
                        <div class="val"><?= htmlspecialchars($loc['wifi_ssid'] ?: '—') ?></div>
                    </div>
                    <div class="nx-loc-info-item">
                        <div class="label">🔌 Wi-Fi BSSID</div>
                        <div class="val mono"><?= htmlspecialchars($loc['wifi_bssid'] ?: '—') ?></div>
                    </div>
                    <div class="nx-loc-info-item">
                        <div class="label">🌐 IP Kantor</div>
                        <div class="val mono"><?= htmlspecialchars($loc['office_ip'] ?: '—') ?></div>
                    </div>
                    <div class="nx-loc-info-item">
                        <div class="label">🔐 Token Dasar</div>
                        <div class="val mono" style="font-size: 11px;"><?= htmlspecialchars($loc['qr_token']) ?></div>
                    </div>
                </div>

                <div class="nx-loc-qr">
                    <div class="nx-qr-frame">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode($loc['qr_token']) ?>" alt="QR">
                        <div class="nx-qr-badge">⏰</div>
                    </div>
                    <div class="nx-qr-caption">
                        QR kini <strong>berputar tiap 60 detik</strong><br>
                        <small style="color:var(--nx-text-muted);">(QR statis ini hanya untuk referensi)</small>
                    </div>
                    <div class="nx-loc-qr-actions">
                        <a href="../qr_display.php" target="_blank" class="btn btn-success btn-sm">🖥️ Kiosk</a>
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="action" value="regen_qr">
                            <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                            <button class="btn btn-danger btn-sm" style="width: 100%;" onclick="return confirmAction('Putar token dasar? QR lama tidak berlaku.')">🔄 Putar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>