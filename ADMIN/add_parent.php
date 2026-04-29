<?php
// ADMIN/add_parent.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$error = '';
$success = '';

// Get all active students
$students = $pdo->query("SELECT id, student_number, full_name, class FROM students WHERE is_active = 1 ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $student_id = $_POST['student_id'];
    $relationship = $_POST['relationship'];
    $password = 'password123'; // Default password
    
    // Validation
    if (empty($username) || empty($full_name) || empty($student_id)) {
        $error = "❌ Username, Full Name, and Student are required!";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                // User exists, check if already a parent
                if ($existing_user['role'] !== 'parent') {
                    throw new Exception("Username '{$username}' already exists but with role '{$existing_user['role']}'. Please use a different username.");
                }
                $parent_id = $existing_user['id'];
                $message = "Existing parent linked to student.";
            } else {
                // Create new parent user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role, is_active) VALUES (?, ?, ?, ?, ?, 'parent', 1)");
                $stmt->execute([$username, $hashed_password, $full_name, $email, $phone]);
                $parent_id = $pdo->lastInsertId();
                $message = "New parent created and linked to student.";
            }
            
            // Check if parent-student link already exists
            $stmt = $pdo->prepare("SELECT id FROM parent_students WHERE parent_id = ? AND student_id = ?");
            $stmt->execute([$parent_id, $student_id]);
            
            if ($stmt->fetch()) {
                // Update existing relationship
                $stmt = $pdo->prepare("UPDATE parent_students SET relationship = ?, is_primary = 1 WHERE parent_id = ? AND student_id = ?");
                $stmt->execute([$relationship, $parent_id, $student_id]);
                $message .= " Relationship updated.";
            } else {
                // Create new relationship
                $stmt = $pdo->prepare("INSERT INTO parent_students (parent_id, student_id, relationship, is_primary) VALUES (?, ?, ?, 1)");
                $stmt->execute([$parent_id, $student_id, $relationship]);
                $message .= " Student linked successfully.";
            }
            
            // Get student name for success message
            $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student_name = $stmt->fetchColumn();
            
            $pdo->commit();
            
            $success = "✅ Parent added successfully!<br>
                        Username: <strong>$username</strong><br>
                        Password: <strong>$password</strong><br>
                        Linked to: <strong>" . htmlspecialchars($student_name) . "</strong><br>
                        <small>$message</small>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

$page_title = "Add Parent";
include 'includes/admin_header.php';
?>

<div class="container">
    <h1>👪 Add Parent & Link to Student</h1>
    
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
                    <label>Parent Username *</label>
                    <input type="text" name="username" required placeholder="e.g., parent.bahati">
                    <small>Username must be unique</small>
                </div>
                <div class="form-group">
                    <label>Parent Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g., Bahati Parent">
                </div>
                <div class="form-group">
                    <label>Parent Email (Optional)</label>
                    <input type="email" name="email" placeholder="parent@example.com">
                </div>
                <div class="form-group">
                    <label>Parent Phone (Optional)</label>
                    <input type="tel" name="phone" placeholder="e.g., 0752643189">
                </div>
                <div class="form-group">
                    <label>Select Student *</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo $s['student_number'] . ' - ' . $s['full_name'] . ' (' . $s['class'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Relationship *</label>
                    <select name="relationship" required>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="guardian">Guardian</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Default password: <strong>password123</strong> (Parent can change after login)
            </div>
            <button type="submit" class="btn btn-primary">➕ Add Parent & Link to Student</button>
            <a href="users.php?role=parent" class="btn btn-secondary">View All Parents</a>
        </form>
    </div>
    
    <div class="form-card">
        <h3>📋 Existing Parents & Their Children</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Parent Name</th>
                        <th>Username</th>
                        <th>Child Name</th>
                        <th>Class</th>
                        <th>Relationship</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT u.full_name as parent_name, u.username, 
                               s.full_name as child_name, s.class, ps.relationship
                        FROM parent_students ps
                        JOIN users u ON ps.parent_id = u.id
                        JOIN students s ON ps.student_id = s.id
                        WHERE u.role = 'parent'
                        ORDER BY u.full_name
                    ");
                    $parents = $stmt->fetchAll();
                    ?>
                    <?php if (empty($parents)): ?>
                        <tr><td colspan="5" style="text-align:center;">No parents added yet</small></td>
                    <?php else: ?>
                        <?php foreach ($parents as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['parent_name']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['username']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['child_name']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['class']); ?></small></td>
                            <td><?php echo ucfirst($p['relationship']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>