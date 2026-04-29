<?php
// PARENTS/fee_balance.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$student_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT s.* FROM parent_students ps JOIN students s ON ps.student_id = s.id WHERE ps.parent_id = ? AND s.id = ?");
$stmt->execute([$_SESSION['user_id'], $student_id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE student_id = ? AND status != 'paid' ORDER BY due_date ASC");
$stmt->execute([$student_id]);
$invoices = $stmt->fetchAll();

$total_balance = 0;
foreach ($invoices as $inv) { $total_balance += $inv['balance']; }

$page_title = "Fee Balance - " . $student['full_name'];
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>💰 Fee Balance: <?php echo htmlspecialchars($student['full_name']); ?></h1>
    
    <div class="stats-grid">
        <div class="stat-card <?php echo $total_balance > 0 ? 'danger' : 'success'; ?>">
            <div class="stat-value">TZS <?php echo number_format($total_balance); ?></div>
            <div class="stat-label">Total Outstanding Balance</div>
        </div>
    </div>
    
    <div class="form-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Invoice #</th><th>Category</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="7" style="text-align:center; color:green;">✅ No outstanding fees! All paid.</small></td>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><?php echo $inv['invoice_number']; ?></small></td>
                            <td><?php echo $inv['category_id'] == 1 ? 'Tuition' : ($inv['category_id'] == 2 ? 'Canteen' : ($inv['category_id'] == 3 ? 'Stationery' : ($inv['category_id'] == 4 ? 'Uniforms' : 'Transport'))); ?></small></td>
                            <td>TZS <?php echo number_format($inv['amount']); ?></small></td>
                            <td>TZS <?php echo number_format($inv['amount_paid']); ?></small></td>
                            <td style="color:red;">TZS <?php echo number_format($inv['balance']); ?></small></td>
                            <td><?php echo date('d-m-Y', strtotime($inv['due_date'])); ?></small></td>
                            <td><?php echo ucfirst($inv['status']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/parent_footer.php'; ?>