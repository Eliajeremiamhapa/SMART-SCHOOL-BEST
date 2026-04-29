<?php
// ADMIN/fee_structure.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$page_title = "Fee Structure";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Get all fee types from revenue_categories
$fee_types = $pdo->query("SELECT id, category_name FROM revenue_categories WHERE is_active = 1")->fetchAll();

// Get all classes from students table
$classes = $pdo->query("SELECT DISTINCT class FROM students WHERE is_active = 1 AND class IS NOT NULL")->fetchAll();
if (empty($classes)) {
    // Default classes if no students exist
    $classes = [
        ['class' => 'Form 1A'], ['class' => 'Form 1B'], ['class' => 'Form 2A'],
        ['class' => 'Form 2B'], ['class' => 'Form 3A'], ['class' => 'Form 3B'],
        ['class' => 'Form 4A'], ['class' => 'Form 4B'], ['class' => 'Standard 5'],
        ['class' => 'Standard 6'], ['class' => 'Standard 7']
    ];
}

// Check table structure and get actual column names
try {
    $columns = $pdo->query("DESCRIBE fee_structure")->fetchAll();
    $has_fee_type = false;
    $has_category_id = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] == 'fee_type') $has_fee_type = true;
        if ($col['Field'] == 'category_id') $has_category_id = true;
    }
} catch (PDOException $e) {
    // Table might not exist, create it
    $pdo->exec("CREATE TABLE IF NOT EXISTS fee_structure (
        id INT PRIMARY KEY AUTO_INCREMENT,
        class VARCHAR(50) NOT NULL,
        fee_type VARCHAR(100) NOT NULL,
        category_id INT,
        amount DECIMAL(15,2) NOT NULL,
        term ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $has_fee_type = true;
}

// Handle Add Fee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_fee'])) {
    $class = $_POST['class'];
    $fee_type = $_POST['fee_type'];
    $amount = $_POST['amount'];
    $term = $_POST['term'];
    $academic_year = $_POST['academic_year'];
    
    // Get category_id from fee_type name if needed
    $category_id = null;
    if ($has_category_id && !$has_fee_type) {
        $stmt = $pdo->prepare("SELECT id FROM revenue_categories WHERE category_name = ?");
        $stmt->execute([$fee_type]);
        $cat = $stmt->fetch();
        $category_id = $cat['id'] ?? null;
    }
    
    if ($has_fee_type) {
        $stmt = $pdo->prepare("INSERT INTO fee_structure (class, fee_type, amount, term, academic_year) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$class, $fee_type, $amount, $term, $academic_year]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO fee_structure (class, category_id, amount, term, academic_year) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$class, $category_id, $amount, $term, $academic_year]);
    }
    $success = "✅ Fee structure added successfully!";
}

// Handle Delete Fee
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM fee_structure WHERE id = ?");
    $stmt->execute([$id]);
    $success = "✅ Fee structure deleted successfully!";
}

// Get all fee structures with proper column handling
if ($has_fee_type) {
    $fee_structures = $pdo->query("SELECT fs.*, rc.category_name as fee_type_name FROM fee_structure fs LEFT JOIN revenue_categories rc ON fs.fee_type = rc.category_name ORDER BY fs.class, fs.term, fs.fee_type")->fetchAll();
} else {
    $fee_structures = $pdo->query("SELECT fs.*, rc.category_name as fee_type_name FROM fee_structure fs LEFT JOIN revenue_categories rc ON fs.category_id = rc.id ORDER BY fs.class, fs.term")->fetchAll();
}
?>

<div class="container">
    <h1>💰 Fee Structure Management</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Add Fee Form -->
        <div class="form-card">
            <h3>➕ Add Fee Structure</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Class</label>
                    <select name="class" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['class']); ?>"><?php echo htmlspecialchars($c['class']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fee Type</label>
                    <select name="fee_type" required>
                        <option value="">-- Select Fee Type --</option>
                        <?php foreach ($fee_types as $ft): ?>
                            <option value="<?php echo htmlspecialchars($ft['category_name']); ?>"><?php echo htmlspecialchars($ft['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (TZS)</label>
                    <input type="number" name="amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Term</label>
                    <select name="term" required>
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="2025" required>
                </div>
                <button type="submit" name="add_fee" class="btn btn-primary">➕ Add Fee Structure</button>
            </form>
        </div>
        
        <!-- Fee Structures List -->
        <div class="form-card">
            <h3>📋 Current Fee Structures</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fee_structures)): ?>
                            <tr><td colspan="6" style="text-align:center;">No fee structures added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($fee_structures as $fs): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fs['class']); ?></small></td>
                                <td><?php echo htmlspecialchars($fs['fee_type_name'] ?? $fs['fee_type'] ?? 'N/A'); ?></small></td>
                                <td>TZS <?php echo number_format($fs['amount']); ?></small></td>
                                <td><?php echo $fs['term']; ?></small></td>
                                <td><?php echo $fs['academic_year']; ?></small></td>
                                <td>
                                    <a href="?delete_id=<?php echo $fs['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this fee structure?')">🗑️ Delete</a>
                                </small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>