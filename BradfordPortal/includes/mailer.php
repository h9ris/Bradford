<?php
/**
 * Email helper for Bradford Portal
 * 
 * To use: install PHPMailer via Composer
 *   composer require phpmailer/phpmailer
 * 
 * Then uncomment and configure SMTP details below.
 */

// require 'vendor/autoload.php';
// use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send registration confirmation email
 */
function send_registration_email($email, $name = null)
{
    /*
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@bradford.gov.uk';
        $mail->Password   = 'your_password_here';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@bradford.gov.uk', 'Bradford Portal');
        $mail->addAddress($email, $name);
        $mail->Subject = 'Thank you for registering at Bradford Portal';
        $mail->Body    = "Thank you for registering at the Bradford Portal.\n\nYou can now log in with your email and password.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
    */
    
    // stub: just log it for now
    error_log("Registration email would be sent to: $email");
    return true;
}

/**
 * Send password reset email with token link
 */
function send_reset_email($email, $token)
{
    /*
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        // ... same SMTP config as above ...
        
        $resetLink = 'http://localhost/BradfordPortal/reset.php?token=' . urlencode($token);
        $mail->setFrom('noreply@bradford.gov.uk', 'Bradford Portal');
        $mail->addAddress($email);
        $mail->Subject = 'Reset your Bradford Portal password';
        $mail->Body    = "Click this link to reset your password (valid for 15 minutes):\n\n$resetLink";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
    */
    
    // stub: just log it for now
    error_log("Password reset email would be sent to: $email with token: $token");
    return true;
}

?>
