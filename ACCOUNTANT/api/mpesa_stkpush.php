<?php
// api/mpesa_stkpush.php - CLEAN VERSION
header('Content-Type: application/json');
require_once '../config/database.php';
require_once 'mpesa_auth.php';

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    $student_id = $data['student_id'] ?? 0;
    $amount = $data['amount'] ?? 0;
    $phone = $data['phone'] ?? '';
    
    if (!$student_id || !$amount || !$phone) {
        throw new Exception('Student ID, Amount, and Phone required');
    }
    
    // Get student details
    $stmt = $pdo->prepare("SELECT full_name, student_number FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        throw new Exception('Student not found');
    }
    
    // Format phone number
    $phone = preg_replace('/^0/', '254', $phone);
    $phone = preg_replace('/^\+/', '', $phone);
    
    // Get access token
    $token = getMpesaAccessToken();
    if (!$token) {
        throw new Exception('Failed to get access token. Check your Consumer Key and Secret.');
    }
    
    // Generate timestamp and password
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    
    // Prepare STK Push request
    $stk_data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int)$amount,
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => $student['student_number'],
        'TransactionDesc' => 'School Fees'
    ];
    
    $url = MPESA_BASE_URL . '/mpesa/stkpush/v1/processrequest';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($stk_data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code == 200) {
        $result_array = json_decode($result, true);
        
        if (isset($result_array['ResponseCode']) && $result_array['ResponseCode'] == '0') {
            // Save to database
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_ref, student_id, amount, payment_method, transaction_date, is_reconciled) VALUES (?, ?, ?, 'mpesa', NOW(), 0)");
            $stmt->execute([$result_array['CheckoutRequestID'], $student_id, $amount]);
            
            $response = [
                'success' => true,
                'checkout_request_id' => $result_array['CheckoutRequestID'],
                'message' => 'STK Push sent successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => $result_array['ResponseDescription'] ?? 'STK Push failed'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'HTTP Error: ' . $http_code,
            'details' => $result
        ];
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>