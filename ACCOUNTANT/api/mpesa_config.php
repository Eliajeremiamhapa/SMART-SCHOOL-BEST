<?php
// api/mpesa_config.php

// Safaricom M-Pesa Sandbox Credentials
define('MPESA_CONSUMER_KEY', 'BOn3kwxIfKXBkXhFZyp4EefOstaKIGx3pSvdToZEX8YEAWdT');      // Consumer Key yako
define('MPESA_CONSUMER_SECRET', 'hvNXyqhfAFs578GEZAawgkFMRZFbCYZyMpABkcSG3fZHxcopduBXJV7QdoMCTtFR');   // Consumer Secret yako
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_SHORTCODE', '174379');               // Sandbox Shortcode
define('MPESA_ENVIRONMENT', 'sandbox');            // 'sandbox' au 'production'

// Base URLs
if (MPESA_ENVIRONMENT == 'sandbox') {
    define('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke');
} else {
    define('MPESA_BASE_URL', 'https://api.safaricom.co.ke');
}

// Callback URL (badilisha na domain yako halisi)
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/api/mpesa_callback.php');
?>