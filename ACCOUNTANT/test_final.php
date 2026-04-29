<?php
require_once 'api/clickpesa_auth.php';
require_once 'api/clickpesa_config.php';

echo "<h1>Final Test</h1>";
$token = getClickPesaToken();

if ($token) {
    echo "<p style='color:green'>✅ Token: " . substr($token, 0, 50) . "...</p>";
    
    // Jaribu ku-order Control Number
    $payload = [
        'amount' => 1000,
        'currency' => 'TZS',
        'customerName' => 'Test Student',
        'customerReference' => 'TEST001',
        'description' => 'Test Payment'
    ];
    
    $ch = curl_init(CLICKPESA_BASE_URL . '/api/v1/billpay/order');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: $http_code</p>";
    echo "<pre>Response: " . print_r(json_decode($response, true), true) . "</pre>";
    
    if ($http_code == 200 || $http_code == 201) {
        echo "<p style='color:green'>✅ Control Number created successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create control number.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Failed to get token. Check credentials.</p>";
}
?>