<?php
// STUDENTS/includes/student_header.php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Direct database connection
$host = 'localhost';
$dbname = 'accountant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Only student can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

// Get school settings for student label
$stmt = $pdo->query("SELECT school_type, default_grading_system FROM school_settings LIMIT 1");
$school_settings = $stmt->fetch();
$school_type = $school_settings['school_type'] ?? 'both';

// Get student details with new fields
$student = null;

// Method 1: Try by username (as student_number)
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->execute([$_SESSION['username']]);
$student = $stmt->fetch();

// Method 2: Try by full_name
if (!$student) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
    $stmt->execute([$_SESSION['full_name']]);
    $student = $stmt->fetch();
}

// Method 3: Create basic profile if still no student
if (!$student) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
    $stmt->execute([$_SESSION['username']]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, school_level, is_active) VALUES (?, ?, ?, ?, 'primary', 1)");
        $stmt->execute([$_SESSION['username'], $_SESSION['full_name'], 'Not Assigned', '']);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
    $stmt->execute([$_SESSION['username']]);
    $student = $stmt->fetch();
}

// Determine student level and label
$student_level = $student['school_level'] ?? 'primary';
$student_role_label = ($student_level == 'primary') ? 'Pupil' : 'Student';
$display_role = ($school_type == 'primary') ? 'Pupil' : (($school_type == 'secondary') ? 'Student' : $student_role_label);

// Make student data available globally
$student_id = $student['id'] ?? 0;
$student_number = $student['student_number'] ?? $_SESSION['username'];
$student_class = $student['class'] ?? 'Not Assigned';
$student_name = $student['full_name'] ?? $_SESSION['full_name'];
$prem_number = $student['prem_number'] ?? '';
$psle_number = $student['psle_number'] ?? '';
$index_number = $student['index_number'] ?? '';

$page_title = $page_title ?? 'Student Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($page_title); ?> - SSMS Tanzania</title>
    <!-- CSS path - from STUDENTS/includes/ to ACCOUNTANT/css/ -->
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
        .info-badge {
            background: #e8f4fd;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.7rem;
            color: #1e3c72;
        }
    </style>
</head>
<body>

<!-- Mobile Header -->
<div class="mobile-header">
    <button class="hamburger" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-logo">🏫 SSMS Tanzania</div>
    <div class="mobile-user">
        <i class="fas fa-user-graduate"></i>
        <span class="user-name-mobile"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?></span>
    </div>
</div>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🏫 SSMS Tanzania</div>
        <div class="sidebar-sub"><?php echo $display_role; ?> Portal</div>
    </div>
    
    <div class="sidebar-user">
        <i class="fas fa-user-graduate"></i>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($student_name); ?></div>
            <div class="sidebar-user-role"><?php echo $display_role; ?></div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="nav-divider">PERSONAL</div>
        
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        
        <div class="nav-divider">ACADEMICS</div>
        
        <a href="results.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>My Results</span>
        </a>
        <a href="attendance.php" class="nav-item">
            <i class="fas fa-calendar-check"></i>
            <span>Attendance</span>
        </a>
        <a href="certificates.php" class="nav-item">
            <i class="fas fa-certificate"></i>
            <span>Certificates</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- EXIT VIEW BUTTON - Only visible when Admin is viewing as student -->
        <?php if (isset($_SESSION['view_as_mode']) && $_SESSION['view_as_mode'] === true): ?>
            <a href="exit_view.php" class="nav-item" style="background: #dc3545; margin-bottom: 0.5rem;">
                <i class="fas fa-arrow-left"></i>
                <span>🔙 Return to Admin Panel</span>
            </a>
        <?php endif; ?>
        
        <!-- Logout -->
        <a href="../ACCOUNTANT/logout.php" class="nav-item logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Main Content -->
<main class="main-content" id="mainContent">