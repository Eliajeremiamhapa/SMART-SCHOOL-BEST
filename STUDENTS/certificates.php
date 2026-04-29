<?php
// STUDENTS/certificates.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Direct database connection
$host = 'localhost';
$dbname = 'accountant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

// Get student details - NO JOIN (user_id doesn't exist in students table)
$student = null;

// Try by student_number (username)
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->execute([$_SESSION['username']]);
$student = $stmt->fetch();

// Try by full_name
if (!$student) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
    $stmt->execute([$_SESSION['full_name']]);
    $student = $stmt->fetch();
}

// If still not found, create basic record
if (!$student) {
    $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$_SESSION['username'], $_SESSION['full_name'], 'Not Assigned', '']);
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
    $stmt->execute([$_SESSION['username']]);
    $student = $stmt->fetch();
}

$page_title = "My Certificates";
include 'includes/student_header.php';

// Check if certificates table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'certificates'");
    $cert_table_exists = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    $cert_table_exists = false;
}

// Get certificates if table exists
$certificates = [];
if ($cert_table_exists) {
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE student_id = ? ORDER BY issue_date DESC");
    $stmt->execute([$student['id']]);
    $certificates = $stmt->fetchAll();
}
?>

<div class="container">
    <h1>📜 My Digital Certificates</h1>
    
    <?php if (!$cert_table_exists): ?>
        <div class="form-card" style="text-align: center;">
            <i class="fas fa-certificate" style="font-size: 4rem; color: #ccc;"></i>
            <p style="margin-top: 1rem;">Digital certificates module is coming soon.</p>
            <p>Certificates will be available here when issued by the school.</p>
        </div>
    <?php elseif (empty($certificates)): ?>
        <div class="form-card" style="text-align: center;">
            <i class="fas fa-certificate" style="font-size: 4rem; color: #ccc;"></i>
            <p style="margin-top: 1rem;">No certificates available yet.</p>
            <p>Certificates will appear here when issued by the school.</p>
            <p style="margin-top: 1rem; font-size: 0.8rem; color: #666;">
                <i class="fas fa-info-circle"></i> Examples: Completion certificates, merit awards, participation certificates
            </p>
        </div>
    <?php else: ?>
        <div class="two-columns">
            <?php foreach ($certificates as $cert): ?>
            <div class="form-card" style="text-align: center;">
                <i class="fas fa-certificate" style="font-size: 3rem; color: #ffc107;"></i>
                <h3><?php echo htmlspecialchars($cert['title']); ?></h3>
                <p><?php echo htmlspecialchars($cert['description']); ?></p>
                <p><small>Issued: <?php echo date('d-m-Y', strtotime($cert['issue_date'])); ?></small></p>
                <?php if (!empty($cert['file_path']) && file_exists('../uploads/certificates/' . $cert['file_path'])): ?>
                    <a href="../uploads/certificates/<?php echo $cert['file_path']; ?>" class="btn btn-primary" download>
                        <i class="fas fa-download"></i> Download Certificate
                    </a>
                <?php else: ?>
                    <p style="color: #999;">Certificate file not available</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="form-card">
        <h3>ℹ️ About Digital Certificates</h3>
        <p>The school issues digital certificates for:</p>
        <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
            <li>📜 Academic achievements (end of term/year)</li>
            <li>🏆 Merit awards and recognitions</li>
            <li>🎓 Participation in school events and competitions</li>
            <li>📋 Completion certificates for courses</li>
        </ul>
        <p style="margin-top: 1rem;">Certificates are issued by the school administration and can be downloaded anytime.</p>
    </div>
</div>

<?php include 'includes/student_footer.php'; ?>