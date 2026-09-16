<?php
// api/face_action.php — V3 Face Enrollment
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if (!isset($_SESSION['user_id'])) respond(false, 'Sesi berakhir. Silakan login ulang.');
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ========== STATUS ==========
if ($action === 'status') {
    $st = $pdo->prepare("SELECT face_descriptor IS NOT NULL AS enrolled, face_enrolled_at FROM users WHERE id = ?");
    $st->execute([$userId]);
    $row = $st->fetch();
    respond(true, 'OK', ['enrolled' => (bool)$row['enrolled'], 'enrolled_at' => $row['face_enrolled_at']]);
}

// ========== ENROLL (daftarkan wajah) ==========
if ($action === 'enroll') {
    $desc = json_decode($_POST['descriptor'] ?? '', true);
    if (!is_array($desc) || count($desc) !== 128) {
        respond(false, 'Descriptor wajah tidak valid. Coba ulang dengan pencahayaan baik.');
    }
    // Validasi angka
    foreach ($desc as $v) if (!is_numeric($v)) respond(false, 'Descriptor korup.');

    $snap = saveFaceSnapshot($userId, $_POST['snapshot'] ?? '');
    $st = $pdo->prepare("UPDATE users SET face_descriptor = ?, face_enrolled_at = NOW() WHERE id = ?");
    $st->execute([json_encode($desc), $userId]);

    respond(true, 'Wajah berhasil didaftarkan! Absen masuk kini dilindungi Face ID.', ['snapshot' => $snap]);
}

// ========== DELETE (hapus data wajah) ==========
if ($action === 'delete') {
    $st = $pdo->prepare("UPDATE users SET face_descriptor = NULL, face_enrolled_at = NULL WHERE id = ?");
    $st->execute([$userId]);
    respond(true, 'Data wajah dihapus. Absen wajah nonaktif.');
}

respond(false, 'Aksi tidak dikenali.');