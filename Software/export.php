<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

$type = $_GET['type'] ?? 'json';
$stmt = $db->prepare('SELECT data FROM uploads WHERE user_id = ?');
$stmt->execute([$user['id']]);
$all = [];
while ($row = $stmt->fetch()) {
    $all[] = json_decode($row['data'], true);
}
if ($type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="uploads.csv"');
    $out = fopen('php://output', 'w');
    foreach ($all as $arr) {
        if (is_array($arr)) {
            foreach ($arr as $r) {
                if (isset($r['lat'],$r['lng'])) {
                    fputcsv($out, [$r['lat'],$r['lng'],$r['name'] ?? '']);
                }
            }
        }
    }
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode($all);
    exit;
}
