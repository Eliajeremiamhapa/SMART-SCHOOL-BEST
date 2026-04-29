<?php
// TEACHER/index.php
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

// Get teacher info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

// Get teacher's classes
$stmt = $pdo->prepare("SELECT tc.*, COUNT(s.id) as student_count 
    FROM teacher_classes tc
    LEFT JOIN students s ON s.class = tc.class AND s.is_active = 1
    WHERE tc.teacher_id = ? AND tc.academic_year = '2025'
    GROUP BY tc.id");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

// Get total students across all classes
$total_students = 0;
foreach ($classes as $class) {
    $total_students += $class['student_count'];
}

$page_title = "Teacher Dashboard";
include 'includes/teacher_header.php';
?>

<div class="container">
    <h1>👩‍🏫 Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-value"><?php echo count($classes); ?></div>
            <div class="stat-label">My Classes</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-value"><?php echo $total_students; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value">0</div>
            <div class="stat-label">Pending Marks</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-value">0</div>
            <div class="stat-label">Today's Attendance</div>
        </div>
    </div>
    
    <div class="two-columns">
        <!-- My Classes -->
        <div class="form-card">
            <h3>📚 My Classes</h3>
            <?php if (empty($classes)): ?>
                <p>No classes assigned yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Class</th><th>Students</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['class']); ?></small></td>
                                <td><?php echo $class['student_count']; ?></small></td>
                                <td>
                                    <a href="my_students.php?class=<?php echo urlencode($class['class']); ?>" class="btn-sm">View Students</a>
                                    <a href="marks.php?class=<?php echo urlencode($class['class']); ?>" class="btn-sm">Enter Marks</a>
                                    <a href="attendance.php?class=<?php echo urlencode($class['class']); ?>" class="btn-sm">Mark Attendance</a>
                                </small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="form-card">
            <h3>⚡ Quick Actions</h3>
            <div class="action-buttons" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <a href="my_students.php" class="btn btn-primary">👨‍🎓 View My Students</a>
                <a href="marks.php" class="btn btn-primary">✏️ Enter Exam Marks</a>
                <a href="attendance.php" class="btn btn-primary">📅 Mark Attendance</a>
                <a href="behavior.php" class="btn btn-primary">📊 Record Behavior</a>
                <a href="profile.php" class="btn btn-primary">👤 My Profile</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/teacher_footer.php'; ?>