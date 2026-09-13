<?php
// api/leave_action.php
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php'; // Tambahan V2

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['user_role'];

// ========== KARYAWAN: AJUKAN CUTI ==========
if ($action === 'submit') {
    if ($role !== 'employee') {
        header('Location: ../admin/dashboard.php');
        exit;
    }

    $leaveType = $_POST['leave_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate   = $_POST['end_date'] ?? '';
    $reason    = trim($_POST['reason'] ?? '');

    if (!in_array($leaveType, ['cuti', 'izin', 'sakit']) || empty($startDate) || empty($endDate) || empty($reason)) {
        $_SESSION['cuti_error'] = 'Semua field wajib diisi dengan benar!';
        header('Location: ../public/cuti.php');
        exit;
    }

    if (strtotime($endDate) < strtotime($startDate)) {
        $_SESSION['cuti_error'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai!';
        header('Location: ../public/cuti.php');
        exit;
    }

    // Lampiran opsional
    $attachmentPath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['cuti_error'] = 'Format lampiran harus JPG/PNG/PDF!';
            header('Location: ../public/cuti.php');
            exit;
        }
        if ($_FILES['attachment']['size'] > 2 * 1024 * 1024) {
            $_SESSION['cuti_error'] = 'Ukuran lampiran maksimal 2MB!';
            header('Location: ../public/cuti.php');
            exit;
        }

        $newName = 'leave_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . '/' . $newName)) {
            $attachmentPath = $newName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, attachment, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $leaveType, $startDate, $endDate, $reason, $attachmentPath]);

    // [V2] Kirim notifikasi Telegram ke Admin
    tgSend("🏖️ <b>Pengajuan cuti baru</b>\nKaryawan: " . htmlspecialchars($_SESSION['user_name']) . "\nJenis: $leaveType\nPeriode: $startDate s/d $endDate");

    $_SESSION['cuti_success'] = 'Pengajuan berhasil dikirim! Menunggu persetujuan admin.';
    header('Location: ../public/cuti.php');
    exit;
}

// ========== ADMIN: SETUJU / TOLAK ==========
if ($action === 'approve' || $action === 'reject') {
    if ($role !== 'admin') {
        header('Location: ../public/index.php');
        exit;
    }

    $leaveId = (int)($_POST['leave_id'] ?? 0);
    $notes   = trim($_POST['admin_notes'] ?? '');
    $status  = ($action === 'approve') ? 'approved' : 'rejected';

    $stmt = $pdo->prepare("UPDATE leaves SET status = ?, approved_by = ?, admin_notes = ? WHERE id = ?");
    $stmt->execute([$status, $userId, $notes, $leaveId]);

    // [V2] Kirim notifikasi Telegram ke Karyawan
    $st = $pdo->prepare("SELECT u.name, u.telegram_id FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
    $st->execute([$leaveId]);
    $lv = $st->fetch();
    $txt = ($status === 'approved' ? "✅ Cuti Anda <b>DISETUJUI</b>" : "❌ Cuti Anda <b>DITOLAK</b>") . ($notes !== '' ? "\nCatatan: $notes" : '');
    if ($lv && $lv['telegram_id']) tgSend($txt, $lv['telegram_id']);

    $_SESSION['admin_leave_msg'] = 'Pengajuan berhasil ' . ($status === 'approved' ? 'DISETUJUI' : 'DITOLAK') . '.';
    header('Location: ../admin/leaves.php');
    exit;
}

header('Location: ../public/index.php');
exit;