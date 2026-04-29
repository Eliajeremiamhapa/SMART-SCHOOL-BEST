<?php
// ADMIN/teacher_subjects.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Assign Teacher Subjects";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Get all teachers
$teachers = $pdo->query("SELECT id, username, full_name FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY full_name")->fetchAll();

// Get all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY school_level, subject_name")->fetchAll();

// Get all classes
$classes = $pdo->query("SELECT DISTINCT class FROM students WHERE is_active = 1 UNION SELECT 'Form 1A' UNION SELECT 'Form 1B' UNION SELECT 'Form 2A' UNION SELECT 'Form 2B' UNION SELECT 'Form 3A' UNION SELECT 'Form 3B' UNION SELECT 'Form 4A' UNION SELECT 'Form 4B' UNION SELECT 'Standard 5' UNION SELECT 'Standard 6' UNION SELECT 'Standard 7'")->fetchAll();

// Handle assignment (using subject name instead of subject_id)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_subject'])) {
    $teacher_id = $_POST['teacher_id'];
    $subject_name = $_POST['subject_name'];
    $class = $_POST['class'];
    $academic_year = $_POST['academic_year'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject, class, academic_year) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $subject_name, $class, $academic_year]);
        $success = "✅ Subject assigned to teacher successfully!";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "❌ This teacher already teaches this subject in this class!";
        } else {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

// Handle removal
if (isset($_GET['remove_id'])) {
    $assignment_id = $_GET['remove_id'];
    $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $success = "✅ Assignment removed successfully!";
}

// Get all assignments
$assignments = $pdo->query("
    SELECT ts.*, u.full_name as teacher_name
    FROM teacher_subjects ts
    JOIN users u ON ts.teacher_id = u.id
    ORDER BY u.full_name, ts.class
")->fetchAll();
?>

<div class="container">
    <h1>👨‍🏫 Assign Teachers to Subjects</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Assignment Form -->
        <div class="form-card">
            <h3>➕ Assign Subject to Teacher</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Select Teacher</label>
                    <select name="teacher_id" required>
                        <option value="">-- Select Teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?> (<?php echo $t['username']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Subject</label>
                    <select name="subject_name" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub['subject_name']); ?>">
                                [<?php echo strtoupper($sub['school_level']); ?>] <?php echo htmlspecialchars($sub['subject_name']); ?> (<?php echo $sub['subject_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="class" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class']; ?>"><?php echo $c['class']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="2025" required>
                </div>
                <button type="submit" name="assign_subject" class="btn btn-primary">➕ Assign Subject</button>
            </form>
        </div>
        
        <!-- Current Assignments -->
        <div class="form-card">
            <h3>📋 Current Assignments</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="5" style="text-align:center;">No assignments yet</small></td>
                        <?php else: ?>
                            <?php foreach ($assignments as $ass): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ass['teacher_name']); ?></small></td>
                                <td><?php echo htmlspecialchars($ass['subject']); ?></small></td>
                                <td><?php echo $ass['class']; ?></small></td>
                                <td><?php echo $ass['academic_year']; ?></small></td>
                                <td>
                                    <a href="?remove_id=<?php echo $ass['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Remove this assignment?')">🗑️ Remove</a>
                                 </small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>