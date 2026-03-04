<?php
// example script to pull data from an external API and store it in uploads
// you could trigger this from a cron job or an admin interface
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
if (!$user['is_admin']) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// external API endpoint that returns JSON array of objects with lat,lng,name
$endpoint = 'https://example.com/data';
$data = file_get_contents($endpoint);
if ($data !== false) {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], 'api_' . time() . '.json', $data]);
    log_activity($user['id'], 'api_import', $endpoint);
    echo "Fetched and stored data";
} else {
    echo "Failed to fetch";
}
