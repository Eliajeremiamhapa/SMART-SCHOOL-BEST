<?php
require_once 'config/database.php';
$page_title = "Fee Management";
include 'includes/header.php';

$error = '';
$success = '';

// Create student_payments table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS student_payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    control_number VARCHAR(100) UNIQUE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'expired') DEFAULT 'pending',
    transaction_ref VARCHAR(100),
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    INDEX idx_control_number (control_number),
    INDEX idx_status (status)
)");

// Handle fee collection submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['collect_fee'])) {
    $student_id = $_POST['student_id'];
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    // Use custom reference if provided, otherwise generate one
    if (!empty($_POST['transaction_ref'])) {
        $transaction_ref = $_POST['transaction_ref'];
    } else {
        $transaction_ref = 'TXN_' . time() . '_' . rand(1000, 9999);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get student's open invoice or create new
        $stmt = $pdo->prepare("SELECT id, amount_paid, amount FROM invoices WHERE student_id = ? AND category_id = ? AND status != 'paid' LIMIT 1");
        $stmt->execute([$student_id, $category_id]);
        $invoice = $stmt->fetch();
        
        if ($invoice) {
            $new_amount_paid = $invoice['amount_paid'] + $amount;
            $new_status = $new_amount_paid >= $invoice['amount'] ? 'paid' : 'partial';
            
            $stmt = $pdo->prepare("UPDATE invoices SET amount_paid = ?, status = ? WHERE id = ?");
            $stmt->execute([$new_amount_paid, $new_status, $invoice['id']]);
            $invoice_id = $invoice['id'];
        } else {
            // Create new invoice
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, student_id, category_id, amount, amount_paid, term, academic_year, issue_date, due_date) VALUES (?, ?, ?, ?, ?, 'Term 1', '2025', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))");
            $invoice_number = 'INV-' . date('Ymd') . '-' . $student_id . '-' . rand(100, 999);
            $stmt->execute([$invoice_number, $student_id, $category_id, $amount, $amount]);
            $invoice_id = $pdo->lastInsertId();
        }
        
        // Record transaction with custom reference
        $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, invoice_id, amount, payment_method, transaction_date, is_reconciled) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$transaction_ref, $student_id, $invoice_id, $amount, $payment_method]);
        
        // If payment is via card, update card balance
        if ($payment_method == 'card') {
            $card_id = $_POST['card_id'] ?? null;
            if ($card_id) {
                $stmt = $pdo->prepare("UPDATE smart_cards SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $card_id]);
            }
        }
        
        $pdo->commit();
        $success = "✅ Malipo ya TZS " . number_format($amount) . " yamepokelewa kikamilifu! Transaction Reference: " . $transaction_ref;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle Edit Transaction Amount
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_amount = $_POST['amount'];
    $edit_reason = trim($_POST['edit_reason']);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $old = $stmt->fetch();
        
        if (!$old) {
            throw new Exception("Transaction not found");
        }
        
        if ($old['is_reconciled'] == 1) {
            throw new Exception("Cannot edit a transaction that has already been reconciled with bank statement!");
        }
        
        $stmt = $pdo->prepare("UPDATE transactions SET amount = ?, notes = CONCAT(IFNULL(notes, ''), ' | EDITED: ', ?, ' (Old amount: ', ?, ')') WHERE id = ?");
        $stmt->execute([$new_amount, $edit_reason, $old['amount'], $transaction_id]);
        
        if ($old['invoice_id']) {
            $stmt = $pdo->prepare("UPDATE invoices SET amount_paid = amount_paid - ? + ? WHERE id = ?");
            $stmt->execute([$old['amount'], $new_amount, $old['invoice_id']]);
        }
        
        $pdo->commit();
        $success = "✅ Transaction updated successfully! New amount: TZS " . number_format($new_amount);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ " . $e->getMessage();
    }
}

