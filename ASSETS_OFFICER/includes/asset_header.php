<?php
// ASSETS_OFFICER/includes/asset_header.php - Asset Management Module Header
// IMPORTANT: Hakuna spaces au characters kabla ya <?php

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname ='accountant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$allowed_roles = ['super_admin', 'accountant', 'store_keeper'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = $page_title ?? 'Asset Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> - SSMS Tanzania</title>
    <link rel="stylesheet" href="../ACCOUNTANT/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Poppins', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            overflow-x: hidden;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #0f2b4d 0%, #0a1f3a 100%);
            color: #fff;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #1e3c72;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #4a7cbf;
            border-radius: 5px;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .sidebar-sub {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1.2rem;
            background: rgba(255,255,255,0.05);
            margin: 1rem;
            border-radius: 12px;
        }
        
        .sidebar-user i {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .sidebar-user-role {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        
        .sidebar-nav {
            padding: 0.5rem 0 1.5rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 1.8rem;
        }
        
        .nav-item i {
            width: 1.5rem;
            font-size: 1.1rem;
        }
        
        .nav-divider {
            padding: 0.8rem 1.5rem 0.3rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            font-weight: 600;
        }
        
        .logout-item {
            margin-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1rem;
            color: #ff9999;
        }
        
        .logout-item:hover {
            background: rgba(255,0,0,0.2);
            color: #ff6666;
        }
        
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .mobile-header {
            display: none;
            background: white;
            padding: 0.8rem 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 999;
            align-items: center;
            justify-content: space-between;
        }
        
        .hamburger {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #1e3c72;
        }
        
        .mobile-logo {
            font-weight: bold;
            color: #1e3c72;
        }
        
        .mobile-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #1e3c72;
        }
        
        .user-name-mobile {
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .content-wrapper {
            padding: 1.5rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h1 {
            color: #1e3c72;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            border-top: 3px solid #1e3c72;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1e3c72;
        }
        
        .stat-card .stat-label {
            color: #666;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }
        
        .stat-card i {
            font-size: 1.8rem;
            color: #4a7cbf;
            margin-bottom: 0.5rem;
        }
        
        .form-card {
            background: white;
            padding: 1.3rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 1.3rem;
        }
        
        .form-card h3, .form-card h4 {
            margin-bottom: 1rem;
            color: #1e3c72;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            font-size: 0.85rem;
            color: #333;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: border 0.2s;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #1e3c72;
        }
        
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #1e3c72;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2a5298;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-sm {
            padding: 0.2rem 0.6rem;
            font-size: 0.7rem;
            border-radius: 4px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        
        .data-table th {
            background: #1e3c72;
            color: white;
            padding: 0.8rem;
            text-align: left;
            font-weight: 500;
        }
        
        .data-table td {
            padding: 0.7rem 0.8rem;
            border-bottom: 1px solid #eee;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .status-nzima {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inahitaji-service {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-mbovu {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-imeuzwa {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .filter-bar {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            font-size: 0.7rem;
            color: #666;
            margin-bottom: 0.2rem;
            display: block;
        }
        
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.8rem;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }
        
        .asset-image {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            background: #f0f0f0;
        }
        
        .dashboard-welcome {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-welcome h2 {
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .dashboard-welcome p {
            opacity: 0.9;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            .content-wrapper {
                padding: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="mobile-header">
    <button class="hamburger" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-logo">🏫 SSMS Tanzania</div>
    <div class="mobile-user">
        <i class="fas fa-boxes"></i>
        <span class="user-name-mobile"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Assets Officer'); ?></span>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🏫 SSMS Tanzania</div>
        <div class="sidebar-sub">Asset Management</div>
    </div>
    
    <div class="sidebar-user">
        <i class="fas fa-boxes"></i>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Assets Officer'); ?></div>
            <div class="sidebar-user-role">Assets Officer</div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="nav-divider">ASSET MANAGEMENT</div>
        
        <a href="register.php" class="nav-item">
            <i class="fas fa-plus-circle"></i>
            <span>Register Asset</span>
        </a>
        
        <a href="assets_list.php" class="nav-item">
            <i class="fas fa-boxes"></i>
            <span>All Assets</span>
        </a>
        
        <div class="nav-divider">ASSETS BY STATUS</div>
        
        <a href="assets_list.php?status=Nzima" class="nav-item">
            <i class="fas fa-check-circle" style="color: #28a745;"></i>
            <span>Nzima (Good)</span>
        </a>
        
        <a href="assets_list.php?status=Inahitaji Service" class="nav-item">
            <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
            <span>Needs Service</span>
        </a>
        
        <a href="assets_list.php?status=Mbovu" class="nav-item">
            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
            <span>Mbovu (Damaged)</span>
        </a>
        
        <div class="nav-divider">REPORTS</div>
        
        <a href="depreciation_report.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Depreciation Report</span>
        </a>
        
        <a href="maintenance_report.php" class="nav-item">
            <i class="fas fa-wrench"></i>
            <span>Maintenance Report</span>
        </a>
        
        <div class="nav-divider">SYSTEM</div>
        
        <a href="settings.php" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="../ACCOUNTANT/logout.php" class="nav-item logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<main class="main-content">
    <div class="content-wrapper">
        
        <div class="page-header">
            <h1><i class="fas fa-boxes"></i> <?php echo $page_title; ?></h1>
        </div>
        
        <script>
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (hamburgerBtn) {
                hamburgerBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('active');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                });
            }
            
            function checkScreenSize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            }
            
            window.addEventListener('resize', checkScreenSize);
            checkScreenSize();
        </script>