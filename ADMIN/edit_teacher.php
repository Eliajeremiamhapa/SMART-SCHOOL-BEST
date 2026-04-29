<?php
// ADMIN/edit_teacher.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$user_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get teacher details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$user_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: teachers_list.php');
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$full_name, $username, $email, $phone, $is_active, $user_id]);
        $success = "✅ Teacher updated successfully!";
        
        // Refresh teacher data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

$page_title = "Edit Teacher";
include 'includes/admin_header.php';
?>

<div class="container">
    <h1>✏️ Edit Teacher</h1>
    
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
                    <label>Username *</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($teacher['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($teacher['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo $teacher['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="teachers_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>