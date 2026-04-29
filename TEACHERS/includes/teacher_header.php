<?php
// TEACHER/includes/teacher_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

// Get teacher info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

// Get teacher's classes
$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ? AND academic_year = '2025'");
$stmt->execute([$_SESSION['user_id']]);
$teacher_classes = $stmt->fetchAll();

$page_title = $page_title ?? 'Teacher Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> - SSMS Tanzania</title>
    <!-- CSS path - from TEACHER/includes/ to ACCOUNTANT/css/ -->
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
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
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
    <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
    <div class="mobile-logo">🏫 SSMS Tanzania</div>
    <div class="mobile-user">
        <i class="fas fa-chalkboard-user"></i>
        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🏫 SSMS Tanzania</div>
        <div class="sidebar-sub">Teacher Portal</div>
    </div>
    <div class="sidebar-user">
        <i class="fas fa-chalkboard-user"></i>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="sidebar-user-role">Teacher</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="index.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- MY STUDENTS -->
        <div class="nav-divider">MY STUDENTS</div>
        <a href="my_students.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>My Students</span>
        </a>
        
        <!-- MY SUBJECTS -->
        <div class="nav-divider">MY SUBJECTS</div>
        <a href="my_subjects.php" class="nav-item">
            <i class="fas fa-book-open"></i>
            <span>My Subjects</span>
        </a>
        
        <!-- ACADEMICS -->
        <div class="nav-divider">ACADEMICS</div>
        <a href="marks.php" class="nav-item">
            <i class="fas fa-pen"></i>
            <span>Enter Marks</span>
        </a>
        <a href="ca_marks.php" class="nav-item">
            <i class="fas fa-pen-alt"></i>
            <span>CA Marks</span>
        </a>
        
        <!-- ATTENDANCE -->
        <div class="nav-divider">ATTENDANCE</div>
        <a href="attendance.php" class="nav-item">
            <i class="fas fa-calendar-check"></i>
            <span>Mark Attendance</span>
        </a>
        <a href="attendance_report.php" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>Attendance Report</span>
        </a>
        
        <!-- REPORTS -->
        <div class="nav-divider">REPORTS</div>
        <a href="class_results.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Class Results</span>
        </a>
        <a href="behavior.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Behavior</span>
        </a>
        
        <!-- ACCOUNT -->
        <div class="nav-divider">ACCOUNT</div>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- Logout -->
        <a href="../ACCOUNTANT/logout.php" class="nav-item logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main class="main-content" id="mainContent">