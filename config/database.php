<?php
// config/database.php
// Jangan simpan username/password langsung di repo.
// Gunakan environment variable, atau file .env yang tidak ikut commit.

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'smart_attendance_db';
$dbUser = getenv('DB_USER') ?: 'smart_attendance_user';
$dbPass = getenv('DB_PASS') ?: 'ubah_password_anda';
$charset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Jangan tampilkan detail database di production
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Server sedang mengalami gangguan. Silakan coba lagi nanti.');
}
