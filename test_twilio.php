<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/twilio.php';

use Twilio\Rest\Client;

try {
    $twilio = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
    // You cant send an SMS without a verified number if trial. We'll simply try to fetch the account info to see if credentials are valid.
    $account = $twilio->api->v2010->accounts(TWILIO_ACCOUNT_SID)->fetch();
    echo "Twilio Account Details:\n";
    echo "Status: " . $account->status . "\n";
    echo "Name: " . $account->friendlyName . "\n";
    echo "Type: " . $account->type . "\n"; // Will show Trial or Full
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
