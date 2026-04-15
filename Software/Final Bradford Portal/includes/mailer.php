<?php
/**
 * Bradford Portal - Email Helper
 * Updated: April 2026 - Working email for password reset & registration
 *
 * SETUP: Edit the 4 constants below to your SMTP credentials.
 * For Gmail: use an App Password (not your main password).
 * Gmail App Passwords: myaccount.google.com > Security > 2-Step Verification > App Passwords
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =====================================================
// CHANGE THESE 4 VALUES TO YOUR EMAIL DETAILS
// =====================================================
define('MAIL_HOST',     getenv('MAIL_HOST')     ?: 'smtp.gmail.com');
define('MAIL_PORT',     getenv('MAIL_PORT')     ?: 587);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'your@gmail.com');       // <-- CHANGE
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: 'your_app_password');    // <-- CHANGE
define('MAIL_SECURE',   getenv('MAIL_SECURE')   ?: 'tls');
define('MAIL_FROM',     getenv('MAIL_FROM')     ?: 'noreply@bradford.gov.uk');
define('MAIL_FROM_NAME','Bradford Council Portal');
// =====================================================

function get_mail_instance() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->Port       = (int)MAIL_PORT;
    $mail->SMTPAuth   = (MAIL_HOST !== 'localhost');
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    if (MAIL_SECURE === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif (MAIL_SECURE === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    return $mail;
}

/**
 * Send welcome/registration confirmation email
 */
function send_registration_email($email, $first_name = null, $last_name = null) {
    try {
        $mail = get_mail_instance();
        $name = trim("$first_name $last_name") ?: $email;
        $mail->addAddress($email, $name);
        $mail->Subject = 'Welcome to Bradford Council Portal';
        $mail->isHTML(true);
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0;">
  <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div style="background: #005ea5; padding: 30px 40px;">
      <h1 style="color: white; margin: 0; font-size: 22px;">&#9632; Bradford Council Portal</h1>
    </div>
    <div style="padding: 40px;">
      <h2 style="color: #005ea5; margin-top: 0;">Welcome, {$first_name}!</h2>
      <p style="color: #444; line-height: 1.6;">Thank you for registering with the Bradford Council Portal. Your account has been successfully created.</p>
      <p style="color: #444; line-height: 1.6;">You can now log in to access the portal, view maps, upload data, and use all available features.</p>
      <div style="text-align: center; margin: 30px 0;">
        <a href="http://localhost/BradfordPortal/index.php" style="background: #005ea5; color: white; padding: 14px 32px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Sign In to Portal</a>
      </div>
      <p style="color: #888; font-size: 13px;">If you have any issues, contact: <a href="mailto:Yunus.mayat@bradford.gov.uk" style="color: #005ea5;">Yunus.mayat@bradford.gov.uk</a></p>
    </div>
    <div style="background: #f4f4f4; padding: 20px 40px; text-align: center;">
      <p style="color: #888; font-size: 12px; margin: 0;">© Bradford Metropolitan District Council</p>
    </div>
  </div>
</body>
</html>
HTML;
        $mail->AltBody = "Welcome to Bradford Council Portal, {$first_name}!\n\nThank you for registering. You can now log in at: http://localhost/BradfordPortal/\n\nIf you have any issues, contact: Yunus.mayat@bradford.gov.uk";
        return $mail->send();
    } catch (Exception $e) {
        error_log('Registration email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send password reset email
 */
function send_reset_email($email, $token) {
    try {
        $mail = get_mail_instance();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/BradfordPortal/');
        $resetLink = $protocol . '://' . $host . $dir . '/reset.php?token=' . urlencode($token);

        $mail->addAddress($email);
        $mail->Subject = 'Reset Your Bradford Portal Password';
        $mail->isHTML(true);
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0;">
  <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div style="background: #005ea5; padding: 30px 40px;">
      <h1 style="color: white; margin: 0; font-size: 22px;">&#9632; Bradford Council Portal</h1>
    </div>
    <div style="padding: 40px;">
      <h2 style="color: #005ea5; margin-top: 0;">Password Reset Request</h2>
      <p style="color: #444; line-height: 1.6;">We received a request to reset your Bradford Portal password. Click the button below to set a new password.</p>
      <div style="text-align: center; margin: 30px 0;">
        <a href="{$resetLink}" style="background: #005ea5; color: white; padding: 14px 32px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Reset My Password</a>
      </div>
      <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p style="margin: 0; color: #856404;"><strong>&#9888; This link expires in 15 minutes.</strong> If you did not request a password reset, please ignore this email — your password will remain unchanged.</p>
      </div>
      <p style="color: #888; font-size: 13px;">If the button doesn't work, copy and paste this link:<br><code style="word-break: break-all;">{$resetLink}</code></p>
    </div>
    <div style="background: #f4f4f4; padding: 20px 40px; text-align: center;">
      <p style="color: #888; font-size: 12px; margin: 0;">© Bradford Metropolitan District Council</p>
    </div>
  </div>
</body>
</html>
HTML;
        $mail->AltBody = "Password Reset Request\n\nReset your password (valid for 15 minutes):\n{$resetLink}\n\nIf you didn't request this, ignore this email.";
        return $mail->send();
    } catch (Exception $e) {
        error_log('Password reset email failed: ' . $e->getMessage());
        return false;
    }
}
