<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

// Get upload history with versions
$stmt = $db->prepare('
    SELECT 
        u.id as upload_id,
        u.filename,
        u.created_at as upload_created,
        u.updated_at as upload_updated,
        COUNT(v.id) as version_count,
        MAX(v.created_at) as last_version_date
    FROM uploads u
    LEFT JOIN upload_versions v ON u.id = v.upload_id
    WHERE u.user_id = ?
    GROUP BY u.id, u.filename, u.created_at, u.updated_at
    ORDER BY u.created_at DESC
');
$stmt->execute([$user['id']]);
$user_uploads = $stmt->fetchAll();

// Get detailed version history if an upload is selected
$selected_upload = null;
$versions = [];
if (isset($_GET['upload_id'])) {
    $upload_id = (int)$_GET['upload_id'];
    
    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM uploads WHERE id = ? AND user_id = ?');
    $stmt->execute([$upload_id, $user['id']]);
    if ($stmt->fetch()) {
        $selected_upload = $upload_id;
        
        $stmt = $db->prepare('
            SELECT v.version_number, v.filename, v.change_description, v.created_at, 
                   LENGTH(v.data) as data_size
            FROM upload_versions v
            WHERE v.upload_id = ?
            ORDER BY v.version_number DESC
        ');
        $stmt->execute([$upload_id]);
        $versions = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data History - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Data History & Audit Trail</h1>
    <p><a href="portal.php">← Back to Portal</a></p>

    <p>This page shows the history of all your data uploads, including versions and changes over time.</p>

    <h2>Your Uploads</h2>
    <?php if (!empty($user_uploads)): ?>
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Created</th>
                    <th>Last Updated</th>
                    <th>Versions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_uploads as $upload): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($upload['filename']); ?></td>
                        <td><?php echo $upload['upload_created']; ?></td>
                        <td><?php echo $upload['upload_updated'] ?: 'Never'; ?></td>
                        <td><?php echo $upload['version_count']; ?></td>
                        <td>
                            <a href="?upload_id=<?php echo $upload['upload_id']; ?>">View History</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You haven't uploaded any data yet.</p>
    <?php endif; ?>

    <?php if ($selected_upload && !empty($versions)): ?>
        <h2>Version History</h2>
        <p><a href="data_history.php">← Back to all uploads</a></p>
        
        <table>
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Filename</th>
                    <th>Description</th>
                    <th>Data Size</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $version): ?>
                    <tr>
                        <td><?php echo $version['version_number']; ?></td>
                        <td><?php echo htmlspecialchars($version['filename']); ?></td>
                        <td><?php echo htmlspecialchars($version['change_description'] ?: 'No description'); ?></td>
                        <td><?php echo number_format($version['data_size']); ?> bytes</td>
                        <td><?php echo $version['created_at']; ?></td>
                        <td>
                            <a href="download_version.php?upload_id=<?php echo $selected_upload; ?>&version=<?php echo $version['version_number']; ?>">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Audit Information</h2>
    <p>
        <strong>What is tracked:</strong><br>
        • All data uploads and modifications<br>
        • Version history with timestamps<br>
        • Change descriptions<br>
        • Data sharing activities (in activity log)<br>
        • API data refreshes<br>
    </p>
    
    <p>
        <strong>Retention:</strong> Version history is kept indefinitely. You can view but not delete historical versions.
    </p>
</body>
</html>