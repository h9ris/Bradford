<?php
require_once __DIR__ . '/includes/auth.php';

$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }
    if (empty($errors)) {
        $token = send_password_reset($email);
        if ($token) {
            // in a real app you would send an email containing the link
            // for now we display the link for testing
            $success = 'A reset link has been emailed. (Token: ' . htmlspecialchars($token) . ')';
        } else {
            $errors[] = 'Email address not found';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Reset password</h1>
    <?php if ($success): ?><p class="success"><?=htmlspecialchars($success)?></p><?php endif; ?>
    <?php if ($errors): ?>
        <ul class="error">
        <?php foreach ($errors as $e): ?>
            <li><?=htmlspecialchars($e)?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Send reset link</button>
    </form>
    <p><a href="index.php">Back to login</a></p>
</body>
</html>