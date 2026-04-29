<?php
// ADMIN/edit_student.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$student_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students_list.php');
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_number = trim($_POST['student_number']);
    $full_name = trim($_POST['full_name']);
    $class = trim($_POST['class']);
    $parent_phone = trim($_POST['parent_phone']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE students SET student_number = ?, full_name = ?, class = ?, parent_phone = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$student_number, $full_name, $class, $parent_phone, $is_active, $student_id]);
        
        // Also update user account username
        $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ? WHERE username = ?");
        $stmt->execute([$student_number, $full_name, $student['student_number']]);
        
        $success = "✅ Student updated successfully!";
        
        // Refresh student data
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

$page_title = "Edit Student";
include 'includes/admin_header.php';
?>

<div class="container">
    <h1>✏️ Edit Student</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST">
            <div class="two-columns">
                <div class="form-group">
                    <label>Student Number *</label>
                    <input type="text" name="student_number" value="<?php echo htmlspecialchars($student['student_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Class *</label>
                    <input type="text" name="class" value="<?php echo htmlspecialchars($student['class']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Parent Phone *</label>
                    <input type="tel" name="parent_phone" value="<?php echo htmlspecialchars($student['parent_phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo $student['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="students_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>