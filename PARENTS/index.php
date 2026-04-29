<?php
// PARENT/index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

// Get parent's children
$stmt = $pdo->prepare("
    SELECT s.*, ps.relationship 
    FROM parent_students ps
    JOIN students s ON ps.student_id = s.id
    WHERE ps.parent_id = ? AND s.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();

// Calculate total fee balance
$total_balance = 0;
foreach ($children as $child) {
    $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) as balance FROM invoices WHERE student_id = ? AND status != 'paid'");
    $stmt2->execute([$child['id']]);
    $total_balance += $stmt2->fetch()['balance'];
}

$page_title = "Parent Dashboard";
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>👪 Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-value"><?php echo count($children); ?></div>
            <div class="stat-label">My Children</div>
        </div>
        <div class="stat-card <?php echo $total_balance > 0 ? 'danger' : 'success'; ?>">
            <div class="stat-icon">💰</div>
            <div class="stat-value">TZS <?php echo number_format($total_balance); ?></div>
            <div class="stat-label">Total Fee Balance</div>
        </div>
    </div>
    
    <div class="form-card">
        <h3>📋 My Children</h3>
        <?php if (empty($children)): ?>
            <div class="alert alert-info">No children linked to your account yet. Please contact the school administration.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Student Name</th><th>Class</th><th>Student ID</th><th>Relationship</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($children as $child): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($child['full_name']); ?></small></td>
                            <td><?php echo htmlspecialchars($child['class']); ?></small></td>
                            <td><?php echo htmlspecialchars($child['student_number']); ?></small></td>
                            <td><?php echo ucfirst($child['relationship']); ?></small></td>
                            <td>
                                <a href="child_results.php?id=<?php echo $child['id']; ?>" class="btn-sm">📊 Results</a>
                                <a href="child_attendance.php?id=<?php echo $child['id']; ?>" class="btn-sm">📅 Attendance</a>
                                <a href="fee_balance.php?id=<?php echo $child['id']; ?>" class="btn-sm">💰 Fee Balance</a>
                                <a href="certificates.php?id=<?php echo $child['id']; ?>" class="btn-sm">📜 Certificates</a>
                            </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="form-card">
        <h3>⚡ Quick Links</h3>
        <div class="action-buttons" style="display: flex; flex-wrap: wrap; gap: 1rem;">
            <a href="gallery.php" class="btn btn-primary">🖼️ School Gallery</a>
            <a href="profile.php" class="btn btn-primary">👤 My Profile</a>
        </div>
    </div>
</div>

<?php include 'includes/parent_footer.php'; ?>