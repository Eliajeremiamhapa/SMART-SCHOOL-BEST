<?php
require_once 'config/database.php';
$page_title = "Accountant Dashboard";
include 'includes/header.php';

// Get statistics
$stats = [];

// Total students
$stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE is_active = 1");
$stats['total_students'] = $stmt->fetch()['total'];

// Total debt
$stmt = $pdo->query("SELECT SUM(balance) as total_debt FROM invoices WHERE status != 'paid'");
$stats['total_debt'] = $stmt->fetch()['total_debt'] ?? 0;

// Today's collections
$stmt = $pdo->prepare("SELECT SUM(amount) as today_collection FROM transactions WHERE DATE(transaction_date) = CURDATE()");
$stmt->execute();
$stats['today_collection'] = $stmt->fetch()['today_collection'] ?? 0;

// FIXED: Count ALL unreconciled transactions (pending + mismatch)
$stmt = $pdo->query("SELECT COUNT(*) as unreconciled FROM bank_transactions WHERE match_status IN ('pending', 'mismatch')");
$stats['unreconciled'] = $stmt->fetch()['unreconciled'];

// Also get separate counts for display
$stmt = $pdo->query("SELECT COUNT(*) as pending FROM bank_transactions WHERE match_status = 'pending'");
$stats['pending_recon'] = $stmt->fetch()['pending'];

$stmt = $pdo->query("SELECT COUNT(*) as mismatch FROM bank_transactions WHERE match_status = 'mismatch'");
$stats['mismatch_count'] = $stmt->fetch()['mismatch'];

// Low stock alerts
$stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM low_stock_alerts WHERE status = 'pending'");
$stats['low_stock_alerts'] = $stmt->fetch()['low_stock'];

// Get total mismatch amount
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as mismatch_total FROM bank_transactions WHERE match_status = 'mismatch'");
$stats['mismatch_total'] = $stmt->fetch()['mismatch_total'];
?>

<div class="dashboard-container">
    <h1>Karibu, Accountant</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
            <div class="stat-label">Wanafunzi</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon">💰</div>
            <div class="stat-value">TZS <?php echo number_format($stats['total_debt']); ?></div>
            <div class="stat-label">Jumla ya Madeni</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">💵</div>
            <div class="stat-value">TZS <?php echo number_format($stats['today_collection']); ?></div>
            <div class="stat-label">Malipo ya Leo</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">🔄</div>
            <div class="stat-value"><?php echo $stats['unreconciled']; ?></div>
            <div class="stat-label">Miamala Isiyolingana</div>
            <small style="font-size:0.7rem;">(Pending: <?php echo $stats['pending_recon']; ?> | Mismatch: <?php echo $stats['mismatch_count']; ?>)</small>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?php echo $stats['low_stock_alerts']; ?></div>
            <div class="stat-label">Low Stock Alerts</div>
        </div>
    </div>
    
    <!-- Mismatch Amount Alert -->
    <?php if ($stats['mismatch_total'] > 0): ?>
    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
        <strong>⚠️ Tahadhari!</strong> Kuna pesa TZS <?php echo number_format($stats['mismatch_total']); ?> zilizoko BENKI lakini HAZIPO kwenye mfumo wa shule!
        <a href="bank_reconciliation.php" style="float:right; color:#721c24;">Angalia Mismatches →</a>
    </div>
    <?php endif; ?>
    
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="fee_management.php" class="btn btn-primary">➕ Collect Fee</a>
            <a href="import_statement.php" class="btn btn-secondary">📥 Import Bank Statement</a>
            <a href="bank_reconciliation.php" class="btn btn-warning">🔄 Auto-Reconcile</a>
            <a href="smart_cards.php" class="btn btn-info">💳 Top-up Card</a>
            <a href="inventory.php" class="btn btn-success">📦 Manage Stock</a>
            <a href="reports.php" class="btn btn-dark">📊 View Reports</a>
        </div>
    </div>
</div>

<style>
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}
</style>

<?php include 'includes/footer.php'; ?>