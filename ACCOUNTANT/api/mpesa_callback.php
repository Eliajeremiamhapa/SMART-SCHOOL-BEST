<?php
// api/mpesa_callback.php
require_once '../config/database.php';

// Log the callback for debugging
$callback_data = json_decode(file_get_contents('php://input'), true);
file_put_contents('mpesa_callback_log.txt', date('Y-m-d H:i:s') . ' - ' . json_encode($callback_data) . PHP_EOL, FILE_APPEND);

if (isset($callback_data['Body']['stkCallback'])) {
    $result_code = $callback_data['Body']['stkCallback']['ResultCode'];
    $checkout_request_id = $callback_data['Body']['stkCallback']['CheckoutRequestID'];
    
    if ($result_code == 0) { // Payment successful
        $amount = $callback_data['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
        $mpesa_receipt = $callback_data['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
        $phone = $callback_data['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];
        
        // Update transaction
        $stmt = $pdo->prepare("UPDATE transactions SET transaction_ref = ?, amount = ?, is_reconciled = 0 WHERE transaction_ref = ?");
        $stmt->execute([$mpesa_receipt, $amount, $checkout_request_id]);
        
        // Find student and update invoice if needed
        // ... (code yako ya kuongeza salio la mwanafunzi)
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        // Payment failed
        $stmt = $pdo->prepare("UPDATE transactions SET notes = ? WHERE transaction_ref = ?");
        $stmt->execute(['Payment failed: ' . $result_code, $checkout_request_id]);
        http_response_code(200);
        echo json_encode(['status' => 'failed']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'invalid_callback']);
}
?>