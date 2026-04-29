<?php
// ADMIN/subjects.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Subject Management";
include 'includes/admin_header.php';

$error = '';
$success = '';
$edit_subject = null;

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $school_level = $_POST['school_level'];
    $category = $_POST['category'];
    $is_compulsory = isset($_POST['is_compulsory']) ? 1 : 0;
    
    if (empty($subject_code) || empty($subject_name)) {
        $error = "❌ Subject code and name are required!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, school_level, category, is_compulsory) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$subject_code, $subject_name, $school_level, $category, $is_compulsory]);
            $success = "✅ Subject added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "❌ Subject code already exists!";
            } else {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Edit Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_subject'])) {
    $subject_id = $_POST['subject_id'];
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $school_level = $_POST['school_level'];
    $category = $_POST['category'];
    $is_compulsory = isset($_POST['is_compulsory']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, school_level = ?, category = ?, is_compulsory = ? WHERE id = ?");
    $stmt->execute([$subject_code, $subject_name, $school_level, $category, $is_compulsory, $subject_id]);
    $success = "✅ Subject updated successfully!";
}

// Handle Delete Subject
if (isset($_GET['delete_id'])) {
    $subject_id = $_GET['delete_id'];
    
    // Check if subject is used in teacher_subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $used = $stmt->fetchColumn();
    
    if ($used > 0) {
        $error = "❌ Cannot delete subject because it is assigned to teachers!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $success = "✅ Subject deleted successfully!";
    }
}

// Get edit data
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_subject = $stmt->fetch();
}

// Get all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY school_level, subject_code")->fetchAll();

// Get counts by level
$primary_count = $pdo->query("SELECT COUNT(*) FROM subjects WHERE school_level IN ('primary', 'both')")->fetchColumn();
$secondary_count = $pdo->query("SELECT COUNT(*) FROM subjects WHERE school_level IN ('secondary', 'both')")->fetchColumn();
?>

<div class="container">
    <h1>📚 Subject Management (TET Curriculum)</h1>
    
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-value"><?php echo $primary_count; ?></div>
            <div class="stat-label">🏫 Primary Subjects</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $secondary_count; ?></div>
            <div class="stat-label">🏛️ Secondary Subjects</div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Add/Edit Subject Form -->
        <div class="form-card">
            <h3><?php echo $edit_subject ? '✏️ Edit Subject' : '➕ Add New Subject'; ?></h3>
            <form method="POST">
                <?php if ($edit_subject): ?>
                    <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Subject Code *</label>
                    <input type="text" name="subject_code" required value="<?php echo $edit_subject['subject_code'] ?? ''; ?>" placeholder="e.g., PRI_MATH, SEC_ENG">
                </div>
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" required value="<?php echo $edit_subject['subject_name'] ?? ''; ?>" placeholder="e.g., Mathematics, Hisabati">
                </div>
                <div class="form-group">
                    <label>School Level *</label>
                    <select name="school_level" required>
                        <option value="primary" <?php echo ($edit_subject['school_level'] ?? '') == 'primary' ? 'selected' : ''; ?>>🏫 Primary Only</option>
                        <option value="secondary" <?php echo ($edit_subject['school_level'] ?? '') == 'secondary' ? 'selected' : ''; ?>>🏛️ Secondary Only</option>
                        <option value="both" <?php echo ($edit_subject['school_level'] ?? '') == 'both' ? 'selected' : ''; ?>>🏫🏛️ Both Levels</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="core" <?php echo ($edit_subject['category'] ?? '') == 'core' ? 'selected' : ''; ?>>Core (Compulsory)</option>
                        <option value="science" <?php echo ($edit_subject['category'] ?? '') == 'science' ? 'selected' : ''; ?>>Science</option>
                        <option value="arts" <?php echo ($edit_subject['category'] ?? '') == 'arts' ? 'selected' : ''; ?>>Arts</option>
                        <option value="business" <?php echo ($edit_subject['category'] ?? '') == 'business' ? 'selected' : ''; ?>>Business</option>
                        <option value="optional" <?php echo ($edit_subject['category'] ?? '') == 'optional' ? 'selected' : ''; ?>>Optional</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_compulsory" value="1" <?php echo ($edit_subject['is_compulsory'] ?? 1) ? 'checked' : ''; ?>>
                        Compulsory Subject
                    </label>
                </div>
                <button type="submit" name="<?php echo $edit_subject ? 'edit_subject' : 'add_subject'; ?>" class="btn btn-primary">
                    <?php echo $edit_subject ? '💾 Update Subject' : '➕ Add Subject'; ?>
                </button>
                <?php if ($edit_subject): ?>
                    <a href="subjects.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Subjects List -->
        <div class="form-card">
            <h3>📋 Subject List</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Level</th>
                            <th>Category</th>
                            <th>Compulsory</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $sub): 
                            $level_icon = ($sub['school_level'] == 'primary') ? '🏫' : (($sub['school_level'] == 'secondary') ? '🏛️' : '🏫🏛️');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['subject_code']); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong></small></td>
                            <td><?php echo $level_icon; ?> <?php echo ucfirst($sub['school_level']); ?></small></td>
                            <td><?php echo ucfirst($sub['category']); ?></small></td>
                            <td><?php echo $sub['is_compulsory'] ? '✅ Yes' : '❌ No'; ?></small></td>
                            <td>
                                <a href="?edit_id=<?php echo $sub['id']; ?>" class="btn-sm">✏️ Edit</a>
                                <a href="?delete_id=<?php echo $sub['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this subject?')">🗑️ Delete</a>
                                <a href="teacher_subjects.php?subject_id=<?php echo $sub['id']; ?>" class="btn-sm" style="background:#17a2b8;">👨‍🏫 Assign Teachers</a>
                            </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Primary School Subjects (TET Curriculum) -->
    <div class="form-card">
        <h3>🏫 Primary School Subjects (TET Curriculum)</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Subject Code</th><th>Subject Name</th><th>Category</th></tr>
                </thead>
                <tbody>
                    <?php
                    $primary_subjects = $pdo->query("SELECT * FROM subjects WHERE school_level IN ('primary', 'both') ORDER BY subject_code")->fetchAll();
                    foreach ($primary_subjects as $ps):
                    ?>
                    <tr>
                        <td><?php echo $ps['subject_code']; ?></td>
                        <td><?php echo $ps['subject_name']; ?></td>
                        <td><?php echo ucfirst($ps['category']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Secondary School Subjects (TET Curriculum) -->
    <div class="form-card">
        <h3>🏛️ Secondary School Subjects (TET Curriculum)</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Subject Code</th><th>Subject Name</th><th>Category</th></tr>
                </thead>
                <tbody>
                    <?php
                    $secondary_subjects = $pdo->query("SELECT * FROM subjects WHERE school_level IN ('secondary', 'both') ORDER BY subject_code")->fetchAll();
                    foreach ($secondary_subjects as $ss):
                    ?>
                    <tr>
                        <td><?php echo $ss['subject_code']; ?></td>
                        <td><?php echo $ss['subject_name']; ?></td>
                        <td><?php echo ucfirst($ss['category']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>