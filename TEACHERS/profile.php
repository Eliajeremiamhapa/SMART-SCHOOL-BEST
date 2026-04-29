<?php
// TEACHERS/profile.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$page_title = "My Profile";
include 'includes/teacher_header.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();
?>

<div class="container">
    <h1>👤 My Profile</h1>
    <div class="form-card">
        <table class="data-table">
            <tr><th>Full Name</th><td><?php echo $teacher['full_name']; ?></td></tr>
            <tr><th>Username</th><td><?php echo $teacher['username']; ?></td></tr>
            <tr><th>Email</th><td><?php echo $teacher['email'] ?? 'Not set'; ?></td></tr>
            <tr><th>Role</th><td>Teacher</td></tr>
            <tr><th>Classes Assigned</th><td><?php foreach($classes as $c) echo $c['class'] . " "; ?></td></tr>
            <tr><th>Account Status</th><td><span style="color:green;">✅ Active</span></td></tr>
        </table>
    </div>
</div>
<?php include 'includes/teacher_footer.php'; ?>