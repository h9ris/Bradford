<?php
require_once __DIR__ . '/includes/auth.php';
$token = $_GET['token'] ?? '';
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password !== $password2) $errors[] = 'Passwords do not match';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password needs an uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password needs a lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password needs a number';
    if (empty($errors)) {
        if (reset_password($token, $password)) {
            $success = true;
        } else {
            $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Bradford Council Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="bradford-crest">&#9632;</div>
            <h1>Bradford Council Portal</h1>
            <p class="auth-subtitle">Set a new password</p>
        </div>
        <?php if ($success): ?>
            <div class="registered-banner">&#10003; Password changed! <a href="index.php">Sign in here</a></div>
        <?php else: ?>
            <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul><?php endif; ?>
            <form method="post" class="auth-form">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required placeholder="Min 8 chars, upper, lower, number">
                </div>
                <div class="form-group">
                    <label for="password2">Confirm Password</label>
                    <input type="password" id="password2" name="password2" required>
                </div>
                <button type="submit" class="btn-primary btn-full">Set New Password</button>
            </form>
        <?php endif; ?>
        <p class="auth-footer"><a href="forgot.php">Request a new reset link</a></p>
    </div>
</div>
</body>
</html>
