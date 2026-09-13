<?php
// config/database.php
$host = 'localhost';
$db   = 'smart_attendance_db';
$user = 'root'; // Sesuaikan dengan username database kamu (default XAMPP: root)
$pass = '';     // Sesuaikan dengan password database kamu (default XAMPP: kosong)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jangan tampilkan error detail di production, tapi untuk development ini membantu
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>