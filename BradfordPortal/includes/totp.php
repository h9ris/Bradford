<?php
/**
 * 2FA / TOTP helper for Bradford Portal
 * 
 * To use: install TOTP library via Composer
 *   composer require sonata-project/google-authenticator
 * 
 * Or use a simpler solution like Spomky-Labs/otphp
 */

// require 'vendor/autoload.php';

/**
 * Generate a TOTP secret for a user
 * This secret is shared between the user's authenticator app and the server
 */
function generate_totp_secret()
{
    // Generate a random 32-character secret
    return bin2hex(random_bytes(16));
}

/**
 * Generate a QR code URL for Google Authenticator setup
 * User scans this with their phone to set up 2FA
 */
function get_totp_qr_code($email, $secret)
{
    // Format: otpauth://totp/Bradford Portal:email@example.com?secret=...&issuer=Bradford%20Portal
    $url = sprintf(
        'otpauth://totp/Bradford%%20Portal:%s?secret=%s&issuer=Bradford%%20Portal',
        urlencode($email),
        urlencode($secret)
    );
    
    // Use Google Charts API to generate QR code
    $qrUrl = 'https://chart.googleapis.com/chart?chs=300x300&chld=M|0&cht=qr&chl=' . urlencode($url);
    return $qrUrl;
}

/**
 * Verify a TOTP code entered by the user
 * This is where you'd use an authenticator library to validate the 6-digit code
 */
function verify_totp_code($secret, $code, $allowedWindow = 1)
{
    // This is a stub showing the concept
    // In production, use:
    //   composer require spomky-labs/otphp
    //   $totp = \OTPHP\TOTP::create($secret);
    //   return $totp->verify($code);
    
    error_log("TOTP verification stub called for secret: $secret, code: $code");
    return true; // stub: always return true for now
}

?>
