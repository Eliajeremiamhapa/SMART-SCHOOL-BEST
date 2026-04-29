<?php
require_once 'config/database.php';
$page_title = "My Profile";
include 'includes/header.php';

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
        $error = "❌ Tafadhali jaza jina lako kamili na username!";
    } else {
        try {
            // Check if username already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $error = "❌ Username '{$username}' tayari inatumiwa na mtumiaji mwingine!";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $username, $email, $user_id]);
                
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $username;
                
                $success = "✅ Taarifa zako zimebadilishwa kikamilifu!";
            }
        } catch (PDOException $e) {
            $error = "❌ Kuna tatizo la kiufundi. Tafadhali wasiliana na msimamizi.";
        }
    }
}

// Handle password change - FIXED WITH VERIFICATION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "❌ Tafadhali jaza sehemu zote za nywila!";
    } elseif ($new_password !== $confirm_password) {
        $error = "❌ Nywila mpya na uthibitisho hazilingani!";
    } elseif (strlen($new_password) < 4) {
        $error = "❌ Nywila mpya lazima iwe na angalau herufi 4!";
    } else {
        // Verify current password
        $password_valid = false;
        
        // Method 1: Try normal password_verify (for hashed passwords)
        if (password_verify($current_password, $user['password'])) {
            $password_valid = true;
        }
        // Method 2: If password in database is plain text (direct comparison)
        elseif ($user['password'] == $current_password) {
            $password_valid = true;
        }
        // Method 3: Special case for default password 'accountant123'
        elseif ($current_password == 'accountant123') {
            $password_valid = true;
        }
        
        if ($password_valid) {
            // Hash the new password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password in database
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hashed_password, $user_id]);
            
            // VERIFY that the update actually worked
            if ($stmt->rowCount() > 0) {
                // Double check by fetching and verifying
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $updated_user = $stmt->fetch();
                
                if (password_verify($new_password, $updated_user['password'])) {
                    $success = "✅ Nywila yako imebadilishwa kikamilifu! Nywila mpya imehifadhiwa kwenye mfumo.";
                } else {
                    $error = "❌ Tatizo: Nywila haikuhifadhiwa vizuri. Tafadhali jaribu tena.";
                }
            } else {
                $error = "❌ Tatizo: Hakuna mabadiliko yaliyofanywa. Jaribu tena.";
            }
        } else {
            $error = "❌ Nywila yako ya sasa si sahihi! Jaribu 'accountant123' kama hujabadilisha.";
        }
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
        <!-- Profile Information -->
        <div class="form-card">
            <h3>📝 Taarifa za Msingi</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Jina Kamili *</label>
                    <input type="text" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Username (Jina la Kuingia) *</label>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>">
                    <small>Hili ndilo jina utakalotumia kuingia kwenye mfumo</small>
                </div>
                <div class="form-group">
                    <label>Barua Pepe (Email)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    <small>Kwa ajili ya taarifa na kurejesha nywila</small>
                </div>
                <div class="form-group">
                    <label>Nafasi (Role)</label>
                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled style="background:#f0f2f5;">
                    <small>Nafasi yako katika mfumo (haiwezi kubadilishwa)</small>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">💾 Hifadhi Mabadiliko</button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="form-card">
            <h3>🔒 Badilisha Nywila</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Nywila ya Sasa *</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="password" name="current_password" id="current_password" required style="flex:1;" placeholder="Ingiza nywila yako ya sasa">
                        <button type="button" onclick="togglePassword('current_password')" style="background: none; border: 1px solid #ddd; border-radius: 5px; padding: 0.5rem; cursor: pointer;">
                            <i class="fas fa-eye" id="eye_current"></i>
                        </button>
                    </div>
                    <small>Nywila ya default ni: <strong>accountant123</strong></small>
                </div>
                <div class="form-group">
                    <label>Nywila Mpya *</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="password" name="new_password" id="new_password" required style="flex:1;" placeholder="Angalau herufi 4">
                        <button type="button" onclick="togglePassword('new_password')" style="background: none; border: 1px solid #ddd; border-radius: 5px; padding: 0.5rem; cursor: pointer;">
                            <i class="fas fa-eye" id="eye_new"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Rudia Nywila Mpya *</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="password" name="confirm_password" id="confirm_password" required style="flex:1;" placeholder="Andika tena nywila mpya">
                        <button type="button" onclick="togglePassword('confirm_password')" style="background: none; border: 1px solid #ddd; border-radius: 5px; padding: 0.5rem; cursor: pointer;">
                            <i class="fas fa-eye" id="eye_confirm"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-warning">🔄 Badilisha Nywila</button>
            </form>
        </div>
    </div>
    
    <!-- Account Info -->
    <div class="form-card">
        <h3>ℹ️ Taarifa za Akaunti</h3>
        <table class="data-table">
            <tr><th>Imeundwa Tarehe</th><td><?php echo $user['created_at']; ?></td></tr>
            <tr><th>Mwisho Kuingia</th><td><?php echo $user['last_login'] ?? 'Hajawahi kuingia'; ?></td></tr>
            <tr><th>Hali ya Akaunti</th><td><?php echo $user['is_active'] ? '<span style="color:green;">✅ Inatumika</span>' : '<span style="color:red;">❌ Imefungwa</span>'; ?></td></tr>
        </table>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById('eye_' + fieldId);
    
    if (field.type === 'password') {
        field.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>

<style>
.two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
@media (max-width: 768px) {
    .two-columns {
        grid-template-columns: 1fr;
    }
}
.alert-success {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}
.btn-warning {
    background: #ffc107;
    color: #333;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>

<?php include 'includes/footer.php'; ?>