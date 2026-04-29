<?php
// ADMIN/users.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "User Management";
include 'includes/admin_header.php';

$error = '';
$success = '';
$edit_user = null;

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $role = $_POST['role'];
    
    if (empty($username) || empty($full_name) || empty($role)) {
        $error = "❌ Please fill all required fields";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$username, $password, $full_name, $email, $role]);
            $user_id = $pdo->lastInsertId();
            $success = "✅ User added successfully! Password: " . $_POST['password'];
            
            // If student role, also create student profile
            if ($role == 'student') {
                $student_number = 'STU' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$student_number, $full_name, 'Not Assigned', '']);
                $success .= " Student profile created with ID: $student_number";
            }
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'Created User', "Created new $role account: $username", $_SERVER['REMOTE_ADDR']]);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "❌ Username already exists!";
            } else {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $role, $is_active, $user_id]);
        
        // Update student profile if role is student
        if ($role == 'student') {
            $class = $_POST['class'] ?? 'Not Assigned';
            $parent_phone = $_POST['parent_phone'] ?? '';
            $student_number = $_POST['student_number'] ?? '';
            
            // Check if student profile exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ? OR full_name = ?");
            $stmt->execute([$student_number, $full_name]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE students SET full_name = ?, class = ?, parent_phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $class, $parent_phone, $existing['id']]);
            } else {
                $student_number = $student_number ?: 'STU' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$student_number, $full_name, $class, $parent_phone, $is_active]);
            }
        }
        
        $pdo->commit();
        $success = "✅ User updated successfully!";
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'Updated User', "Updated user ID: $user_id", $_SERVER['REMOTE_ADDR']]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle Reset Password
if (isset($_GET['reset_password'])) {
    $user_id = $_GET['reset_password'];
    $new_password = 'password123';
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ?, login_attempts = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);
    $success = "✅ Password reset to 'password123' for user ID: $user_id";
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $user_id = $_GET['delete_id'];
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "❌ You cannot delete your own account!";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Delete student profile if exists
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if ($user_data) {
                $stmt = $pdo->prepare("DELETE FROM students WHERE full_name = ?");
                $stmt->execute([$user_data['full_name']]);
            }
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            $success = "✅ User deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "❌ Cannot delete user with existing records: " . $e->getMessage();
        }
    }
}

// Handle Unlock Account
if (isset($_GET['unlock_id'])) {
    $user_id = $_GET['unlock_id'];
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
    $success = "✅ Account unlocked successfully!";
}

// Get edit user data
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
    
    // Get student details if role is student
    if ($edit_user && $edit_user['role'] == 'student') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
        $stmt->execute([$edit_user['full_name']]);
        $student_details = $stmt->fetch();
    }
}

// Get all users
$role_filter = $_GET['role'] ?? '';
$sql = "SELECT * FROM users ORDER BY created_at DESC";
if ($role_filter) {
    $sql = "SELECT * FROM users WHERE role = '$role_filter' ORDER BY created_at DESC";
}
$users = $pdo->query($sql)->fetchAll();

// Get student details separately
foreach ($users as &$user) {
    if ($user['role'] == 'student') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
        $stmt->execute([$user['full_name']]);
        $student = $stmt->fetch();
        
        if ($student) {
            $user['student_class'] = $student['class'] ?? 'Not Assigned';
            $user['student_parent_phone'] = $student['parent_phone'] ?? 'Not set';
            $user['student_number'] = $student['student_number'] ?? 'Not set';
        } else {
            $user['student_class'] = 'Not Assigned';
            $user['student_parent_phone'] = 'Not set';
            $user['student_number'] = 'Not set';
        }
    }
}

// Get statistics
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$role_counts = $stmt->fetchAll();

// Get classes for dropdown
$classes = ['Form 1A', 'Form 1B', 'Form 2A', 'Form 2B', 'Form 3A', 'Form 3B', 'Form 4A', 'Form 4B', 'Standard 5', 'Standard 6', 'Standard 7'];
?>

