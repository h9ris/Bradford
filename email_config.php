<?php
session_start();

// Admin only
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial;">
        <h2>Admin Only</h2>
        <p>Only administrators can configure email.</p>
    </div>');
}

require_once 'includes/auth.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = $_POST['test_email'] ?? '';
    
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } else {
        $subject = 'Bradford Portal - Email Test';
        $htmlBody = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #8B3A62;'>Email Configuration Test</h2>
            <p>If you received this email, your email configuration is working correctly!</p>
            <p><strong>✓ Test Status: SUCCESS</strong></p>
            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
            <p style='color: #666; font-size: 12px;'>
                Bradford Portal Email System
            </p>
        </body>
        </html>";
        
        $textBody = "Email Configuration Test

If you received this email, your email configuration is working correctly!

Test Status: SUCCESS

Bradford Portal Email System";
        
        if (send_email($testEmail, 'Admin', $subject, $htmlBody, $textBody)) {
            $success = true;
            $message = "✓ Test email sent successfully to $testEmail";
        } else {
            $message = "✗ Failed to send test email. Check error logs.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container h1 {
            color: #8B3A62;
            margin-bottom: 10px;
        }
        .config-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .config-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin: 12px 0;
            align-items: center;
        }
        .config-label {
            font-weight: bold;
            color: #333;
        }
        .config-value {
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-family: monospace;
            font-size: 12px;
        }
        .form-group {
            margin: 20px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input[type="submit"] {
            width: auto;
            background: #8B3A62;
            color: white;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }
        .form-group input[type="submit"]:hover {
            background: #6B2A4A;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 13px;
            color: #666;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #8B3A62;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration</h1>
        <p>Configure and test email sending for registration confirmations and password resets.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <h2>Current Configuration</h2>
        <div class="config-section">
            <div class="config-row">
                <div class="config-label">From Email:</div>
                <div class="config-value"><?php echo defined('MAIL_FROM_EMAIL') ? htmlspecialchars(MAIL_FROM_EMAIL) : 'Not configured'; ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">From Name:</div>
                <div class="config-value"><?php echo defined('MAIL_FROM_NAME') ? htmlspecialchars(MAIL_FROM_NAME) : 'Not configured'; ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">SMTP Host:</div>
                <div class="config-value"><?php echo defined('MAIL_SMTP_HOST') ? htmlspecialchars(MAIL_SMTP_HOST) : 'Not configured'; ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">SMTP Port:</div>
                <div class="config-value"><?php echo defined('MAIL_SMTP_PORT') ? htmlspecialchars(MAIL_SMTP_PORT) : 'Not configured'; ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">SMTP Secure:</div>
                <div class="config-value"><?php echo defined('MAIL_SMTP_SECURE') && MAIL_SMTP_SECURE ? htmlspecialchars(MAIL_SMTP_SECURE) : 'Disabled'; ?></div>
            </div>
        </div>
        
        <h2>Test Email Sending</h2>
        <form method="POST">
            <div class="form-group">
                <label for="test_email">📧 Test Email Address</label>
                <input type="email" id="test_email" name="test_email" placeholder="your@email.com" required>
            </div>
            
            <div class="form-group">
                <input type="submit" name="test_email" value="📤 Send Test Email">
            </div>
        </form>
        
        <div class="instructions">
            <strong>⚙️ Email Configuration Options:</strong><br><br>
            Email is configured via environment variables or PHP constants in <code>includes/auth.php</code>:<br><br>
            <strong>For Development (MailHog):</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Install MailHog: <code>brew install mailhog</code> (macOS) or download from mailhog.github.io</li>
                <li>Run: <code>mailhog</code> - starts on localhost:1025 (SMTP) and 8025 (web)</li>
                <li>View emails at: <code>http://localhost:8025</code></li>
                <li>No configuration needed - uses default localhost:1025</li>
            </ul>
            
            <strong>For Production (Gmail, AWS SES, SendGrid, etc.):</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Set environment variables:</li>
            </ul>
            <code style="display: block; background: white; padding: 10px; margin: 10px 0; border-radius: 4px;">
export MAIL_SMTP_HOST=smtp.gmail.com<br>
export MAIL_SMTP_PORT=587<br>
export MAIL_SMTP_USER=your@email.com<br>
export MAIL_SMTP_PASS=your_app_password<br>
export MAIL_SMTP_SECURE=tls<br>
export MAIL_FROM_EMAIL=noreply@yoursite.com<br>
export MAIL_FROM_NAME="Bradford Portal"
            </code>
            
            <strong>✓ Features Enabled:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>✓ Registration confirmation emails</li>
                <li>✓ Password reset emails</li>
                <li>✓ HTML + plain text versions</li>
                <li>✓ Professional email templates</li>
            </ul>
        </div>
        
        <a href="admin.php" class="back-link">← Back to Admin</a>
    </div>
</body>
</html>
