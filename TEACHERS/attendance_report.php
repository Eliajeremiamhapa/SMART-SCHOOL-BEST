<?php
// TEACHERS/attendance_report.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') { 
    header('Location: ../ACCOUNTANT/login_fixed.php'); 
    exit(); 
}

$class = $_GET['class'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get students in class
$students = [];
$attendance_summary = [];

if ($class) {
    $stmt = $pdo->prepare("
        SELECT id, student_number, full_name, school_level 
        FROM students 
        WHERE class = ? AND is_active = 1 
        ORDER BY full_name
    ");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll();
    
    foreach ($students as $student) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                COUNT(*) as total
            FROM attendance 
            WHERE student_id = ? AND attendance_date BETWEEN ? AND ?
        ");
        $stmt->execute([$student['id'], $start_date, $end_date]);
        $stats = $stmt->fetch();
        
        $attendance_summary[$student['id']] = [
            'present' => $stats['present'] ?? 0,
            'absent' => $stats['absent'] ?? 0,
            'late' => $stats['late'] ?? 0,
            'total' => $stats['total'] ?? 0,
            'percentage' => ($stats['total'] > 0) ? round(($stats['present'] / $stats['total']) * 100, 1) : 0
        ];
    }
}

$page_title = "Attendance Report";
include 'includes/teacher_header.php';
?>

<div class="container">
    <h1>📊 Attendance Report</h1>
    
    <div class="form-card">
        <form method="GET">
            <div class="two-columns">
                <div class="form-group">
                    <label>Class</label>
                    <select name="class" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php
                        $stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $classes = $stmt->fetchAll();
                        foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class']; ?>" <?php echo $class == $c['class'] ? 'selected' : ''; ?>><?php echo $c['class']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
    </div>
    
    <?php if ($class && !empty($students)): ?>
    <div class="form-card">
        <h3>📋 Attendance Summary for <?php echo htmlspecialchars($class); ?></h3>
        <p>Period: <?php echo date('d-m-Y', strtotime($start_date)); ?> to <?php echo date('d-m-Y', strtotime($end_date)); ?></p>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Total Days</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($students as $student): 
                        $summary = $attendance_summary[$student['id']];
                        $percentage_class = $summary['percentage'] >= 80 ? 'success' : ($summary['percentage'] >= 60 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                        <td><span style="color:green;"><?php echo $summary['present']; ?></span></small></td>
                        <td><span style="color:red;"><?php echo $summary['absent']; ?></span></small></small></td>
                        <td><span style="color:orange;"><?php echo $summary['late']; ?></span></small></small></small></td>
                        <td><?php echo $summary['total']; ?></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></td>
                        <td>
                            <span class="status-badge status-<?php echo $percentage_class; ?>">
                                <?php echo $summary['percentage']; ?>%
                            </span>
                         </small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-info" style="margin-top: 1rem;">
            <strong>ℹ️ Attendance Key:</strong>
            <ul>
                <li><strong style="color:green;">≥ 80%</strong> - Good attendance</li>
                <li><strong style="color:orange;">60% - 79%</strong> - Moderate attendance (needs improvement)</li>
                <li><strong style="color:red;">&lt; 60%</strong> - Poor attendance (requires intervention)</li>
            </ul>
        </div>
    </div>
    <?php elseif ($class): ?>
        <div class="alert alert-warning">No students found in this class.</div>
    <?php endif; ?>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
}
.status-success {
    background: #d4edda;
    color: #155724;
}
.status-warning {
    background: #fff3cd;
    color: #856404;
}
.status-danger {
    background: #f8d7da;
    color: #721c24;
}
</style>

<?php include 'includes/teacher_footer.php'; ?>