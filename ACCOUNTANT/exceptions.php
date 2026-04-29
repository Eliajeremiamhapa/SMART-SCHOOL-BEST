<?php
require_once 'config/database.php';
$page_title = "Exceptions Handling";
include 'includes/header.php';

// Handle failed top-up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resolve_failed_topup'])) {
    $student_id = $_POST['student_id'];
    $amount = $_POST['amount'];
    $notes = $_POST['notes'];
    
    // Record manual adjustment
    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, amount, payment_method, notes, transaction_date) VALUES (?, ?, ?, 'manual_adjustment', ?, NOW())");
    $ref = 'ADJ-' . time() . '-' . $student_id;
    $stmt->execute([$ref, $student_id, $amount, $notes]);
    
    // Update card balance if exists
    $stmt = $pdo->prepare("UPDATE smart_cards SET balance = balance + ? WHERE student_id = ?");
    $stmt->execute([$amount, $student_id]);
    
    $success = "Failed top-up resolved! Amount added to student's card.";
}

// Block lost card
if (isset($_GET['block_lost_card'])) {
    $card_id = $_GET['block_lost_card'];
    $stmt = $pdo->prepare("UPDATE smart_cards SET is_active = 0 WHERE id = ?");
    $stmt->execute([$card_id]);
    
    // Record the lost card incident
    $stmt = $pdo->prepare("INSERT INTO lost_cards (card_id, reported_date, status) VALUES (?, NOW(), 'blocked')");
    $stmt->execute([$card_id]);
    
    $success = "Card blocked successfully! Issue a new card for the student.";
}

// Manual adjustment (edit transaction)
if (isset($_POST['manual_adjustment'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_amount = $_POST['new_amount'];
    $adjustment_reason = $_POST['adjustment_reason'];
    
    $stmt = $pdo->prepare("UPDATE transactions SET amount = ?, notes = CONCAT(notes, ' | Adjusted: ', ?) WHERE id = ?");
    $stmt->execute([$new_amount, $adjustment_reason, $transaction_id]);
    
    $success = "Transaction adjusted successfully!";
}

// Get failed top-ups (simulated - in real system, track from payment gateway)
$failed_topups = []; // Would come from payment gateway logs

// Get all cards
$cards = $pdo->query("
    SELECT sc.*, s.full_name, s.student_number 
    FROM smart_cards sc 
    JOIN students s ON sc.student_id = s.id 
    WHERE sc.is_active = 1
")->fetchAll();

// Get recent transactions for manual adjustment
$transactions = $pdo->query("
    SELECT t.*, s.full_name 
    FROM transactions t 
    JOIN students s ON t.student_id = s.id 
    ORDER BY t.transaction_date DESC 
    LIMIT 50
")->fetchAll();
?>

<div class="container">
    <h1>Exceptions Handling</h1>
    
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-value"><?php echo count($failed_topups); ?></div>
            <div class="stat-label">Failed Top-ups</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💳</div>
            <div class="stat-value"><?php echo count($cards); ?></div>
            <div class="stat-label">Active Cards</div>
        </div>
    </div>
    
    <!-- Failed Top-ups Section -->
    <div class="form-card">
        <h3>Handle Failed Top-up</h3>
        <p class="note">When a parent reports failed M-Pesa/Tigo Pesa top-up</p>
        <form method="POST">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php
                    $students = $pdo->query("SELECT id, full_name, student_number FROM students WHERE is_active = 1")->fetchAll();
                    foreach ($students as $s):
                    ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['student_number'] . ' - ' . $s['full_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Amount (TZS)</label>
                <input type="number" step="0.01" name="amount" required placeholder="Amount that failed">
            </div>
            <div class="form-group">
                <label>Notes / Reference</label>
                <textarea name="notes" placeholder="M-Pesa transaction ID, parent phone number, etc."></textarea>
            </div>
            <button type="submit" name="resolve_failed_topup" class="btn btn-warning">💰 Resolve & Add to Card</button>
        </form>
    </div>
    
    <div class="two-columns">
        <!-- Block Lost Card -->
        <div class="form-card">
            <h3>Report Lost / Stolen Card</h3>
            <form method="GET" onsubmit="return confirm('Block this card? The student will need a new card.');">
                <div class="form-group">
                    <label>Select Card to Block</label>
                    <select name="block_lost_card" required>
                        <option value="">-- Select Card --</option>
                        <?php foreach ($cards as $card): ?>
                            <option value="<?php echo $card['id']; ?>">
                                <?php echo $card['card_uid'] . ' - ' . $card['full_name'] . ' (Balance: TZS ' . number_format($card['balance']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger">🔒 Block Card</button>
            </form>
        </div>
        
        <!-- Issue New Card (for lost cards) -->
        <div class="form-card">
            <h3>Issue Replacement Card</h3>
            <form method="POST" action="issue_card.php">
                <div class="form-group">
                    <label>Student (without active card)</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php
                        $students_without_card = $pdo->query("
                            SELECT s.id, s.full_name, s.student_number 
                            FROM students s 
                            WHERE s.is_active = 1 
                            AND s.id NOT IN (SELECT student_id FROM smart_cards WHERE is_active = 1)
                        ")->fetchAll();
                        foreach ($students_without_card as $s):
                        ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['student_number'] . ' - ' . $s['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Card UID</label>
                    <input type="text" name="card_uid" placeholder="Scan new card UID" required>
                </div>
                <div class="form-group">
                    <label>Payment Reference</label>
                    <input type="text" name="payment_reference" placeholder="Unique reference" required>
                </div>
                <button type="submit" class="btn btn-success">➕ Issue New Card</button>
            </form>
        </div>
    </div>
    
    <!-- Manual Adjustments -->
    <div class="section">
        <h3>Manual Transaction Adjustments</h3>
        <table class="data-table">
            <thead>
                <tr><th>Ref No</th><th>Student</th><th>Original Amount</th><th>Payment Method</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?php echo $tx['transaction_ref']; ?></td>
                    <td><?php echo $tx['full_name']; ?></td>
                    <td>TZS <?php echo number_format($tx['amount']); ?></td>
                    <td><?php echo $tx['payment_method']; ?></td>
                    <td><?php echo $tx['transaction_date']; ?></td>
                    <td>
                        <button class="btn-small" onclick="openAdjustModal(<?php echo $tx['id']; ?>, <?php echo $tx['amount']; ?>)">Adjust</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Manual Adjustment -->
<div id="adjustModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Manual Transaction Adjustment</h3>
        <form method="POST">
            <input type="hidden" name="transaction_id" id="adjust_txn_id">
            <div class="form-group">
                <label>New Amount (TZS)</label>
                <input type="number" step="0.01" name="new_amount" id="new_amount" required>
            </div>
            <div class="form-group">
                <label>Reason for Adjustment</label>
                <textarea name="adjustment_reason" required placeholder="e.g., Wrong amount entered, duplicate, refund"></textarea>
            </div>
            <button type="submit" name="manual_adjustment" class="btn btn-warning">Apply Adjustment</button>
        </form>
    </div>
</div>

<script>
function openAdjustModal(txnId, currentAmount) {
    document.getElementById('adjust_txn_id').value = txnId;
    document.getElementById('new_amount').value = currentAmount;
    document.getElementById('adjustModal').style.display = 'block';
}

var modal = document.getElementById('adjustModal');
var span = document.getElementsByClassName("close")[0];
span.onclick = function() { modal.style.display = "none"; }
window.onclick = function(event) { if (event.target == modal) modal.style.display = "none"; }
</script>

<?php include 'includes/footer.php'; ?>