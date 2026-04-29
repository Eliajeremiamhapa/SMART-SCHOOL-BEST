<?php
require_once 'config/database.php';

$error = '';
$success = '';
$card_uid = '';
$item_id = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $card_uid = trim($_POST['card_uid']);
    $item_id = (int)$_POST['item_id'];
    
    // Get card details
    $stmt = $pdo->prepare("SELECT * FROM smart_cards WHERE card_uid = ? AND is_active = 1");
    $stmt->execute([$card_uid]);
    $card = $stmt->fetch();
    
    if (!$card) {
        $error = "❌ Kadi haipatikani au haitumiki. Tafadhali hakikisha card UID ni sahihi.";
    } else {
        // Get student name
        $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
        $stmt->execute([$card['student_id']]);
        $student = $stmt->fetch();
        
        // Get item details
        $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ? AND is_active = 1");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            $error = "❌ Bidhaa haipatikani kwenye mfumo.";
        } else if ($item['current_stock'] <= 0) {
            $error = "❌ Bidhaa '{$item['item_name']}' imeisha stock! Tafadhali jaza stock kwanza.";
        } else {
            $amount = $item['unit_price'];
            
            if ($card['balance'] < $amount) {
                $error = "❌ Salio la kadi haitoshi! Salio la sasa: TZS " . number_format($card['balance']) . ". Unahitaji TZS " . number_format($amount);
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Get current balance before deduction
                    $old_balance = $card['balance'];
                    $new_balance = $old_balance - $amount;
                    
                    // Deduct from card
                    $stmt = $pdo->prepare("UPDATE smart_cards SET balance = balance - ? WHERE id = ?");
                    $stmt->execute([$amount, $card['id']]);
                    
                    // Record transaction
                    $ref = 'CARD_PAY_' . time() . '_' . rand(100, 999);
                    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, card_id, amount, payment_method, transaction_date) VALUES (?, ?, ?, ?, 'card', NOW())");
                    $stmt->execute([$ref, $card['student_id'], $card['id'], $amount]);
                    
                    // Deduct stock
                    $stmt = $pdo->prepare("UPDATE inventory_items SET current_stock = current_stock - 1 WHERE id = ?");
                    $stmt->execute([$item_id]);
                    
                    // Record stock transaction
                    $stmt = $pdo->prepare("INSERT INTO stock_transactions (item_id, transaction_type, quantity, unit_price, total_amount, reference_type, reference_id, student_id) VALUES (?, 'out', 1, ?, ?, 'sale_card', ?, ?)");
                    $stmt->execute([$item_id, $amount, $amount, $ref, $card['student_id']]);
                    
                    $pdo->commit();
                    
                    $success = "
                        <strong>✅ Malipo yamefanyika kikamilifu!</strong><br>
                        🧑‍🎓 Mwanafunzi: " . htmlspecialchars($student['full_name']) . "<br>
                        🏷️ Bidhaa: " . htmlspecialchars($item['item_name']) . "<br>
                        💰 Kiasi: TZS " . number_format($amount) . "<br>
                        💳 Salio la zamani: TZS " . number_format($old_balance) . "<br>
                        💳 Salio lipya: TZS " . number_format($new_balance) . "<br>
                        📦 Stock iliyobaki: " . ($item['current_stock'] - 1) . " " . $item['unit_of_measure'];
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "❌ Kuna tatizo la kiufundi: " . $e->getMessage();
                }
            }
        }
    }
}

// Get all active cards for dropdown
$cards = $pdo->query("
    SELECT sc.card_uid, sc.balance, s.full_name, s.student_number 
    FROM smart_cards sc
    JOIN students s ON sc.student_id = s.id
    WHERE sc.is_active = 1
    ORDER BY s.full_name
")->fetchAll();

// Get all inventory items with stock
$items = $pdo->query("
    SELECT id, item_name, current_stock, unit_price, unit_of_measure 
    FROM inventory_items 
    WHERE is_active = 1 AND current_stock > 0
    ORDER BY item_name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Payment Simulator (POS)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #666;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        select, input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #218838;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e8f4fd;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .info-box h4 {
            margin-bottom: 0.5rem;
            color: #0c5460;
        }
        .info-box ul {
            margin-left: 1.5rem;
            color: #0c5460;
        }
        hr {
            margin: 1rem 0;
            border-color: #eee;
        }
        .current-cards {
            margin-top: 1rem;
        }
        .current-cards table {
            width: 100%;
            border-collapse: collapse;
        }
        .current-cards th, .current-cards td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .current-cards th {
            background: #f0f2f5;
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>💳 Card Payment Simulator</h1>
            <div class="subtitle">Kununua kwa kadi (Simulate POS Machine)</div>
            
            <?php if ($error): ?>
                <div class="alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>🔑 Chagua Kadi ya Mwanafunzi</label>
                    <select name="card_uid" required>
                        <option value="">-- Chagua Kadi --</option>
                        <?php foreach ($cards as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['card_uid']); ?>" <?php echo ($card_uid == $c['card_uid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['full_name'] . ' (' . $c['student_number'] . ') - Salio: TZS ' . number_format($c['balance'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>🛒 Chagua Bidhaa</label>
                    <select name="item_id" required>
                        <option value="">-- Chagua Bidhaa --</option>
                        <?php foreach ($items as $i): ?>
                            <option value="<?php echo $i['id']; ?>" <?php echo ($item_id == $i['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($i['item_name'] . ' - TZS ' . number_format($i['unit_price']) . ' (Stock: ' . $i['current_stock'] . ' ' . $i['unit_of_measure'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit">💸 PROCESS PAYMENT</button>
            </form>
            
            <div class="info-box">
                <h4>📌 Maelezo:</h4>
                <ul>
                    <li>Mfumo utakata kiasi cha bidhaa kwenye kadi ya mwanafunzi</li>
                    <li>Stock ya bidhaa itapungua kiotomatiki</li>
                    <li>Transaction itarekodiwa kwenye mfumo</li>
                    <li>Mwanafunzi atapokea risiti ya kidijitali</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h3>📋 Kadi Zilizopo</h3>
            <div class="current-cards">
                <?php if (count($cards) == 0): ?>
                    <p style="color:orange;">⚠️ Hakuna kadi iliyosajiliwa. Nenda kwenye Smart Cards kutoa kadi.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Mwanafunzi</th><th>Card UID</th><th>Salio</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cards as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['full_name']); ?> (<?php echo $c['student_number']; ?>)</td>
                                <td><code><?php echo htmlspecialchars($c['card_uid']); ?></code></td>
                                <td>TZS <?php echo number_format($c['balance']); ?></td>
                                <td><span class="badge <?php echo $c['balance'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $c['balance'] > 0 ? '✅ Active' : '⚠️ Low Balance'; ?>
                                </span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h3>📦 Bidhaa Zilizopo (Stock)</h3>
            <?php if (count($items) == 0): ?>
                <p style="color:orange;">⚠️ Hakuna bidhaa kwenye stock. Nenda kwenye Inventory kuongeza bidhaa.</p>
            <?php else: ?>
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr><th>Bidhaa</th><th>Bei</th><th>Stock</th><th>Kitengo</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i['item_name']); ?></td>
                            <td>TZS <?php echo number_format($i['unit_price']); ?></td>
                            <td><?php echo $i['current_stock']; ?></td>
                            <td><?php echo $i['unit_of_measure']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>