<?php
// api/clickpesa_auth.php
require_once 'clickpesa_config.php';

function getClickPesaToken() {
    $ch = curl_init(CLICKPESA_BASE_URL . '/api/v1/oauth/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => CLICKPESA_CLIENT_ID,
        'client_secret' => CLICKPESA_API_KEY,
        'grant_type' => 'client_credentials'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            return $data['access_token'];
        }
    }
    
    // Log error for debugging
    error_log("ClickPesa Auth Error: HTTP $http_code - $response");
    return null;
}
?>