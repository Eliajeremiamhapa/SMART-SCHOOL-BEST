<?php
// ADMIN/edit_parent.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$user_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get parent details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'parent'");
$stmt->execute([$user_id]);
$parent = $stmt->fetch();

if (!$parent) {
    header('Location: parents_list.php');
    exit();
}

// Get parent's children
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.class, ps.relationship
    FROM parent_students ps
    JOIN students s ON ps.student_id = s.id
    WHERE ps.parent_id = ?
");
$stmt->execute([$user_id]);
$children = $stmt->fetchAll();

// Get all students for dropdown
$students = $pdo->query("SELECT id, full_name, class, student_number FROM students WHERE is_active = 1 ORDER BY full_name")->fetchAll();

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
        $success = "✅ Parent updated successfully!";
        
        // Refresh parent data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $parent = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle add child link
if (isset($_POST['add_child'])) {
    $student_id = $_POST['student_id'];
    $relationship = $_POST['relationship'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO parent_students (parent_id, student_id, relationship, is_primary) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE relationship = ?");
        $stmt->execute([$user_id, $student_id, $relationship, $relationship]);
        $success = "✅ Child linked successfully!";
        
        // Refresh children list
        $stmt = $pdo->prepare("
            SELECT s.id, s.full_name, s.class, ps.relationship
            FROM parent_students ps
            JOIN students s ON ps.student_id = s.id
            WHERE ps.parent_id = ?
        ");
        $stmt->execute([$user_id]);
        $children = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle remove child
if (isset($_GET['remove_child'])) {
    $student_id = $_GET['remove_child'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM parent_students WHERE parent_id = ? AND student_id = ?");
        $stmt->execute([$user_id, $student_id]);
        $success = "✅ Child removed successfully!";
        
        // Refresh children list
        $stmt = $pdo->prepare("
            SELECT s.id, s.full_name, s.class, ps.relationship
            FROM parent_students ps
            JOIN students s ON ps.student_id = s.id
            WHERE ps.parent_id = ?
        ");
        $stmt->execute([$user_id]);
        $children = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

$page_title = "Edit Parent";
include 'includes/admin_header.php';
?>

<div class="container">
    <h1>✏️ Edit Parent</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Parent Information -->
        <div class="form-card">
            <h3>📝 Parent Information</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($parent['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($parent['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($parent['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($parent['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo $parent['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="parents_list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        
        <!-- Link Child -->
        <div class="form-card">
            <h3>👶 Link Child to Parent</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['student_number'] . ' - ' . $s['full_name'] . ' (' . $s['class'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Relationship</label>
                    <select name="relationship" required>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="guardian">Guardian</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <button type="submit" name="add_child" class="btn btn-primary">➕ Link Child</button>
            </form>
        </div>
    </div>
    
    <!-- Linked Children -->
    <div class="form-card">
        <h3>👨‍👩‍👧‍👦 Linked Children</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Student ID</th>
                        <th>Relationship</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($children)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No children linked to this parent yet.</small></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($children as $child): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($child['full_name']); ?></small></td>
                            <td><?php echo htmlspecialchars($child['class']); ?></small></td>
                            <td><?php echo htmlspecialchars($child['student_number'] ?? 'N/A'); ?></small></td>
                            <td><?php echo ucfirst($child['relationship']); ?></small></td>
                            <td>
                                <a href="?remove_child=<?php echo $child['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Remove this child from parent?')">🗑️ Remove</a>
                            </small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>