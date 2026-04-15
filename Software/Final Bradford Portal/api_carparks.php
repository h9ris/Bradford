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

$filename = __DIR__ . '/CarParkCurrent.csv';
if (!file_exists($filename)) {
    echo "Error: CarParkCurrent.csv not found in " . __DIR__;
    exit;
}
$csv = file_get_contents($filename);
$lines = explode("\n", trim($csv));
$header = str_getcsv(array_shift($lines)); // remove header

$carparks = [];
foreach ($lines as $line) {
    if (empty($line)) continue;
    $cols = str_getcsv($line);
    if (count($cols) === count($header)) {
        $carparks[] = array_combine($header, $cols);
    }
}

$data = json_encode($carparks);
$stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], 'carparks.csv', $data]);

log_activity($user['id'], 'api_fetch', 'car parks imported');
echo "Car parks data imported successfully.";
?>