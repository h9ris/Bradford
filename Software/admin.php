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
$users = $db->query('SELECT id, email, name, is_admin, created_at FROM users')->fetchAll();
?>
<?php
// handle admin toggles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    foreach ($users as $u) {
        $checked = isset($_POST['admin_' . $u['id']]);
        $stmt = $db->prepare('UPDATE users SET is_admin = ? WHERE id = ?');
        $stmt->execute([$checked ? 1 : 0, $u['id']]);
    }
    // reload page to reflect changes
    header('Location: admin.php');
    exit;
}

// handle add user
$add_errors = [];
$add_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $add_errors[] = 'Invalid email';
    }
    if (empty($name)) {
        $add_errors[] = 'Name is required';
    }
    if (strlen($password) < 8) {
        $add_errors[] = 'Password must be at least 8 characters';
    }
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $add_errors[] = 'Email already exists';
    }
    if (empty($add_errors)) {
        $hash = hash_password($password);
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $hash, encrypt_data($name), $is_admin]);
        log_activity($user['id'], 'admin_add_user', "email:$email");
        $add_message = 'User added successfully';
        // send welcome email
        send_email($email, 'Welcome to Bradford Portal', "Thank you for registering. Your account has been created by an admin.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* a little extra styling for admin area */
        body { background-color: #e8f0fe; }
        h1 { color: #222; }
        table th { background-color: #005ea5; color: #fff; }
    </style>
</head>
<body>
    <h1>Admin Control Panel</h1>
    <p>Logged in as <?=htmlspecialchars($user['email'])?> (<a href="logout.php">Logout</a>)</p>
    <h2>Users</h2>
    <form method="post">
    <table>
        <tr><th>Email</th><th>Name</th><th>Admin</th><th>Registered</th><th>Action</th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?=htmlspecialchars($u['email'])?></td>
                <td><?=htmlspecialchars(decrypt_data($u['name'] ?? ''))?></td>
                <td><input type="checkbox" name="admin_<?=$u['id']?>" <?= $u['is_admin'] ? 'checked' : ''?>></td>
                <td><?=htmlspecialchars($u['created_at'])?></td>
                <td><button type="submit" name="save" value="1">Save</button></td>
            </tr>
        <?php endforeach; ?>
    </table>
    </form>
    <h2>Add New User</h2>
    <?php if ($add_message): ?><p class="success"><?=htmlspecialchars($add_message)?></p><?php endif; ?>
    <?php if ($add_errors): ?>
        <ul class="error">
        <?php foreach ($add_errors as $e): ?>
            <li><?=htmlspecialchars($e)?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required minlength="8">
        <label for="is_admin">Admin:</label>
        <input type="checkbox" name="is_admin" id="is_admin">
        <button type="submit" name="add_user">Add User</button>
    </form>
    <h2>Actions</h2>
    <p>As an admin you can <a href="reset_user.php">reset a user's password</a>, view <a href="activity_log.php">activity log</a>, or manage users.</p>
    <p>You can also <a href="api_fetch.php">import postcode data from an external API</a> (example).</p>
    <p>You can also <a href="api_schools.php">import sample schools data</a>.</p>
    <p>You can also <a href="api_carparks.php">import car parks data</a>.</p>
    <p>You can also <a href="api_sports.php">import sports centres data</a>.</p>
    <p>You can also <a href="refresh_data.php">refresh live data</a> from external sources.</p>
    <p><strong>Security:</strong> <a href="sql_injection_test.php">Run SQL injection tests</a></p>
    <p><strong>Debug:</strong> <a href="debug_uploads.php">View all uploaded data</a></p>
</body>
</html>