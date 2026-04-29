<?php
// ADMIN/profile.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$page_title = "My Profile";
include 'includes/admin_header.php';

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    if (empty($full_name) || empty($username)) {
        $error = "❌ Please fill all required fields!";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $username, $email, $user_id]);
        $_SESSION['full_name'] = $full_name;
        $_SESSION['username'] = $username;
        $success = "✅ Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "❌ Please fill all password fields!";
    } elseif ($new_password !== $confirm_password) {
        $error = "❌ New password and confirmation do not match!";
    } elseif (strlen($new_password) < 4) {
        $error = "❌ New password must be at least 4 characters!";
    } elseif (!password_verify($current_password, $user['password']) && $current_password != $user['password']) {
        $error = "❌ Current password is incorrect!";
    } else {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hashed, $user_id]);
        $success = "✅ Password changed successfully!";
    }
}
?>

<div class="container">
    <h1>👤 My Profile</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <div class="form-card">
            <h3>📝 Profile Information</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled style="background:#f0f2f5;">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
            </form>
        </div>
        
        <div class="form-card">
            <h3>🔒 Change Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-warning">🔄 Change Password</button>
            </form>
        </div>
    </div>
    
    <