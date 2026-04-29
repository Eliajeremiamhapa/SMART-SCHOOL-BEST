<?php
require_once 'config/database.php';
$page_title = "Invoices Management";
include 'includes/header.php';

// Generate new invoice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_invoice'])) {
    $student_id = $_POST['student_id'];
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];
    $term = $_POST['term'];
    $academic_year = $_POST['academic_year'];
    $due_date = $_POST['due_date'];
    
    $invoice_number = 'INV-' . date('Ymd') . '-' . $student_id . '-' . rand(100, 999);
    
    $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, student_id, category_id, amount, amount_paid, term, academic_year, issue_date, due_date, status) VALUES (?, ?, ?, ?, 0, ?, ?, CURDATE(), ?, 'pending')");
    $stmt->execute([$invoice_number, $student_id, $category_id, $amount, $term, $academic_year, $due_date]);
    
    $success = "Invoice generated successfully!";
}

// Get all invoices
$invoices = $pdo->query("
    SELECT i.*, s.full_name, s.student_number, s.class, rc.category_name
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    JOIN revenue_categories rc ON i.category_id = rc.id
    ORDER BY i.issue_date DESC
    LIMIT 100
")->fetchAll();

// Get students
$students = $pdo->query("SELECT id, student_number, full_name, class FROM students WHERE is_active = 1")->fetchAll();
$categories = $pdo->query("SELECT id, category_name FROM revenue_categories WHERE is_active = 1")->fetchAll();
?>

<div class="container">
    <h1>Invoices Management</h1>
    
    <div class="two-columns">
        <div class="form-card">
            <h3>Generate New Invoice</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['student_number'] . ' - ' . $s['full_name'] . ' (' . $s['class'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fee Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (TZS)</label>
                    <input type="number" step="0.01" name="amount" required>
                </div>
                <div class="form-group">
                    <label>Term</label>
                    <select name="term">
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="2025" required>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required>
                </div>
                <button type="submit" name="generate_invoice" class="btn btn-primary">📄 Generate Invoice</button>
            </form>
        </div>
        
        <div class="form-card">
            <h3>Invoice Statistics</h3>
            <?php
            $stats = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial,
                    SUM(balance) as total_balance
                FROM invoices
            ")->fetch();
            ?>
            <div class="stats-mini">
                <div>Total Invoices: <strong><?php echo $stats['total']; ?></strong></div>
                <div>Paid: <strong class="success"><?php echo $stats['paid']; ?></strong></div>
                <div>Pending: <strong class="warning"><?php echo $stats['pending']; ?></strong></div>
                <div>Partial: <strong class="info"><?php echo $stats['partial']; ?></strong></div>
                <div>Outstanding Balance: <strong class="danger">TZS <?php echo number_format($stats['total_balance']); ?></strong></div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h3>All Invoices</h3>
        <table class="data-table">
            <thead>
                <tr><th>Invoice #</th><th>Student</th><th>Category</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): 
                    $statusClass = $inv['status'] == 'paid' ? 'success' : ($inv['status'] == 'partial' ? 'warning' : 'danger');
                ?>
                <tr>
                    <td><?php echo $inv['invoice_number']; ?></td>
                    <td><?php echo $inv['full_name'] . ' (' . $inv['student_number'] . ')'; ?></td>
                    <td><?php echo $inv['category_name']; ?></td>
                    <td>TZS <?php echo number_format($inv['amount']); ?></td>
                    <td>TZS <?php echo number_format($inv['amount_paid']); ?></td>
                    <td>TZS <?php echo number_format($inv['balance']); ?></td>
                    <td><?php echo $inv['due_date']; ?> (<?php echo $inv['due_date'] < date('Y-m-d') ? 'Overdue' : ''; ?>)</td>
                    <td class="<?php echo $statusClass; ?>"><?php echo strtoupper($inv['status']); ?></td>
                    <td>
                        <a href="fee_management.php?student=<?php echo $inv['student_id']; ?>" class="btn-small">Collect</a>
                        <button onclick="printInvoice(<?php echo $inv['id']; ?>)" class="btn-small">Print</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function printInvoice(id) {
    window.open('print_invoice.php?id=' + id, '_blank', 'width=800,height=600');
}
</script>

<?php include 'includes/footer.php'; ?>