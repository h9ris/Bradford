<?php
/**
 * Test script to verify email configuration
 * 
 * Run this in a browser: http://localhost/BradfordPortal/test_email.php
 * 
 * SECURITY: Delete this file after testing! It should NOT be on production.
 */

require_once __DIR__ . '/includes/mailer.php';

// Check if PHPMailer is installed
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo '<h1>❌ PHPMailer Not Installed</h1>';
    echo '<p>Run this command in PowerShell to install:</p>';
    echo '<code>cd C:\xampp\htdocs\BradfordPortal && composer require phpmailer/phpmailer</code>';
    exit;
}

$result = '';
$testEmail = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $testEmail) {
    $result = test_email_config($testEmail);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bradford Portal - Email Test</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-box {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
        code {
            background: #333;
            color: #0f0;
            padding: 10px;
            border-radius: 3px;
            display: block;
            margin: 10px 0;
            font-family: monospace;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>📧 Bradford Portal - Email Configuration Test</h1>
    
    <div class="test-box">
        <h2>Current Configuration</h2>
        <table>
            <tr><td><strong>SMTP Host:</strong></td><td><code><?=htmlspecialchars(MAIL_HOST)?></code></td></tr>
            <tr><td><strong>SMTP Port:</strong></td><td><code><?=MAIL_PORT?></code></td></tr>
            <tr><td><strong>Username:</strong></td><td><code><?=htmlspecialchars(MAIL_USERNAME)?></code></td></tr>
            <tr><td><strong>Encryption:</strong></td><td><code><?=MAIL_SECURE ?: 'none'?></code></td></tr>
            <tr><td><strong>From:</strong></td><td><code><?=htmlspecialchars(MAIL_FROM)?></code></td></tr>
        </table>
    </div>

    <?php if ($result): ?>
        <div class="test-box">
            <h3>Test Result:</h3>
            <p class="<?=$result === 'Test email sent successfully!' ? 'success' : 'error'?>">
                <?=htmlspecialchars($result)?>
            </p>
        </div>
    <?php endif; ?>

    <div class="test-box">
        <h2>Send Test Email</h2>
        <form method="post">
            <label for="email">Recipient Email Address:</label>
            <input type="email" id="email" name="email" required placeholder="test@example.com" value="<?=htmlspecialchars($testEmail)?>">
            <button type="submit">Send Test Email</button>
        </form>
    </div>

    <div class="test-box">
        <h2>Configuration Guide</h2>
        <p>If the test fails, check:</p>
        <ul>
            <li><strong>SMTP Settings:</strong> Edit <code>includes/mailer.php</code> lines 30-33</li>
            <li><strong>Gmail:</strong> Use App Password (not your regular password)</li>
            <li><strong>Mailpit:</strong> Download from <a href="https://github.com/axllent/mailpit/releases" target="_blank">GitHub</a> and run <code>mailpit.exe</code></li>
            <li><strong>Firewall:</strong> Ensure port <?=MAIL_PORT?> isn't blocked</li>
        </ul>
        <p>See <code>WINDOWS_EMAIL_SETUP.md</code> for detailed instructions.</p>
    </div>

    <div class="test-box">
        <h2>⚠️ Security Warning</h2>
        <p><strong>Delete this file after testing!</strong> It should not be on production servers.</p>
        <code>del C:\xampp\htdocs\BradfordPortal\test_email.php</code>
    </div>
</body>
</html>
