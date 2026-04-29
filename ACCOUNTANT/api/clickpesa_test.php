<?php
// api/clickpesa_test.php - Test ClickPesa connection
header('Content-Type: application/json');
require_once 'clickpesa_auth.php';
require_once 'clickpesa_config.php';

$token = getClickPesaToken();

if ($token) {
    echo json_encode(['success' => true, 'message' => 'Connection successful! API keys are working.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Connection failed. Check your CLIENT_ID and API_KEY in clickpesa_config.php']);
}
?>