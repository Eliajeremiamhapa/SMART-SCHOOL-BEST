<?php
// TEACHERS/my_subjects.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "My Subjects";
include 'includes/teacher_header.php';

// Get teacher's assigned subjects
$stmt = $pdo->prepare("
    SELECT ts.*, s.subject_name, s.subject_code, s.category
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject = s.subject_name
    WHERE ts.teacher_id = ? AND ts.academic_year = '2025'
    ORDER BY ts.class, s.subject_name
");
$stmt->execute([$_SESSION['user_id']]);
$subjects = $stmt->fetchAll();

// Group by class
$subjects_by_class = [];
foreach ($subjects as $sub) {
    $subjects_by_class[$sub['class']][] = $sub;
}
?>

<div class="container">
    <h1>📚 My Subjects</h1>
    
    <?php if (empty($subjects)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            No subjects assigned to you yet. Please contact the school administrator.
        </div>
    <?php else: ?>
        <?php foreach ($subjects_by_class as $class => $class_subjects): ?>
        <div class="form-card">
            <h3>📖 Class: <?php echo htmlspecialchars($class); ?></h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($class_subjects as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong></td>
                            <td><?php echo ucfirst($sub['category']); ?></td>
                            <td>
                                <a href="marks.php?subject=<?php echo urlencode($sub['subject_name']); ?>&class=<?php echo urlencode($class); ?>" class="btn-sm">✏️ Enter Marks</a>
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/teacher_footer.php'; ?>