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
$logs = $db->query('SELECT a.*, u.email FROM activity_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 100')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity log</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Activity log</h1>
    <p><a href="admin.php">Back to admin</a></p>
    <table>
        <tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><?=htmlspecialchars($l['created_at'])?></td>
                <td><?=htmlspecialchars($l['email'])?></td>
                <td><?=htmlspecialchars($l['action'])?></td>
                <td><?=htmlspecialchars($l['details'])?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>