// Handle Edit Transaction Reference
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_reference'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_reference = trim($_POST['new_reference']);
    $reason = trim($_POST['reason']);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT is_reconciled, transaction_ref FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $txn = $stmt->fetch();
        
        if (!$txn) {
            throw new Exception("Transaction not found!");
        }
        
        if ($txn['is_reconciled'] == 1) {
            throw new Exception("Cannot edit reference - transaction is already reconciled! Please undo reconciliation first from Bank Reconciliation page.");
        }
        
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE transaction_ref = ? AND id != ?");
        $stmt->execute([$new_reference, $transaction_id]);
        if ($stmt->fetch()) {
            throw new Exception("Reference number '{$new_reference}' already exists! Please use a different reference.");
        }
        
        $old_ref = $txn['transaction_ref'];
        
        $stmt = $pdo->prepare("UPDATE transactions SET transaction_ref = ?, notes = CONCAT(IFNULL(notes, ''), ' | REFERENCE CHANGED: ', ?, ' (Old: ', ?, ')') WHERE id = ?");
        $stmt->execute([$new_reference, $reason, $old_ref, $transaction_id]);
        
        $pdo->commit();
        $success = "✅ Transaction reference changed from '{$old_ref}' to '{$new_reference}'!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ " . $e->getMessage();
    }
}

