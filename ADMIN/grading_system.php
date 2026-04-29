<?php
// ADMIN/grading_system.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$page_title = "Grading System";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Handle Add Grade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_grade'])) {
    $grade = $_POST['grade'];
    $min_score = $_POST['min_score'];
    $max_score = $_POST['max_score'];
    $description = $_POST['description'];
    $points = $_POST['points'];
    
    $stmt = $pdo->prepare("INSERT INTO grading_system (grade, min_score, max_score, description, points) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$grade, $min_score, $max_score, $description, $points]);
    $success = "✅ Grade added successfully!";
}

// Handle Edit Grade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_grade'])) {
    $id = $_POST['grade_id'];
    $grade = $_POST['grade'];
    $min_score = $_POST['min_score'];
    $max_score = $_POST['max_score'];
    $description = $_POST['description'];
    $points = $_POST['points'];
    
    $stmt = $pdo->prepare("UPDATE grading_system SET grade = ?, min_score = ?, max_score = ?, description = ?, points = ? WHERE id = ?");
    $stmt->execute([$grade, $min_score, $max_score, $description, $points, $id]);
    $success = "✅ Grade updated successfully!";
}

// Handle Delete Grade
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM grading_system WHERE id = ?");
    $stmt->execute([$id]);
    $success = "✅ Grade deleted successfully!";
}

// Get all grades
$grades = $pdo->query("SELECT * FROM grading_system ORDER BY min_score DESC")->fetchAll();

// Get edit data
$edit_grade = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM grading_system WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_grade = $stmt->fetch();
}
?>

<div class="container">
    <h1>📊 Grading System</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Add/Edit Grade Form -->
        <div class="form-card">
            <h3><?php echo $edit_grade ? '✏️ Edit Grade' : '➕ Add New Grade'; ?></h3>
            <form method="POST">
                <?php if ($edit_grade): ?>
                    <input type="hidden" name="grade_id" value="<?php echo $edit_grade['id']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Grade (e.g., A, B+, C)</label>
                    <input type="text" name="grade" value="<?php echo $edit_grade['grade'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Minimum Score (%)</label>
                    <input type="number" name="min_score" value="<?php echo $edit_grade['min_score'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Maximum Score (%)</label>
                    <input type="number" name="max_score" value="<?php echo $edit_grade['max_score'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" value="<?php echo $edit_grade['description'] ?? ''; ?>" placeholder="e.g., Outstanding">
                </div>
                <div class="form-group">
                    <label>Points (GPA)</label>
                    <input type="number" step="0.1" name="points" value="<?php echo $edit_grade['points'] ?? '0'; ?>" required>
                </div>
                <button type="submit" name="<?php echo $edit_grade ? 'edit_grade' : 'add_grade'; ?>" class="btn btn-primary">
                    <?php echo $edit_grade ? '💾 Update Grade' : '➕ Add Grade'; ?>
                </button>
                <?php if ($edit_grade): ?>
                    <a href="grading_system.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Grades List -->
        <div class="form-card">
            <h3>📋 Current Grading Scale</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Grade</th><th>Min %</th><th>Max %</th><th>Description</th><th>Points</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $g): ?>
                        <tr>
                            <td><strong><?php echo $g['grade']; ?></strong></td>
                            <td><?php echo $g['min_score']; ?>%</small></td>
                            <td><?php echo $g['max_score']; ?>%</small></td>
                            <td><?php echo $g['description']; ?></small></td>
                            <td><?php echo $g['points']; ?></small></td>
                            <td>
                                <a href="?edit_id=<?php echo $g['id']; ?>" class="btn-sm">✏️</a>
                                <a href="?delete_id=<?php echo $g['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this grade?')">🗑️</a>
                            </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>