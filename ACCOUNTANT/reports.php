<?php
require_once 'config/database.php';
$page_title = "Financial Reports";
include 'includes/header.php';

$report_type = $_GET['report'] ?? 'dashboard';

// Daily Settlement Report
if ($report_type == 'daily_settlement') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN payment_method = 'card' THEN amount ELSE 0 END) as card_total,
            SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash_total,
            SUM(CASE WHEN payment_method = 'mpesa' THEN amount ELSE 0 END) as mpesa_total,
            SUM(CASE WHEN payment_method = 'tigopesa' THEN amount ELSE 0 END) as tigo_total,
            SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) as bank_total,
            SUM(amount) as grand_total
        FROM transactions 
        WHERE DATE(transaction_date) = ?
    ");
    $stmt->execute([$date]);
    $settlement = $stmt->fetch();
}

// Revenue Breakdown
if ($report_type == 'revenue_breakdown') {
    $stmt = $pdo->query("
        SELECT rc.category_name, COALESCE(SUM(t.amount), 0) as total
        FROM revenue_categories rc
        LEFT JOIN invoices i ON i.category_id = rc.id
        LEFT JOIN transactions t ON t.invoice_id = i.id
        GROUP BY rc.id
        ORDER BY total DESC
    ");
    $revenue_breakdown = $stmt->fetchAll();
}

// Unreconciled Items (Pesa zilizoko benki lakini hazijatolewa risiti shuleni)
if ($report_type == 'unreconciled') {
    $unreconciled = $pdo->query("
        SELECT bt.*, bs.bank_name 
        FROM bank_transactions bt
        JOIN bank_statements bs ON bt.bank_statement_id = bs.id
        WHERE bt.match_status = 'mismatch'
        ORDER BY bt.transaction_date DESC
    ")->fetchAll();
}

// Outstanding Deposits (Pesa zilizorekodiwa shuleni lakini hazijaonekana benki)
if ($report_type == 'outstanding') {
    $outstanding = $pdo->query("
        SELECT t.*, s.full_name, s.student_number, s.class
        FROM transactions t
        JOIN students s ON t.student_id = s.id
        WHERE t.is_reconciled = 0 
        AND t.payment_method != 'card'
        AND NOT EXISTS (
            SELECT 1 FROM bank_transactions bt 
            WHERE bt.transaction_ref = t.transaction_ref 
            AND bt.match_status = 'matched'
        )
        ORDER BY t.transaction_date DESC
    ")->fetchAll();
}

// Debt Tracking
if ($report_type == 'debts') {
    $debts = $pdo->query("
        SELECT s.full_name, s.student_number, s.class, 
               SUM(i.balance) as total_debt,
               COUNT(i.id) as invoice_count,
               MIN(i.due_date) as oldest_due
        FROM students s
        JOIN invoices i ON s.id = i.student_id
        WHERE i.status != 'paid' AND i.balance > 0
        GROUP BY s.id
        HAVING total_debt > 0
        ORDER BY total_debt DESC
    ")->fetchAll();
}

// Stock Audit Report
if ($report_type == 'stock_audit') {
    $stock_audit = $pdo->query("
        SELECT i.item_name, i.item_code, i.current_stock as system_stock,
               i.reorder_level,
               COALESCE(SUM(st.quantity), 0) as total_sold,
               CASE 
                   WHEN i.current_stock <= i.reorder_level THEN 'Low Stock'
                   ELSE 'OK'
               END as stock_status
        FROM inventory_items i
        LEFT JOIN stock_transactions st ON i.id = st.item_id AND st.transaction_type = 'out'
        GROUP BY i.id
        ORDER BY stock_status DESC, i.current_stock ASC
    ")->fetchAll();
}

// Final Reconciliation Report
if ($report_type == 'reconciliation') {
    // Get matched transactions total
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_bank FROM bank_transactions WHERE match_status = 'matched'");
    $bank_total = $stmt->fetch()['total_bank'];
    
    // Get system transactions that are reconciled
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_system FROM transactions WHERE is_reconciled = 1");
    $system_total = $stmt->fetch()['total_system'];
    
    // Get unmatched from bank
    $stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM bank_transactions WHERE match_status = 'mismatch'");
    $unmatched_bank = $stmt->fetch();
    
    // Get outstanding from system
    $stmt = $pdo->query("
        SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM transactions t
        WHERE t.is_reconciled = 0 
        AND t.payment_method != 'card'
        AND NOT EXISTS (
            SELECT 1 FROM bank_transactions bt 
            WHERE bt.transaction_ref = t.transaction_ref 
            AND bt.match_status = 'matched'
        )
    ");
    $outstanding_system = $stmt->fetch();
    
    $variance = $bank_total - $system_total;
}

// Dashboard summary
if ($report_type == 'dashboard') {
    // Monthly collections
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURDATE())");
    $monthly_collection = $stmt->fetch()['total'];
    
    // Monthly expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE())");
    $monthly_expense = $stmt->fetch()['total'];
    
    // Total debt
    $stmt = $pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM invoices WHERE status != 'paid'");
    $total_debt = $stmt->fetch()['total'];
    
    // Unreconciled count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bank_transactions WHERE match_status = 'mismatch'");
    $unreconciled_count = $stmt->fetch()['count'];
    
    // Outstanding deposits count
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM transactions t
        WHERE t.is_reconciled = 0 
        AND t.payment_method != 'card'
        AND NOT EXISTS (
            SELECT 1 FROM bank_transactions bt 
            WHERE bt.transaction_ref = t.transaction_ref 
            AND bt.match_status = 'matched'
        )
    ");
    $outstanding_count = $stmt->fetch()['count'];
    
    // *** FIX: Get debts data for dashboard ***
    $debts = $pdo->query("
        SELECT s.full_name, s.student_number, s.class, 
               SUM(i.balance) as total_debt,
               COUNT(i.id) as invoice_count,
               MIN(i.due_date) as oldest_due
        FROM students s
        JOIN invoices i ON s.id = i.student_id
        WHERE i.status != 'paid' AND i.balance > 0
        GROUP BY s.id
        HAVING total_debt > 0
        ORDER BY total_debt DESC
    ")->fetchAll();
}
?>

<div class="container">
    <h1>📊 Financial Reports</h1>
    
    <div class="report-nav">
        <a href="?report=dashboard" class="btn <?php echo $report_type == 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a>
        <a href="?report=daily_settlement" class="btn <?php echo $report_type == 'daily_settlement' ? 'active' : ''; ?>">📅 Daily Settlement</a>
        <a href="?report=revenue_breakdown" class="btn <?php echo $report_type == 'revenue_breakdown' ? 'active' : ''; ?>">📈 Revenue Breakdown</a>
        <a href="?report=unreconciled" class="btn <?php echo $report_type == 'unreconciled' ? 'active' : ''; ?>">⚠️ Unreconciled Items</a>
        <a href="?report=outstanding" class="btn <?php echo $report_type == 'outstanding' ? 'active' : ''; ?>">⏳ Outstanding Deposits</a>
        <a href="?report=debts" class="btn <?php echo $report_type == 'debts' ? 'active' : ''; ?>">💰 Debt Tracking</a>
        <a href="?report=stock_audit" class="btn <?php echo $report_type == 'stock_audit' ? 'active' : ''; ?>">📦 Stock Audit</a>
        <a href="?report=reconciliation" class="btn <?php echo $report_type == 'reconciliation' ? 'active' : ''; ?>">🔄 Final Reconciliation</a>
    </div>
    
    <!-- DASHBOARD REPORT -->
    <?php if ($report_type == 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">TZS <?php echo number_format($monthly_collection); ?></div>
                <div class="stat-label">Total Collections (MTD)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💸</div>
                <div class="stat-value">TZS <?php echo number_format($monthly_expense); ?></div>
                <div class="stat-label">Total Expenses (MTD)</div>
            </div>
            <div class="stat-card <?php echo ($monthly_collection - $monthly_expense) >= 0 ? 'success' : 'danger'; ?>">
                <div class="stat-icon">📈</div>
                <div class="stat-value">TZS <?php echo number_format($monthly_collection - $monthly_expense); ?></div>
                <div class="stat-label">Net Income (MTD)</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">💳</div>
                <div class="stat-value">TZS <?php echo number_format($total_debt); ?></div>
                <div class="stat-label">Total Outstanding Debt</div>
            </div>
        </div>
        
        <div class="two-columns" style="margin-top: 1.5rem;">
            <div class="form-card">
                <h3>⚠️ Pending Actions</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                        🔴 <strong><?php echo $unreconciled_count; ?></strong> Unreconciled bank transactions 
                        <a href="?report=unreconciled" class="btn-small">View</a>
                    </li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                        ⏳ <strong><?php echo $outstanding_count; ?></strong> Outstanding deposits (in system, not in bank)
                        <a href="?report=outstanding" class="btn-small">View</a>
                    </li>
                    <li style="padding: 0.5rem 0;">
                        💰 <strong><?php echo count($debts); ?></strong> Students with debt
                        <a href="?report=debts" class="btn-small">View</a>
                    </li>
                </ul>
            </div>
            <div class="form-card">
                <h3>📋 Quick Actions</h3>
                <div class="action-buttons">
                    <a href="bank_reconciliation.php" class="btn btn-primary">🔄 Go to Reconciliation</a>
                    <a href="import_statement.php" class="btn btn-info">📥 Import Bank Statement</a>
                    <a href="fee_management.php" class="btn btn-success">💰 Record Payment</a>
                </div>
            </div>
        </div>
    
    <!-- DAILY SETTLEMENT REPORT -->
    <?php elseif ($report_type == 'daily_settlement'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Daily Settlement - <?php echo $date; ?></h3>
            <div>
                <a href="?report=daily_settlement&date=<?php echo date('Y-m-d', strtotime($date . ' -1 day')); ?>" class="btn-small">← Previous</a>
                <a href="?report=daily_settlement&date=<?php echo date('Y-m-d'); ?>" class="btn-small">Today</a>
                <a href="?report=daily_settlement&date=<?php echo date('Y-m-d', strtotime($date . ' +1 day')); ?>" class="btn-small">Next →</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Payment Method</th><th>Amount</th></tr></thead>
                <tbody>
                    <tr><td>💳 Smart Card</td><td>TZS <?php echo number_format($settlement['card_total'] ?? 0); ?></td></tr>
                    <tr><td>💵 Cash</td><td>TZS <?php echo number_format($settlement['cash_total'] ?? 0); ?></td></tr>
                    <tr><td>📱 M-Pesa</td><td>TZS <?php echo number_format($settlement['mpesa_total'] ?? 0); ?></td></tr>
                    <tr><td>📱 Tigo Pesa</td><td>TZS <?php echo number_format($settlement['tigo_total'] ?? 0); ?></td></tr>
                    <tr><td>🏦 Bank Transfer</td><td>TZS <?php echo number_format($settlement['bank_total'] ?? 0); ?></td></tr>
                    <tr style="font-weight:bold; background:#e6f7ff;"><td>GRAND TOTAL</td><td>TZS <?php echo number_format($settlement['grand_total'] ?? 0); ?></td></tr>
                </tbody>
            </table>
        </div>
    
    <!-- REVENUE BREAKDOWN REPORT -->
    <?php elseif ($report_type == 'revenue_breakdown'): ?>
        <h3>Revenue Breakdown by Category</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Category</th><th>Total Revenue</th><th>Percentage</th></tr></thead>
                <tbody>
                    <?php 
                    $grand = array_sum(array_column($revenue_breakdown, 'total'));
                    foreach ($revenue_breakdown as $rb): 
                        $percent = $grand > 0 ? ($rb['total'] / $grand * 100) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rb['category_name']); ?></td>
                        <td>TZS <?php echo number_format($rb['total']); ?></td>
                        <td><?php echo round($percent, 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold; background:#e6f7ff;">
                        <td>TOTAL</td>
                        <td>TZS <?php echo number_format($grand); ?></td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    
    <!-- UNRECONCILED ITEMS (Pesa bank lakini haipo system) -->
    <?php elseif ($report_type == 'unreconciled'): ?>
        <h3>⚠️ Unreconciled Items</h3>
        <p class="alert alert-info">Hizi ni pesa zilizoko BENKI lakini HAZIJATOLEWA RISITI shuleni.</p>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Bank</th><th>Reference</th><th>Date</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (count($unreconciled) == 0): ?>
                        <tr><td colspan="6" style="text-align:center; color:green;">✅ No unreconciled items found!</td></tr>
                    <?php else: ?>
                        <?php foreach ($unreconciled as $ur): ?>
                        <tr style="background-color:#ffebee;">
                            <td><?php echo htmlspecialchars($ur['bank_name']); ?></td>
                            <td><strong>⚠️ <?php echo htmlspecialchars($ur['transaction_ref']); ?></strong></td>
                            <td><?php echo $ur['transaction_date']; ?></td>
                            <td><?php echo htmlspecialchars(substr($ur['description'], 0, 50)); ?></td>
                            <td style="color:red; font-weight:bold;">TZS <?php echo number_format($ur['amount']); ?></td>
                            <td><span class="badge-danger">MISMATCH</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">
            <a href="bank_reconciliation.php" class="btn btn-primary">🔴 Go to Reconciliation & Match</a>
        </div>
    
    <!-- OUTSTANDING DEPOSITS (Pesa system lakini haipo bank) -->
    <?php elseif ($report_type == 'outstanding'): ?>
        <h3>⏳ Outstanding Deposits</h3>
        <p class="alert alert-warning">Hizi ni pesa zilizorekodiwa SHULENI lakini HAZIJAONEKANA BENKI.</p>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Class</th><th>Reference</th><th>Amount</th><th>Payment Method</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (count($outstanding) == 0): ?>
                        <tr><td colspan="7" style="text-align:center; color:green;">✅ No outstanding deposits found!</td></tr>
                    <?php else: ?>
                        <?php foreach ($outstanding as $os): ?>
                        <tr style="background-color:#fff3e0;">
                            <td><?php echo htmlspecialchars($os['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($os['class']); ?></td>
                            <td><?php echo htmlspecialchars($os['transaction_ref']); ?></td>
                            <td style="color:#f39c12; font-weight:bold;">TZS <?php echo number_format($os['amount']); ?></td>
                            <td><?php echo ucfirst($os['payment_method']); ?></td>
                            <td><?php echo $os['transaction_date']; ?></td>
                            <td><a href="bank_reconciliation.php" class="btn-small">Check Bank</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    
    <!-- DEBT TRACKING REPORT -->
    <?php elseif ($report_type == 'debts'): ?>
        <h3>💰 Student Debt Tracking</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Student #</th><th>Name</th><th>Class</th><th>Invoices</th><th>Total Debt</th><th>Oldest Debt</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (count($debts) == 0): ?>
                        <tr><td colspan="7" style="text-align:center; color:green;">✅ No students with outstanding debt!</td></tr>
                    <?php else: ?>
                        <?php foreach ($debts as $debt): 
                            $overdue_days = (strtotime($debt['oldest_due']) < time()) ? ceil((time() - strtotime($debt['oldest_due'])) / (60*60*24)) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($debt['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($debt['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($debt['class']); ?></td>
                            <td><?php echo $debt['invoice_count']; ?></td>
                            <td style="color:red; font-weight:bold;">TZS <?php echo number_format($debt['total_debt']); ?></td>
                            <td><?php echo $debt['oldest_due']; ?> <?php echo $overdue_days > 0 ? "(<span style='color:red;'>$overdue_days days overdue</span>)" : ''; ?></td>
                            <td><a href="fee_management.php?student_id=<?php echo $debt['student_number']; ?>" class="btn-small">Collect Payment</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    
    <!-- STOCK AUDIT REPORT -->
    <?php elseif ($report_type == 'stock_audit'): ?>
        <h3>📦 Stock Audit Report</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Item Code</th><th>Item Name</th><th>Current Stock</th><th>Total Sold</th><th>Reorder Level</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($stock_audit as $sa): ?>
                    <tr <?php echo $sa['stock_status'] == 'Low Stock' ? 'style="background-color:#ffebee;"' : ''; ?>>
                        <td><?php echo htmlspecialchars($sa['item_code']); ?></td>
                        <td><?php echo htmlspecialchars($sa['item_name']); ?></td>
                        <td><?php echo $sa['system_stock']; ?></td>
                        <td><?php echo $sa['total_sold']; ?></td>
                        <td><?php echo $sa['reorder_level']; ?></td>
                        <td>
                            <?php if ($sa['stock_status'] == 'Low Stock'): ?>
                                <span style="color:red;">🔴 Low Stock</span>
                            <?php else: ?>
                                <span style="color:green;">✅ OK</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="inventory.php" class="btn-small">Manage Stock</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    
    <!-- FINAL RECONCILIATION REPORT -->
    <?php elseif ($report_type == 'reconciliation'): ?>
        <h3>🔄 Final Reconciliation Report</h3>
        
        <div class="alert <?php echo $variance == 0 ? 'alert-success' : 'alert-danger'; ?>">
            <strong>Reconciliation Status:</strong>
            <?php if ($variance == 0): ?>
                ✅ RECONCILED - Bank and School books match 100%
            <?php else: ?>
                ⚠️ DISCREPANCY - Variance of TZS <?php echo number_format(abs($variance)); ?>
            <?php endif; ?>
        </div>
        
        <div class="two-columns">
            <div class="form-card">
                <h3>🏦 Bank Side</h3>
                <table class="data-table">
                    <tr><td>Total Matched Credits</td><td><strong>TZS <?php echo number_format($bank_total); ?></strong></td></tr>
                    <tr style="color:red;"><td>Unmatched (Mismatch)</td><td><?php echo $unmatched_bank['count']; ?> transactions - TZS <?php echo number_format($unmatched_bank['total']); ?></td></tr>
                </table>
            </div>
            <div class="form-card">
                <h3>📚 School System Side</h3>
                <table class="data-table">
                    <tr><td>Total Reconciled Credits</td><td><strong>TZS <?php echo number_format($system_total); ?></strong></td></tr>
                    <tr style="color:#f39c12;"><td>Outstanding Deposits</td><td><?php echo $outstanding_system['count']; ?> transactions - TZS <?php echo number_format($outstanding_system['total']); ?></td></tr>
                </table>
            </div>
        </div>
        
        <div class="action-buttons" style="margin-top: 1rem;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
            <a href="bank_reconciliation.php" class="btn btn-warning">🔴 Fix Mismatches</a>
        </div>
    <?php endif; ?>
</div>

<style>
.report-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.report-nav a {
    padding: 0.4rem 0.8rem;
    background: #f0f2f5;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    font-size: 0.8rem;
}
.report-nav a:hover, .report-nav a.active {
    background: #667eea;
    color: white;
}
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.badge-danger {
    background: #dc3545;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.7rem;
}
.stat-card.success .stat-value { color: #28a745; }
.stat-card.danger .stat-value { color: #dc3545; }
</style>

<?php include 'includes/footer.php'; ?>