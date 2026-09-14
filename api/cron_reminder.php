<?php
// api/cron_reminder.php — Dipanggil via cron job untuk reminder absen
require_once '../config/database.php';
require_once '../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$key = $_GET['key'] ?? '';
$cronKey = getSetting('cron_key') ?: 'CRON-RAHASIA';

if (!hash_equals($cronKey, $key)) {
    echo json_encode(['success' => false, 'message' => 'Key tidak valid']);
    exit;
}

$now = new DateTime();
$today = $now->format('Y-m-d');

$users = $pdo->query("SELECT u.id, u.name, u.telegram_id, s.start_time, s.late_tolerance_minutes
    FROM users u 
    LEFT JOIN shifts s ON u.shift_id = s.id
    WHERE u.role = 'employee' AND u.status = 'active'")->fetchAll();

$sent = 0;
foreach ($users as $u) {
    // Cek apakah sudah absen hari ini
    $st = $pdo->prepare("SELECT id FROM attendances WHERE user_id = ? AND date = ? AND clock_in_time IS NOT NULL");
    $st->execute([$u['id'], $today]);
    if ($st->fetch()) continue;

    // Cek apakah sedang cuti
    $st = $pdo->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND ? BETWEEN start_date AND end_date");
    $st->execute([$u['id'], $today]);
    if ($st->fetch()) continue;

    // Cek apakah sudah lewat jam masuk + toleransi
    $limit = new DateTime($u['start_time'] ?? '08:00:00');
    $limit->modify('+' . (int)($u['late_tolerance_minutes'] ?? 15) . ' minutes');
    if ($now <= $limit) continue;

    // Kirim reminder jika punya telegram_id
    if ($u['telegram_id']) {
        tgSend("⏰ <b>Reminder Absen</b>\n\nHai " . htmlspecialchars($u['name']) . ",\nAnda belum absen masuk hari ini!\n\nSegera lakukan absensi sebelum terlambat.", $u['telegram_id']);
        $sent++;
    }
}

echo json_encode([
    'success' => true,
    'sent' => $sent,
    'message' => "Reminder terkirim ke $sent karyawan"
]);