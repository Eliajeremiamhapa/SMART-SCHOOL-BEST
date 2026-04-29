<?php
require_once 'config/database.php';
$page_title = "Bank Reconciliation";
include 'includes/header.php';

$error = '';
$success = '';

// Auto-match function - FIXED to also update is_reconciled in transactions table
if (isset($_GET['auto_match'])) {
    try {
        $pdo->beginTransaction();
        
        // First, match bank transactions with system transactions by reference
        $pdo->query("
            UPDATE bank_transactions bt
            INNER JOIN transactions t ON bt.transaction_ref = t.transaction_ref
            SET 
                bt.matched_transaction_id = t.id, 
                bt.match_status = 'matched',
                bt.notes = CONCAT(IFNULL(bt.notes, ''), ' | Auto-matched on ', NOW())
            WHERE bt.match_status IN ('pending', 'mismatch')
        ");
        
        // Then mark remaining pending as mismatch
        $pdo->query("
            UPDATE bank_transactions 
            SET match_status = 'mismatch' 
            WHERE match_status = 'pending'
        ");
        
        // CRITICAL FIX: Update transactions table - set is_reconciled = 1 for matched transactions
        $matched_count = $pdo->query("
            UPDATE transactions t
            INNER JOIN bank_transactions bt ON t.transaction_ref = bt.transaction_ref
            SET t.is_reconciled = 1, t.reconciled_date = CURDATE()
            WHERE bt.match_status = 'matched' AND t.is_reconciled = 0
        ")->rowCount();
        
        $pdo->commit();
        
        $total_matched = $pdo->query("SELECT COUNT(*) FROM bank_transactions WHERE match_status = 'matched'")->fetchColumn();
        $success = "Auto-reconciliation completed! " . $total_matched . " transactions matched. " . $matched_count . " system transactions marked as reconciled.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error during auto-reconciliation: " . $e->getMessage();
    }
}

// Manual match - also updates is_reconciled
if (isset($_POST['manual_match'])) {
    $bank_txn_id = $_POST['bank_txn_id'];
    $transaction_id = $_POST['transaction_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Update bank transaction
        $stmt = $pdo->prepare("
            UPDATE bank_transactions 
            SET matched_transaction_id = ?, match_status = 'matched', 
                notes = CONCAT(IFNULL(notes, ''), ' | Manually matched on ', NOW())
            WHERE id = ?
        ");
        $stmt->execute([$transaction_id, $bank_txn_id]);
        
        // Update transaction as reconciled
        $stmt = $pdo->prepare("
            UPDATE transactions t
            INNER JOIN bank_transactions bt ON t.id = ?
            SET t.is_reconciled = 1, t.reconciled_date = CURDATE()
            WHERE bt.id = ? AND bt.match_status = 'matched'
        ");
        $stmt->execute([$transaction_id, $bank_txn_id]);
        
        $pdo->commit();
        $success = "Transaction matched manually and marked as reconciled!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error during manual match: " . $e->getMessage();
    }
}

// UNDO MATCH / UNRECONCILE - NEW FEATURE
if (isset($_GET['unmatch_id'])) {
    $bank_txn_id = $_GET['unmatch_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get the bank transaction details
        $stmt = $pdo->prepare("SELECT * FROM bank_transactions WHERE id = ? AND match_status = 'matched'");
        $stmt->execute([$bank_txn_id]);
        $bank_txn = $stmt->fetch();
        
        if ($bank_txn) {
            // Update bank transaction back to pending
            $stmt = $pdo->prepare("
                UPDATE bank_transactions 
                SET matched_transaction_id = NULL, 
                    match_status = 'pending', 
                    notes = CONCAT(IFNULL(notes, ''), ' | UNMATCHED on ', NOW())
                WHERE id = ?
            ");
            $stmt->execute([$bank_txn_id]);
            
            // Update system transaction - set is_reconciled back to 0
            if ($bank_txn['matched_transaction_id']) {
                $stmt = $pdo->prepare("
                    UPDATE transactions 
                    SET is_reconciled = 0, 
                        reconciled_date = NULL,
                        notes = CONCAT(IFNULL(notes, ''), ' | RECONCILIATION REMOVED on ', NOW())
                    WHERE id = ?
                ");
                $stmt->execute([$bank_txn['matched_transaction_id']]);
            }
            
            $pdo->commit();
            $success = "✅ Reconciliation undone successfully! Transaction is now pending again. You can now edit or delete it.";
        } else {
            $error = "Transaction not found or already unmatched.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// DELETE SINGLE BANK TRANSACTION (only if not matched)
if (isset($_GET['delete_bank_txn'])) {
    $bank_txn_id = $_GET['delete_bank_txn'];
    
    try {
        $stmt = $pdo->prepare("SELECT match_status FROM bank_transactions WHERE id = ?");
        $stmt->execute([$bank_txn_id]);
        $bank_txn = $stmt->fetch();
        
        if ($bank_txn && $bank_txn['match_status'] != 'matched') {
            $stmt = $pdo->prepare("DELETE FROM bank_transactions WHERE id = ?");
            $stmt->execute([$bank_txn_id]);
            $success = "✅ Bank transaction deleted successfully!";
        } else {
            $error = "❌ Cannot delete a transaction that is already matched! Please undo reconciliation first.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get matched transactions (GREEN)
$matched = $pdo->query("
    SELECT bt.*, bs.bank_name, t.transaction_ref as system_ref, t.amount as system_amount, t.is_reconciled
    FROM bank_transactions bt
    JOIN bank_statements bs ON bt.bank_statement_id = bs.id
    LEFT JOIN transactions t ON bt.matched_transaction_id = t.id
    WHERE bt.match_status = 'matched'
    ORDER BY bt.transaction_date DESC
    LIMIT 50
")->fetchAll();

// Get mismatches (RED)
$mismatches = $pdo->query("
    SELECT bt.*, bs.bank_name 
    FROM bank_transactions bt
    JOIN bank_statements bs ON bt.bank_statement_id = bs.id
    WHERE bt.match_status = 'mismatch'
    ORDER BY bt.transaction_date DESC
    LIMIT 50
")->fetchAll();

// Get pending reconciliations
$pending = $pdo->query("
    SELECT bt.*, bs.bank_name 
    FROM bank_transactions bt
    JOIN bank_statements bs ON bt.bank_statement_id = bs.id
    WHERE bt.match_status = 'pending'
    ORDER BY bt.transaction_date DESC
    LIMIT 50
")->fetchAll();

// Get all system transactions for manual matching
$system_txns = $pdo->query("SELECT id, transaction_ref, amount, payment_method, transaction_date, is_reconciled FROM transactions ORDER BY transaction_date DESC LIMIT 100")->fetchAll();

// Get statistics
$stats = $pdo->query("
    SELECT 
        COUNT(CASE WHEN match_status = 'matched' THEN 1 END) as matched_count,
        COUNT(CASE WHEN match_status = 'mismatch' THEN 1 END) as mismatch_count,
        COUNT(CASE WHEN match_status = 'pending' THEN 1 END) as pending_count,
        COALESCE(SUM(CASE WHEN match_status = 'mismatch' THEN amount ELSE 0 END), 0) as mismatch_total
    FROM bank_transactions
")->fetch();

// Get system reconciliation stats
$system_stats = $pdo->query("
    SELECT 
        COUNT(CASE WHEN is_reconciled = 1 THEN 1 END) as reconciled_count,
        COUNT(CASE WHEN is_reconciled = 0 THEN 1 END) as not_reconciled_count
    FROM transactions
")->fetch();
?>

<div class="container">
    <h1>🏦 Bank Reconciliation</h1>
    
    <!-- Statistics Summary -->
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card success">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?php echo $stats['matched_count']; ?></div>
            <div class="stat-label">Bank Matched</div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">📚</div>
            <div class="stat-value"><?php echo $system_stats['reconciled_count']; ?></div>
            <div class="stat-label">System Reconciled</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon">🔴</div>
            <div class="stat-value"><?php echo $stats['mismatch_count']; ?></div>
            <div class="stat-label">Mismatch (Red)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">TZS <?php echo number_format($stats['mismatch_total']); ?></div>
            <div class="stat-label">Total Mismatch Amount</div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="action-bar">
        <a href="?auto_match=1" class="btn btn-success" onclick="return confirm('Start auto-reconciliation? This will match bank transactions with system records by reference number.')">🔄 Run Auto-Reconcile</a>
        <a href="reports.php?report=reconciliation" class="btn btn-info">📄 Final Reconciliation Report</a>
    </div>
    
    <!-- MATCHED TRANSACTIONS (GREEN) - WITH UNDO BUTTON -->
    <div class="section">
        <h3>✅ Matched Transactions (Green)</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th>Ref No</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>System Ref</th>
                        <th>Reconciled</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($matched) == 0): ?>
                        <tr style="background-color: #fff3cd;">
                            <td colspan="8" style="text-align:center;">⏳ No matched transactions yet. Run auto-reconcile first.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($matched as $m): ?>
                        <tr style="background-color: #d4edda; border-left: 4px solid #28a745;">
                            <td>🏦 <?php echo htmlspecialchars($m['bank_name']); ?></td>
                            <td><strong>✅ <?php echo htmlspecialchars($m['transaction_ref']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['transaction_date']); ?></td>
                            <td><?php echo htmlspecialchars(substr($m['description'], 0, 40)); ?></td>
                            <td style="color: #28a745; font-weight: bold;">TZS <?php echo number_format($m['amount']); ?></td>
                            <td><?php echo htmlspecialchars($m['system_ref'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($m['is_reconciled']): ?>
                                    <span style="color:green;">✅ Yes</span>
                                <?php else: ?>
                                    <span style="color:orange;">⏳ No</span>
                                <?php endif; ?>
                              </small></td>
                            <td>
                                <button class="btn-small" style="background:#dc3545; color:white;" onclick="confirmUnmatch(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['transaction_ref']); ?>')">↩️ Undo</button>
                             </small></td>
                         </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
             </table>
        </div>
    </div>
    
    <!-- MISMATCHES (RED FLAGGED) -->
    <div class="section">
        <h3>⚠️ Mismatches (Needs Attention) - Red</h3>
        <p style="margin-bottom: 1rem; color: #666;">Hizi ni pesa zilizoko BENKI lakini HAZIPO kwenye mfumo wa shule. Ziangalie kwa makini.</p>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th>Ref No</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Action</th>
                     </tr>
                </thead>
                <tbody>
                    <?php if (count($mismatches) == 0): ?>
                        <tr style="background-color: #d4edda;">
                            <td colspan="6" style="text-align:center; color: #155724;">✅ No mismatches found! All transactions are reconciled.</td>
                         </tr>
                    <?php else: ?>
                        <?php foreach ($mismatches as $mm): ?>
                        <tr style="background-color: #ffebee; border-left: 4px solid #f44336;">
                            <td style="font-weight: bold;">🏦 <?php echo htmlspecialchars($mm['bank_name']); ?></td>
                            <td style="font-weight: bold; color: #c62828;">⚠️ <?php echo htmlspecialchars($mm['transaction_ref']); ?></td>
                            <td><?php echo htmlspecialchars($mm['transaction_date']); ?></td>
                            <td><?php echo htmlspecialchars(substr($mm['description'], 0, 50)); ?></td>
                            <td style="font-weight: bold; color: #d32f2f; font-size: 1rem;">TZS <?php echo number_format($mm['amount']); ?></td>
                            <td>
                                <button class="btn-small mismatch-btn" onclick="openMatchModal(<?php echo $mm['id']; ?>, '<?php echo htmlspecialchars($mm['transaction_ref']); ?>', <?php echo $mm['amount']; ?>)">🔴 Match Manually</button>
                                <button class="btn-small" style="background:#ffc107; color:#333; margin-top:0.3rem;" onclick="confirmDeleteBankTxn(<?php echo $mm['id']; ?>, '<?php echo htmlspecialchars($mm['transaction_ref']); ?>')">🗑️ Delete</button>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
              </table>
        </div>
    </div>
    
    <!-- PENDING RECONCILIATIONS -->
    <div class="section">
        <h3>⏳ Pending Reconciliations</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th>Ref No</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending) == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">✅ No pending reconciliations</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending as $p): ?>
                        <tr style="background-color: #fff3e0;">
                            <td><?php echo htmlspecialchars($p['bank_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['transaction_ref']); ?></td>
                            <td><?php echo htmlspecialchars($p['transaction_date']); ?></td>
                            <td>TZS <?php echo number_format($p['amount']); ?></td>
                            <td>
                                <button class="btn-small" style="background:#28a745;" onclick="openMatchModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['transaction_ref']); ?>', <?php echo $p['amount']; ?>)">✅ Match Now</button>
                                <button class="btn-small" style="background:#dc3545; margin-top:0.3rem;" onclick="confirmDeleteBankTxn(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['transaction_ref']); ?>')">🗑️ Delete</button>
                             </td>
                          </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
              </table>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="info-box">
        <h4>📌 How to correct mistakes:</h4>
        <ul>
            <li><strong>To undo a match:</strong> Click "Undo" button on any matched transaction (Green section)</li>
            <li><strong>To delete a bank transaction:</strong> Click "Delete" on Pending or Mismatch transactions</li>
            <li><strong>After undo:</strong> The transaction becomes pending and can be edited/deleted</li>
            <li><strong>To re-match:</strong> Run Auto-Reconcile again or match manually</li>
        </ul>
    </div>
</div>

<!-- Modal for manual matching -->
<div id="matchModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" style="float: right; font-size: 1.5rem; cursor: pointer;">&times;</span>
        <h3 style="color: #d32f2f;">🔴 Manual Match Transaction</h3>
        <form method="POST">
            <input type="hidden" name="bank_txn_id" id="bank_txn_id">
            <div class="form-group">
                <label>Select System Transaction (match by reference number)</label>
                <select name="transaction_id" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">-- Select --</option>
                    <?php foreach ($system_txns as $st): ?>
                    <option value="<?php echo $st['id']; ?>">
                        Ref: <?php echo htmlspecialchars($st['transaction_ref']); ?> - TZS <?php echo number_format($st['amount']); ?> (<?php echo $st['transaction_date']; ?>) <?php echo $st['is_reconciled'] ? '✅' : '⏳'; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="manual_match" class="btn btn-primary" style="background: #d32f2f; width: 100%;">✅ Match Transaction</button>
        </form>
    </div>
</div>

<style>
/* Additional styles */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.mismatch-btn {
    background-color: #d32f2f !important;
    color: white !important;
    font-weight: bold !important;
    border: none;
    padding: 0.3rem 0.8rem;
    border-radius: 4px;
    cursor: pointer;
}
.mismatch-btn:hover {
    background-color: #b71c1c !important;
}
.data-table tr:hover {
    opacity: 0.95;
}
.section {
    margin-bottom: 2rem;
}
.section h3 {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #ddd;
}
.action-bar {
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.btn-success {
    background-color: #28a745;
    color: white;
    padding: 0.5rem 1rem;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
}
.btn-info {
    background-color: #17a2b8;
    color: white;
    padding: 0.5rem 1rem;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    padding: 0.75rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}
.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    padding: 0.75rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}
.stat-card {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.stat-card .stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}
.stat-card.success .stat-value { color: #28a745; }
.stat-card.danger .stat-value { color: #dc3545; }
.stat-card.warning .stat-value { color: #ffc107; }
.info-box {
    background: #e8f4fd;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1.5rem;
    border-left: 4px solid #2196f3;
}
.info-box ul {
    margin-left: 1.5rem;
    margin-top: 0.5rem;
}
.info-box li {
    margin-bottom: 0.5rem;
}
</style>

<script>
function openMatchModal(bankTxnId, refNo, amount) {
    document.getElementById('bank_txn_id').value = bankTxnId;
    document.getElementById('matchModal').style.display = 'flex';
    
    // Highlight matching transaction in dropdown
    var select = document.querySelector('#matchModal select[name="transaction_id"]');
    for(var i = 0; i < select.options.length; i++) {
        if(select.options[i].text.includes(refNo)) {
            select.selectedIndex = i;
            break;
        }
    }
}

function confirmUnmatch(id, ref) {
    if(confirm('⚠️ UNDO RECONCILIATION\n\nAre you sure you want to undo reconciliation for transaction: ' + ref + '?\n\nThis will:\n✓ Remove the match status\n✓ Allow editing/deleting the transaction\n✓ Require re-reconciliation later\n\nProceed?')) {
        window.location.href = 'bank_reconciliation.php?unmatch_id=' + id;
    }
}

function confirmDeleteBankTxn(id, ref) {
    if(confirm('🗑️ DELETE TRANSACTION\n\nAre you sure you want to delete bank transaction: ' + ref + '?\n\nThis action cannot be undone!\n\nProceed?')) {
        window.location.href = 'bank_reconciliation.php?delete_bank_txn=' + id;
    }
}

// Get modal elements
var modal = document.getElementById('matchModal');
var span = document.getElementsByClassName("close")[0];

span.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php include 'includes/footer.php'; ?>