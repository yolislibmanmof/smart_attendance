<?php
// api/absen_action.php — FINAL V2 (Rotating QR + Offline Sync + Telegram Late)
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$rawInput = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $_POST['action'] ?? ($rawInput['action'] ?? '');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if (!isset($_SESSION['user_id'])) respond(false, 'Sesi berakhir. Silakan login ulang.');
$userId = $_SESSION['user_id'];

function distanceMeters($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip === '::1') $ip = '127.0.0.1';
    return $ip;
}

function verifyPresence($location, $lat, $lon, $qrToken, &$distance) {
    $withinGeofence = false;
    if ($lat !== null && $lon !== null) {
        $distance = distanceMeters($lat, $lon, (float)$location['latitude'], (float)$location['longitude']);
        $withinGeofence = $distance <= (float)$location['radius_meter'];
    }
    $qrValid      = verifyRotatingQr($location, $qrToken);
    $wifiVerified = !empty($location['office_ip']) && getClientIp() === $location['office_ip'];
    return ['allowed' => ($withinGeofence || $qrValid || $wifiVerified), 'geofence' => $withinGeofence, 'qr' => $qrValid, 'wifi' => $wifiVerified];
}

function getClockTime() {
    if ((int)($_POST['offline'] ?? 0) === 1 && !empty($_POST['client_time'])) {
        try { return new DateTime($_POST['client_time']); } catch (Exception $e) {}
    }
    return new DateTime();
}

$location = $pdo->query("SELECT * FROM locations WHERE is_active = TRUE LIMIT 1")->fetch();

// ========== CEK LOKASI ==========
if ($action === 'check_location') {
    if (!$location) respond(false, 'Belum ada lokasi aktif.');
    $lat = $rawInput['latitude'] ?? null;
    $lon = $rawInput['longitude'] ?? null;
    if ($lat === null || $lon === null) respond(false, 'Koordinat tidak valid.');
    $distance = distanceMeters($lat, $lon, (float)$location['latitude'], (float)$location['longitude']);
    respond(true, 'OK', ['valid' => $distance <= (float)$location['radius_meter'], 'distance' => round($distance), 'radius' => (int)$location['radius_meter']]);
}

