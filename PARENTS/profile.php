<?php
// PARENTS/profile.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$parent = $stmt->fetch();

$page_title = "My Profile";
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>👤 My Profile</h1>
    <div class="form-card">
        <table class="data-table">
            <tr><th>Full Name</th><td><?php echo htmlspecialchars($parent['full_name']); ?></small></td>
            <tr><th>Username</th><td><?php echo htmlspecialchars($parent['username']); ?></small></td>
            <tr><th>Email</th><td><?php echo htmlspecialchars($parent['email'] ?? 'Not set'); ?></small></td>
            <tr><th>Role</th><td>Parent</small></td>
            <tr><th>Account Status</th><td><span style="color:green;">✅ Active</span></small></td>
            <tr><th>Registered Since</th><td><?php echo date('d-m-Y', strtotime($parent['created_at'])); ?></small></td>
        </table>
    </div>
</div>
<?php include 'includes/parent_footer.php'; ?>