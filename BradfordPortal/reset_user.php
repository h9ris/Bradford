<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
if (!$user['is_admin']) {
    http_response_code(403);
    echo "Access denied";
    exit;
}
$db = get_db();

$errors = [];
success: $message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = $_POST['user_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match';
    }
    if (empty($errors)) {
        $hash = hash_password($password);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $target]);
        log_activity($user['id'], 'admin_reset', "user:$target");
        $message = 'Password updated';
    }
}
$users = $db->query('SELECT id, email FROM users')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reset User Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Reset user password</h1>
    <?php if ($message): ?><p class="success"><?=htmlspecialchars($message)?></p><?php endif; ?>
    <?php if ($errors): ?>
        <ul class="error">
        <?php foreach ($errors as $e): ?>
            <li><?=htmlspecialchars($e)?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post">
        <label for="user_id">User:</label>
        <select name="user_id" id="user_id">
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['email']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="password">New password:</label>
        <input type="password" name="password" id="password" required>
        <label for="password2">Confirm:</label>
        <input type="password" name="password2" id="password2" required>
        <button type="submit">Update</button>
    </form>
    <p><a href="admin.php">Back to admin</a></p>
</body>
</html>