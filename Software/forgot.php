<?php
require_once __DIR__ . '/includes/auth.php';
$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($errors)) {
        $token = send_password_reset($email);
        if ($token) {
            require_once __DIR__ . '/includes/mailer.php';
            send_reset_email($email, $token);
        }
        // always show success to prevent email enumeration
        $success = 'If that email is registered, a password reset link has been sent. Check your inbox (valid for 15 minutes).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Bradford Council Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="bradford-crest">&#9632;</div>
            <h1>Bradford Council Portal</h1>
            <p class="auth-subtitle">Reset your password</p>
        </div>
        <?php if ($success): ?>
            <div class="registered-banner">&#9993; <?=htmlspecialchars($success)?></div>
        <?php endif; ?>
        <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul><?php endif; ?>
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com">
            </div>
            <button type="submit" class="btn-primary btn-full">Send Reset Link</button>
        </form>
        <p class="auth-footer"><a href="index.php">&#8592; Back to sign in</a></p>
    </div>
</div>
</body>
</html>