// Handle Delete Transaction
if (isset($_GET['delete_id'])) {
    $transaction_id = $_GET['delete_id'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new Exception("Transaction not found!");
        }
        
        if ($transaction['is_reconciled'] == 1) {
            throw new Exception("Cannot delete a transaction that has already been reconciled with bank statement! Please unreconcile it first from Bank Reconciliation page.");
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bank_transactions WHERE matched_transaction_id = ?");
        $stmt->execute([$transaction_id]);
        $linked = $stmt->fetchColumn();
        
        if ($linked > 0) {
            throw new Exception("Cannot delete this transaction because it has been matched with a bank statement. Please unmatch it first from Bank Reconciliation page.");
        }
        
        if ($transaction['invoice_id']) {
            $stmt = $pdo->prepare("UPDATE invoices SET amount_paid = amount_paid - ? WHERE id = ?");
            $stmt->execute([$transaction['amount'], $transaction['invoice_id']]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        
        $pdo->commit();
        $success = "✅ Transaction deleted successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ " . $e->getMessage();
    }
}

// Get all students
$students = $pdo->query("SELECT id, student_number, full_name, class, parent_phone FROM students WHERE is_active = 1 ORDER BY full_name")->fetchAll();

// Get revenue categories
$categories = $pdo->query("SELECT id, category_name FROM revenue_categories WHERE is_active = 1")->fetchAll();
?>

<div class="container">
    <h1>💰 Fee Collection</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <h3>Collect Payment</h3>
        <form method="POST" id="feeForm">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" id="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['student_number'] . ' - ' . $student['full_name'] . ' (' . $student['class'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Payment Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Amount (TZS)</label>
                <input type="number" name="amount" id="payment_amount" step="0.01" required placeholder="Enter amount">
            </div>
            
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="cash">💵 Cash</option>
                    <option value="mpesa">📱 M-Pesa</option>
                    <option value="tigopesa">📱 Tigo Pesa</option>
                    <option value="bank_transfer">🏦 Bank Transfer</option>
                    <option value="card">💳 Smart Card</option>
                    <option value="mobile_money">📱 Lipia kwa Simu (TigoPesa/M-Pesa/Airtel)</option>
                </select>
            </div>
            
            <div class="form-group" id="reference_group">
                <label>🔗 Transaction Reference (For Bank Matching)</label>
                <input type="text" name="transaction_ref" id="transaction_ref" placeholder="e.g., TXN_001, M-Pesa Ref: RTVX7T3L, or Bank Reference">
                <small style="color: #666; display: block; margin-top: 5px;">
                    ⚠️ MUHIMU: Weka reference number kutoka M-Pesa au benki ili mfumo uweze kulinganisha na bank statement automatically!
                    <br>Ukiacha tupu, mfumo uta generate yake mwenyewe.
                </small>
            </div>
            
            <div class="form-group" id="card_group" style="display:none;">
                <label>Smart Card ID</label>
                <input type="text" name="card_id" placeholder="Enter Card UID or scan QR">
            </div>
            
            <div id="payment_status" style="margin-top: 1rem;"></div>
            
            <button type="submit" name="collect_fee" class="btn btn-primary" id="submitBtn">💵 Process Payment</button>
            <button type="button" class="btn btn-success" id="mobilePaymentBtn" style="background:#25D366; display:none;" onclick="initiateMobilePayment()">📱 Lipia kwa Simu (TigoPesa/M-Pesa)</button>
        </form>
    </div>
    
    <div class="recent-transactions">
        <h3>📋 Recent Transactions</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref No</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Reconciled</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = $pdo->query("SELECT t.*, s.full_name FROM transactions t JOIN students s ON t.student_id = s.id ORDER BY t.transaction_date DESC LIMIT 20")->fetchAll();
                    foreach ($recent as $row): 
                        $reconciled_status = $row['is_reconciled'] ? '✅ Yes' : '⏳ No';
                        $reconciled_class = $row['is_reconciled'] ? 'success' : 'warning';
                        $can_edit_delete = $row['is_reconciled'] == 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['transaction_ref']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td>TZS <?php echo number_format($row['amount']); ?></td>
                        <td><?php echo ucfirst($row['payment_method']); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($row['transaction_date'])); ?></td>
                        <td class="<?php echo $reconciled_class; ?>"><?php echo $reconciled_status; ?></td>
                        <td>
                            <?php if ($can_edit_delete): ?>
                                <button class="btn-small" style="background:#17a2b8;" onclick="openFixRefModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['transaction_ref']); ?>')">🔄 Fix Ref</button>
                                <button class="btn-small" style="background:#ffc107; color:#333;" onclick="openEditModal(<?php echo $row['id']; ?>, <?php echo $row['amount']; ?>, '<?php echo htmlspecialchars($row['transaction_ref']); ?>')">✏️ Edit</button>
                                <button class="btn-small" style="background:#dc3545;" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['transaction_ref']); ?>')">🗑️ Delete</button>
                            <?php else: ?>
                                <span style="color:#999; font-size:0.7rem;">🔒 Locked (Reconciled)</span>
                                <br>
                                <small><a href="bank_reconciliation.php" style="font-size:0.7rem;">Undo first</a></small>
                            <?php endif; ?>
                         </small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
        </div>
    </div>
</div>

<!-- Edit Amount Modal -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3 style="color: #ffc107;">✏️ Edit Transaction Amount</h3>
        <form method="POST">
            <input type="hidden" name="transaction_id" id="edit_txn_id">
            <div class="form-group">
                <label>Transaction Reference</label>
                <input type="text" id="edit_txn_ref" readonly style="background:#f0f2f5; width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:5px;">
                <small>To change reference number, use "Fix Ref" button</small>
            </div>
            <div class="form-group">
                <label>Current Amount</label>
                <input type="text" id="edit_current_amount" readonly style="background:#f0f2f5; width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:5px;">
            </div>
            <div class="form-group">
                <label>New Amount (TZS)</label>
                <input type="number" name="amount" id="edit_amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Reason for Edit</label>
                <textarea name="edit_reason" required placeholder="e.g., Wrong amount entered, correction, adjustment" rows="3"></textarea>
            </div>
            <button type="submit" name="edit_transaction" class="btn btn-warning">💾 Save Changes</button>
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- Fix Reference Modal -->
<div id="fixRefModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeFixRefModal()">&times;</span>
        <h3 style="color: #17a2b8;">🔄 Fix Transaction Reference</h3>
        <form method="POST">
            <input type="hidden" name="transaction_id" id="fix_txn_id">
            <div class="form-group">
                <label>Current Reference (Wrong)</label>
                <input type="text" id="fix_old_ref" readonly style="background:#f0f2f5; width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:5px;">
            </div>
            <div class="form-group">
                <label>New Correct Reference</label>
                <input type="text" name="new_reference" id="fix_new_ref" required placeholder="Enter correct reference number">
                <small>Use the exact reference from bank statement or M-Pesa</small>
            </div>
            <div class="form-group">
                <label>Reason for Correction</label>
                <textarea name="reason" required placeholder="e.g., Wrong reference entered, typo correction" rows="2"></textarea>
            </div>
            <div class="alert alert-warning" style="background:#fff3cd; padding:0.75rem; border-radius:5px; margin-bottom:1rem;">
                ⚠️ Kumbuka: Kubadilisha reference kutaathiri bank reconciliation. Hakikisha reference ni sahihi kabisa!
            </div>
            <button type="submit" name="edit_reference" class="btn btn-info" style="background:#17a2b8;">🔄 Change Reference</button>
            <button type="button" class="btn btn-secondary" onclick="closeFixRefModal()">Cancel</button>
        </form>
    </div>
</div>

<style>
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.success {
    color: #27ae60;
    font-weight: bold;
}
.warning {
    color: #f39c12;
    font-weight: bold;
}
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    position: relative;
}
.close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
}
.close:hover {
    color: #333;
}
.btn-warning {
    background: #ffc107;
    color: #333;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 0.5rem;
}
.btn-info {
    background: #17a2b8;
    color: white;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 0.5rem;
}
.btn-success {
    background: #25D366;
    color: white;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 0.5rem;
}
.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>

