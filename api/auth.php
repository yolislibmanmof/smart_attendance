<?php
// api/auth.php
session_start();
require_once '../config/database.php';

$action = $_GET['action'] ?? '';

// ================= LOGIN =================
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email dan Password wajib diisi!";
        header('Location: ../public/index.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login Sukses
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_nip'] = $user['nip'];

        // Redirect berdasarkan role
        if ($user['role'] === 'admin') {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../public/dashboard.php');
        }
        exit;
    } else {
        $_SESSION['login_error'] = "Email tidak ditemukan atau Password salah!";
        header('Location: ../public/index.php');
        exit;
    }
}

// ================= REGISTER =================
elseif ($action === 'register') {
    $nip = trim($_POST['nip'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nip) || empty($name) || empty($email) || empty($password)) {
        die("Semua field wajib diisi! <a href='../public/register.php'>Kembali</a>");
    }

    // Cek apakah email atau NIP sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nip = ?");
    $stmt->execute([$email, $nip]);
    
    if ($stmt->fetch()) {
        die("Email atau NIP sudah terdaftar! <a href='../public/register.php'>Kembali</a>");
    }

    // Hash password dan insert ke database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (nip, name, email, password, role, status) VALUES (?, ?, ?, ?, 'employee', 'active')");
    $stmt->execute([$nip, $name, $email, $hashedPassword]);

    $_SESSION['register_success'] = "Registrasi berhasil! Silakan login.";
    header('Location: ../public/register.php');
    exit;
}

// ================= LOGOUT =================
elseif ($action === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ../public/index.php');
    exit;
}

// Jika action tidak dikenali
else {
    header('Location: ../public/index.php');
    exit;
}
?>