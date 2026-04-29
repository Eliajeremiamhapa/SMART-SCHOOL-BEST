<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_stats':
        $stats = [];
        
        // Total students
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE is_active = 1");
        $stats['total_students'] = $stmt->fetch()['total'];
        
        // Today's collection
        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()");
        $stmt->execute();
        $stats['today_collection'] = $stmt->fetch()['total'] ?? 0;
        
        // Pending reconciliations
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM bank_transactions WHERE match_status = 'pending'");
        $stats['pending_recon'] = $stmt->fetch()['pending'];
        
        // Low stock alerts
        $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM low_stock_alerts WHERE status = 'pending'");
        $stats['low_stock_alerts'] = $stmt->fetch()['low_stock'];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    case 'get_card_balance':
        $card_uid = $_GET['card_uid'] ?? '';
        $stmt = $pdo->prepare("SELECT sc.*, s.full_name FROM smart_cards sc JOIN students s ON sc.student_id = s.id WHERE sc.card_uid = ? AND sc.is_active = 1");
        $stmt->execute([$card_uid]);
        $card = $stmt->fetch();
        
        if ($card) {
            echo json_encode(['success' => true, 'data' => $card]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Card not found or inactive']);
        }
        break;
        
    case 'process_card_payment':
        $card_uid = $_POST['card_uid'];
        $amount = $_POST['amount'];
        $item_id = $_POST['item_id'] ?? null;
        
        try {
            $pdo->beginTransaction();
            
            // Get card
            $stmt = $pdo->prepare("SELECT * FROM smart_cards WHERE card_uid = ? AND is_active = 1");
            $stmt->execute([$card_uid]);
            $card = $stmt->fetch();
            
            if (!$card) {
                throw new Exception('Invalid card');
            }
            
            if ($card['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            // Deduct from card
            $stmt = $pdo->prepare("UPDATE smart_cards SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $card['id']]);
            
            // Record transaction
            $ref = 'CARD_' . time() . '_' . $card['id'];
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, card_id, amount, payment_method, transaction_date) VALUES (?, ?, ?, ?, 'card', NOW())");
            $stmt->execute([$ref, $card['student_id'], $card['id'], $amount]);
            
            // If item purchased, deduct stock
            if ($item_id) {
                $stmt = $pdo->prepare("UPDATE inventory_items SET current_stock = current_stock - 1 WHERE id = ?");
                $stmt->execute([$item_id]);
                
                $stmt = $pdo->prepare("INSERT INTO stock_transactions (item_id, transaction_type, quantity, unit_price, total_amount, reference_type, reference_id, student_id) VALUES (?, 'out', 1, (SELECT unit_price FROM inventory_items WHERE id = ?), ?, 'sale_card', ?, ?)");
                $stmt->execute([$item_id, $item_id, $amount, $ref, $card['student_id']]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Payment successful', 'new_balance' => $card['balance'] - $amount]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'get_low_stock':
        $alerts = $pdo->query("
            SELECT lsa.*, i.item_name, i.current_stock, i.reorder_level 
            FROM low_stock_alerts lsa 
            JOIN inventory_items i ON lsa.item_id = i.id 
            WHERE lsa.status = 'pending'
        ")->fetchAll();
        echo json_encode(['success' => true, 'data' => $alerts]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>