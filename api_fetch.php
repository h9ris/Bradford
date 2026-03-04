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

// examples: fetch a CSV or JSON dataset and convert into assets
// call like: api_fetch.php?url=<url>&category=Schools
$url = $_GET['url'] ?? '';
$categoryName = $_GET['category'] ?? '';
if (!$url || !$categoryName) {
    echo "Usage: api_fetch.php?url=<file URL>&category=<CategoryName>";
    exit;
}

// attempt to download file
$data = @file_get_contents($url);
if ($data === false) {
    echo "Failed to fetch $url";
    exit;
}

// determine format by extension or content
$isJson = false;
$assets = [];
if (preg_match('/\.json$/i', parse_url($url, PHP_URL_PATH)) || strpos(trim($data), '{') === 0 || strpos(trim($data), '[') === 0) {
    $isJson = true;
}
if ($isJson) {
    $parsed = json_decode($data, true);
    if (is_array($parsed)) {
        foreach ($parsed as $item) {
            if (isset($item['lat'], $item['lng'])) {
                $assets[] = $item;
            }
        }
    }
} else {
    // treat as CSV
    $lines = explode("\n", trim($data));
    $header = null;
    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        if (!$header) {
            $header = $cols;
            continue;
        }
        $row = array_combine($header, $cols);
        if ($row && isset($row['lat']) && isset($row['lng'])) {
            $assets[] = $row;
        } elseif (count($cols) >= 2 && is_numeric($cols[0]) && is_numeric($cols[1])) {
            $assets[] = ['lat' => $cols[0], 'lng' => $cols[1], 'name' => $cols[2] ?? ''];
        }
    }
}

// find or create category
$db = get_db();
$stmt = $db->prepare('SELECT id FROM categories WHERE name = ?');
$stmt->execute([$categoryName]);
$cat = $stmt->fetch();
if (!$cat) {
    $db->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$categoryName]);
    $catId = $db->lastInsertId();
} else {
    $catId = $cat['id'];
}

$insert = $db->prepare('INSERT INTO assets (name, latitude, longitude, category_id, description, created_by) VALUES (?, ?, ?, ?, ?, ?)');
$added = 0;
foreach ($assets as $a) {
    $lat = isset($a['lat']) ? (float)$a['lat'] : 0;
    $lng = isset($a['lng']) ? (float)$a['lng'] : 0;
    $name = $a['name'] ?? ($a['Name'] ?? '');
    $desc = $a['description'] ?? '';
    if ($lat && $lng) {
        $insert->execute([$name, $lat, $lng, $catId, $desc, $user['id']]);
        $added++;
    }
}
log_activity($user['id'], 'api_import', $url . '->' . $categoryName);
echo "Imported $added assets into category $categoryName.";
