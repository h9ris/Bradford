<?php
// auth.php - authentication helpers for Bradford Portal

require_once __DIR__ . '/db.php';

// PHPMailer for email sending
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

// simple admin code for registration (replace with secure mechanism in real use)
define('ADMIN_REGISTRATION_CODE', 'BradfordAdmin2026');

// Email configuration
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'noreply@bradfordportal.local');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Bradford Portal');
define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: 'localhost');
define('MAIL_SMTP_PORT', getenv('MAIL_SMTP_PORT') ?: 1025); // MailHog default
define('MAIL_SMTP_USER', getenv('MAIL_SMTP_USER') ?: '');
define('MAIL_SMTP_PASS', getenv('MAIL_SMTP_PASS') ?: '');
define('MAIL_SMTP_SECURE', getenv('MAIL_SMTP_SECURE') ?: false); // Set to 'tls' or 'ssl' for real SMTP

/**
 * Send email using PHPMailer
 */
function send_email($toEmail, $toName, $subject, $htmlBody, $textBody = null)
{
    try {
        $mail = new PHPMailer(true);
        
        // SMTP configuration
        if (MAIL_SMTP_HOST !== 'localhost') {
            $mail->isSMTP();
            $mail->Host = MAIL_SMTP_HOST;
            $mail->Port = MAIL_SMTP_PORT;
            $mail->SMTPAuth = !empty(MAIL_SMTP_USER);
            if (MAIL_SMTP_USER) {
                $mail->Username = MAIL_SMTP_USER;
                $mail->Password = MAIL_SMTP_PASS;
            }
            if (MAIL_SMTP_SECURE) {
                $mail->SMTPSecure = MAIL_SMTP_SECURE;
            }
        } else {
            // Local development - use sendmail or MailHog
            $mail->isSMTP();
            $mail->Host = MAIL_SMTP_HOST;
            $mail->Port = MAIL_SMTP_PORT;
            $mail->SMTPAuth = false;
        }
        
        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        if ($textBody) {
            $mail->AltBody = $textBody;
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send registration confirmation email
 */
function send_registration_email($email, $name)
{
    $subject = 'Welcome to Bradford Portal';
    
    $htmlBody = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2 style='color: #8B3A62;'>Welcome to Bradford Portal</h2>
        <p>Hello " . htmlspecialchars($name) . ",</p>
        <p>Thank you for registering with Bradford Portal. Your account has been successfully created.</p>
        <p>You can now <a href='http://localhost/BradfordPortal./index.php'>log in</a> with your email and password.</p>
        <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
        <p style='color: #666; font-size: 12px;'>
            If you did not create this account, please ignore this email.<br>
            Bradford Portal Team
        </p>
    </body>
    </html>";
    
    $textBody = "
Welcome to Bradford Portal

Hello $name,

Thank you for registering with Bradford Portal. Your account has been successfully created.

You can now log in at: http://localhost/BradfordPortal./index.php

If you did not create this account, please ignore this email.

Bradford Portal Team";
    
    return send_email($email, $name, $subject, $htmlBody, $textBody);
}

/**
 * Send password reset email
 */
function send_reset_email($email, $name, $resetToken)
{
    $resetLink = 'http://localhost/BradfordPortal./reset.php?token=' . urlencode($resetToken);
    $subject = 'Bradford Portal - Password Reset';
    
    $htmlBody = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2 style='color: #8B3A62;'>Password Reset Request</h2>
        <p>Hello " . htmlspecialchars($name) . ",</p>
        <p>We received a request to reset your password. Click the link below to create a new password:</p>
        <p>
            <a href='$resetLink' style='
                display: inline-block;
                padding: 10px 20px;
                background-color: #8B3A62;
                color: white;
                text-decoration: none;
                border-radius: 4px;
            '>Reset Password</a>
        </p>
        <p>Or copy and paste this link:</p>
        <p><code>$resetLink</code></p>
        <p style='color: #d9534f;'><strong>⚠️ This link expires in 15 minutes.</strong></p>
        <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
        <p style='color: #666; font-size: 12px;'>
            If you did not request a password reset, please ignore this email.<br>
            Bradford Portal Team
        </p>
    </body>
    </html>";
    
    $textBody = "
Bradford Portal - Password Reset

Hello $name,

We received a request to reset your password. Copy the link below and paste it in your browser:

$resetLink

This link expires in 15 minutes.

If you did not request a password reset, please ignore this email.

Bradford Portal Team";
    
    return send_email($email, $name, $subject, $htmlBody, $textBody);
}

/**
 * Hash a password using bcrypt.
 */
function hash_password($password)
{
	return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a candidate password against a hash.
 */
function verify_password($password, $hash)
{
	return password_verify($password, $hash);
}

/**
 * Create a new user record.
 */
/**
 * Create a new user record.
 *
 * @param string $email
 * @param string $password
 * @param string|null $name
 * @param bool $isAdmin
 * @return int user id
 */
function register_user($email, $password, $name = null, $isAdmin = false)
{
    $db = get_db();
    $hash = hash_password($password);
    // encrypt the name field if provided
    if ($name !== null && $name !== '') {
        $name = encrypt_data($name);
    }
	// if this is the first user ever, make them admin regardless of code
	if (!$isAdmin) {
		$count = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
		if ($count == 0) {
			$isAdmin = true;
		}
	}
	$stmt = $db->prepare('INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, ?, ?)');
	$stmt->execute([$email, $hash, $name, $isAdmin ? 1 : 0]);
    $userId = $db->lastInsertId();
    log_activity($userId, 'register');
    return $userId;
}

/**
 * Authenticate credentials and start a session.
 */
function login_user($email, $password)
{
	$db = get_db();
	$stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
	$stmt->execute([$email]);
	$user = $stmt->fetch();
	if ($user && verify_password($password, $user['password_hash'])) {
		// simple session, could add 2FA check here later
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['is_admin'] = (bool)$user['is_admin'];
		log_activity($user['id'], 'login');
		return true;
	}
	return false;
}

/**
 * Returns the currently logged in user or null.
 */
function current_user()
{
    if (isset($_SESSION['user_id'])) {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && !empty($user['name'])) {
            $user['name'] = decrypt_data($user['name']);
        }
        return $user;
    }
    return null;
}

/**
 * Ensure the user is logged in; redirect to login page if not.
 */
function require_login()
{
	if (!current_user()) {
		header('Location: index.php');
		exit;
	}
}

/**
 * Generate a secure random token.
 */
function generate_token($length = 32)
{
	return bin2hex(random_bytes($length));
}

/**
 * Initiate a password reset process for an email.
 */
function send_password_reset($email)
{
	$db = get_db();
	$stmt = $db->prepare('SELECT id, name FROM users WHERE email = ?');
	$stmt->execute([$email]);
	$user = $stmt->fetch();
	if (!$user) {
		return false;
	}
	
	$token = generate_token(16);
	$expires = date('Y-m-d H:i:s', time() + 15*60);
	$stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
	$stmt->execute([$token, $expires, $user['id']]);
	
	// Send password reset email
	$name = !empty($user['name']) ? decrypt_data($user['name']) : 'User';
	send_reset_email($email, $name, $token);
	
	log_activity($user['id'], 'password_reset_requested');
	return $token;
}

/**
 * Verify reset token and set new password.
 */
function reset_password($token, $newPassword)
{
	$db = get_db();
	$stmt = $db->prepare('SELECT id, reset_expires FROM users WHERE reset_token = ?');
	$stmt->execute([$token]);
	$user = $stmt->fetch();
	if ($user && strtotime($user['reset_expires']) > time()) {
		$hash = hash_password($newPassword);
		$stmt = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
		$stmt->execute([$hash, $user['id']]);
		log_activity($user['id'], 'password_reset');
		return true;
	}
	return false;
}

?>
