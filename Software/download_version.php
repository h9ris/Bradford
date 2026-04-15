<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

$upload_id = (int)($_GET['upload_id'] ?? 0);
$version = (int)($_GET['version'] ?? 0);

if (!$upload_id || !$version) {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

// Verify ownership
$stmt = $db->prepare('SELECT id FROM uploads WHERE id = ? AND user_id = ?');
$stmt->execute([$upload_id, $user['id']]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// Get the version data
$stmt = $db->prepare('
    SELECT v.filename, v.data
    FROM upload_versions v
    WHERE v.upload_id = ? AND v.version_number = ? AND v.user_id = ?
');
$stmt->execute([$upload_id, $version, $user['id']]);
$version_data = $stmt->fetch();

if (!$version_data) {
    http_response_code(404);
    echo "Version not found";
    exit;
}

// Log the download
log_activity($user['id'], 'version_download', "Downloaded version $version of upload $upload_id");

// Send the file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $version_data['filename'] . '"');
header('Content-Length: ' . strlen($version_data['data']));
echo $version_data['data'];
exit;
?>