<?php
// api/qr_action.php
require_once '../config/database.php';
require_once '../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$loc = $pdo->query("SELECT * FROM locations WHERE is_active = TRUE LIMIT 1")->fetch();
if (!$loc) { echo json_encode(['success' => false]); exit; }

$secret = getSetting('qr_secret');
echo json_encode([
    'success'      => true,
    'code'         => currentQrCode($loc['qr_token'], $secret),
    'seconds_left' => 60 - (time() % 60),
]);