<?php
// api/clickpesa_check_status.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once 'clickpesa_config.php';

$control_number = $_GET['control_number'] ?? '';

if (!$control_number) {
    echo json_encode(['success' => false, 'message' => 'Control number required']);
    exit;
}

// Check local database first
$stmt = $pdo->prepare("SELECT * FROM student_payments WHERE control_number = ?");
$stmt->execute([$control_number]);
$local = $stmt->fetch();

if ($local && $local['status'] == 'completed') {
    echo json_encode(['success' => true, 'status' => 'completed', 'transaction_ref' => $local['transaction_ref']]);
    exit;
}

// Check with ClickPesa API
$ch = curl_init(CLICKPESA_BASE_URL . '/api/v1/billpay/status/' . $control_number);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . CLICKPESA_API_KEY]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $result = json_decode($response, true);
    echo json_encode(['success' => true, 'status' => $result['data']['status'] ?? 'pending']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error checking status']);
}
?>