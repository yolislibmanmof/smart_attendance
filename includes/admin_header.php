<?php
// includes/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../public/index.php');
    exit;
}

$currentFile = basename($_SERVER['PHP_SELF']);

// Hitung inisial admin untuk avatar
$nameParts = explode(' ', $_SESSION['user_name'] ?? 'A');
$initials = strtoupper(substr($nameParts[0], 0, 1)) . (isset($nameParts[1]) ? strtoupper(substr($nameParts[1], 0, 1)) : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= $pageTitle ?? 'Smart Attendance' ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⏰</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <!-- ================================================ -->
    <!--   NX DESIGN SYSTEM — Premium Enterprise Theme    -->
    <!--   Inline CSS agar tidak menyentuh admin.css      -->
    <!-- ================================================ -->
    <style>
        /* ===== VARIABLES & RESET ===== */
        :root {
            --nx-bg: #0a0e1a;
            --nx-bg-2: #111827;
            --nx-surface: #1a2030;
            --nx-surface-2: #232a3d;
            --nx-border: rgba(255,255,255,0.08);
            --nx-border-strong: rgba(255,255,255,0.15);
            --nx-text: #e5e7eb;
            --nx-text-dim: #9ca3af;
            --nx-text-muted: #6b7280;
            --nx-accent: #6366f1;
            --nx-accent-2: #8b5cf6;
            --nx-accent-3: #ec4899;
            --nx-cyan: #06b6d4;
            --nx-emerald: #10b981;
            --nx-amber: #f59e0b;
            --nx-rose: #f43f5e;
            --nx-gradient-1: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            --nx-gradient-2: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            --nx-gradient-3: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            --nx-gradient-4: linear-gradient(135deg, #f59e0b 0%, #f43f5e 100%);
            --nx-gradient-5: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            --nx-shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
            --nx-shadow: 0 4px 12px rgba(0,0,0,0.4);
            --nx-shadow-lg: 0 10px 30px rgba(0,0,0,0.5);
            --nx-shadow-glow: 0 0 30px rgba(99,102,241,0.35);
            --nx-radius: 14px;
            --nx-radius-sm: 8px;
            --nx-radius-lg: 20px;
            --nx-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --nx-mono: 'JetBrains Mono', 'Courier New', monospace;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: var(--nx-bg); }
        body {
            font-family: var(--nx-font);
            color: var(--nx-text);
            background:
                radial-gradient(circle at 10% 20%, rgba(99,102,241,0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(236,72,153,0.06) 0%, transparent 40%),
                var(--nx-bg);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== LAYOUT ===== */
        .admin-layout { display: flex; min-height: 100vh; }

        /* ===== SIDEBAR — Glassmorphism Dark Premium ===== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, rgba(17,24,39,0.95) 0%, rgba(10,14,26,0.98) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--nx-border);
            color: var(--nx-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 20;
            box-shadow: 4px 0 24px rgba(0,0,0,0.3);
        }

        .sidebar::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, var(--nx-accent), transparent);
            opacity: 0.6;
        }

        .sidebar-brand {
            padding: 26px 22px;
            border-bottom: 1px solid var(--nx-border);
            position: relative;
        }
        .sidebar-brand h2 {
            font-size: 19px;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: -0.3px;
            background: var(--nx-gradient-1);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        .sidebar-brand small {
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--nx-text-muted);
            text-transform: uppercase;
        }
        .sidebar-brand::after {
            content: '';
            position: absolute; bottom: -1px; left: 22px; right: 22px; height: 1px;
            background: linear-gradient(90deg, var(--nx-accent), transparent);
            opacity: 0.3;
        }

        /* ===== MENU ===== */
        .sidebar-menu {
            flex: 1;
            padding: 20px 14px;
            overflow-y: auto;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            margin-bottom: 4px;
            border-radius: 10px;
            color: var(--nx-text-dim);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
        }
        .sidebar-menu a:hover {
            background: rgba(99,102,241,0.08);
            color: #fff;
            transform: translateX(2px);
        }
        .sidebar-menu a.active {
            background: linear-gradient(135deg, rgba(99,102,241,0.18) 0%, rgba(139,92,246,0.18) 100%);
            color: #fff;
            font-weight: 600;
            border: 1px solid rgba(99,102,241,0.3);
            box-shadow: 0 4px 12px rgba(99,102,241,0.2), inset 0 1px 0 rgba(255,255,255,0.05);
        }
        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: -14px; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 28px;
            background: var(--nx-gradient-1);
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 12px var(--nx-accent);
        }
        .sidebar-menu a .nx-ico {
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            transition: all 0.25s;
        }
        .sidebar-menu a.active .nx-ico {
            background: var(--nx-gradient-1);
            box-shadow: 0 0 16px rgba(99,102,241,0.5);
        }

        /* ===== SIDEBAR FOOTER ===== */
        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid var(--nx-border);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border-radius: 10px;
            background: rgba(244,63,94,0.08);
            color: #fb7185;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(244,63,94,0.2);
            transition: all 0.2s;
        }
        .sidebar-footer a:hover {
            background: rgba(244,63,94,0.18);
            transform: translateY(-1px);
        }

        /* ===== MAIN CONTENT ===== */
        .admin-content {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .admin-topbar {
            background: rgba(17,24,39,0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--nx-border);
            position: sticky; top: 0; z-index: 10;
        }
        .admin-topbar h1 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            letter-spacing: -0.4px;
        }
        .admin-topbar h1::before {
            content: '';
            display: inline-block;
            width: 4px; height: 22px;
            background: var(--nx-gradient-1);
            border-radius: 2px;
            margin-right: 12px;
            vertical-align: middle;
            box-shadow: 0 0 12px var(--nx-accent);
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 16px 6px 6px;
            background: var(--nx-surface);
            border: 1px solid var(--nx-border-strong);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: var(--nx-text);
            box-shadow: var(--nx-shadow);
        }
        .nx-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--nx-gradient-1);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800; color: #fff;
            box-shadow: 0 0 0 2px var(--nx-surface), 0 0 0 3px rgba(99,102,241,0.4);
            flex-shrink: 0;
        }
        .admin-user-info { line-height: 1.2; }
        .admin-user-info small {
            display: block;
            font-size: 10px;
            color: var(--nx-text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== BODY ===== */
        .admin-body { padding: 28px 32px; }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }
        .stat-card {
            position: relative;
            background: var(--nx-surface);
            border: 1px solid var(--nx-border);
            border-radius: var(--nx-radius);
            padding: 22px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--nx-border-strong);
            box-shadow: var(--nx-shadow-lg);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: var(--nx-gradient-2);
        }
        .stat-card.success::before { background: var(--nx-gradient-3); }
        .stat-card.warning::before { background: var(--nx-gradient-4); }
        .stat-card.danger::before  { background: var(--nx-gradient-5); }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .stat-card.success::after { background: radial-gradient(circle, rgba(16,185,129,0.15) 0%, transparent 70%); }
        .stat-card.warning::after { background: radial-gradient(circle, rgba(245,158,11,0.15) 0%, transparent 70%); }
        .stat-card.danger::after  { background: radial-gradient(circle, rgba(236,72,153,0.15) 0%, transparent 70%); }

        .stat-icon {
            width: 54px; height: 54px;
            border-radius: 14px;
            background: var(--nx-gradient-2);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(6,182,212,0.3);
            position: relative;
            z-index: 1;
        }
        .stat-card.success .stat-icon { background: var(--nx-gradient-3); box-shadow: 0 8px 20px rgba(16,185,129,0.3); }
        .stat-card.warning .stat-icon { background: var(--nx-gradient-4); box-shadow: 0 8px 20px rgba(245,158,11,0.3); }
        .stat-card.danger  .stat-icon { background: var(--nx-gradient-5); box-shadow: 0 8px 20px rgba(236,72,153,0.3); }

        .stat-info { flex: 1; position: relative; z-index: 1; }
        .stat-info h3 {
            font-size: 30px;
            font-weight: 800;
            margin: 0 0 4px;
            color: #fff;
            letter-spacing: -0.8px;
            line-height: 1;
            font-family: var(--nx-font);
        }
        .stat-info p {
            font-size: 12.5px;
            color: var(--nx-text-dim);
            margin: 0;
            font-weight: 500;
        }
        .nx-stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 6px;
            padding: 2px 8px;
            border-radius: 50px;
            background: rgba(16,185,129,0.15);
            color: var(--nx-emerald);
        }
        .nx-stat-trend.down { background: rgba(244,63,94,0.15); color: var(--nx-rose); }

        /* ===== PANELS & CARDS ===== */
        .panel {
            background: var(--nx-surface);
            border: 1px solid var(--nx-border);
            border-radius: var(--nx-radius);
            overflow: hidden;
            box-shadow: var(--nx-shadow);
            margin-bottom: 24px;
        }
        .panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--nx-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, transparent 100%);
        }
        .panel-header h2 {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panel-header h2::before {
            content: '';
            width: 6px; height: 18px;
            background: var(--nx-gradient-1);
            border-radius: 3px;
        }

        /* ===== TABLES ===== */
        .table-responsive { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .data-table th {
            padding: 13px 18px;
            text-align: left;
            background: var(--nx-bg-2);
            color: var(--nx-text-dim);
            font-weight: 600;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1px solid var(--nx-border);
            white-space: nowrap;
        }
        .data-table td {
            padding: 14px 18px;
            color: var(--nx-text);
            border-bottom: 1px solid var(--nx-border);
        }
        .data-table tbody tr {
            transition: all 0.2s;
        }
        .data-table tbody tr:hover {
            background: rgba(99,102,241,0.04);
        }
        .data-table tbody tr:last-child td { border-bottom: none; }

        .empty-row {
            text-align: center;
            padding: 50px 20px !important;
            color: var(--nx-text-muted);
            font-size: 14px;
        }
        .nx-empty-icon {
            display: block;
            font-size: 50px;
            opacity: 0.3;
            margin-bottom: 12px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        .badge.success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .badge.warning { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge.danger  { background: rgba(244,63,94,0.15); color: #fb7185; border: 1px solid rgba(244,63,94,0.3); }
        .badge.info    { background: rgba(99,102,241,0.15); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }

        .nx-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
            animation: nxPulse 2s infinite;
        }
        @keyframes nxPulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 currentColor; }
            50% { opacity: 0.6; box-shadow: 0 0 0 4px transparent; }
        }

        /* ===== BUTTONS ===== */
        .btn {
            padding: 9px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: var(--nx-font);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--nx-gradient-1);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99,102,241,0.35);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(99,102,241,0.5);
        }
        .btn-success { background: var(--nx-gradient-3); color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,0.35); }
        .btn-danger  { background: var(--nx-gradient-4); color: #fff; box-shadow: 0 4px 12px rgba(244,63,94,0.35); }
        .btn-light   { background: var(--nx-surface-2); color: var(--nx-text); border: 1px solid var(--nx-border-strong); }
        .btn-light:hover { background: var(--nx-bg-2); }
        .btn-sm { padding: 6px 11px; font-size: 12px; border-radius: 6px; }

        /* ===== FORMS ===== */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--nx-text-dim);
            font-size: 12.5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            background: var(--nx-bg-2);
            border: 1px solid var(--nx-border-strong);
            border-radius: 8px;
            color: var(--nx-text);
            font-size: 13.5px;
            font-family: var(--nx-font);
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--nx-accent);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid;
        }
        .alert.success {
            background: rgba(16,185,129,0.08);
            color: #34d399;
            border-color: rgba(16,185,129,0.3);
        }
        .alert.error {
            background: rgba(244,63,94,0.08);
            color: #fb7185;
            border-color: rgba(244,63,94,0.3);
        }
        .alert.warning {
            background: rgba(245,158,11,0.08);
            color: #fbbf24;
            border-color: rgba(245,158,11,0.3);
        }

        /* ===== WELCOME BANNER ===== */
        .nx-welcome {
            position: relative;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
            border-radius: var(--nx-radius-lg);
            padding: 28px 32px;
            margin-bottom: 28px;
            overflow: hidden;
            border: 1px solid rgba(139,92,246,0.3);
            box-shadow: 0 10px 40px rgba(99,102,241,0.25);
        }
        .nx-welcome::before {
            content: '';
            position: absolute;
            top: -50%; right: -10%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(236,72,153,0.3) 0%, transparent 70%);
            animation: nxFloat 8s ease-in-out infinite;
        }
        @keyframes nxFloat {
            0%, 100% { transform: translate(0,0); }
            50% { transform: translate(-20px, 20px); }
        }
        .nx-welcome-content { position: relative; z-index: 1; }
        .nx-welcome h2 {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
        }
        .nx-welcome p {
            color: rgba(255,255,255,0.75);
            margin: 0;
            font-size: 14px;
        }
        .nx-welcome-chips {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        .nx-chip {
            padding: 5px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            font-size: 12px;
            color: #fff;
            backdrop-filter: blur(10px);
        }

        /* ===== MINI AVATAR ROW ===== */
        .nx-user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nx-user-cell .nx-avatar {
            width: 32px; height: 32px;
            font-size: 11px;
            box-shadow: 0 0 0 1px var(--nx-border-strong);
        }
        .nx-user-cell-info { line-height: 1.25; }
        .nx-user-cell-info strong {
            display: block;
            font-size: 13px;
            color: #fff;
            font-weight: 600;
        }
        .nx-user-cell-info small {
            font-size: 11px;
            color: var(--nx-text-muted);
            font-family: var(--nx-mono);
        }

        /* ===== MONO TEXT ===== */
        .nx-mono {
            font-family: var(--nx-mono);
            font-size: 12px;
            color: var(--nx-cyan);
        }

        /* ===== PROGRESS BAR ===== */
        .nx-progress {
            height: 6px;
            background: var(--nx-bg-2);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        .nx-progress-bar {
            height: 100%;
            background: var(--nx-gradient-3);
            border-radius: 3px;
            transition: width 1s ease;
        }

        /* ===== LIVE INDICATOR ===== */
        .nx-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            color: var(--nx-emerald);
        }
        .nx-live::before {
            content: '';
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--nx-emerald);
            box-shadow: 0 0 8px var(--nx-emerald);
            animation: nxPulse 1.5s infinite;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .sidebar { width: 72px; }
            .sidebar-brand h2, .sidebar-brand small,
            .sidebar-menu a .menu-text,
            .sidebar-menu a span:not(.nx-ico) { display: none; }
            .sidebar-menu a { justify-content: center; padding: 12px; }
            .sidebar-menu a .nx-ico { width: 36px; height: 36px; font-size: 18px; }
            .admin-content { margin-left: 72px; }
            .admin-body { padding: 18px; }
            .form-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 560px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        /* ===== ANIMATION ENTRANCE ===== */
        .admin-body > * {
            animation: nxFadeUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        .admin-body > *:nth-child(1) { animation-delay: 0.05s; }
        .admin-body > *:nth-child(2) { animation-delay: 0.1s; }
        .admin-body > *:nth-child(3) { animation-delay: 0.15s; }
        .admin-body > *:nth-child(4) { animation-delay: 0.2s; }
        @keyframes nxFadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== SCROLLBAR CUSTOM ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--nx-bg); }
        ::-webkit-scrollbar-thumb { background: var(--nx-surface-2); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--nx-accent); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>⏰ Smart Attendance</h2>
            <small>Panel Administrator</small>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="<?= $currentFile == 'dashboard.php' ? 'active' : '' ?>">
                <span class="nx-ico">📊</span><span class="menu-text">Dashboard</span>
            </a>
            <a href="users.php" class="<?= $currentFile == 'users.php' ? 'active' : '' ?>">
                <span class="nx-ico">👥</span><span class="menu-text">Data Karyawan</span>
            </a>
            <a href="shifts.php" class="<?= $currentFile == 'shifts.php' ? 'active' : '' ?>">
                <span class="nx-ico">🕐</span><span class="menu-text">Shift & Jam Kerja</span>
            </a>
            <a href="locations.php" class="<?= $currentFile == 'locations.php' ? 'active' : '' ?>">
                <span class="nx-ico">📍</span><span class="menu-text">Lokasi & Wi-Fi</span>
            </a>
            <a href="leaves.php" class="<?= $currentFile == 'leaves.php' ? 'active' : '' ?>">
                <span class="nx-ico">🏖️</span><span class="menu-text">Pengajuan Cuti</span>
            </a>
            <a href="reports.php" class="<?= $currentFile == 'reports.php' ? 'active' : '' ?>">
                <span class="nx-ico">📈</span><span class="menu-text">Laporan</span>
            </a>
            <a href="analytics.php" class="<?= $currentFile == 'analytics.php' ? 'active' : '' ?>">
                <span class="nx-ico">🧠</span><span class="menu-text">Analitik AI</span>
            </a>
            <a href="settings.php" class="<?= $currentFile == 'settings.php' ? 'active' : '' ?>">
                <span class="nx-ico">⚙️</span><span class="menu-text">Pengaturan</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../api/auth.php?action=logout" onclick="return confirm('Yakin ingin logout?')">🚪 <span>Keluar</span></a>
        </div>
    </aside>

    <main class="admin-content">
        <header class="admin-topbar">
            <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
            <div class="admin-user">
                <div class="nx-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="admin-user-info">
                    <small>Administrator</small>
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
            </div>
        </header>
        <div class="admin-body">