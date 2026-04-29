<?php
require_once 'config/database.php';
$page_title = "Smart Card Management";
include 'includes/header.php';

// Top-up card
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['topup'])) {
    $card_id = $_POST['card_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    $stmt = $pdo->prepare("UPDATE smart_cards SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $card_id]);
    
    // Record top-up transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, card_id, amount, payment_method, transaction_date) VALUES (?, (SELECT student_id FROM smart_cards WHERE id = ?), ?, ?, ?, NOW())");
    $ref = 'TOPUP_' . time() . '_' . $card_id;
    $stmt->execute([$ref, $card_id, $card_id, $amount, $payment_method]);
    
    $success = "Card topped up successfully!";
}

// Block card
if (isset($_GET['block'])) {
    $card_id = $_GET['block'];
    $stmt = $pdo->prepare("UPDATE smart_cards SET is_active = 0 WHERE id = ?");
    $stmt->execute([$card_id]);
    $success = "Card blocked successfully!";
}

// Get all cards
$cards = $pdo->query("
    SELECT sc.*, s.full_name, s.student_number, s.class 
    FROM smart_cards sc 
    JOIN students s ON sc.student_id = s.id 
    ORDER BY sc.id DESC
")->fetchAll();
?>

<div class="container">
    <h1>Smart Card Management</h1>
    
    <div class="two-columns">
        <div class="form-card">
            <h3>Top-up Card</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Select Card</label>
                    <select name="card_id" required>
                        <option value="">-- Select Card --</option>
                        <?php foreach ($cards as $card): ?>
                            <option value="<?php echo $card['id']; ?>">
                                <?php echo $card['card_uid'] . ' - ' . $card['full_name'] . ' (Balance: TZS ' . number_format($card['balance']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (TZS)</label>
                    <input type="number" name="amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="tigopesa">Tigo Pesa</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" name="topup" class="btn btn-primary">💰 Top-up Card</button>
            </form>
        </div>
        
        <div class="form-card">
            <h3>Issue New Card</h3>
            <form method="POST" action="issue_card.php">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" required>
                        <?php
                        $students = $pdo->query("SELECT id, full_name, student_number FROM students WHERE is_active = 1 AND id NOT IN (SELECT student_id FROM smart_cards WHERE is_active = 1)")->fetchAll();
                        foreach ($students as $s):
                        ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['student_number'] . ' - ' . $s['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Card UID (RFID/NFC)</label>
                    <input type="text" name="card_uid" placeholder="Scan or enter card UID" required>
                </div>
                <div class="form-group">
                    <label>Payment Reference</label>
                    <input type="text" name="payment_reference" placeholder="Unique reference number" required>
                </div>
                <button type="submit" class="btn btn-success">➕ Issue New Card</button>
            </form>
        </div>
    </div>
    
    <div class="section">
        <h3>All Smart Cards</h3>
        <table class="data-table">
            <thead>
                <tr><th>Card UID</th><th>Student</th><th>Class</th><th>Balance</th><th>Payment Ref</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $card): ?>
                <tr>
                    <td><?php echo $card['card_uid']; ?></td>
                    <td><?php echo $card['full_name']; ?></td>
                    <td><?php echo $card['class']; ?></td>
                    <td>TZS <?php echo number_format($card['balance']); ?></td>
                    <td><?php echo $card['payment_reference']; ?></td>
                    <td><?php echo $card['is_active'] ? 'Active' : 'Blocked'; ?></td>
                    <td>
                        <?php if ($card['is_active']): ?>
                            <a href="?block=<?php echo $card['id']; ?>" onclick="return confirm('Block this card?')" class="btn-small danger">Block</a>
                        <?php endif; ?>
                        <a href="card_history.php?card_id=<?php echo $card['id']; ?>" class="btn-small">History</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>