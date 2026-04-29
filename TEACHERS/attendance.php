<?php
// TEACHERS/attendance.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') { 
    header('Location: ../ACCOUNTANT/login_fixed.php'); 
    exit(); 
}

$success = '';
$error = '';
$selected_class = $_GET['class'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_period = $_GET['period'] ?? '';

// Get teacher's classes
$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

// Get school settings to know attendance type
$stmt = $pdo->query("SELECT enable_period_attendance, school_type FROM school_settings LIMIT 1");
$settings = $stmt->fetch();
$enable_period_attendance = $settings['enable_period_attendance'] ?? 0;

// Get periods for secondary (if enabled)
$periods = ['Period 1', 'Period 2', 'Period 3', 'Period 4', 'Period 5', 'Period 6', 'Period 7', 'Period 8'];

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $class = $_POST['class'];
    $attendance_date = $_POST['attendance_date'];
    $period = $_POST['period'] ?? null;
    
    // Get students in this class
    $stmt = $pdo->prepare("SELECT id, school_level FROM students WHERE class = ? AND is_active = 1");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll();
    
    $count_saved = 0;
    
    foreach ($students as $student) {
        $status = $_POST['status_' . $student['id']] ?? 'present';
        $remarks = $_POST['remarks_' . $student['id']] ?? '';
        $school_level = $student['school_level'] ?? 'primary';
        
        try {
            // Check if attendance already exists
            if ($enable_period_attendance && $period) {
                // Period-wise attendance (Secondary)
                $stmt_check = $pdo->prepare("
                    SELECT id FROM attendance 
                    WHERE student_id = ? AND attendance_date = ? AND period = ?
                ");
                $stmt_check->execute([$student['id'], $attendance_date, $period]);
            } else {
                // Daily attendance (Primary)
                $stmt_check = $pdo->prepare("
                    SELECT id FROM attendance 
                    WHERE student_id = ? AND attendance_date = ?
                ");
                $stmt_check->execute([$student['id'], $attendance_date]);
            }
            
            if ($stmt_check->fetch()) {
                // Update existing
                if ($enable_period_attendance && $period) {
                    $stmt_update = $pdo->prepare("
                        UPDATE attendance 
                        SET status = ?, remarks = ?, marked_by = ?
                        WHERE student_id = ? AND attendance_date = ? AND period = ?
                    ");
                    $stmt_update->execute([$status, $remarks, $_SESSION['user_id'], $student['id'], $attendance_date, $period]);
                } else {
                    $stmt_update = $pdo->prepare("
                        UPDATE attendance 
                        SET status = ?, remarks = ?, marked_by = ?
                        WHERE student_id = ? AND attendance_date = ?
                    ");
                    $stmt_update->execute([$status, $remarks, $_SESSION['user_id'], $student['id'], $attendance_date]);
                }
            } else {
                // Insert new
                if ($enable_period_attendance && $period) {
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO attendance (student_id, attendance_date, period, status, remarks, school_level, marked_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_insert->execute([$student['id'], $attendance_date, $period, $status, $remarks, $school_level, $_SESSION['user_id']]);
                } else {
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO attendance (student_id, attendance_date, status, remarks, school_level, marked_by)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_insert->execute([$student['id'], $attendance_date, $status, $remarks, $school_level, $_SESSION['user_id']]);
                }
            }
            $count_saved++;
        } catch(Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if ($count_saved > 0) {
        $success = "✅ Attendance saved for $count_saved student(s) on " . date('d-m-Y', strtotime($attendance_date));
    }
}

// Get students for selected class
$students_list = [];
if ($selected_class) {
    $stmt = $pdo->prepare("
        SELECT id, student_number, full_name, school_level 
        FROM students 
        WHERE class = ? AND is_active = 1 
        ORDER BY full_name
    ");
    $stmt->execute([$selected_class]);
    $students_list = $stmt->fetchAll();
}

$page_title = "Mark Attendance";
include 'includes/teacher_header.php';
?>

<div class="container">
    <h1>📅 Mark Attendance</h1>
    
    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <!-- Class Selection -->
    <div class="form-card">
        <form method="GET" id="selectClassForm">
            <div class="two-columns">
                <div class="form-group">
                    <label>Select Class</label>
                    <select name="class" id="class_select" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['class']); ?>" <?php echo $selected_class == $c['class'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>">
                </div>
                <?php if ($enable_period_attendance): ?>
                <div class="form-group">
                    <label>Period (Secondary)</label>
                    <select name="period" class="form-control">
                        <option value="">-- Select Period --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?php echo $p; ?>" <?php echo $selected_period == $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Load Students</button>
        </form>
    </div>
    
    <!-- Attendance Form -->
    <?php if ($selected_class && !empty($students_list)): ?>
    <div class="form-card">
        <h3>📋 Attendance for <?php echo htmlspecialchars($selected_class); ?> - <?php echo date('d-m-Y', strtotime($selected_date)); ?></h3>
        <?php if ($enable_period_attendance && $selected_period): ?>
            <p><strong>Period:</strong> <?php echo htmlspecialchars($selected_period); ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="class" value="<?php echo htmlspecialchars($selected_class); ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
            <?php if ($enable_period_attendance): ?>
                <input type="hidden" name="period" value="<?php echo htmlspecialchars($selected_period); ?>">
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($students_list as $student): 
                            // Get current attendance status
                            if ($enable_period_attendance && $selected_period) {
                                $stmt = $pdo->prepare("
                                    SELECT status, remarks FROM attendance 
                                    WHERE student_id = ? AND attendance_date = ? AND period = ?
                                ");
                                $stmt->execute([$student['id'], $selected_date, $selected_period]);
                            } else {
                                $stmt = $pdo->prepare("
                                    SELECT status, remarks FROM attendance 
                                    WHERE student_id = ? AND attendance_date = ?
                                ");
                                $stmt->execute([$student['id'], $selected_date]);
                            }
                            $current = $stmt->fetch();
                            $current_status = $current['status'] ?? 'present';
                            $current_remarks = $current['remarks'] ?? '';
                            
                            $level_label = ($student['school_level'] == 'secondary') ? '🏛️ Secondary' : '🏫 Primary';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['student_number']); ?></small></td>
                            <td><?php echo $level_label; ?></small></td>
                            <td>
                                <select name="status_<?php echo $student['id']; ?>" class="form-control" style="width: auto;">
                                    <option value="present" <?php echo $current_status == 'present' ? 'selected' : ''; ?>>✅ Present</option>
                                    <option value="absent" <?php echo $current_status == 'absent' ? 'selected' : ''; ?>>❌ Absent</option>
                                    <option value="late" <?php echo $current_status == 'late' ? 'selected' : ''; ?>>⏰ Late</option>
                                </select>
                             </small></td>
                            <td>
                                <input type="text" name="remarks_<?php echo $student['id']; ?>" class="form-control" placeholder="Optional remarks" value="<?php echo htmlspecialchars($current_remarks); ?>" style="width: 150px;">
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons" style="margin-top: 1rem;">
                <button type="submit" name="save_attendance" class="btn btn-primary">💾 Save Attendance</button>
                <a href="attendance_report.php?class=<?php echo urlencode($selected_class); ?>" class="btn btn-info">📊 View Report</a>
            </div>
        </form>
    </div>
    <?php elseif ($selected_class && empty($students_list)): ?>
        <div class="alert alert-warning">No students found in <?php echo htmlspecialchars($selected_class); ?> class.</div>
    <?php endif; ?>
    
    <!-- Legend -->
    <div class="form-card">
        <h4>📌 Legend</h4>
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <span><span style="color:green;">✅ Present</span> - Student was present</span>
            <span><span style="color:red;">❌ Absent</span> - Student was absent</span>
            <span><span style="color:orange;">⏰ Late</span> - Student arrived late</span>
        </div>
        <?php if ($enable_period_attendance): ?>
        <hr>
        <p><strong>ℹ️ Note:</strong> Period-wise attendance is enabled for secondary school. Select a period to mark attendance for each class period.</p>
        <?php else: ?>
        <p><strong>ℹ️ Note:</strong> Daily attendance is enabled for primary school. Mark attendance once per day.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-submit when class is selected
document.getElementById('class_select').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('selectClassForm').submit();
    }
});
</script>

<?php include 'includes/teacher_footer.php'; ?>