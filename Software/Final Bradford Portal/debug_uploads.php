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
$uploads = $db->query('SELECT id, filename, LENGTH(data) as size, created_at FROM uploads ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug - Uploaded Data</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Debug: Uploaded Data</h1>
    <p><a href="portal.php">Back to Portal</a> | <a href="admin.php">Admin Panel</a></p>

    <h2>All Uploads (<?php echo count($uploads); ?> total)</h2>
    <table border="1" style="width: 100%; border-collapse: collapse;">
        <tr style="background: #005ea5; color: white;">
            <th>ID</th>
            <th>Filename</th>
            <th>Size (bytes)</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($uploads as $upload): ?>
        <tr>
            <td><?php echo $upload['id']; ?></td>
            <td><?php echo htmlspecialchars($upload['filename']); ?></td>
            <td><?php echo $upload['size']; ?></td>
            <td><?php echo $upload['created_at']; ?></td>
            <td>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $upload['id']; ?>">
                    <button type="submit" onclick="return confirm('Delete this upload?')">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $stmt = $db->prepare('DELETE FROM uploads WHERE id = ?');
        $stmt->execute([$_POST['delete_id']]);
        header('Location: debug_uploads.php');
        exit;
    }
    ?>
</body>
</html>