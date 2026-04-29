<?php
// ADMIN/includes/admin_header.php - DIRECT DATABASE CONNECTION

// Direct database connection (no external file)
$host = 'localhost';
$dbname = 'accountant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only super_admin can access admin folder
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = $page_title ?? 'Super Admin Dashboard';
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1e3c72;
        }
        .stat-card .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .btn-sm {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            font-size: 0.7rem;
            border-radius: 4px;
            text-decoration: none;
            background: #667eea;
            color: white;
            margin: 0.1rem;
        }
        .btn-sm:hover {
            background: #5a67d8;
        }
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .form-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: #1e3c72;
            color: white;
        }
        .btn-primary:hover {
            background: #2a5298;
        }
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
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
        <i class="fas fa-user-cog"></i>
        <span class="user-name-mobile"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Super Admin'); ?></span>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🏫 SSMS Tanzania</div>
        <div class="sidebar-sub">Super Admin Panel</div>
    </div>
    
    <div class="sidebar-user">
        <i class="fas fa-user-cog"></i>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Super Admin'); ?></div>
            <div class="sidebar-user-role">Super Administrator</div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="nav-divider">USER MANAGEMENT</div>
        
        <a href="users.php" class="nav-item">
            <i class="fas fa-users-cog"></i>
            <span>Manage Users</span>
        </a>
        
        <a href="add_parent.php" class="nav-item">
            <i class="fas fa-user-plus"></i>
            <span>Add Parent</span>
        </a>
        
        <div class="nav-divider">USER LISTS</div>
        
        <a href="students_list.php" class="nav-item">
            <i class="fas fa-user-graduate"></i>
            <span>Students List</span>
        </a>
        
        <a href="teachers_list.php" class="nav-item">
            <i class="fas fa-chalkboard-user"></i>
            <span>Teachers List</span>
        </a>
        
        <a href="parents_list.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Parents List</span>
        </a>
        
        <a href="accountants_list.php" class="nav-item">
            <i class="fas fa-calculator"></i>
            <span>Accountants List</span>
        </a>
        
        <!-- STORE KEEPERS LIST - NEW LINK ADDED -->
        <a href="store_keepers_list.php" class="nav-item">
            <i class="fas fa-boxes"></i>
            <span>Store Keepers List</span>
        </a>
        
        <!-- FIX ACCOUNTS -->
        <div class="nav-divider">MAINTENANCE</div>
        
        <a href="fix_missing_accounts.php" class="nav-item">
            <i class="fas fa-tools"></i>
            <span>Fix Accounts</span>
        </a>
        
        <!-- ACADEMIC SETUP - NEW SECTION -->
        <div class="nav-divider">ACADEMIC SETUP</div>
        
        <a href="subjects.php" class="nav-item">
            <i class="fas fa-book"></i>
            <span>Manage Subjects</span>
        </a>
        
        <a href="teacher_subjects.php" class="nav-item">
            <i class="fas fa-chalkboard-user"></i>
            <span>Assign Subjects</span>
        </a>
        
        <div class="nav-divider">SCHOOL SETUP</div>
        
        <a href="school_settings.php" class="nav-item">
            <i class="fas fa-school"></i>
            <span>School Settings</span>
        </a>
        <a href="grading_system.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Grading System</span>
        </a>
        <a href="fee_structure.php" class="nav-item">
            <i class="fas fa-money-bill-wave"></i>
            <span>Fee Structure</span>
        </a>
        <a href="calendar.php" class="nav-item">
            <i class="fas fa-calendar-alt"></i>
            <span>School Calendar</span>
        </a>
        
        <div class="nav-divider">CONTENT</div>
        
        <a href="gallery.php" class="nav-item">
            <i class="fas fa-images"></i>
            <span>Gallery</span>
        </a>
        
        <div class="nav-divider">SYSTEM</div>
        
        <a href="system_logs.php" class="nav-item">
            <i class="fas fa-history"></i>
            <span>System Logs</span>
        </a>
        <a href="backup.php" class="nav-item">
            <i class="fas fa-database"></i>
            <span>Backup</span>
        </a>
        
        <div class="nav-divider">ACCOUNT</div>
        
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="../ACCOUNTANT/logout.php" class="nav-item logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main class="main-content" id="mainContent">