// ========== ABSEN MASUK ==========
if ($action === 'absen_masuk') {
    if (!$location) respond(false, 'Belum ada lokasi aktif. Hubungi admin.');

    $lat      = isset($_POST['latitude'])  && $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lon      = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    $accuracy = isset($_POST['accuracy'])  && $_POST['accuracy']  !== '' ? (float)$_POST['accuracy']  : null;
    $isMock   = (int)($_POST['is_mock'] ?? 0) === 1;
    $qrToken  = trim($_POST['qr_token'] ?? '');
    $offline  = (int)($_POST['offline'] ?? 0) === 1;

    if ($isMock || ($accuracy !== null && $accuracy == 0)) {
        respond(false, '🚫 Indikasi Fake GPS / Mock Location terdeteksi! Absen ditolak.');
    }

    $distance = null;
    $check = verifyPresence($location, $lat, $lon, $qrToken, $distance);
    if (!$check['allowed']) {
        respond(false, 'Absen ditolak: di luar area, QR tidak valid/kedaluwarsa, dan tidak di Wi-Fi kantor.');
    }

    $clock = getClockTime();
    $today = $clock->format('Y-m-d');

    $stmt = $pdo->prepare("SELECT id FROM leaves WHERE user_id = ? AND status = 'approved' AND ? BETWEEN start_date AND end_date");
    $stmt->execute([$userId, $today]);
    if ($stmt->fetch()) respond(false, 'Anda sedang cuti/izin yang disetujui hari ini.');

    $stmt = $pdo->prepare("SELECT id, clock_in_time FROM attendances WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $today]);
    $existing = $stmt->fetch();
    if ($existing && $existing['clock_in_time']) respond(false, 'Anda sudah absen masuk hari ini.');

    $stmt = $pdo->prepare("SELECT s.start_time, s.late_tolerance_minutes FROM users u LEFT JOIN shifts s ON u.shift_id = s.id WHERE u.id = ?");
    $stmt->execute([$userId]);
    $shift = $stmt->fetch();
    $limit = new DateTime($shift['start_time'] ?? '08:00:00');
    $limit->modify('+' . (int)($shift['late_tolerance_minutes'] ?? 15) . ' minutes');
    $status = ($clock > $limit) ? 'late' : 'present';

    $methods = [];
    if ($check['geofence']) $methods[] = 'GPS';
    if ($check['qr'])       $methods[] = 'QR';
    if ($check['wifi'])     $methods[] = 'Wi-Fi';
    if ($offline)           $methods[] = 'Offline Sync';
    $notes = 'Verifikasi: ' . implode(', ', $methods);

    $ip      = getClientIp();
    $timeSql = $clock->format('H:i:s');

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE attendances SET clock_in_time = ?, clock_in_lat = ?, clock_in_long = ?, clock_in_ip = ?, clock_in_location_id = ?, is_qr_verified = ?, scanned_qr_token = ?, status = ?, notes = ? WHERE id = ?");
        $stmt->execute([$timeSql, $lat, $lon, $ip, $location['id'], $check['qr'] ? 1 : 0, $qrToken ?: null, $status, $notes, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendances (user_id, date, clock_in_time, clock_in_lat, clock_in_long, clock_in_ip, clock_in_location_id, is_qr_verified, scanned_qr_token, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $today, $timeSql, $lat, $lon, $ip, $location['id'], $check['qr'] ? 1 : 0, $qrToken ?: null, $status, $notes]);
    }

    // [V2] Notifikasi Telegram jika terlambat
    if ($status === 'late') {
        tgSend("⚠️ <b>" . htmlspecialchars($_SESSION['user_name']) . "</b> terlambat absen masuk (" . $timeSql . ").");
    }

    respond(true, '✅ Absen masuk berhasil! Status: ' . ($status === 'present' ? 'Hadir' : 'Terlambat') . ($offline ? ' (sync offline)' : ''), ['status' => $status, 'time' => $timeSql]);
}

// ========== ABSEN PULANG ==========
if ($action === 'absen_pulang') {
    if (!$location) respond(false, 'Belum ada lokasi aktif. Hubungi admin.');

    $lat     = isset($_POST['latitude'])  && $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lon     = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    $isMock  = (int)($_POST['is_mock'] ?? 0) === 1;
    $qrToken = trim($_POST['qr_token'] ?? '');
    $offline = (int)($_POST['offline'] ?? 0) === 1;

    if ($isMock) respond(false, '🚫 Indikasi Fake GPS terdeteksi! Absen ditolak.');

    $clock = getClockTime();
    $today = $clock->format('Y-m-d');

    $stmt = $pdo->prepare("SELECT * FROM attendances WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $today]);
    $row = $stmt->fetch();
    if (!$row || !$row['clock_in_time']) respond(false, 'Anda belum absen masuk hari ini.');
    if ($row['clock_out_time']) respond(false, 'Anda sudah absen pulang hari ini.');

    $distance = null;
    $check = verifyPresence($location, $lat, $lon, $qrToken, $distance);
    if (!$check['allowed']) respond(false, 'Absen pulang ditolak: di luar area, QR tidak valid, dan tidak di Wi-Fi kantor.');

    $ip      = getClientIp();
    $timeSql = $clock->format('H:i:s');
    $stmt = $pdo->prepare("UPDATE attendances SET clock_out_time = ?, clock_out_lat = ?, clock_out_long = ?, clock_out_ip = ?, clock_out_location_id = ? WHERE id = ?");
    $stmt->execute([$timeSql, $lat, $lon, $ip, $location['id'], $row['id']]);

    respond(true, '✅ Absen pulang berhasil! Hati-hati di jalan.' . ($offline ? ' (sync offline)' : ''), ['time' => $timeSql]);
}

respond(false, 'Aksi tidak dikenali.');
?>