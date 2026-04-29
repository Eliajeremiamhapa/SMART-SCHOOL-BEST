<?php
// TEACHER/my_students.php
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
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$selected_class = $_GET['class'] ?? '';

// Get teacher's classes
$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ? AND academic_year = '2025'");
$stmt->execute([$_SESSION['user_id']]);
$teacher_classes = $stmt->fetchAll();

// Get students in selected class
$students = [];
if ($selected_class) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? AND is_active = 1 ORDER BY full_name");
    $stmt->execute([$selected_class]);
    $students = $stmt->fetchAll();
}

$page_title = "My Students";
include 'includes/teacher_header.php';
?>

<div class="container">
    <h1>👨‍🎓 My Students</h1>
    
    <!-- Class Selection -->
    <div class="form-card">
        <h3>Select Class</h3>
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="flex: 1;">
                <label>Choose Class</label>
                <select name="class" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($teacher_classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class['class']); ?>" <?php echo $selected_class == $class['class'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    
    <?php if ($selected_class): ?>
        <div class="form-card">
            <h3>📋 Students in <?php echo htmlspecialchars($selected_class); ?></h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Parent Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5" style="text-align:center;">No students found in <?php echo htmlspecialchars($selected_class); ?></small></td>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_number']); ?></small></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></small></td>
                                <td><?php echo htmlspecialchars($student['parent_phone']); ?></small></td>
                                <td><?php echo $student['is_active'] ? '<span style="color:green;">✅ Active</span>' : '<span style="color:red;">❌ Inactive</span>'; ?></small></td>
                                <td>
                                    <a href="marks.php?student_id=<?php echo $student['id']; ?>" class="btn-sm">✏️ Marks</a>
                                    <a href="attendance.php?student_id=<?php echo $student['id']; ?>" class="btn-sm">📅 Attendance</a>
                                    <a href="behavior.php?student_id=<?php echo $student['id']; ?>" class="btn-sm">📊 Behavior</a>
                                </small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/teacher_footer.php'; ?>