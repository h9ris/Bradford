<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // manual entry
    if (isset($_POST['manual'])) {
        $lat = $_POST['lat'] ?? '';
        $lng = $_POST['lng'] ?? '';
        $nameVal = $_POST['name'] ?? '';
        if (is_numeric($lat) && is_numeric($lng)) {
            $csv = $lat . ',' . $lng . ',' . $nameVal;
            $db = get_db();
            $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], 'manual', $csv]);
            log_activity($user['id'], 'manual_add', $csv);
            header('Location: portal.php?added=1');
            exit;
        }
    }
    // file upload
    if (isset($_FILES['datafile'])) {
        $upload = $_FILES['datafile'];
        if ($upload['error'] === UPLOAD_ERR_OK) {
            $tmp = $upload['tmp_name'];
            $name = basename($upload['name']);
            $contents = file_get_contents($tmp);
            // simplistic storage: store raw contents in uploads table
            $db = get_db();
            $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $name, $contents]);
            log_activity($user['id'], 'upload', $name);
            header('Location: portal.php?uploaded=1');
            exit;
        }
    }
}
header('Location: portal.php');
exit;
