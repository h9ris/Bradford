<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/totp.php';

require_login();
$user = current_user();
$db = get_db();

$message = '';

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['disable'])) {
        // remove secret
        $stmt = $db->prepare('UPDATE users SET two_factor_secret = NULL WHERE id = ?');
        $stmt->execute([$user['id']]);
        $message = 'Two-factor authentication has been disabled.';
    } elseif (isset($_POST['enable'])) {
        $secret = generate_totp_secret();
        $stmt = $db->prepare('UPDATE users SET two_factor_secret = ? WHERE id = ?');
        $stmt->execute([$secret, $user['id']]);
        $message = 'Two-factor authentication enabled. Scan the QR code with your app.';
        $user['two_factor_secret'] = $secret;
    }
    // refresh user data
    $user = current_user();
}

$has2fa = !empty($user['two_factor_secret']);
$qrCode = $has2fa ? get_totp_qr_code($user['email'], $user['two_factor_secret']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Setup - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Two-Factor Authentication</h1>
    <?php if ($message): ?>
        <p class="success"><?=htmlspecialchars($message)?></p>
    <?php endif; ?>
    <?php if ($has2fa): ?>
        <p>2FA is currently <strong>enabled</strong> for your account.</p>
        <p>Scan this QR code with your authenticator app (Google Authenticator, Microsoft Authenticator, Authy, etc.):</p>
        <div style="text-align: center; margin: 20px 0;">
            <img src="<?=htmlspecialchars($qrCode)?>" alt="QR code for authenticator app" 
                 style="border: 2px solid #005ea5; border-radius: 10px; padding: 10px; background: white; max-width: 100%; height: auto;"
                 onerror="this.style.display='none'; document.getElementById('qr-fallback').style.display='block';">
        </div>
        <div id="qr-fallback" style="display: none; background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; margin: 10px 0;">
            <strong>QR Code not loading?</strong><br>
            Manually enter this secret in your authenticator app:<br>
            <code style="background: #f8f9fa; padding: 8px; border-radius: 3px; font-family: monospace; font-size: 16px; display: block; margin: 10px 0; word-break: break-all;"><?=htmlspecialchars($user['two_factor_secret'])?></code>
            <small>Choose "Time-based" or "TOTP" when adding manually. Account name: "Bradford Portal"</small>
        </div>
        <form method="post">
            <button type="submit" name="disable" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Disable 2FA</button>
        </form>
    <?php else: ?>
        <p>Two-factor authentication (2FA) adds an extra layer of security to your account.</p>
        <p><strong>How to set up 2FA:</strong></p>
        <ol>
            <li>Install an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, Authy, etc.)</li>
            <li>Click "Enable 2FA" below</li>
            <li>Scan the QR code with your app, or enter the secret manually</li>
            <li>Next time you log in, enter the 6-digit code from your app</li>
        </ol>
        <form method="post">
            <button type="submit" name="enable" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px;">Enable 2FA</button>
        </form>
    <?php endif; ?>
    <p><a href="portal.php">Back to dashboard</a></p>
</body>
</html>