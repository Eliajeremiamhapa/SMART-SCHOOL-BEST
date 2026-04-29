<?php
require_once 'config/database.php';
$page_title = "Expense Tracking";
include 'includes/header.php';

// Add expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $expense_number = 'EXP-' . date('Ymd') . '-' . rand(1000, 9999);
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $payment_method = $_POST['payment_method'];
    $approved_by = $_POST['approved_by'];
    
    $stmt = $pdo->prepare("INSERT INTO expenses (expense_number, category, description, amount, expense_date, payment_method, approved_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$expense_number, $category, $description, $amount, $expense_date, $payment_method, $approved_by]);
    
    $success = "Expense recorded successfully!";
}

// Get all expenses
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 100")->fetchAll();

// Get expense summary by category
$summary = $pdo->query("
    SELECT category, SUM(amount) as total, COUNT(*) as count 
    FROM expenses 
    WHERE MONTH(expense_date) = MONTH(CURDATE()) 
    GROUP BY category 
    ORDER BY total DESC
")->fetchAll();
?>

<div class="container">
    <h1>Expense Tracking</h1>
    
    <div class="two-columns">
        <div class="form-card">
            <h3>Record New Expense</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Expense Category</label>
                    <select name="category" required>
                        <option value="Salaries">Salaries & Wages</option>
                        <option value="Utilities">Utilities (Electricity, Water)</option>
                        <option value="Stationery">Stationery & Supplies</option>
                        <option value="Maintenance">Maintenance & Repairs</option>
                        <option value="Transport">Transport & Fuel</option>
                        <option value="Meals">Meals & Catering</option>
                        <option value="Events">Events & Sports</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required placeholder="Detailed description of expense"></textarea>
                </div>
                <div class="form-group">
                    <label>Amount (TZS)</label>
                    <input type="number" step="0.01" name="amount" required>
                </div>
                <div class="form-group">
                    <label>Expense Date</label>
                    <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Approved By</label>
                    <input type="text" name="approved_by" placeholder="Name of approver">
                </div>
                <button type="submit" name="add_expense" class="btn btn-primary">💰 Record Expense</button>
            </form>
        </div>
        
        <div class="form-card">
            <h3>Monthly Expense Summary</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Category</th><th>Total Amount</th><th>Transactions</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $total_expenses = 0;
                    foreach ($summary as $s): 
                        $total_expenses += $s['total'];
                    ?>
                    <tr>
                        <td><?php echo $s['category']; ?></td>
                        <td>TZS <?php echo number_format($s['total']); ?></td>
                        <td><?php echo $s['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold; background:#e6f7ff;">
                        <td>TOTAL</td>
                        <td>TZS <?php echo number_format($total_expenses); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="section">
        <h3>Recent Expenses</h3>
        <table class="data-table">
            <thead>
                <tr><th>Expense #</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Payment Method</th><th>Approved By</th></tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $exp): ?>
                <tr>
                    <td><?php echo $exp['expense_number']; ?></td>
                    <td><?php echo $exp['expense_date']; ?></td>
                    <td><?php echo $exp['category']; ?></td>
                    <td><?php echo substr($exp['description'], 0, 50); ?>...</td>
                    <td style="color:red;">TZS <?php echo number_format($exp['amount']); ?></td>
                    <td><?php echo $exp['payment_method']; ?></td>
                    <td><?php echo $exp['approved_by']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>