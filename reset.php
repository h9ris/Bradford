<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match';
    }
    if (empty($errors)) {
        if (reset_password($token, $password)) {
            $success = 'Password has been reset. <a href="index.php">Login</a>';
        } else {
            $errors[] = 'Invalid or expired token';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Set new password</h1>
    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php else: ?>
        <?php if ($errors): ?>
            <ul class="error">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post">
            <label for="password">New password:</label>
            <input type="password" id="password" name="password" required>
            <label for="password2">Confirm:</label>
            <input type="password" id="password2" name="password2" required>
            <button type="submit">Reset</button>
        </form>
    <?php endif; ?>
</body>
</html>