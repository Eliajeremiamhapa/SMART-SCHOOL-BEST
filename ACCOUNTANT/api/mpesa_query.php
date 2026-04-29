<?php
// api/mpesa_query.php
require_once 'mpesa_auth.php';
require_once 'mpesa_config.php';

// Badilisha hii na CheckoutRequestID uliyopata hapo awali
$checkoutRequestID = 'ws_CO_13042026080651717708374149';

$token = getMpesaAccessToken();
if (!$token) {
    die("Failed to get access token");
}

$url = MPESA_BASE_URL . '/mpesa/stkpushquery/v1/query';
$data = [
    'BusinessShortCode' => MPESA_SHORTCODE,
    'Password' => base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . date('YmdHis')),
    'Timestamp' => date('YmdHis'),
    'CheckoutRequestID' => $checkoutRequestID
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "<h1>STK Push Query Result</h1>";
echo "<p>HTTP Code: $http_code</p>";
echo "<pre>Response: " . print_r(json_decode($response, true), true) . "</pre>";
?>