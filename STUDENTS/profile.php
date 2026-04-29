<?php
// STUDENTS/profile.php
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
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

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

// Try by student_number (username)
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->execute([$_SESSION['username']]);
$student = $stmt->fetch();

// Try by full_name
if (!$student) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
    $stmt->execute([$_SESSION['full_name']]);
    $student = $stmt->fetch();
}

// If still not found, create basic record
if (!$student) {
    $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, school_level, is_active) VALUES (?, ?, ?, ?, 'primary', 1)");
    $stmt->execute([$_SESSION['username'], $_SESSION['full_name'], 'Not Assigned', '']);
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
    $stmt->execute([$_SESSION['username']]);
    $student = $stmt->fetch();
}

// Determine student level and label
$student_level = $student['school_level'] ?? 'primary';
$student_role_label = ($student_level == 'primary') ? 'Pupil' : 'Student';

$page_title = "My Profile";
include 'includes/student_header.php';
?>

<div class="container">
    <h1>👤 My Profile</h1>
    
    <div class="two-columns">
        <!-- Profile Information -->
        <div class="form-card">
            <div style="text-align: center;">
                <div style="width: 100px; height: 100px; background: #1e3c72; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                    <i class="fas fa-user-graduate" style="font-size: 3rem; color: white;"></i>
                </div>
                <span class="info-badge"><?php echo $student_role_label; ?></span>
            </div>
            
            <h3>📝 Personal Information</h3>
            <table class="data-table">
                <tr><th><?php echo ($student_level == 'primary') ? 'Pupil ID' : 'Student ID'; ?></th><td><?php echo htmlspecialchars($student['student_number']); ?></small></td>
                <tr><th>Full Name</th><td><?php echo htmlspecialchars($student['full_name']); ?></small></small></td>
                <tr><th>Class</th><td><?php echo htmlspecialchars($student['class']); ?></small></small></td>
                <tr><th>School Level</th><td><?php echo ($student_level == 'primary') ? '🏫 Primary School' : '🏛️ Secondary School'; ?></small></small></td>
                <tr><th>Parent Phone</th><td><?php echo htmlspecialchars($student['parent_phone']); ?></small></small></td>
                <tr><th>Status</th><td><?php echo $student['is_active'] ? '<span style="color:green;">✅ Active</span>' : '<span style="color:red;">❌ Inactive</span>'; ?></small></small></td>
                <tr><th>Registered Date</th><td><?php echo date('d-m-Y', strtotime($student['created_at'])); ?></small></small></td>
            </table>
        </div>
        
        <!-- Account Information -->
        <div class="form-card">
            <h3>🔐 Account Information</h3>
            <table class="data-table">
                <tr><th>Username</th><td><?php echo htmlspecialchars($_SESSION['username']); ?></small></small></td>
                <tr><th>Role</th><td><?php echo ucfirst($_SESSION['role']); ?></small></small></td>
                <tr><th>Account Status</th><td><span class="status-badge status-active">Active</span></small></small></td>
            </table>
            
            <h3 style="margin-top: 1.5rem;">📱 Smart Card Information</h3>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM smart_cards WHERE student_id = ? AND is_active = 1");
            $stmt->execute([$student['id']]);
            $card = $stmt->fetch();
            ?>
            <table class="data-table">
                <tr><th>Card UID</th><td><?php echo $card ? htmlspecialchars($card['card_uid']) : 'No card assigned'; ?></small></small></td>
                <tr><th>Payment Reference</th><td><?php echo $card ? htmlspecialchars($card['payment_reference']) : 'N/A'; ?></small></small></td>
                <tr><th>Card Balance</th><td><?php echo $card ? 'TZS ' . number_format($card['balance']) : 'N/A'; ?></small></small></td>
                <tr><th>Card Status</th><td><?php echo $card ? '<span style="color:green;">✅ Active</span>' : '<span style="color:orange;">⚠️ Not Issued</span>'; ?></small></small></td>
            </table>
        </div>
    </div>
    
    <!-- Identification Numbers Section (PREM, PSLE, Index) -->
    <div class="form-card">
        <h3>🆔 Identification Numbers</h3>
        <div class="two-columns">
            <div class="form-group">
                <label>PREM Number</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['prem_number'] ?? 'Not assigned'); ?>" readonly disabled style="background:#f8f9fa;">
                </div>
                <small>Primary Record Education Management - Unique ID for life</small>
            </div>
            
            <?php if ($student_level == 'secondary'): ?>
            <div class="form-group">
                <label>PSLE Number</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['psle_number'] ?? 'Not assigned'); ?>" readonly disabled style="background:#f8f9fa;">
                </div>
                <small>Primary School Leaving Examination Number</small>
            </div>
            
            <div class="form-group">
                <label>NECTA Index Number</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['index_number'] ?? 'Not assigned'); ?>" readonly disabled style="background:#f8f9fa;">
                </div>
                <small>National Examination Council Index Number</small>
            </div>
            <?php endif; ?>
        </div>
        <p class="alert alert-info" style="margin-top: 1rem;">
            <i class="fas fa-info-circle"></i> 
            <strong>About PREM Number:</strong> This is a unique identification number assigned to every student in Tanzania from pre-primary through secondary education. It remains the same even if the student transfers schools.
        </p>
    </div>
    
    <!-- Parent Information -->
    <div class="form-card">
        <h3>👪 Parent / Guardian Information</h3>
        <table class="data-table">
            <tr><th>Parent Phone</th><td><?php echo htmlspecialchars($student['parent_phone']); ?></small></small></td>
            <tr><th>Emergency Contact</th><td><?php echo htmlspecialchars($student['parent_phone']); ?></small></small></td>
        </table>
        <p class="alert alert-info" style="margin-top: 1rem;">
            <i class="fas fa-info-circle"></i> For any changes to your profile information, please contact the school administration.
        </p>
    </div>
</div>

<style>
.info-badge {
    display: inline-block;
    background: #1e3c72;
    color: white;
    padding: 0.2rem 0.8rem;
    border-radius: 20px;
    font-size: 0.7rem;
    margin-top: 0.5rem;
}
.input-group {
    margin-bottom: 0.5rem;
}
.form-control[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
</style>

<?php include 'includes/student_footer.php'; ?>