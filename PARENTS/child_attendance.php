<?php
// PARENTS/child_attendance.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$student_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT s.* FROM parent_students ps JOIN students s ON ps.student_id = s.id WHERE ps.parent_id = ? AND s.id = ?");
$stmt->execute([$_SESSION['user_id'], $student_id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 30");
$stmt->execute([$student_id]);
$attendance = $stmt->fetchAll();

$present = $absent = $late = 0;
foreach ($attendance as $a) {
    if ($a['status'] == 'present') $present++;
    elseif ($a['status'] == 'absent') $absent++;
    elseif ($a['status'] == 'late') $late++;
}
$total = $present + $absent + $late;
$rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

$page_title = "Attendance - " . $student['full_name'];
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>📅 Attendance: <?php echo htmlspecialchars($student['full_name']); ?></h1>
    
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value"><?php echo $rate; ?>%</div><div class="stat-label">Attendance Rate</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $present; ?></div><div class="stat-label">Present</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $absent; ?></div><div class="stat-label">Absent</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $late; ?></div><div class="stat-label">Late</div></div>
    </div>
    
    <div class="form-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Status</th><th>Check In</th><th>Remarks</th></tr></thead>
                <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="4" style="text-align:center;">No attendance records available</small></td>
                    <?php else: ?>
                        <?php foreach ($attendance as $a): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($a['attendance_date'])); ?></small></td>
                            <td><?php echo $a['status'] == 'present' ? '<span style="color:green;">✅ Present</span>' : ($a['status'] == 'absent' ? '<span style="color:red;">❌ Absent</span>' : '<span style="color:orange;">⏰ Late</span>'); ?></small></td>
                            <td><?php echo $a['check_in'] ?? '-'; ?></small></td>
                            <td><?php echo $a['remarks'] ?? '-'; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/parent_footer.php'; ?>