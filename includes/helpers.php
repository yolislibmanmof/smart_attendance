<?php
// includes/helpers.php - fungsi bersama V2

function getSetting($key) {
    global $pdo;
    $st = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return ($v === false || $v === null || $v === '') ? null : $v;
}

function setSetting($key, $value) {
    global $pdo;
    $st = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $st->execute([$key, $value, $value]);
}

// ===== QR BERPUTAR (model TOTP, jendela 60 detik) =====
function qrCodeForWindow($baseToken, $secret, $window) {
    return strtoupper(substr(hash_hmac('sha256', $baseToken . '|' . $window, $secret), 0, 10));
}

function currentQrCode($baseToken, $secret, $offset = 0) {
    return qrCodeForWindow($baseToken, $secret, intdiv(time(), 60) + $offset);
}

function verifyRotatingQr($location, $scanned) {
    $secret = getSetting('qr_secret');
    if (!$secret || empty($scanned)) return false;
    // Terima jendela sekarang + 1 menit sebelumnya (grace period)
    foreach ([0, -1] as $offset) {
        $expected = currentQrCode($location['qr_token'], $secret, $offset);
        if (hash_equals($expected, strtoupper(trim($scanned)))) return true;
    }
    return false;
}

// ===== Telegram =====
function tgSend($text, $chatId = null) {
    $token = getSetting('telegram_bot_token');
    $chat  = $chatId ?? getSetting('telegram_chat_id');
    if (!$token || !$chat) return false;
    $url = "https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query(['chat_id' => $chat, 'text' => $text, 'parse_mode' => 'HTML']);
    @file_get_contents($url);
    return true;
}
?>