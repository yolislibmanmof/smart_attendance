<?php
// includes/public_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['index.php', 'register.php'];
$isAuthPage  = in_array($currentPage, $publicPages);

if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin' && !$isAuthPage) {
    header('Location: ../admin/dashboard.php');
    exit;
}

if (isset($_SESSION['user_id']) && $isAuthPage) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

// Hitung inisial user untuk avatar
$nxInitials = '';
if (isset($_SESSION['user_name'])) {
    $parts = explode(' ', $_SESSION['user_name']);
    $nxInitials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
}

// Greeting dinamis
$nxHour = (int)date('H');
if ($nxHour < 11) $nxGreet = ['Selamat Pagi', '🌅', 'Semangat pagi!'];
elseif ($nxHour < 15) $nxGreet = ['Selamat Siang', '☀️', 'Tetap produktif!'];
elseif ($nxHour < 18) $nxGreet = ['Selamat Sore', '🌤️', 'Hampir selesai!'];
else $nxGreet = ['Selamat Malam', '🌙', 'Istirahat yang cukup!'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance — <?= $pageTitle ?? 'Portal Karyawan' ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⏰</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public.css">
    <link rel="manifest" href="../manifest.webmanifest">

    <!-- ================================================ -->
    <!--   NX LIGHT DESIGN SYSTEM — Friendly Pastel Theme -->
    <!--   Inline CSS untuk tidak menyentuh public.css    -->
    <!-- ================================================ -->
    <style>
        :root {
            --nx-bg: #f6f8fc;
            --nx-bg-2: #eef2f9;
            --nx-surface: #ffffff;
            --nx-border: #e5ebf5;
            --nx-text: #1a2539;
            --nx-text-dim: #6b7a99;
            --nx-text-muted: #9aa5bd;
            --nx-primary: #4f46e5;
            --nx-primary-light: #818cf8;
            --nx-primary-bg: #eef2ff;
            --nx-cyan: #06b6d4;
            --nx-cyan-bg: #ecfeff;
            --nx-emerald: #10b981;
            --nx-emerald-bg: #ecfdf5;
            --nx-amber: #f59e0b;
            --nx-amber-bg: #fffbeb;
            --nx-rose: #f43f5e;
            --nx-rose-bg: #fff1f2;
            --nx-purple: #a855f7;
            --nx-purple-bg: #faf5ff;
            --nx-grad-hero: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --nx-grad-primary: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --nx-grad-success: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            --nx-grad-warning: linear-gradient(135deg, #f59e0b 0%, #fb923c 100%);
            --nx-grad-danger: linear-gradient(135deg, #f43f5e 0%, #ec4899 100%);
            --nx-grad-pink: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
            --nx-grad-cyan: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            --nx-grad-pastel: linear-gradient(135deg, #ffecd2 0%, #fcb69f 50%, #ff9a9e 100%);
            --nx-radius: 16px;
            --nx-radius-lg: 24px;
            --nx-radius-xl: 32px;
            --nx-font: 'Plus Jakarta Sans', -apple-system, sans-serif;
            --nx-mono: 'JetBrains Mono', monospace;
            --nx-shadow-sm: 0 2px 8px rgba(30, 41, 59, 0.04);
            --nx-shadow: 0 4px 16px rgba(30, 41, 59, 0.06);
            --nx-shadow-lg: 0 12px 40px rgba(30, 41, 59, 0.08);
            --nx-shadow-glow: 0 8px 24px rgba(79, 70, 229, 0.25);
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--nx-font) !important;
            background: var(--nx-bg);
            color: var(--nx-text);
            -webkit-font-smoothing: antialiased;
            background-image:
                radial-gradient(at 10% 0%, rgba(79,70,229,0.05) 0px, transparent 50%),
                radial-gradient(at 90% 100%, rgba(236,72,153,0.04) 0px, transparent 50%);
            background-attachment: fixed;
        }

        /* ===== AUTH SPLIT SCREEN (LOGIN & REGISTER) ===== */
        body:has(.auth-container) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #ec4899 100%);
            min-height: 100vh;
        }

        .auth-container {
            display: grid !important;
            grid-template-columns: 1fr 1fr;
            max-width: 1100px;
            width: 100%;
            min-height: 100vh;
            margin: 0 auto;
            padding: 0 !important;
            background: #fff;
            box-shadow: 0 30px 80px rgba(0,0,0,0.2);
            border-radius: 0;
        }
        @media (max-width: 900px) {
            .auth-container { grid-template-columns: 1fr; }
        }

        .nx-auth-illustration {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            color: #fff;
            position: relative;
            overflow: hidden;
            min-height: 100%;
        }
        .nx-auth-illustration::before {
            content: '';
            position: absolute;
            top: -50%; left: -20%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(236,72,153,0.4) 0%, transparent 70%);
            animation: nxFloat 10s ease-in-out infinite;
        }
        .nx-auth-illustration::after {
            content: '';
            position: absolute;
            bottom: -30%; right: -20%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(6,182,212,0.3) 0%, transparent 70%);
            animation: nxFloat 12s ease-in-out infinite reverse;
        }
        @keyframes nxFloat {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(-30px, 30px) scale(1.1); }
        }

        .nx-auth-illustration-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 400px;
        }
        .nx-auth-illustration .nx-logo {
            font-size: 80px;
            margin-bottom: 20px;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.2));
            animation: nxBounce 3s ease-in-out infinite;
        }
        @keyframes nxBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .nx-auth-illustration h2 {
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 12px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .nx-auth-illustration p {
            font-size: 15px;
            opacity: 0.9;
            margin: 0 0 30px;
            line-height: 1.6;
        }
        .nx-auth-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            text-align: left;
        }
        .nx-auth-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
        }
        .nx-auth-feature .ico {
            width: 32px; height: 32px;
            background: rgba(255,255,255,0.25);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .auth-box {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 50px !important;
            background: #fff !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }
        @media (max-width: 900px) {
            .nx-auth-illustration { display: none; }
            .auth-box { padding: 40px 25px !important; }
        }

        .nx-auth-header { margin-bottom: 32px; }
        .nx-auth-header .nx-welcome {
            display: inline-block;
            padding: 6px 14px;
            background: var(--nx-primary-bg);
            color: var(--nx-primary);
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
            letter-spacing: 0.3px;
        }
        .auth-box h2 {
            text-align: left !important;
            color: var(--nx-text) !important;
            font-size: 28px !important;
            font-weight: 800 !important;
            margin-bottom: 8px !important;
            letter-spacing: -0.5px !important;
        }
        .subtitle {
            text-align: left !important;
            color: var(--nx-text-dim) !important;
            font-size: 14px !important;
            margin-bottom: 0 !important;
        }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: flex !important;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px !important;
            font-weight: 600 !important;
            color: var(--nx-text) !important;
            font-size: 13px !important;
        }
        .form-group label .ico { font-size: 15px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 16px !important;
            background: var(--nx-bg) !important;
            border: 2px solid transparent !important;
            border-radius: 12px !important;
            font-size: 14px !important;
            font-family: var(--nx-font) !important;
            color: var(--nx-text) !important;
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none !important;
            border-color: var(--nx-primary) !important;
            background: #fff !important;
            box-shadow: 0 0 0 4px var(--nx-primary-bg) !important;
        }
        .form-group input::placeholder { color: var(--nx-text-muted); }

        .btn-primary {
            width: 100%;
            padding: 14px !important;
            background: var(--nx-grad-primary) !important;
            color: #fff !important;
            border: none;
            border-radius: 12px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            font-family: var(--nx-font) !important;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
            margin-top: 8px;
            letter-spacing: 0.3px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(79,70,229,0.4) !important;
            background: var(--nx-grad-primary) !important;
        }
        .btn-primary:active { transform: translateY(0); }

        .auth-footer {
            text-align: center !important;
            margin-top: 24px !important;
            padding-top: 20px;
            border-top: 1px solid var(--nx-border);
            font-size: 13px !important;
            color: var(--nx-text-dim) !important;
        }
        .auth-footer a {
            color: var(--nx-primary) !important;
            font-weight: 700 !important;
            text-decoration: none;
        }
        .auth-footer a:hover { text-decoration: underline; }

        .alert {
            padding: 12px 16px !important;
            border-radius: 12px !important;
            margin-bottom: 20px !important;
            font-size: 13px !important;
            display: flex !important;
            align-items: center;
            gap: 10px;
            border: 1px solid !important;
            text-align: left !important;
        }
        .alert.error {
            background: var(--nx-rose-bg) !important;
            color: var(--nx-rose) !important;
            border-color: rgba(244,63,94,0.2) !important;
        }
        .alert.success {
            background: var(--nx-emerald-bg) !important;
            color: var(--nx-emerald) !important;
            border-color: rgba(16,185,129,0.2) !important;
        }

        /* ===== PORTAL LAYOUT (INTERNAL PAGES) ===== */
        .emp-layout {
            display: flex;
            min-height: 100vh;
        }

        .emp-sidebar {
            width: 260px !important;
            background: #fff;
            border-right: 1px solid var(--nx-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 20;
            box-shadow: 4px 0 24px rgba(30,41,59,0.03);
        }

        .emp-brand {
            padding: 24px 22px !important;
            border-bottom: 1px solid var(--nx-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nx-brand-ico {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--nx-grad-primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            box-shadow: 0 6px 16px rgba(79,70,229,0.3);
            flex-shrink: 0;
        }
        .emp-brand h2 {
            font-size: 16px !important;
            font-weight: 800 !important;
            color: var(--nx-text) !important;
            margin: 0 !important;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        .emp-brand small {
            color: var(--nx-text-muted) !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .emp-menu {
            flex: 1;
            padding: 20px 14px !important;
            overflow-y: auto;
        }
        .emp-menu .nx-menu-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--nx-text-muted);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0 12px 10px;
            margin-top: 10px;
        }
        .emp-menu a {
            display: flex !important;
            align-items: center;
            gap: 12px !important;
            padding: 11px 14px !important;
            margin-bottom: 4px !important;
            border-radius: 10px !important;
            color: var(--nx-text-dim) !important;
            text-decoration: none;
            font-size: 14px !important;
            font-weight: 600 !important;
            transition: all 0.2s;
            position: relative;
        }
        .emp-menu a .ico {
            width: 34px; height: 34px;
            background: var(--nx-bg);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .emp-menu a:hover {
            background: var(--nx-bg) !important;
            color: var(--nx-primary) !important;
        }
        .emp-menu a:hover .ico {
            background: var(--nx-primary-bg);
        }
        .emp-menu a.active {
            background: var(--nx-grad-primary) !important;
            color: #fff !important;
            box-shadow: 0 6px 16px rgba(79,70,229,0.3) !important;
        }
        .emp-menu a.active .ico {
            background: rgba(255,255,255,0.2);
        }

        .emp-sidebar-footer {
            padding: 16px !important;
            border-top: 1px solid var(--nx-border);
        }
        .emp-user-card {
            background: var(--nx-primary-bg) !important;
            border: 1px solid rgba(79,70,229,0.15) !important;
            border-radius: 14px !important;
            padding: 12px !important;
            display: flex !important;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px !important;
        }
        .nx-sidebar-avatar {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: var(--nx-grad-primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .emp-user-card .info {
            flex: 1;
            min-width: 0;
            line-height: 1.3;
        }
        .emp-user-card strong {
            display: block !important;
            font-size: 13px !important;
            color: var(--nx-text) !important;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .emp-user-card small {
            color: var(--nx-text-muted) !important;
            font-size: 11px !important;
            font-family: var(--nx-mono);
        }

        .btn-logout {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px !important;
            border-radius: 10px !important;
            background: var(--nx-rose-bg) !important;
            color: var(--nx-rose) !important;
            text-decoration: none;
            font-weight: 700 !important;
            font-size: 13px !important;
            border: 1px solid rgba(244,63,94,0.15);
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: #ffe4e6 !important;
            transform: translateY(-1px);
        }

        .emp-content {
            flex: 1;
            margin-left: 260px !important;
            min-height: 100vh;
        }
        .emp-topbar {
            background: rgba(255,255,255,0.85) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 18px 32px !important;
            border-bottom: 1px solid var(--nx-border) !important;
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .emp-topbar h1 {
            font-size: 20px !important;
            font-weight: 800 !important;
            color: var(--nx-text) !important;
            margin: 0;
            letter-spacing: -0.4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .emp-topbar h1::before {
            content: '';
            width: 4px; height: 22px;
            background: var(--nx-grad-primary);
            border-radius: 2px;
        }
        .date-chip {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: var(--nx-primary) !important;
            background: var(--nx-primary-bg) !important;
            padding: 8px 14px !important;
            border-radius: 50px !important;
            border: 1px solid rgba(79,70,229,0.1);
        }

        .emp-body {
            padding: 28px 32px !important;
        }
        .emp-body .dashboard-container,
        .emp-body .absen-container {
            padding: 0 !important;
            max-width: 100% !important;
            margin: 0 !important;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
            gap: 18px !important;
            margin-bottom: 28px !important;
        }
        .stat-card {
            position: relative;
            background: #fff !important;
            border: 1px solid var(--nx-border) !important;
            border-radius: var(--nx-radius) !important;
            padding: 22px !important;
            overflow: hidden;
            transition: all 0.25s;
            border-left: none !important;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(79,70,229,0.2) !important;
            box-shadow: var(--nx-shadow-lg);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--nx-grad-cyan);
        }
        .stat-card.success::before { background: var(--nx-grad-success); }
        .stat-card.warning::before { background: var(--nx-grad-warning); }
        .stat-card.danger::before  { background: var(--nx-grad-danger); }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            background: var(--nx-grad-cyan);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(6,182,212,0.3);
        }
        .stat-card.success .stat-icon { background: var(--nx-grad-success); box-shadow: 0 6px 16px rgba(16,185,129,0.3); }
        .stat-card.warning .stat-icon { background: var(--nx-grad-warning); box-shadow: 0 6px 16px rgba(245,158,11,0.3); }
        .stat-card.danger  .stat-icon { background: var(--nx-grad-danger);  box-shadow: 0 6px 16px rgba(244,63,94,0.3); }
        .stat-info h3 {
            font-size: 28px !important;
            font-weight: 800 !important;
            color: var(--nx-text) !important;
            letter-spacing: -0.6px;
            margin: 4px 0 !important;
        }
        .stat-info p {
            font-size: 12px !important;
            color: var(--nx-text-dim) !important;
            font-weight: 600;
            margin: 0 !important;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) !important;
            gap: 16px !important;
            margin-bottom: 28px !important;
        }
        .btn-action {
            display: flex !important;
            align-items: center;
            gap: 16px !important;
            background: #fff !important;
            padding: 22px !important;
            border-radius: var(--nx-radius) !important;
            text-decoration: none;
            color: var(--nx-text) !important;
            border: 1px solid var(--nx-border) !important;
            transition: all 0.25s;
        }
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: var(--nx-shadow-lg);
            border-color: rgba(79,70,229,0.2) !important;
        }
        .btn-action.primary {
            background: var(--nx-grad-primary) !important;
            color: #fff !important;
            border: none !important;
            box-shadow: 0 8px 24px rgba(79,70,229,0.25);
        }
        .btn-action.primary:hover { box-shadow: 0 12px 32px rgba(79,70,229,0.4); }
        .btn-icon {
            width: 50px; height: 50px;
            background: var(--nx-primary-bg);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .btn-action.primary .btn-icon { background: rgba(255,255,255,0.2); }
        .btn-action strong {
            display: block;
            font-size: 15px !important;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .btn-action small {
            font-size: 12px !important;
            opacity: 0.8;
            color: inherit;
        }

        /* ===== PANELS & TABLES ===== */
        .content-box,
        .table-responsive {
            background: #fff !important;
            border-radius: var(--nx-radius) !important;
            border: 1px solid var(--nx-border);
            padding: 24px !important;
            box-shadow: var(--nx-shadow-sm) !important;
            margin-bottom: 24px !important;
        }
        .table-responsive { padding: 0 !important; overflow-x: auto; }

        .content-box h2,
        .recent-activity h2 {
            color: var(--nx-text) !important;
            font-size: 16px !important;
            font-weight: 800 !important;
            margin-bottom: 16px !important;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .content-box h2::before,
        .recent-activity h2::before {
            content: '';
            width: 4px; height: 18px;
            background: var(--nx-grad-primary);
            border-radius: 2px;
        }

        .data-table { font-size: 13.5px !important; }
        .data-table th {
            background: var(--nx-bg) !important;
            color: var(--nx-text-dim) !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 14px 18px !important;
            border-bottom: 1px solid var(--nx-border) !important;
        }
        .data-table td {
            padding: 14px 18px !important;
            color: var(--nx-text) !important;
            border-bottom: 1px solid var(--nx-border) !important;
        }
        .data-table tbody tr { transition: all 0.15s; }
        .data-table tbody tr:hover { background: var(--nx-bg); }
        .data-table tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px !important;
            border-radius: 50px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.2px;
        }
        .badge.success { background: var(--nx-emerald-bg) !important; color: var(--nx-emerald) !important; }
        .badge.warning { background: var(--nx-amber-bg) !important; color: var(--nx-amber) !important; }
        .badge.danger  { background: var(--nx-rose-bg) !important; color: var(--nx-rose) !important; }
        .badge.info    { background: var(--nx-primary-bg) !important; color: var(--nx-primary) !important; }

        /* ===== MOOD & OTHER ===== */
        .mood-box {
            margin-top: 20px !important;
            padding-top: 20px !important;
            border-top: 2px dashed var(--nx-border) !important;
        }
        .mood-options { display: flex; justify-content: center; gap: 10px; margin: 14px 0; }
        .mood-btn {
            font-size: 30px !important;
            background: var(--nx-bg) !important;
            border: 2px solid transparent !important;
            border-radius: 14px !important;
            padding: 10px 14px !important;
            cursor: pointer;
            transition: all 0.2s;
        }
        .mood-btn:hover {
            transform: scale(1.2) !important;
            border-color: var(--nx-primary) !important;
            background: var(--nx-primary-bg) !important;
            box-shadow: 0 6px 16px rgba(79,70,229,0.2);
        }

        /* ===== FORMS INTERNAL ===== */
        .form-group textarea {
            min-height: 100px !important;
            resize: vertical;
        }
        .filter-bar {
            display: flex !important;
            gap: 10px !important;
            align-items: center;
            margin-bottom: 20px !important;
            flex-wrap: wrap;
            padding: 14px 18px;
            background: #fff;
            border-radius: var(--nx-radius);
            border: 1px solid var(--nx-border);
        }
        .filter-bar input[type="month"] {
            padding: 10px 14px !important;
            border: 2px solid var(--nx-border) !important;
            border-radius: 10px !important;
            font-size: 13px !important;
            font-family: var(--nx-font);
            color: var(--nx-text);
            background: var(--nx-bg);
        }
        .filter-bar input[type="month"]:focus {
            outline: none !important;
            border-color: var(--nx-primary) !important;
        }

        /* ===== ABSEN PAGE ===== */
        .absen-box {
            background: #fff !important;
            border-radius: var(--nx-radius-lg) !important;
            padding: 32px !important;
            box-shadow: var(--nx-shadow) !important;
            border: 1px solid var(--nx-border);
            text-align: center;
        }
        .absen-box h2 {
            color: var(--nx-text) !important;
            font-size: 22px !important;
            font-weight: 800 !important;
            margin-bottom: 18px !important;
            letter-spacing: -0.4px;
        }
        .location-info {
            background: var(--nx-cyan-bg) !important;
            color: var(--nx-cyan) !important;
            padding: 14px 20px !important;
            border-radius: 14px !important;
            font-size: 13px !important;
            font-weight: 600;
            margin-bottom: 22px !important;
            border: 1px solid rgba(6,182,212,0.2);
        }
        .location-status {
            background: var(--nx-bg) !important;
            border-radius: 14px !important;
            padding: 18px !important;
            margin-bottom: 22px !important;
            font-size: 13px !important;
            border: 1px solid var(--nx-border);
        }
        .location-status .success { color: var(--nx-emerald) !important; font-weight: 600; }
        .location-status .error { color: var(--nx-rose) !important; font-weight: 600; }
        .location-status .warning { color: var(--nx-amber) !important; font-weight: 600; margin-top: 8px; }

        .form-info {
            background: var(--nx-bg) !important;
            border-radius: 12px !important;
            padding: 14px 16px !important;
            margin-bottom: 22px !important;
            font-size: 13px !important;
            text-align: left;
            border: 1px solid var(--nx-border);
        }

        .btn-absen {
            width: 100%;
            padding: 16px !important;
            border: none;
            border-radius: 14px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            font-family: var(--nx-font);
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-masuk {
            background: var(--nx-grad-success) !important;
            box-shadow: 0 8px 20px rgba(16,185,129,0.3);
        }
        .btn-masuk:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,0.4) !important; background: var(--nx-grad-success) !important; }
        .btn-pulang {
            background: var(--nx-grad-warning) !important;
            box-shadow: 0 8px 20px rgba(245,158,11,0.3);
        }
        .btn-pulang:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(245,158,11,0.4) !important; background: var(--nx-grad-warning) !important; }

        .qr-section {
            margin: 22px 0 !important;
            padding: 20px !important;
            background: var(--nx-bg) !important;
            border-radius: 16px !important;
            border: 2px dashed var(--nx-border);
        }
        .qr-section p {
            margin-bottom: 14px !important;
            font-size: 13px !important;
            font-weight: 600;
            color: var(--nx-text-dim);
        }

        .absen-info {
            text-align: left;
            background: var(--nx-bg) !important;
            padding: 16px 18px !important;
            border-radius: 14px !important;
            margin-bottom: 18px !important;
            font-size: 13px !important;
            border: 1px solid var(--nx-border);
        }
        .absen-info p { margin-bottom: 6px !important; }

        .thank-you {
            color: var(--nx-emerald) !important;
            font-weight: 700 !important;
            margin-top: 14px !important;
            font-size: 14px !important;
        }

        .back-link {
            text-align: center;
            margin-top: 24px !important;
        }
        .back-link a {
            color: var(--nx-primary) !important;
            text-decoration: none;
            font-size: 13px !important;
            font-weight: 700 !important;
            padding: 8px 16px;
            border-radius: 50px;
            transition: all 0.2s;
        }
        .back-link a:hover {
            background: var(--nx-primary-bg);
        }

        #status-message .alert {
            border-radius: 14px !important;
            padding: 14px 18px !important;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 800px) {
            .emp-sidebar { width: 72px !important; }
            .emp-brand h2, .emp-brand small,
            .emp-menu a .menu-text,
            .emp-menu .nx-menu-label,
            .emp-user-card .info { display: none !important; }
            .emp-brand { justify-content: center; padding: 18px 10px !important; }
            .emp-menu a { justify-content: center; padding: 12px !important; }
            .emp-menu a .ico { width: 40px; height: 40px; font-size: 18px; }
            .emp-user-card { justify-content: center; padding: 8px !important; }
            .emp-content { margin-left: 72px !important; }
            .emp-body { padding: 18px !important; }
            .emp-topbar { padding: 14px 18px !important; }
            .emp-topbar h1 { font-size: 17px !important; }
        }
        @media (max-width: 600px) {
            .stats-grid, .action-buttons { grid-template-columns: 1fr !important; }
            .emp-topbar h1::before { display: none; }
        }

        /* ===== ANIMATION ENTRANCE ===== */
        .emp-body > * {
            animation: nxFadeUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        .emp-body > *:nth-child(1) { animation-delay: 0.05s; }
        .emp-body > *:nth-child(2) { animation-delay: 0.1s; }
        .emp-body > *:nth-child(3) { animation-delay: 0.15s; }
        .emp-body > *:nth-child(4) { animation-delay: 0.2s; }
        .emp-body > *:nth-child(5) { animation-delay: 0.25s; }
        @keyframes nxFadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d9e6; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--nx-primary-light); }
    </style>
</head>
<body>

<?php if (!$isAuthPage): ?>
<!-- ===== LAYOUT KHUSUS KARYAWAN (PREMIUM LIGHT) ===== -->
<div class="emp-layout">
    <aside class="emp-sidebar">
        <div class="emp-brand">
            <div class="nx-brand-ico">⏰</div>
            <div>
                <h2>Smart Attendance</h2>
                <small>Portal Karyawan</small>
            </div>
        </div>
        <nav class="emp-menu">
            <div class="nx-menu-label">Menu Utama</div>
            <a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <span class="ico">🏠</span><span class="menu-text">Dashboard</span>
            </a>
            <a href="absen.php" class="<?= $currentPage == 'absen.php' ? 'active' : '' ?>">
                <span class="ico">📍</span><span class="menu-text">Absen Sekarang</span>
            </a>
            <div class="nx-menu-label">Riwayat</div>
            <a href="riwayat.php" class="<?= $currentPage == 'riwayat.php' ? 'active' : '' ?>">
                <span class="ico">📋</span><span class="menu-text">Riwayat Absensi</span>
            </a>
            <a href="cuti.php" class="<?= $currentPage == 'cuti.php' ? 'active' : '' ?>">
                <span class="ico">🏖️</span><span class="menu-text">Cuti / Izin</span>
            </a>
        </nav>
        <div class="emp-sidebar-footer">
            <div class="emp-user-card">
                <div class="nx-sidebar-avatar"><?= htmlspecialchars($nxInitials ?: 'U') ?></div>
                <div class="info">
                    <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong>
                    <small><?= htmlspecialchars($_SESSION['user_nip'] ?? '-') ?></small>
                </div>
            </div>
            <a href="../api/auth.php?action=logout" class="btn-logout" onclick="return confirm('Yakin ingin logout dari Portal Karyawan?')">🚪 <span>Keluar</span></a>
        </div>
    </aside>

    <main class="emp-content">
        <header class="emp-topbar">
            <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
            <span class="date-chip"><?= $nxGreet[1] ?> <?= date('d M Y') ?></span>
        </header>
        <div class="emp-body">
<?php endif; ?>