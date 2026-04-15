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

// sample external API: postcodes.io returns UK postcode data
$endpoint = 'https://api.postcodes.io/postcodes?limit=50';
$response = file_get_contents($endpoint);
if ($response !== false) {
    $json = json_decode($response, true);
    $filtered = [];
    if (isset($json['result']) && is_array($json['result'])) {
        foreach ($json['result'] as $item) {
            if (isset($item['latitude'], $item['longitude'])) {
                $filtered[] = [
                    'lat' => $item['latitude'],
                    'lng' => $item['longitude'],
                    'name' => $item['postcode']
                ];
            }
        }
    }
    if (!empty($filtered)) {
        $db = get_db();
        $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], 'api_postcodes_' . time() . '.json', json_encode($filtered)]);
        log_activity($user['id'], 'api_import', $endpoint);
        echo "Fetched " . count($filtered) . " points and stored in uploads.";
        exit;
    }
    echo "No usable data returned.";
} else {
    echo "Failed to fetch from $endpoint";
}
