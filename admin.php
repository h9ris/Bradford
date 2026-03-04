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
    <h2>Actions</h2>
    <p>As an admin you can:</p>
    <ul>
        <li><a href="manage_categories.php">Manage Asset Categories</a> - Create and manage asset categories (Schools, Parks, etc.)</li>
        <li><a href="import_schools.php">Import Schools</a> - Upload Bradford school information CSV</li>
        <li><a href="geocode_schools.php">🌍 Geocode Schools</a> - Add coordinates to schools using OpenStreetMap</li>
        <li><a href="import_performance.php">📊 Import Performance Data</a> - Upload KS4/KS5 performance metrics</li>
        <li><a href="schools.php">View Schools Directory</a> - Browse all imported schools</li>
        <li><a href="email_config.php">📧 Email Configuration</a> - Configure and test email sending</li>
        <li><a href="reset_user.php">Reset User Password</a> - Reset a user's password</li>
        <li><a href="activity_log.php">View Activity Log</a> - See all user activities</li>
        <li><a href="api_fetch.php">Import Data from API</a> - Fetch external data sources</li>
    </ul>
</body>
</html>