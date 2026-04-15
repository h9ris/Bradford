<?php
// Quick import script for carpark data
require_once __DIR__ . '/includes/db.php';

$filename = __DIR__ . '/CarParkCurrent.csv';
if (!file_exists($filename)) {
    die("CarParkCurrent.csv not found");
}

$csv = file_get_contents($filename);
$lines = explode("\n", trim($csv));
$header = str_getcsv(array_shift($lines));

$carparks = [];
foreach ($lines as $line) {
    if (empty($line)) continue;
    $cols = str_getcsv($line);
    if (count($cols) === count($header)) {
        $carparks[] = array_combine($header, $cols);
    }
}

$data = json_encode($carparks);
$db = get_db();

// Check if already exists
$stmt = $db->prepare('SELECT id FROM uploads WHERE filename = ?');
$stmt->execute(['carparks.csv']);
if (!$stmt->fetch()) {
    $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
    $stmt->execute([1, 'carparks.csv', $data]); // user_id 1 = admin
    echo "Car parks imported successfully!";
} else {
    echo "Car parks already imported.";
}
?>