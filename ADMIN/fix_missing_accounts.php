<?php
// ADMIN/fix_missing_accounts.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Fix Missing Accounts";
include 'includes/admin_header.php';

$error = '';
$success = '';
$fixed_count = 0;

// Find students without user accounts
$stmt = $pdo->query("
    SELECT s.* 
    FROM students s
    LEFT JOIN users u ON u.username = s.student_number
    WHERE u.id IS NULL
");
$missing_users = $stmt->fetchAll();

// Fix missing accounts
if (isset($_POST['fix_accounts'])) {
    $students_to_fix = $_POST['student_ids'] ?? [];
    $default_password = 'password123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    foreach ($students_to_fix as $student_id) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if ($student) {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$student['student_number']]);
            if (!$stmt->fetch()) {
                // Create user account
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'student', 1)");
                $stmt->execute([$student['student_number'], $hashed_password, $student['full_name'], $student['email'] ?? '']);
                $fixed_count++;
            }
        }
    }
    
    if ($fixed_count > 0) {
        $success = "✅ Created $fixed_count user account(s) successfully! Default password: $default_password";
    } else {
        $error = "❌ No accounts were created. Please select at least one student.";
    }
    
    // Refresh the list
    $stmt = $pdo->query("
        SELECT s.* 
        FROM students s
        LEFT JOIN users u ON u.username = s.student_number
        WHERE u.id IS NULL
    ");
    $missing_users = $stmt->fetchAll();
}

// Fix single account
if (isset($_GET['fix_single'])) {
    $student_id = $_GET['fix_single'];
    $default_password = 'password123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if ($student) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'student', 1)");
        $stmt->execute([$student['student_number'], $hashed_password, $student['full_name'], $student['email'] ?? '']);
        $success = "✅ User account created for {$student['full_name']}! Username: {$student['student_number']}, Password: $default_password";
    }
    
    // Refresh the list
    $stmt = $pdo->query("
        SELECT s.* 
        FROM students s
        LEFT JOIN users u ON u.username = s.student_number
        WHERE u.id IS NULL
    ");
    $missing_users = $stmt->fetchAll();
}
?>

<div class="container">
    <h1>🔧 Fix Missing User Accounts</h1>
    
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Notice:</strong> The following students have profiles but no login accounts. This prevents them from accessing the system.
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <h3>📋 Students Without Login Accounts</h3>
        
        <?php if (empty($missing_users)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                All students have valid login accounts! No missing accounts found.
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select_all"></th>
                                <th>Student Number</th>
                                <th>Full Name</th>
                                <th>Class</th>
                                <th>Parent Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($missing_users as $student): ?>
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" class="student_checkbox"></td>
                                <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class']); ?></td>
                                <td><?php echo htmlspecialchars($student['parent_phone']); ?></td>
                                <td>
                                    <a href="?fix_single=<?php echo $student['id']; ?>" class="btn-sm" style="background:#28a745;" onclick="return confirm('Create login account for <?php echo htmlspecialchars($student['full_name']); ?>?')">
                                        🔧 Fix Single
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="action-buttons" style="margin-top: 1rem;">
                    <button type="submit" name="fix_accounts" class="btn btn-primary" onclick="return confirm('Create login accounts for all selected students? Default password will be: password123')">
                        🔧 Create Login Accounts for Selected
                    </button>
                    <a href="students_list.php" class="btn btn-secondary">View All Students</a>
                </div>
            </form>
            
            <div class="alert alert-info" style="margin-top: 1rem;">
                <i class="fas fa-info-circle"></i>
                <strong>Default Login Credentials:</strong><br>
                Username: Student Number (e.g., <?php echo htmlspecialchars($missing_users[0]['student_number'] ?? '7'); ?>)<br>
                Password: <strong>password123</strong>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('select_all')?.addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.student_checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php include 'includes/admin_footer.php'; ?>