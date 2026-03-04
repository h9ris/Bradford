<?php
/**
 * Bradford Portal - Two-Factor Authentication Setup Page
 * Users can enable/disable 2FA and view their secret here
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/totp.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = get_db_connection();

// Get current user info
$stmt = $pdo->prepare('SELECT email, two_factor_secret FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$email = $user['email'];
$existing_secret = $user['two_factor_secret'];
$two_fa_enabled = !empty($existing_secret);

$message = '';
$error = '';
$qr_code_url = '';
$temp_secret = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable_2fa') {
        // Generate a new secret and show QR code
        $totp_data = generate_totp_secret($email);
        $temp_secret = $totp_data['secret'];
        $uri = $totp_data['uri'];
        $qr_code_url = get_totp_qr_code_url($uri);
        
        // Store temporarily in session for verification
        $_SESSION['temp_2fa_secret'] = $temp_secret;
        $message = 'Scan the QR code below with your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.) and enter the 6-digit code to confirm.';
        
    } elseif ($action === 'confirm_2fa') {
        // User enters the 6-digit code from their authenticator app
        $code = trim($_POST['totp_code'] ?? '');
        $temp_secret = $_SESSION['temp_2fa_secret'] ?? '';
        
        if (empty($temp_secret)) {
            $error = 'Please start the setup process again.';
        } elseif (empty($code) || !preg_match('/^\d{6}$/', $code)) {
            $error = 'Please enter a valid 6-digit code.';
        } elseif (verify_totp_code($temp_secret, $code)) {
            // Verification succeeded; save the secret to the database
            $stmt = $pdo->prepare('UPDATE users SET two_factor_secret = ? WHERE id = ?');
            $stmt->execute([$temp_secret, $user_id]);
            
            unset($_SESSION['temp_2fa_secret']);
            log_activity($user_id, '2FA_ENABLED', 'User enabled two-factor authentication');
            
            $message = 'Two-factor authentication has been successfully enabled!';
            $two_fa_enabled = true;
            
        } else {
            $error = 'Invalid code. Please check and try again. (Note: codes expire after 30 seconds)';
        }
        
    } elseif ($action === 'disable_2fa') {
        // User disables 2FA (requires password confirmation for security)
        $password = $_POST['password'] ?? '';
        
        if (!verify_password($password, $user_id)) {
            $error = 'Incorrect password. 2FA was not disabled.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET two_factor_secret = NULL WHERE id = ?');
            $stmt->execute([$user_id]);
            
            log_activity($user_id, '2FA_DISABLED', 'User disabled two-factor authentication');
            $message = 'Two-factor authentication has been disabled.';
            $two_fa_enabled = false;
        }
    }
}

// If we're in the middle of enabling, and we already generated a temp secret, show it
if (!empty($_SESSION['temp_2fa_secret']) && empty($temp_secret)) {
    $temp_secret = $_SESSION['temp_2fa_secret'];
    $uri = get_totp_qr_uri($email, $temp_secret);
    $qr_code_url = get_totp_qr_code_url($uri);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Setup - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .totp-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .totp-status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .totp-status.enabled {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .totp-status.disabled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .qr-section img {
            max-width: 300px;
            margin: 15px 0;
        }
        .secret-code {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .button-group button {
            flex: 1;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-primary {
            background: #8B3A62;
            color: white;
        }
        .btn-primary:hover {
            background: #6d2d4a;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #8B3A62;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="totp-container">
        <h1>Two-Factor Authentication (2FA)</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="totp-status <?php echo $two_fa_enabled ? 'enabled' : 'disabled'; ?>">
            Status: <?php echo $two_fa_enabled ? '✓ Enabled' : '✗ Disabled'; ?>
        </div>
        
        <?php if ($two_fa_enabled && empty($_SESSION['temp_2fa_secret'])): ?>
            <!-- 2FA is enabled; show disable option -->
            <p>Two-factor authentication is currently <strong>enabled</strong> on your account. You will be asked for a 6-digit code from your authenticator app each time you log in.</p>
            
            <form method="POST" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="password">Enter your password to disable 2FA:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <input type="hidden" name="action" value="disable_2fa">
                <button type="submit" class="btn-danger">Disable Two-Factor Authentication</button>
            </form>
            
        <?php elseif (!$two_fa_enabled && empty($_SESSION['temp_2fa_secret'])): ?>
            <!-- 2FA is disabled; show enable option -->
            <p>Two-factor authentication adds an extra layer of security to your account. You'll need to enter a code from an authenticator app each time you log in.</p>
            
            <h3>Compatible Apps:</h3>
            <ul>
                <li>Google Authenticator</li>
                <li>Microsoft Authenticator</li>
                <li>Authy</li>
                <li>FreeOTP</li>
            </ul>
            
            <form method="POST">
                <input type="hidden" name="action" value="enable_2fa">
                <button type="submit" class="btn-primary">Enable Two-Factor Authentication</button>
            </form>
            
        <?php else: ?>
            <!-- Show QR code setup process -->
            <p><strong>Step 1:</strong> Scan this QR code with your authenticator app:</p>
            
            <div class="qr-section">
                <?php if ($qr_code_url): ?>
                    <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="QR Code for 2FA">
                    <p><small>Can't scan? Enter this code manually:</small></p>
                    <div class="secret-code"><?php echo htmlspecialchars($temp_secret); ?></div>
                <?php endif; ?>
            </div>
            
            <p><strong>Step 2:</strong> Enter the 6-digit code from your authenticator app below:</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="totp_code">6-Digit Code:</label>
                    <input type="text" id="totp_code" name="totp_code" 
                           pattern="\d{6}" 
                           placeholder="000000" 
                           maxlength="6" 
                           autocomplete="off"
                           inputmode="numeric"
                           required>
                </div>
                
                <div class="button-group">
                    <input type="hidden" name="action" value="confirm_2fa">
                    <button type="submit" class="btn-primary">Verify and Enable 2FA</button>
                </div>
            </form>
            
            <p><small style="color: #666;">Having trouble? Make sure your device time is synchronized with the internet.</small></p>
        <?php endif; ?>
        
        <a href="portal.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