<div class="container">
    <h1>👥 User Management</h1>
    
    <!-- Add Student Button -->
    <div style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="add_student.php" class="btn btn-primary" style="background: #28a745;">
            <i class="fas fa-user-graduate"></i> + Add New Student
        </a>
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i> Refresh
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Role Summary -->
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <?php foreach ($role_counts as $rc): ?>
        <div class="stat-card">
            <div class="stat-value"><?php echo $rc['count']; ?></div>
            <div class="stat-label"><?php echo ucfirst($rc['role']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="two-columns">
        <!-- Add User Form -->
        <div class="form-card">
            <h3>➕ Add New User</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required placeholder="e.g., teacher_juma">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" value="password123" placeholder="Default: password123">
                    <small>Default password: password123 (user can change later)</small>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g., Juma Hassan">
                </div>
                <div class="form-group">
                    <label>Email (Optional)</label>
                    <input type="email" name="email" placeholder="email@example.com">
                    <small>Email is optional</small>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="">-- Select Role --</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="accountant">Accountant</option>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="student">Student</option>
                        <option value="store_keeper">🏢 Assets Officer (Store Keeper)</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">➕ Add User</button>
            </form>
        </div>
        
        <!-- Edit User Form -->
        <?php if ($edit_user): ?>
        <div class="form-card">
            <h3>✏️ Edit User</h3>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($edit_user['username']); ?>" disabled style="background:#f0f2f5;">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (Optional)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role">
                        <option value="super_admin" <?php echo $edit_user['role'] == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                        <option value="accountant" <?php echo $edit_user['role'] == 'accountant' ? 'selected' : ''; ?>>Accountant</option>
                        <option value="teacher" <?php echo $edit_user['role'] == 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                        <option value="parent" <?php echo $edit_user['role'] == 'parent' ? 'selected' : ''; ?>>Parent</option>
                        <option value="student" <?php echo $edit_user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                        <option value="store_keeper" <?php echo $edit_user['role'] == 'store_keeper' ? 'selected' : ''; ?>>🏢 Assets Officer (Store Keeper)</option>
                    </select>
                </div>
                
                <!-- Student specific fields -->
                <div id="student_fields" style="display: <?php echo $edit_user['role'] == 'student' ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label>Student Number</label>
                        <input type="text" name="student_number" value="<?php echo htmlspecialchars($student_details['student_number'] ?? ''); ?>" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c; ?>" <?php echo ($student_details['class'] ?? '') == $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="tel" name="parent_phone" value="<?php echo htmlspecialchars($student_details['parent_phone'] ?? ''); ?>" placeholder="e.g., 0712345678">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo $edit_user['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
                <button type="submit" name="edit_user" class="btn btn-primary">💾 Save Changes</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Users List -->
    <div class="form-card">
        <h3>📋 All Users</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></small></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></small></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></small></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></small></small></td>
                        <td>
                            <span class="status-badge status-active">
                                <?php 
                                if ($user['role'] == 'store_keeper') {
                                    echo '🏢 Assets Officer';
                                } else {
                                    echo ucfirst($user['role']);
                                }
                                ?>
                            </span>
                         </small></small></td>
                        <td>
                            <?php if ($user['role'] == 'student'): ?>
                                <small style="display:block;">
                                    📚 Class: <?php echo htmlspecialchars($user['student_class'] ?? 'Not set'); ?><br>
                                    📞 Parent: <?php echo htmlspecialchars($user['student_parent_phone'] ?? 'Not set'); ?><br>
                                    🆔 ID: <?php echo htmlspecialchars($user['student_number'] ?? 'Not set'); ?>
                                </small>
                            <?php elseif ($user['role'] == 'store_keeper'): ?>
                                <small style="color:#17a2b8;">🏢 Asset Management Officer</small>
                            <?php else: ?>
                                <small style="color:#999;">-</small>
                            <?php endif; ?>
                         </small></small></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                            <?php if ($user['login_attempts'] >= 5): ?>
                                <br><small class="status-badge status-pending">Locked</small>
                            <?php endif; ?>
                         </small></small></td>
                        <td><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></small></td>
                        <td>
                            <a href="?edit_id=<?php echo $user['id']; ?>" class="btn-sm">✏️ Edit</a>
                            <a href="?reset_password=<?php echo $user['id']; ?>" class="btn-sm" onclick="return confirm('Reset password to default?')">🔑 Reset</a>
                            <?php if ($user['login_attempts'] >= 5): ?>
                                <a href="?unlock_id=<?php echo $user['id']; ?>" class="btn-sm" style="background:#28a745;">🔓 Unlock</a>
                            <?php endif; ?>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete_id=<?php echo $user['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this user?')">🗑️ Delete</a>
                            <?php endif; ?>
                            <?php if ($user['role'] == 'student'): ?>
                                <a href="../STUDENTS/view_as.php?id=<?php echo $user['id']; ?>" class="btn-sm" style="background:#17a2b8;" target="_blank">👤 View as Student</a>
                            <?php endif; ?>
                            <?php if ($user['role'] == 'store_keeper'): ?>
                                <a href="../ASSETS_OFFICER/dashboard.php?user_id=<?php echo $user['id']; ?>" class="btn-sm" style="background:#6c757d;" target="_blank">🏢 Login as Assets Officer</a>
                            <?php endif; ?>
                         </small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Show/hide student fields based on role selection
var roleSelect = document.getElementById('edit_role');
if (roleSelect) {
    roleSelect.addEventListener('change', function() {
        var studentFields = document.getElementById('student_fields');
        if (this.value === 'student') {
            studentFields.style.display = 'block';
        } else {
            studentFields.style.display = 'none';
        }
    });
}
</script>

<?php include 'includes/admin_footer.php'; ?>