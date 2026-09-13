<?php
$pageTitle = "Pengaturan";
require_once '../config/database.php';
require_once '../includes/helpers.php';
require_once '../includes/admin_header.php';

$msg = $_SESSION['admin_set_msg'] ?? '';
unset($_SESSION['admin_set_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    setSetting('telegram_bot_token', trim($_POST['telegram_bot_token'] ?? ''));
    setSetting('telegram_chat_id', trim($_POST['telegram_chat_id'] ?? ''));
    setSetting('cron_key', trim($_POST['cron_key'] ?? '') ?: 'CRON-RAHASIA');
    $_SESSION['admin_set_msg'] = 'Pengaturan tersimpan!';
    if (!empty($_POST['send_test'])) {
        $ok = tgSend("✅ Tes koneksi bot Smart Attendance berhasil!");
        $_SESSION['admin_set_msg'] = $ok ? 'Tersimpan & pesan tes TERKIRIM!' : 'Tersimpan, tapi tes GAGAL. Periksa token & chat ID.';
    }
    header('Location: settings.php');
    exit;
}
$cronKey = getSetting('cron_key') ?: 'CRON-RAHASIA';
$botToken = getSetting('telegram_bot_token') ?? '';
$chatId = getSetting('telegram_chat_id') ?? '';
$isConfigured = !empty($botToken) && !empty($chatId);
?>

<style>
    .nx-settings-grid { display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .nx-settings-grid { grid-template-columns: 1fr; } }

    .nx-settings-main {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius-lg);
        overflow: hidden;
    }
    .nx-settings-header {
        padding: 22px 26px;
        background: linear-gradient(135deg, rgba(6,182,212,0.08) 0%, rgba(99,102,241,0.06) 100%);
        border-bottom: 1px solid var(--nx-border);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .nx-settings-ico {
        width: 48px; height: 48px;
        border-radius: 14px;
        background: var(--nx-gradient-2);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
        box-shadow: 0 8px 20px rgba(6,182,212,0.35);
        flex-shrink: 0;
    }
    .nx-settings-header h2 { margin: 0; font-size: 17px; color: #fff; font-weight: 700; letter-spacing: -0.2px; }
    .nx-settings-header p { margin: 3px 0 0; font-size: 12.5px; color: var(--nx-text-dim); }

    .nx-settings-body { padding: 26px; }

    .nx-steps {
        background: var(--nx-bg-2);
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 22px;
    }
    .nx-steps-title { font-size: 12px; font-weight: 700; color: var(--nx-text-dim); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px; }
    .nx-step { display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; font-size: 13px; color: var(--nx-text); }
    .nx-step-num {
        width: 24px; height: 24px;
        border-radius: 50%;
        background: var(--nx-gradient-1);
        display: flex; align-items: center; justify-content: center;
        font-size: 11px;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }
    .nx-step code {
        background: var(--nx-surface-2);
        color: var(--nx-cyan);
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 11.5px;
        font-family: var(--nx-mono);
    }

    .nx-form-section { margin-bottom: 20px; }
    .nx-form-section-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--nx-text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--nx-border);
    }

    .nx-save-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }
    .nx-checkbox-fancy {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        background: var(--nx-bg-2);
        border: 1px solid var(--nx-border-strong);
        border-radius: 8px;
        cursor: pointer;
        font-size: 12.5px;
        color: var(--nx-text);
        font-weight: 600;
        transition: all 0.2s;
    }
    .nx-checkbox-fancy:hover { background: var(--nx-surface-2); }
    .nx-checkbox-fancy input { accent-color: var(--nx-accent); }

    /* ===== SIDEBAR INFO ===== */
    .nx-info-sidebar { display: flex; flex-direction: column; gap: 16px; }

    .nx-info-card {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius);
        padding: 20px;
    }
    .nx-info-card h3 {
        margin: 0 0 14px;
        font-size: 13px;
        color: #fff;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .nx-info-card h3::before {
        content: '';
        width: 4px; height: 16px;
        background: var(--nx-gradient-1);
        border-radius: 2px;
    }

    .nx-status-big {
        padding: 14px 16px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .nx-status-big.ok { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); }
    .nx-status-big.warn { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); }
    .nx-status-big .ico {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .nx-status-big.ok .ico { background: var(--nx-gradient-3); }
    .nx-status-big.warn .ico { background: var(--nx-gradient-4); }
    .nx-status-big .txt strong { display: block; color: #fff; font-size: 13px; font-weight: 700; }
    .nx-status-big .txt small { color: var(--nx-text-dim); font-size: 11.5px; }

    .nx-cron-box {
        background: var(--nx-bg-2);
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 14px 16px;
    }
    .nx-cron-box .lbl {
        font-size: 10.5px;
        color: var(--nx-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .nx-cron-url {
        font-family: var(--nx-mono);
        font-size: 11px;
        color: var(--nx-cyan);
        background: var(--nx-surface-2);
        padding: 8px 12px;
        border-radius: 6px;
        word-break: break-all;
        line-height: 1.4;
        border: 1px solid var(--nx-border-strong);
        margin-bottom: 8px;
    }
    .nx-cron-tip {
        font-size: 11.5px;
        color: var(--nx-text-dim);
        line-height: 1.5;
    }
    .nx-cron-tip code { color: var(--nx-accent); background: transparent; padding: 0; font-size: 11px; }

    .nx-tips-list { list-style: none; padding: 0; margin: 0; }
    .nx-tips-list li {
        padding: 8px 0;
        font-size: 12.5px;
        color: var(--nx-text);
        display: flex;
        gap: 10px;
        align-items: flex-start;
        line-height: 1.5;
    }
    .nx-tips-list li + li { border-top: 1px solid var(--nx-border); }
    .nx-tips-list .emoji { font-size: 14px; flex-shrink: 0; margin-top: 2px; }
</style>

<?php if ($msg): ?>
    <div class="alert success">✨ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="nx-settings-grid">
    <!-- ===== FORM UTAMA ===== -->
    <div class="nx-settings-main">
        <div class="nx-settings-header">
            <div class="nx-settings-ico">💬</div>
            <div>
                <h2>Integrasi Telegram & Sistem</h2>
                <p>Atur bot notifikasi & kunci keamanan cron</p>
            </div>
        </div>
        <div class="nx-settings-body">
            <form method="POST">
                <div class="nx-steps">
                    <div class="nx-steps-title">📋 Cara Mendapatkan Token & Chat ID</div>
                    <div class="nx-step"><div class="nx-step-num">1</div><div>Buka Telegram, cari <code>@BotFather</code>, kirim <code>/newbot</code>, ikuti petunjuk → salin token bot.</div></div>
                    <div class="nx-step"><div class="nx-step-num">2</div><div>Kirim pesan apa saja ke bot baru Anda (untuk mendaftarkan chat).</div></div>
                    <div class="nx-step"><div class="nx-step-num">3</div><div>Buka <code>api.telegram.org/botTOKEN/getUpdates</code> → cari <code>"chat":{"id":</code> → salin angka chat ID.</div></div>
                </div>

                <div class="nx-form-section">
                    <div class="nx-form-section-title">🤖 Kredensial Bot Telegram</div>
                    <div class="form-group">
                        <label>Token Bot</label>
                        <input type="text" name="telegram_bot_token" value="<?= htmlspecialchars($botToken) ?>" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
                    </div>
                    <div class="form-group">
                        <label>Chat ID Admin / Grup</label>
                        <input type="text" name="telegram_chat_id" value="<?= htmlspecialchars($chatId) ?>" placeholder="Contoh: 123456789 atau -1001234567890 (grup)">
                    </div>
                </div>

                <div class="nx-form-section">
                    <div class="nx-form-section-title">🔐 Keamanan Cron Reminder</div>
                    <div class="form-group">
                        <label>Kunci Cron (rahasia)</label>
                        <input type="text" name="cron_key" value="<?= htmlspecialchars($cronKey) ?>" placeholder="CRON-RAHASIA">
                        <small style="color:var(--nx-text-muted); font-size:11.5px; margin-top:6px; display:block;">Digunakan sebagai parameter URL pada cron job untuk mencegah akses tidak sah.</small>
                    </div>
                </div>

                <div class="nx-save-bar">
                    <button type="submit" class="btn btn-primary">💾 Simpan Pengaturan</button>
                    <label class="nx-checkbox-fancy">
                        <input type="checkbox" name="send_test" value="1">
                        Kirim pesan tes saat menyimpan
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== SIDEBAR INFO ===== -->
    <div class="nx-info-sidebar">
        <div class="nx-info-card">
            <h3>Status Integrasi</h3>
            <?php if ($isConfigured): ?>
                <div class="nx-status-big ok">
                    <div class="ico">✅</div>
                    <div class="txt">
                        <strong>Bot Terkonfigurasi</strong>
                        <small>Notifikasi aktif berjalan</small>
                    </div>
                </div>
            <?php else: ?>
                <div class="nx-status-big warn">
                    <div class="ico">⚠️</div>
                    <div class="txt">
                        <strong>Belum Lengkap</strong>
                        <small>Isi token & chat ID untuk mengaktifkan</small>
                    </div>
                </div>
            <?php endif; ?>

            <div style="font-size: 12px; color: var(--nx-text-dim); line-height: 1.6;">
                Notifikasi akan dikirim untuk:<br>
                ✓ Pengajuan cuti baru (ke admin)<br>
                ✓ Approval/Rejection cuti (ke karyawan)<br>
                ✓ Karyawan terlambat absen (ke admin)
            </div>
        </div>

        <div class="nx-info-card">
            <h3>🔁 URL Cron Reminder</h3>
            <div class="nx-cron-box">
                <div class="nx-cron-label">🌐 Panggil URL ini tiap 10 menit</div>
                <div class="nx-cron-url">
                    <?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost/smart_attendance') . '/api/cron_reminder.php?key=' . $cronKey) ?>
                </div>
                <div class="nx-cron-tip">
                    Gunakan <code>Task Scheduler</code> (Windows) atau <code>crontab</code> (Linux).<br>
                    Contoh crontab: <code>*/10 * * * * curl -s URL_DI_ATAS</code>
                </div>
            </div>
        </div>

        <div class="nx-info-card">
            <h3>💡 Tips Keamanan</h3>
            <ul class="nx-tips-list">
                <li><span class="emoji">🔐</span><span>Jangan bagikan <b>cron_key</b> ke pihak luar. Ganti secara berkala.</span></li>
                <li><span class="emoji">🤖</span><span>Simpan token bot di tempat aman. Jika bocor, revoke via <code>@BotFather</code> & generate baru.</span></li>
                <li><span class="emoji">⏱️</span><span>Cron idealnya dijalankan tiap <b>10 menit</b> di jam kerja (07:00–10:00) agar reminder tepat waktu.</span></li>
                <li><span class="emoji">📬</span><span>Untuk grup Telegram, chat ID selalu diawali tanda minus (<code>-100...</code>).</span></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>