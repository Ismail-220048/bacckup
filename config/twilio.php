<?php
/**
 * CivicTrack — Twilio Configuration
 * =============================================
 * Fill in your Twilio credentials below.
 * Account SID and Auth Token are found in:
 *   https://console.twilio.com/
 * =============================================
 */

define('TWILIO_ACCOUNT_SID', '');   // e.g. ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
define('TWILIO_AUTH_TOKEN',  '');      // e.g. your auth token from console
define('TWILIO_FROM_NUMBER', '');  // e.g. +1415XXXXXXX  (your Twilio phone number)

/**
 * OTP settings
 */
define('OTP_EXPIRY_SECONDS', 300); // OTP valid for 5 minutes
define('OTP_LENGTH', 6);
