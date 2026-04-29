<?php
// PARENTS/my_children.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$stmt = $pdo->prepare("SELECT s.*, ps.relationship FROM parent_students ps JOIN students s ON ps.student_id = s.id WHERE ps.parent_id = ? AND s.is_active = 1");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();

$page_title = "My Children";
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>👨‍👩‍👧‍👦 My Children</h1>
    
    <div class="form-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Photo</th><th>Student Name</th><th>Class</th><th>Student ID</th><th>Relationship</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($children as $child): ?>
                    <tr>
                        <td><div style="width:50px;height:50px;background:#1e3c72;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;"><i class="fas fa-user-graduate"></i></div></td>
                        <td><strong><?php echo htmlspecialchars($child['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($child['class']); ?></td>
                        <td><?php echo htmlspecialchars($child['student_number']); ?></td>
                        <td><?php echo ucfirst($child['relationship']); ?></td>
                        <td>
                            <a href="child_results.php?id=<?php echo $child['id']; ?>" class="btn-sm">📊 Results</a>
                            <a href="child_attendance.php?id=<?php echo $child['id']; ?>" class="btn-sm">📅 Attendance</a>
                            <a href="fee_balance.php?id=<?php echo $child['id']; ?>" class="btn-sm">💰 Fee Balance</a>
                        </small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/parent_footer.php'; ?>