<?php
// STUDENTS/index.php
require_once '../config/database.php';

// Only student can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$page_title = "Student Dashboard";
include 'includes/student_header.php';

// Now use $student variables from header (student_id, student_number, student_class, student_name)
// If $student_id is not set from header, try to fetch
if (!isset($student_id) || $student_id == 0) {
    // Try to get student by username (student_number)
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
    $stmt->execute([$_SESSION['username']]);
    $student_data = $stmt->fetch();
    
    if ($student_data) {
        $student_id = $student_data['id'];
        $student_number = $student_data['student_number'];
        $student_class = $student_data['class'];
        $student_name = $student_data['full_name'];
    } else {
        // Try by full_name
        $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
        $stmt->execute([$_SESSION['full_name']]);
        $student_data = $stmt->fetch();
        
        if ($student_data) {
            $student_id = $student_data['id'];
            $student_number = $student_data['student_number'];
            $student_class = $student_data['class'];
            $student_name = $student_data['full_name'];
        } else {
            // Create basic student record
            $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$_SESSION['username'], $_SESSION['full_name'], 'Not Assigned', '']);
            
            $student_id = $pdo->lastInsertId();
            $student_number = $_SESSION['username'];
            $student_class = 'Not Assigned';
            $student_name = $_SESSION['full_name'];
        }
    }
}

// Get statistics from existing tables
$stats = [];

// Get total fees paid (from transactions)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM transactions WHERE student_id = ?");
$stmt->execute([$student_id]);
$stats['total_paid'] = $stmt->fetch()['total_paid'];

// Get fee balance (from invoices)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) as fee_balance FROM invoices WHERE student_id = ? AND status != 'paid'");
$stmt->execute([$student_id]);
$stats['fee_balance'] = $stmt->fetch()['fee_balance'];

// Get smart card balance
$stmt = $pdo->prepare("SELECT COALESCE(balance, 0) as card_balance FROM smart_cards WHERE student_id = ? AND is_active = 1");
$stmt->execute([$student_id]);
$card = $stmt->fetch();
$stats['card_balance'] = $card['card_balance'] ?? 0;

// Get number of transactions
$stmt = $pdo->prepare("SELECT COUNT(*) as transaction_count FROM transactions WHERE student_id = ?");
$stmt->execute([$student_id]);
$stats['transaction_count'] = $stmt->fetch()['transaction_count'];

// Get recent transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE student_id = ? ORDER BY transaction_date DESC LIMIT 5");
$stmt->execute([$student_id]);
$recent_transactions = $stmt->fetchAll();
?>

<div class="container">
    <h1>🎓 Welcome, <?php echo htmlspecialchars($student_name); ?>!</h1>
    <p>Class: <?php echo htmlspecialchars($student_class); ?> | Student ID: <?php echo htmlspecialchars($student_number); ?></p>
    
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">TZS <?php echo number_format($stats['total_paid']); ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card <?php echo $stats['fee_balance'] > 0 ? 'danger' : 'success'; ?>">
            <div class="stat-icon">💳</div>
            <div class="stat-value">TZS <?php echo number_format($stats['fee_balance']); ?></div>
            <div class="stat-label">Fee Balance</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💎</div>
            <div class="stat-value">TZS <?php echo number_format($stats['card_balance']); ?></div>
            <div class="stat-label">Smart Card Balance</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?php echo $stats['transaction_count']; ?></div>
            <div class="stat-label">Transactions</div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="form-card">
        <h3>📋 Recent Transactions</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_transactions)): ?>
                        <tr style="background-color: #f8f9fa;">
                            <td colspan="5" style="text-align:center; color: #666;">📌 No transactions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $trans): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($trans['transaction_ref']); ?></strong></td>
                            <td>TZS <?php echo number_format($trans['amount']); ?></td>
                            <td><?php echo ucfirst($trans['payment_method']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($trans['transaction_date'])); ?></td>
                            <td>
                                <?php if ($trans['is_reconciled']): ?>
                                    <span style="color:green;">✅ Reconciled</span>
                                <?php else: ?>
                                    <span style="color:orange;">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="form-card">
        <h3>⚡ Quick Links</h3>
        <div class="action-buttons" style="display: flex; flex-wrap: wrap; gap: 1rem;">
            <a href="profile.php" class="btn btn-primary" style="background: #1e3c72;">👤 My Profile</a>
            <a href="card_info.php" class="btn btn-primary" style="background: #1e3c72;">💳 Smart Card Info</a>
        </div>
    </div>
</div>

<?php include 'includes/student_footer.php'; ?>