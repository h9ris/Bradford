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

// Sample schools in Bradford (for demo)
$schools = [
    ['name' => 'Bradford Grammar School', 'postcode' => 'BD9 4JP', 'lat' => 53.806, 'lng' => -1.781],
    ['name' => 'Carlton Bolling College', 'postcode' => 'BD3 7DU', 'lat' => 53.796, 'lng' => -1.726],
    ['name' => 'Feversham Academy', 'postcode' => 'BD3 9QR', 'lat' => 53.805, 'lng' => -1.726],
    // Add more as needed
];

foreach ($schools as $school) {
    $data = json_encode($school);
    $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], 'school_' . $school['name'], $data]);
}

log_activity($user['id'], 'api_fetch', 'schools imported');
echo "Schools data imported successfully.";
?>