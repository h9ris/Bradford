<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

$message = '';

// Handle sharing request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $message = "CSRF validation failed.";
    } else {
        $upload_id = (int)$_POST['upload_id'];
        $shared_with_email = trim($_POST['shared_with_email']);
        $permissions = $_POST['permissions'] ?? 'view';

        // Verify the upload belongs to current user
        $stmt = $db->prepare('SELECT id FROM uploads WHERE id = ? AND user_id = ?');
        $stmt->execute([$upload_id, $user['id']]);
        if (!$stmt->fetch()) {
            $message = "You can only share your own data.";
        } else {
            // Find the user to share with
            $stmt = $db->prepare('SELECT id, name FROM users WHERE email = ?');
            $stmt->execute([$shared_with_email]);
            $target_user = $stmt->fetch();

            if (!$target_user) {
                $message = "User with email '$shared_with_email' not found.";
            } else {
                // Check if already shared
                $stmt = $db->prepare('SELECT id FROM shared_data WHERE upload_id = ? AND shared_with_user_id = ?');
                $stmt->execute([$upload_id, $target_user['id']]);
                if ($stmt->fetch()) {
                    $message = "This data is already shared with that user.";
                } else {
                    // Share the data
                    $stmt = $db->prepare('INSERT INTO shared_data (upload_id, shared_by_user_id, shared_with_user_id, permissions) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$upload_id, $user['id'], $target_user['id'], $permissions]);
                    log_activity($user['id'], 'data_share', "Shared upload ID $upload_id with user {$target_user['id']}");
                    $message = "Data shared successfully with {$target_user['name']}.";
                }
            }
        }
    }
}

// Handle unsharing request
if (isset($_GET['unshare'])) {
    $share_id = (int)$_GET['unshare'];
    $stmt = $db->prepare('DELETE FROM shared_data WHERE id = ? AND shared_by_user_id = ?');
    $stmt->execute([$share_id, $user['id']]);
    if ($stmt->rowCount() > 0) {
        log_activity($user['id'], 'data_unshare', "Unshared data ID $share_id");
        $message = "Data unshared successfully.";
    }
}

// Get user's uploads
$stmt = $db->prepare('SELECT id, filename, created_at FROM uploads WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$user_uploads = $stmt->fetchAll();

// Get shared data (data shared by this user)
$stmt = $db->prepare('
    SELECT s.id, s.permissions, s.created_at, u.filename, usr.name as shared_with_name, usr.email as shared_with_email
    FROM shared_data s
    JOIN uploads u ON s.upload_id = u.id
    JOIN users usr ON s.shared_with_user_id = usr.id
    WHERE s.shared_by_user_id = ?
    ORDER BY s.created_at DESC
');
$stmt->execute([$user['id']]);
$shared_by_user = $stmt->fetchAll();

// Get data shared with this user
$stmt = $db->prepare('
    SELECT s.permissions, u.id as upload_id, u.filename, u.created_at, usr.name as shared_by_name
    FROM shared_data s
    JOIN uploads u ON s.upload_id = u.id
    JOIN users usr ON s.shared_by_user_id = usr.id
    WHERE s.shared_with_user_id = ?
    ORDER BY s.created_at DESC
');
$stmt->execute([$user['id']]);
$shared_with_user = $stmt->fetchAll();

// Get all users for sharing dropdown
$stmt = $db->prepare('SELECT id, name, email FROM users WHERE id != ? ORDER BY name');
$stmt->execute([$user['id']]);
$all_users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Sharing - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Data Sharing</h1>
    <p><a href="portal.php">← Back to Portal</a></p>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <h2>Share Your Data</h2>
    <p>Share your uploaded data with other users. They will be able to view it on their maps.</p>

    <?php if (!empty($user_uploads)): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <label for="upload_id">Select data to share:</label>
            <select name="upload_id" id="upload_id" required>
                <option value="">Choose data...</option>
                <?php foreach ($user_uploads as $upload): ?>
                    <option value="<?php echo $upload['id']; ?>">
                        <?php echo htmlspecialchars($upload['filename']); ?> (uploaded <?php echo $upload['created_at']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="shared_with_email">Share with user:</label>
            <select name="shared_with_email" id="shared_with_email" required>
                <option value="">Choose user...</option>
                <?php foreach ($all_users as $u): ?>
                    <option value="<?php echo htmlspecialchars($u['email']); ?>">
                        <?php echo htmlspecialchars($u['name'] . ' (' . $u['email'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="permissions">Permissions:</label>
            <select name="permissions" id="permissions">
                <option value="view">View only</option>
                <option value="edit">View and edit</option>
            </select>

            <button type="submit">Share Data</button>
        </form>
    <?php else: ?>
        <p>You haven't uploaded any data yet. <a href="portal.php">Upload some data</a> first.</p>
    <?php endif; ?>

    <h2>Data You've Shared</h2>
    <?php if (!empty($shared_by_user)): ?>
        <table>
            <thead>
                <tr>
                    <th>Data File</th>
                    <th>Shared With</th>
                    <th>Permissions</th>
                    <th>Shared Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shared_by_user as $share): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($share['filename']); ?></td>
                        <td><?php echo htmlspecialchars($share['shared_with_name'] . ' (' . $share['shared_with_email'] . ')'); ?></td>
                        <td><?php echo htmlspecialchars($share['permissions']); ?></td>
                        <td><?php echo $share['created_at']; ?></td>
                        <td><a href="?unshare=<?php echo $share['id']; ?>" onclick="return confirm('Stop sharing this data?')">Unshare</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You haven't shared any data yet.</p>
    <?php endif; ?>

    <h2>Data Shared With You</h2>
    <?php if (!empty($shared_with_user)): ?>
        <table>
            <thead>
                <tr>
                    <th>Data File</th>
                    <th>Shared By</th>
                    <th>Permissions</th>
                    <th>Shared Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shared_with_user as $share): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($share['filename']); ?></td>
                        <td><?php echo htmlspecialchars($share['shared_by_name']); ?></td>
                        <td><?php echo htmlspecialchars($share['permissions']); ?></td>
                        <td><?php echo $share['created_at']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No data has been shared with you yet.</p>
    <?php endif; ?>
</body>
</html>