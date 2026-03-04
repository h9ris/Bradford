<?php
/**
 * 2FA / TOTP helper for Bradford Portal
 * Uses spomky-labs/otphp for TOTP generation and verification
 */

require __DIR__ . '/../vendor/autoload.php';

use OTPHP\TOTP;

/**
 * Generate a new TOTP secret for a user
 * Returns both the secret and a provisioning URI for QR code generation
 */
function generate_totp_secret($email, $issuer = 'Bradford Portal')
{
    $totp = TOTP::create();
    $totp->setLabel($email);
    $totp->setIssuer($issuer);
    
    $secret = $totp->getSecret();
    $provisioningUri = $totp->getProvisioningUri();
    
    return [
        'secret' => $secret,
        'uri' => $provisioningUri
    ];
}

/**
 * Get the provisioning URI for QR code generation
 * This is what the user scans with Google Authenticator / Authy
 */
function get_totp_qr_uri($email, $secret, $issuer = 'Bradford Portal')
{
    $totp = TOTP::create($secret);
    $totp->setLabel($email);
    $totp->setIssuer($issuer);
    return $totp->getProvisioningUri();
}

/**
 * Generate a QR code image URL using Google Charts
 */
function get_totp_qr_code_url($uri)
{
    return 'https://chart.googleapis.com/chart?chs=300x300&chld=M|0&cht=qr&chl=' . urlencode($uri);
}

/**
 * Verify a TOTP code entered by the user
 * Returns true if the code is valid, false otherwise
 */
function verify_totp_code($secret, $code, $allowedWindow = 1)
{
    try {
        $totp = TOTP::create($secret);
        // The verify() method checks the code with a time window (default ±1 time step)
        return $totp->verify($code, time(), $allowedWindow);
    } catch (Exception $e) {
        return false;
    }
}

?>
