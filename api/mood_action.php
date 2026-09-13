<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']); exit;
}
$mood = (int)($_POST['mood'] ?? 0);
if ($mood < 1 || $mood > 5) { echo json_encode(['success' => false, 'message' => 'Mood tidak valid.']); exit; }

$stmt = $pdo->prepare("UPDATE attendances SET mood = ? WHERE user_id = ? AND date = ? AND clock_in_time IS NOT NULL");
$stmt->execute([$mood, $_SESSION['user_id'], date('Y-m-d')]);
$ok = $stmt->rowCount() > 0;
echo json_encode(['success' => $ok, 'message' => $ok ? 'Tersimpan' : 'Tidak ada record absen hari ini.']);