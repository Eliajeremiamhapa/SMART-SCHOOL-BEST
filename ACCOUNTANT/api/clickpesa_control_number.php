<?php
// api/clickpesa_control_number.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once 'clickpesa_auth.php';
require_once 'clickpesa_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? 0;
$amount = $data['amount'] ?? 0;

if (!$student_id || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Student ID and Amount required']);
    exit;
}

// Get student details
$stmt = $pdo->prepare("SELECT full_name, student_number FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Get token
$token = getClickPesaToken();
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Authentication failed. Check credentials.']);
    exit;
}

// Create payload for Order BillPay
$payload = [
    'amount' => (float)$amount,
    'currency' => 'TZS',
    'customerName' => $student['full_name'],
    'customerReference' => $student['student_number'],
    'description' => 'School Fees - ' . $student['student_number'],
    'callbackUrl' => CLICKPESA_WEBHOOK_URL
];

$ch = curl_init(CLICKPESA_BASE_URL . '/api/v1/billpay/order');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 || $http_code == 201) {
    $result = json_decode($response, true);
    $control_number = $result['controlNumber'] ?? $result['billPayNumber'] ?? null;
    
    if ($control_number) {
        // Save to database
        $stmt = $pdo->prepare("INSERT INTO student_payments (student_id, control_number, amount, description, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$student_id, $control_number, $amount, 'School Fees']);
        
        echo json_encode([
            'success' => true,
            'control_number' => $control_number,
            'amount' => $amount,
            'message' => 'Control number generated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No control number in response', 'response' => $result]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'API Error: HTTP ' . $http_code, 'response' => $response]);
}
?>