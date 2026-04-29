<?php
// PARENTS/includes/parent_header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; 
$dbname = 'accountant'; 
$username = 'root'; 
$password = '';

try { 
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { 
    die("Database Error: " . $e->getMessage()); 
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = $page_title ?? 'Parent Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> - SSMS Tanzania</title>
    <!-- CSS path - same as admin (from includes folder to ACCOUNTANT/css/) -->
    <link rel="stylesheet" href="../ACCOUNTANT/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); text-align: center; }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: bold; color: #1e3c72; }
        .stat-card .stat-label { color: #666; margin-top: 0.5rem; }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 768px) { .two-columns { grid-template-columns: 1fr; } }
        .grade-A { color: #28a745; font-weight: bold; }
        .grade-B { color: #17a2b8; font-weight: bold; }
        .grade-C { color: #ffc107; font-weight: bold; }
        .grade-D { color: #fd7e14; font-weight: bold; }
        .grade-F { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

<div class="mobile-header">
    <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
    <div class="mobile-logo">🏫 SSMS Tanzania</div>
    <div class="mobile-user">
        <i class="fas fa-users"></i>
        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🏫 SSMS Tanzania</div>
        <div class="sidebar-sub">Parent Portal</div>
    </div>
    <div class="sidebar-user">
        <i class="fas fa-users"></i>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="sidebar-user-role">Parent</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <div class="nav-divider">MY CHILDREN</div>
        <a href="my_children.php" class="nav-item"><i class="fas fa-child"></i><span>My Children</span></a>
        <div class="nav-divider">SCHOOL</div>
        <a href="gallery.php" class="nav-item"><i class="fas fa-images"></i><span>School Gallery</span></a>
        <div class="nav-divider">ACCOUNT</div>
        <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i><span>My Profile</span></a>
        <div class="nav-divider"></div>
        <!-- Logout - same pattern as admin -->
        <a href="../ACCOUNTANT/logout.php" class="nav-item logout-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main class="main-content" id="mainContent">