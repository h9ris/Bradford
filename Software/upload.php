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
            // Store as JSON array for portal.php map display
            $point = json_encode([
                [
                    'lat' => floatval($lat),
                    'lng' => floatval($lng),
                    'name' => $nameVal ?: 'Unnamed Point'
                ]
            ]);
            $db = get_db();
            $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], 'manual', $point]);
            $upload_id = $db->lastInsertId();
            
            // Create version 1 (optional versioning)
            try {
                $stmt = $db->prepare('INSERT INTO upload_versions (upload_id, user_id, version_number, filename, data, change_description) VALUES (?, ?, 1, ?, ?, ?)');
                $stmt->execute([$upload_id, $user['id'], 'manual', $point, 'Manual entry']);
            } catch (PDOException $e) {
                // Table may not exist yet - versioning is optional
            }
            
            log_activity($user['id'], 'manual_add', $nameVal);
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
            
            // Limit file size to prevent database issues (10MB)
            if (strlen($contents) > 10 * 1024 * 1024) {
                header('Location: portal.php?error=File%20too%20large');
                exit;
            }
            
            try {
                $db = get_db();
                $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
                $stmt->execute([$user['id'], $name, $contents]);
                $upload_id = $db->lastInsertId();
                
                // Create version 1 (optional versioning)
                try {
                    $stmt = $db->prepare('INSERT INTO upload_versions (upload_id, user_id, version_number, filename, data, change_description) VALUES (?, ?, 1, ?, ?, ?)');
                    $stmt->execute([$upload_id, $user['id'], $name, $contents, 'Initial upload']);
                } catch (PDOException $e) {
                    // Table may not exist yet - versioning is optional
                }
                
                log_activity($user['id'], 'upload', $name);
                header('Location: portal.php?uploaded=1');
                exit;
            } catch (PDOException $e) {
                header('Location: portal.php?error=Upload%20failed:%20' . urlencode($e->getMessage()));
                exit;
            }
        } else {
            $error_msg = 'Upload error: ' . $upload['error'];
            header('Location: portal.php?error=' . urlencode($error_msg));
            exit;
        }
    }
}
header('Location: portal.php');
exit;