<script>
// Show/hide card field based on payment method
document.getElementById('payment_method').addEventListener('change', function() {
    var cardGroup = document.getElementById('card_group');
    var mobileBtn = document.getElementById('mobilePaymentBtn');
    var submitBtn = document.getElementById('submitBtn');
    var referenceGroup = document.getElementById('reference_group');
    
    if (this.value === 'card') {
        cardGroup.style.display = 'block';
        mobileBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
        referenceGroup.style.display = 'block';
    } else if (this.value === 'mobile_money') {
        cardGroup.style.display = 'none';
        mobileBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
        referenceGroup.style.display = 'block';
    } else {
        cardGroup.style.display = 'none';
        mobileBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
        referenceGroup.style.display = 'block';
    }
});

// Auto-generate reference preview
document.getElementById('transaction_ref').addEventListener('focus', function() {
    if (this.value === '') {
        var suggested = 'TXN_' + new Date().getTime().toString().slice(-8);
        this.placeholder = 'e.g., ' + suggested;
    }
});

// Mobile Payment Function
function initiateMobilePayment() {
    var studentId = document.getElementById('student_id').value;
    var amount = document.getElementById('payment_amount').value;
    var categoryId = document.getElementById('category_id').value;
    var transactionRef = document.getElementById('transaction_ref').value;
    
    if (!studentId) {
        alert('Tafadhali chagua mwanafunzi');
        return;
    }
    if (!amount || amount <= 0) {
        alert('Tafadhali weka kiasi cha kulipa');
        return;
    }
    if (!categoryId) {
        alert('Tafadhali chagua aina ya malipo');
        return;
    }
    
    document.getElementById('payment_status').innerHTML = '<div class="alert alert-info">⏳ Inatengeneza control number...</div>';
    
    // First, create a pending payment record
    fetch('api/create_pending_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            student_id: studentId, 
            amount: amount, 
            description: 'School Fees Payment',
            transaction_ref: transactionRef,
            category_id: categoryId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('payment_status').innerHTML = `
                <div class="alert alert-success">
                    ✅ Malipo yameanza!<br>
                    Control Number: <strong>${data.control_number}</strong><br>
                    Kiasi: TZS ${Number(amount).toLocaleString()}<br>
                    <hr>
                    <strong>📱 Mwongozo wa Kulipa:</strong><br>
                    1. Fungua TigoPesa / M-Pesa / Airtel Money<br>
                    2. Chagua <strong>"Lipa kwa Biashara"</strong><br>
                    3. Weka Business Number: <strong>${data.control_number}</strong><br>
                    4. Weka Kiasi: ${Number(amount).toLocaleString()}<br>
                    5. Weka Reference: ${data.control_number}<br>
                    6. Thibitisha malipo yako<br>
                    <br>
                    Utapokea SMS uthibitisho baada ya malipo kukamilika.
                    <br><br>
                    <button class="btn btn-primary" onclick="checkPaymentStatus('${data.control_number}')">🔍 Angalia Status ya Malipo</button>
                </div>
            `;
            // Auto-check payment status every 15 seconds
            autoCheckPaymentStatus(data.control_number);
        } else {
            document.getElementById('payment_status').innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
        }
    })
    .catch(error => {
        document.getElementById('payment_status').innerHTML = `<div class="alert alert-danger">❌ Error: ${error}</div>`;
    });
}

