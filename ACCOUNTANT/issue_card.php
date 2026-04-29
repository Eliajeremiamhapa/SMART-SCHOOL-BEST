<?php
require_once 'config/database.php';
$page_title = "Issue Smart Card";
include 'includes/header.php';

$student_id = $_GET['student_id'] ?? 0;
$student = null;

if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND is_active = 1");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $card_uid = trim($_POST['card_uid']);
    $payment_reference = trim($_POST['payment_reference']);
    
    // Check if card already exists
    $stmt = $pdo->prepare("SELECT id FROM smart_cards WHERE card_uid = ? OR payment_reference = ?");
    $stmt->execute([$card_uid, $payment_reference]);
    
    if ($stmt->fetch()) {
        $error = "❌ Kadi hii au Reference number tayari imesajiliwa!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO smart_cards (card_uid, student_id, payment_reference, balance, issued_date) VALUES (?, ?, ?, 0, CURDATE())");
        $stmt->execute([$card_uid, $student_id, $payment_reference]);
        $success = "✅ Kadi imetolewa kikamilifu kwa mwanafunzi! Sasa unaweza kuongeza salio.";
    }
}

// Get all students for dropdown
$students = $pdo->query("SELECT id, student_number, full_name, class FROM students WHERE is_active = 1 ORDER BY full_name")->fetchAll();
?>

<div class="container">
    <h1>💳 Toa Kadi Mpya</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <h3>📝 Sajili Kadi kwa Mwanafunzi</h3>
        
        <?php if ($student): ?>
            <div class="alert alert-info">
                <strong>Mwanafunzi aliyechaguliwa:</strong> <?php echo htmlspecialchars($student['full_name']); ?> (<?php echo $student['student_number']; ?>)
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Chagua Mwanafunzi *</label>
                <select name="student_id" required>
                    <option value="">-- Chagua Mwanafunzi --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($student_id == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['student_number'] . ' - ' . $s['full_name'] . ' (' . $s['class'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Card UID / RFID Number *</label>
                <input type="text" name="card_uid" required placeholder="Scan au ingiza namba ya kadi">
                <small>Namba ya kipekee kutoka kwenye kadi (RFID au NFC)</small>
            </div>
            
            <div class="form-group">
                <label>Payment Reference Number *</label>
                <input type="text" name="payment_reference" required placeholder="Mfano: REF001, SSMS001">
                <small>Namba ya kipekee ya kumtambulisha mwanafunzi kwenye mfumo wa malipo</small>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Hifadhi Kadi</button>
            <a href="students.php" class="btn btn-secondary">Rudi kwa Wanafunzi</a>
        </form>
    </div>
</div>

<style>
.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.6rem 1.2rem;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
    margin-left: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>