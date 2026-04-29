<?php
// PARENTS/certificates.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$student_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT s.* FROM parent_students ps JOIN students s ON ps.student_id = s.id WHERE ps.parent_id = ? AND s.id = ?");
$stmt->execute([$_SESSION['user_id'], $student_id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit(); }

// Check if certificates table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'certificates'");
    $table_exists = $stmt->rowCount() > 0;
    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM certificates WHERE student_id = ? ORDER BY issue_date DESC");
        $stmt->execute([$student_id]);
        $certificates = $stmt->fetchAll();
    } else {
        $certificates = [];
    }
} catch(PDOException $e) {
    $certificates = [];
}

$page_title = "Certificates - " . $student['full_name'];
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>📜 Certificates: <?php echo htmlspecialchars($student['full_name']); ?></h1>
    
    <div class="form-card">
        <?php if (empty($certificates)): ?>
            <div style="text-align:center;">
                <i class="fas fa-certificate" style="font-size:4rem;color:#ccc;"></i>
                <p>No certificates available yet.</p>
                <p>Certificates will appear here when issued by the school.</p>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:1.5rem;">
                <?php foreach ($certificates as $cert): ?>
                <div style="text-align:center; padding:1rem; border:1px solid #eee; border-radius:10px;">
                    <i class="fas fa-certificate" style="font-size:3rem; color:#ffc107;"></i>
                    <h3><?php echo htmlspecialchars($cert['title']); ?></h3>
                    <p><?php echo htmlspecialchars($cert['description']); ?></p>
                    <p><small>Issued: <?php echo date('d-m-Y', strtotime($cert['issue_date'])); ?></small></p>
                    <a href="../uploads/certificates/<?php echo $cert['file_path']; ?>" class="btn btn-primary" download>📥 Download Certificate</a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/parent_footer.php'; ?>