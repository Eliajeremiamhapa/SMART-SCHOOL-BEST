<?php
// api/clickpesa_webhook.php - Receives payment notifications from ClickPesa
require_once '../config/database.php';
require_once 'clickpesa_config.php';

// Log all incoming webhooks for debugging
$webhook_data = json_decode(file_get_contents('php://input'), true);
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . ' - ' . json_encode($webhook_data) . PHP_EOL, FILE_APPEND);

// Verify webhook signature (if you have secret)
// ... verification code here

if (isset($webhook_data['event']) && $webhook_data['event'] == 'payment.successful') {
    $control_number = $webhook_data['data']['control_number'] ?? null;
    $transaction_ref = $webhook_data['data']['transaction_reference'] ?? null;
    $amount = $webhook_data['data']['amount'] ?? 0;
    $payment_method = $webhook_data['data']['payment_method'] ?? 'mobile_money';
    $phone = $webhook_data['data']['customer_phone'] ?? '';
    
    if ($control_number && $transaction_ref) {
        try {
            $pdo->beginTransaction();
            
            // Find the pending payment by control number
            $stmt = $pdo->prepare("SELECT * FROM student_payments WHERE control_number = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$control_number]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                // Update payment status
                $stmt = $pdo->prepare("UPDATE student_payments SET status = 'completed', transaction_ref = ?, payment_method = ?, completed_at = NOW() WHERE control_number = ?");
                $stmt->execute([$transaction_ref, $payment_method, $control_number]);
                
                // Record transaction in your system
                $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, amount, payment_method, transaction_date, is_reconciled) VALUES (?, ?, ?, ?, NOW(), 0)");
                $stmt->execute([$transaction_ref, $payment['student_id'], $amount, $payment_method]);
                
                // Update invoice if exists
                $stmt = $pdo->prepare("SELECT id, amount_paid, amount FROM invoices WHERE student_id = ? AND status != 'paid' LIMIT 1");
                $stmt->execute([$payment['student_id']]);
                $invoice = $stmt->fetch();
                
                if ($invoice) {
                    $new_amount_paid = $invoice['amount_paid'] + $amount;
                    $new_status = $new_amount_paid >= $invoice['amount'] ? 'paid' : 'partial';
                    $stmt = $pdo->prepare("UPDATE invoices SET amount_paid = ?, status = ? WHERE id = ?");
                    $stmt->execute([$new_amount_paid, $new_status, $invoice['id']]);
                }
                
                $pdo->commit();
                
                // Send SMS to parent (optional - you'll need SMS API)
                // sendSMS($phone, "Payment of TZS " . number_format($amount) . " received successfully");
                
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Payment recorded']);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Payment record not found']);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . ' - ERROR: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    }
} else {
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
}
?>