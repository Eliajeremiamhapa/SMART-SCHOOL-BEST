<?php
// STUDENTS/attendance.php
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

// Get student details
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

$student_level = $student['school_level'] ?? 'primary';
$page_title = "My Attendance";
include 'includes/student_header.php';

// Check if attendance table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'attendance'");
    $attendance_table_exists = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    $attendance_table_exists = false;
}

// Get attendance records
$attendance = [];
if ($attendance_table_exists) {
    $stmt = $pdo->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? 
        ORDER BY attendance_date DESC 
        LIMIT 30
    ");
    $stmt->execute([$student['id']]);
    $attendance = $stmt->fetchAll();
}

// Calculate statistics
$present = 0;
$absent = 0;
$late = 0;
foreach ($attendance as $a) {
    if ($a['status'] == 'present') $present++;
    elseif ($a['status'] == 'absent') $absent++;
    elseif ($a['status'] == 'late') $late++;
}
$total = $present + $absent + $late;
$attendance_rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
?>

<div class="container">
    <h1>📅 My Attendance Record</h1>
    
    <?php if (!$attendance_table_exists): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Attendance module is coming soon. Smart card integration will be available.
        </div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $attendance_rate; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $present; ?></div>
            <div class="stat-label">Days Present</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $absent; ?></div>
            <div class="stat-label">Days Absent</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $late; ?></div>
            <div class="stat-label">Days Late</div>
        </div>
    </div>
    
    <div class="form-card">
        <h3>📋 Recent Attendance Records</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <?php if ($student_level == 'secondary'): ?>
                        <th>Period</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$attendance_table_exists): ?>
                        <tr style="background-color: #f8f9fa;">
                            <td colspan="<?php echo ($student_level == 'secondary') ? '4' : '3'; ?>" style="text-align:center; color: #666;">
                                📌 Attendance tracking will start when smart card system is activated
                            </td>
                        </tr>
                    <?php elseif (empty($attendance)): ?>
                        <tr style="background-color: #f8f9fa;">
                            <td colspan="<?php echo ($student_level == 'secondary') ? '4' : '3'; ?>" style="text-align:center; color: #666;">
                                📌 No attendance records available yet
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $a): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($a['attendance_date'])); ?></td>
                            <?php if ($student_level == 'secondary'): ?>
                            <td><?php echo $a['period'] ?? '-'; ?></td>
                            <?php endif; ?>
                            <td>
                                <?php if ($a['status'] == 'present'): ?>
                                    <span style="color:green;">✅ Present</span>
                                <?php elseif ($a['status'] == 'absent'): ?>
                                    <span style="color:red;">❌ Absent</span>
                                <?php else: ?>
                                    <span style="color:orange;">⏰ Late</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $a['remarks'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="form-card">
        <h3>ℹ️ About Attendance Tracking</h3>
        <p>Attendance is recorded using smart cards (RFID/NFC) and QR codes. Students tap their cards at the entrance to mark attendance.</p>
        <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
            <li>✅ Present: Student checked in on time</li>
            <li>⏰ Late: Student arrived after the allowed time</li>
            <li>❌ Absent: Student was not present</li>
        </ul>
        <?php if ($student_level == 'secondary'): ?>
        <hr>
        <p><strong>🏛️ Secondary School:</strong> Attendance is recorded per period. Each subject/period has its own attendance record.</p>
        <?php else: ?>
        <hr>
        <p><strong>🏫 Primary School:</strong> Attendance is recorded once per day.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/student_footer.php'; ?>