let statusCheckInterval;

function autoCheckPaymentStatus(controlNumber) {
    let attempts = 0;
    const maxAttempts = 40; // Check for 10 minutes (every 15 seconds)
    
    if (statusCheckInterval) clearInterval(statusCheckInterval);
    
    statusCheckInterval = setInterval(() => {
        attempts++;
        fetch(`api/check_payment_status.php?control_number=${controlNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    clearInterval(statusCheckInterval);
                    document.getElementById('payment_status').innerHTML = `
                        <div class="alert alert-success">
                            ✅ MALIPO YAMEKUBALIWA!<br>
                            Transaction Ref: ${data.transaction_ref}<br>
                            Ukurasa uta refresh kwa sekunde chache...
                        </div>
                    `;
                    setTimeout(() => location.reload(), 3000);
                } else if (data.status === 'failed') {
                    clearInterval(statusCheckInterval);
                    document.getElementById('payment_status').innerHTML = `
                        <div class="alert alert-danger">
                            ❌ Malipo yameshindikana. Tafadhali jaribu tena.
                        </div>
                    `;
                } else if (attempts >= maxAttempts) {
                    clearInterval(statusCheckInterval);
                    document.getElementById('payment_status').innerHTML = `
                        <div class="alert alert-warning">
                            ⏳ Malipo bado hayajakamilika. Utapokea SMS uthibitisho baada ya kukamilika.<br>
                            Unaweza kuangalia tena baadaye kwa kubonyeza "Angalia Status ya Malipo".
                        </div>
                    `;
                }
            });
    }, 15000);
}

function checkPaymentStatus(controlNumber) {
    fetch(`api/check_payment_status.php?control_number=${controlNumber}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                document.getElementById('payment_status').innerHTML = `
                    <div class="alert alert-success">
                        ✅ MALIPO YAMEKUBALIWA!<br>
                        Transaction Ref: ${data.transaction_ref}<br>
                        Ukurasa uta refresh...
                    </div>
                `;
                setTimeout(() => location.reload(), 2000);
            } else if (data.status === 'pending') {
                document.getElementById('payment_status').innerHTML = `
                    <div class="alert alert-info">
                        ⏳ Malipo bado hayajakamilika. Tafadhali subiri au fanya malipo kwa kutumia control number hapo juu.
                    </div>
                `;
            } else {
                document.getElementById('payment_status').innerHTML = `
                    <div class="alert alert-warning">
                        ⏳ Status: ${data.status}. Tafadhali subiri au wasiliana na msaada.
                    </div>
                `;
            }
        });
}

// Edit Amount Modal functions
function openEditModal(id, amount, ref) {
    document.getElementById('edit_txn_id').value = id;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_txn_ref').value = ref;
    document.getElementById('edit_current_amount').value = 'TZS ' + Number(amount).toLocaleString();
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Fix Reference Modal functions
function openFixRefModal(id, oldRef) {
    document.getElementById('fix_txn_id').value = id;
    document.getElementById('fix_old_ref').value = oldRef;
    document.getElementById('fix_new_ref').value = '';
    document.getElementById('fixRefModal').style.display = 'flex';
}

function closeFixRefModal() {
    document.getElementById('fixRefModal').style.display = 'none';
}

// Delete confirmation
function confirmDelete(id, ref) {
    if(confirm('⚠️ Are you sure you want to delete transaction ' + ref + '?\n\nThis action cannot be undone!')) {
        window.location.href = 'fee_management.php?delete_id=' + id;
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
    if (event.target == document.getElementById('fixRefModal')) {
        closeFixRefModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>