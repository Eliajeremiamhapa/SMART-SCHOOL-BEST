<?php
require_once 'config/database.php';
$page_title = "Debt Tracking";
include 'includes/header.php';

// Handle partial payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_payment'])) {
    $invoice_id = $_POST['invoice_id'];
    $payment_amount = $_POST['payment_amount'];
    $payment_method = $_POST['payment_method'];
    $transaction_ref = 'PAY_' . time() . '_' . rand(1000, 9999);
    
    try {
        $pdo->beginTransaction();
        
        // Get invoice details
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND status != 'paid'");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
        
        if ($invoice) {
            $new_amount_paid = $invoice['amount_paid'] + $payment_amount;
            $new_status = $new_amount_paid >= $invoice['amount'] ? 'paid' : 'partial';
            
            // Update invoice
            $stmt = $pdo->prepare("UPDATE invoices SET amount_paid = ?, status = ? WHERE id = ?");
            $stmt->execute([$new_amount_paid, $new_status, $invoice_id]);
            
            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, invoice_id, amount, payment_method, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$transaction_ref, $invoice['student_id'], $invoice_id, $payment_amount, $payment_method]);
            
            $pdo->commit();
            $success = "Payment of TZS " . number_format($payment_amount) . " recorded successfully!";
        } else {
            $error = "Invoice not found or already paid.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Get all students with debts
$debtors = $pdo->query("
    SELECT 
        s.id,
        s.student_number,
        s.full_name,
        s.class,
        s.parent_phone,
        SUM(i.balance) as total_debt,
        COUNT(i.id) as invoice_count,
        MIN(i.due_date) as oldest_due_date
    FROM students s
    JOIN invoices i ON s.id = i.student_id
    WHERE i.status != 'paid' AND i.balance > 0
    GROUP BY s.id
    ORDER BY total_debt DESC
")->fetchAll();

// Get detailed invoices for selected student
$selected_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$student_invoices = [];
$current_student = null;

if ($selected_student > 0) {
    // Get student details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$selected_student]);
    $current_student = $stmt->fetch();
    
    // Get unpaid invoices for this student
    $stmt = $pdo->prepare("
        SELECT i.*, rc.category_name 
        FROM invoices i
        JOIN revenue_categories rc ON i.category_id = rc.id
        WHERE i.student_id = ? AND i.status != 'paid' AND i.balance > 0
        ORDER BY i.due_date ASC
    ");
    $stmt->execute([$selected_student]);
    $student_invoices = $stmt->fetchAll();
}

// Summary statistics
$total_debt_all = $pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM invoices WHERE status != 'paid'")->fetch()['total'];
$debtor_count = count($debtors);
$avg_debt = $debtor_count > 0 ? $total_debt_all / $debtor_count : 0;
?>

<div class="container">
    <h1>💰 Debt Tracking Management</h1>
    
    <!-- Summary Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card danger">
            <div class="stat-icon">💸</div>
            <div class="stat-value">TZS <?php echo number_format($total_debt_all); ?></div>
            <div class="stat-label">Total Outstanding Debt</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-value"><?php echo $debtor_count; ?></div>
            <div class="stat-label">Students with Debt</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value">TZS <?php echo number_format($avg_debt); ?></div>
            <div class="stat-label">Average Debt per Student</div>
        </div>
    </div>
    
    <!-- Debtors List -->
    <div class="form-card">
        <h3>📋 Students with Outstanding Debt</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student #</th>
                        <th>Full Name</th>
                        <th>Class</th>
                        <th>Total Debt</th>
                        <th>Invoices</th>
                        <th>Oldest Debt</th>
                        <th>Parent Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($debtors) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; color:green;">
                                ✅ No outstanding debts! All invoices are paid.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($debtors as $debtor): 
                            $overdue_days = (strtotime($debtor['oldest_due_date']) < time()) ? ceil((time() - strtotime($debtor['oldest_due_date'])) / (60 * 60 * 24)) : 0;
                            $row_class = $overdue_days > 90 ? 'overdue-90' : ($overdue_days > 60 ? 'overdue-60' : ($overdue_days > 30 ? 'overdue-30' : ''));
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo htmlspecialchars($debtor['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($debtor['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($debtor['class']); ?></td>
                            <td style="color:red; font-weight:bold;">TZS <?php echo number_format($debtor['total_debt']); ?></td>
                            <td><?php echo $debtor['invoice_count']; ?></td>
                            <td>
                                <?php echo $debtor['oldest_due_date']; ?>
                                <?php if ($overdue_days > 0): ?>
                                    <span class="overdue-badge">(<?php echo $overdue_days; ?> days overdue)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($debtor['parent_phone']); ?></td>
                            <td>
                                <a href="?student_id=<?php echo $debtor['id']; ?>" class="btn-small">View Details</a>
                                <button onclick="sendReminder('<?php echo $debtor['parent_phone']; ?>', '<?php echo htmlspecialchars($debtor['full_name']); ?>', <?php echo $debtor['total_debt']; ?>)" class="btn-small" style="background:#f39c12;">Send SMS</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Student Details and Payment Section -->
    <?php if ($selected_student > 0 && $current_student): ?>
    <div class="form-card">
        <h3>🎓 Student Details: <?php echo htmlspecialchars($current_student['full_name']); ?></h3>
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div><strong>Student #:</strong> <?php echo htmlspecialchars($current_student['student_number']); ?></div>
            <div><strong>Class:</strong> <?php echo htmlspecialchars($current_student['class']); ?></div>
            <div><strong>Parent Phone:</strong> <?php echo htmlspecialchars($current_student['parent_phone']); ?></div>
            <div><strong>Total Debt:</strong> <span style="color:red;">TZS <?php echo number_format(array_sum(array_column($student_invoices, 'balance'))); ?></span></div>
        </div>
        
        <h4>📄 Unpaid Invoices</h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Category</th>
                        <th>Total Amount</th>
                        <th>Amount Paid</th>
                        <th>Balance</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_invoices as $inv): 
                        $overdue = strtotime($inv['due_date']) < time();
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                        <td><?php echo htmlspecialchars($inv['category_name']); ?></td>
                        <td>TZS <?php echo number_format($inv['amount']); ?></td>
                        <td>TZS <?php echo number_format($inv['amount_paid']); ?></td>
                        <td style="color:red;">TZS <?php echo number_format($inv['balance']); ?></td>
                        <td <?php echo $overdue ? 'style="color:red;"' : ''; ?>>
                            <?php echo $inv['due_date']; ?>
                            <?php echo $overdue ? ' (Overdue)' : ''; ?>
                        </td>
                        <td><?php echo ucfirst($inv['status']); ?></td>
                        <td>
                            <button onclick="openPaymentModal(<?php echo $inv['id']; ?>, <?php echo $inv['balance']; ?>)" class="btn-small" style="background:#27ae60;">Make Payment</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1rem;">
            <a href="debts.php" class="btn">← Back to Debtors List</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- SMS Reminder Modal -->
    <div id="smsModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeSMSModal()">&times;</span>
            <h3>📱 Send SMS Reminder</h3>
            <div id="smsContent">
                <p><strong>Parent Phone:</strong> <span id="smsPhone"></span></p>
                <p><strong>Student:</strong> <span id="smsStudent"></span></p>
                <p><strong>Amount Due:</strong> <span id="smsAmount"></span></p>
                <hr>
                <p><strong>SMS Preview:</strong></p>
                <div id="smsPreview" style="background:#f0f2f5; padding:0.75rem; border-radius:5px; margin:0.5rem 0;"></div>
                <button onclick="sendSMS()" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">Send SMS Reminder</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closePaymentModal()">&times;</span>
        <h3>💰 Make Payment</h3>
        <form method="POST">
            <input type="hidden" name="invoice_id" id="payment_invoice_id">
            <div class="form-group">
                <label>Payment Amount (TZS)</label>
                <input type="number" name="payment_amount" id="payment_amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="cash">Cash</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="tigopesa">Tigo Pesa</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Smart Card</option>
                </select>
            </div>
            <button type="submit" name="make_payment" class="btn btn-success">Process Payment</button>
        </form>
    </div>
</div>

<script>
// SMS Reminder Functions
let currentSMSData = {};

function sendReminder(phone, studentName, amount) {
    currentSMSData = { phone, studentName, amount };
    document.getElementById('smsPhone').innerText = phone;
    document.getElementById('smsStudent').innerText = studentName;
    document.getElementById('smsAmount').innerText = 'TZS ' + amount.toLocaleString();
    
    const preview = `Dear Parent/Guardian of ${studentName},\n\nYour child has an outstanding school fee balance of TZS ${amount.toLocaleString()}. Please clear the debt as soon as possible to avoid penalties.\n\nThank you,\nSSMS Tanzania`;
    document.getElementById('smsPreview').innerText = preview;
    
    document.getElementById('smsModal').style.display = 'flex';
}

function closeSMSModal() {
    document.getElementById('smsModal').style.display = 'none';
}

function sendSMS() {
    // In real system, this would call SMS API
    alert(`SMS sent to ${currentSMSData.phone}\n\nReminder sent for ${currentSMSData.studentName} - Balance TZS ${currentSMSData.amount.toLocaleString()}`);
    closeSMSModal();
}

// Payment Modal Functions
function openPaymentModal(invoiceId, balance) {
    document.getElementById('payment_invoice_id').value = invoiceId;
    document.getElementById('payment_amount').value = balance;
    document.getElementById('payment_amount').max = balance;
    document.getElementById('paymentModal').style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const paymentModal = document.getElementById('paymentModal');
    const smsModal = document.getElementById('smsModal');
    if (event.target == paymentModal) closePaymentModal();
    if (event.target == smsModal) closeSMSModal();
}
</script>

<style>
.overdue-30 {
    background-color: #fff3cd !important;
}
.overdue-60 {
    background-color: #ffe0b2 !important;
}
.overdue-90 {
    background-color: #ffccbc !important;
}
.overdue-badge {
    background: #e74c3c;
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    margin-left: 0.5rem;
}
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
@media (max-width: 768px) {
    .data-table th, .data-table td {
        font-size: 0.75rem;
        padding: 0.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>