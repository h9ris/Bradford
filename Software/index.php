<?php
require_once __DIR__ . '/includes/auth.php';
enforce_https();

if (current_user()) {
    header('Location: portal.php');
    exit;
}

$error = '';
$error_type = '';
$remaining_attempts = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $code     = $_POST['code']     ?? null;
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!check_rate_limit($email, $ip)) {
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
        $remaining_attempts = get_remaining_attempts($email, $ip);
    } else {
        $result = login_user($email, $password, $code);
        if ($result === true) {
            header('Location: portal.php');
            exit;
        } elseif ($result === '2fa_required') {
            $error = 'Invalid or missing 2FA code. Please enter the 6-digit code from your authenticator app.';
            $error_type = '2fa';
        } else {
            $error = 'Incorrect email or password.';
            $remaining_attempts = get_remaining_attempts($email, $ip);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In - Bradford Council Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<a href="#main" class="skip-link">Skip to content</a>
<div class="auth-wrapper-new">
    <div class="auth-card-side" id="main">
        <div class="auth-card">
        <div class="auth-logo">
            <div class="bradford-crest">&#9632;</div>
            <h1>Bradford Council Portal</h1>
            <p class="auth-subtitle">Metropolitan District Council</p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="registered-banner">&#10003; Registration successful! Please sign in below.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-list">
                <?=htmlspecialchars($error)?>
                <?php if ($remaining_attempts): ?>
                    <br><small>Remaining attempts — IP: <?=$remaining_attempts['ip_remaining']?>, Email: <?=$remaining_attempts['email_remaining']?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       placeholder="you@example.com"
                       value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Your password">
            </div>
            <div class="form-group" id="totp-field" <?=($error_type !== '2fa' && empty($_POST['code'])) ? 'style="display:none"' : ''?>>
                <label for="code">2FA Code <span class="optional">(if enabled)</span></label>
                <input type="text" id="code" name="code" pattern="\d{6}" maxlength="6"
                       placeholder="6-digit code from your app"
                       value="<?=htmlspecialchars($_POST['code'] ?? '')?>">
            </div>
            <button type="submit" class="btn-primary btn-full">Sign In</button>
            <p style="text-align:center; margin-top:10px; font-size:13px; color:#6b7280;">
                Have a 2FA code? <a href="#" onclick="document.getElementById('totp-field').style.display='block'; return false;">Show 2FA field</a>
            </p>
        </form>

        <p class="auth-footer">
            <a href="register.php">Create an account</a> &nbsp;|&nbsp;
            <a href="forgot.php">Forgot password?</a>
        </p>
        </div>  <!-- closes auth-card -->
    </div>  <!-- closes auth-card-side -->
    
    <!-- Bradford skyline image on right -->
    <div class="auth-image-side">
        <div class="auth-image-overlay"></div>
        <img src="images/bradford-skyline.jpg" alt="Bradford City Skyline" class="auth-image">
    </div>
</div>  <!-- closes auth-wrapper-new -->
</body>
</html>
