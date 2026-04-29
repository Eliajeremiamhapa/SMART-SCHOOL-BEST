<?php
// api/clickpesa_config.php

// API Credentials zako kutoka ClickPesa
define('CLICKPESA_CLIENT_ID', 'IDbPKPihr5jbe4GCgdKgd6jdI4O85OK7');
define('CLICKPESA_API_KEY', 'SKMTyFk89YGBFFMHHbe9ubxZB6FUOhiW29MsZoi4Ad');
define('CLICKPESA_WEBHOOK_SECRET', ''); // Unaweza kuacha tupu kwa sasa

// Environment: 'sandbox' kwa majaribio (bure), 'production' kwa pesa halisi
define('CLICKPESA_ENVIRONMENT', 'sandbox');

// Base URL kwa ClickPesa
if (CLICKPESA_ENVIRONMENT == 'sandbox') {
    define('CLICKPESA_BASE_URL', 'https://sandbox.clickpesa.com');
} else {
    define('CLICKPESA_BASE_URL', 'https://api.clickpesa.com');
}

// Webhook URL (badilisha na domain yako halisi)
define('CLICKPESA_WEBHOOK_URL', 'https://yourdomain.com/api/clickpesa_webhook.php');
?>