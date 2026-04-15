<?php
require_once __DIR__ . '/includes/auth.php';

// Enforce HTTPS in production
enforce_https();

$error = '';
$remaining_attempts = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Check rate limit before attempting login
    if (!check_rate_limit($email, $ip)) {
        $error = 'Too many failed login attempts. Please try again later.';
        $remaining_attempts = get_remaining_attempts($email, $ip);
    } elseif (login_user($email, $password)) {
        $user = current_user();
        if ($user && $user['is_admin']) {
            header('Location: admin.php');
            exit;
        } else {
            $error = 'You are not authorised as an admin';
            // log out
            session_destroy();
        }
    } else {
        $error = 'Invalid email or password';
        $remaining_attempts = get_remaining_attempts($email, $ip);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Administrator Login - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Administrator Login</h1>
    <?php if ($error): ?>
        <p class="error"><?=htmlspecialchars($error)?></p>
        <?php if ($remaining_attempts): ?>
            <p class="error">Remaining attempts: IP (<?=$remaining_attempts['ip_remaining']?>), Email (<?=$remaining_attempts['email_remaining']?>)</p>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p><a href="index.php">User login</a></p>
</body>
</html>