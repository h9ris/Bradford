<?php
// Script to refresh live data from APIs
// Can be called manually or via cron job
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
if (!$user['is_admin']) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

$db = get_db();
$message = "";

// Function to refresh car park data
function refresh_carparks($db, $user_id) {
    $filename = __DIR__ . '/CarParkCurrent.csv';
    if (!file_exists($filename)) {
        return "Error: CarParkCurrent.csv not found";
    }

    // Get current car park data
    $stmt = $db->prepare('SELECT id FROM uploads WHERE filename = ? AND user_id = ?');
    $stmt->execute(['carparks.csv', $user_id]);
    $existing = $stmt->fetch();

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

    if ($existing) {
        // Get current version number
        $stmt = $db->prepare('SELECT MAX(version_number) as max_version FROM upload_versions WHERE upload_id = ?');
        $stmt->execute([$existing['id']]);
        $version_row = $stmt->fetch();
        $next_version = ($version_row['max_version'] ?? 0) + 1;
        
        // Update existing record
        $stmt = $db->prepare('UPDATE uploads SET data = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$data, $existing['id']]);
        
        // Create new version
        $stmt = $db->prepare('INSERT INTO upload_versions (upload_id, user_id, version_number, filename, data, change_description) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$existing['id'], $user_id, $next_version, 'carparks.csv', $data, 'Refreshed car park data']);
        
        log_activity($user_id, 'data_refresh', 'car parks updated');
        return "Car parks data updated successfully.";
    } else {
        // Insert new record
        $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, 'carparks.csv', $data]);
        log_activity($user_id, 'data_refresh', 'car parks imported');
        return "Car parks data imported successfully.";
    }
}

// Function to refresh postcode data (example)
function refresh_postcodes($db, $user_id) {
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
            $data = json_encode($filtered);
            // Check if exists
            $stmt = $db->prepare('SELECT id FROM uploads WHERE filename LIKE ? AND user_id = ?');
            $stmt->execute(['api_postcodes_%', $user_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare('UPDATE uploads SET data = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$data, $existing['id']]);
                
                // Get current version number
                $stmt = $db->prepare('SELECT MAX(version_number) as max_version FROM upload_versions WHERE upload_id = ?');
                $stmt->execute([$existing['id']]);
                $version_row = $stmt->fetch();
                $next_version = ($version_row['max_version'] ?? 0) + 1;
                
                // Create new version
                $stmt = $db->prepare('INSERT INTO upload_versions (upload_id, user_id, version_number, filename, data, change_description) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$existing['id'], $user_id, $next_version, 'api_postcodes_' . time() . '.json', $data, 'Refreshed postcode data from API']);
                
            } else {
                $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, 'api_postcodes_' . time() . '.json', $data]);
                $upload_id = $db->lastInsertId();
                
                // Create version 1
                $stmt = $db->prepare('INSERT INTO upload_versions (upload_id, user_id, version_number, filename, data, change_description) VALUES (?, ?, 1, ?, ?, ?)');
                $stmt->execute([$upload_id, $user_id, 'api_postcodes_' . time() . '.json', $data, 'Initial postcode data from API']);
            }
            log_activity($user_id, 'data_refresh', 'postcodes updated from API');
            return "Fetched " . count($filtered) . " postcodes and updated data.";
        }
        return "No usable postcode data returned.";
    } else {
        return "Failed to fetch from $endpoint";
    }
}

// Handle refresh requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['refresh_carparks'])) {
        $message = refresh_carparks($db, $user['id']);
    } elseif (isset($_POST['refresh_postcodes'])) {
        $message = refresh_postcodes($db, $user['id']);
    } elseif (isset($_POST['refresh_all'])) {
        $msg1 = refresh_carparks($db, $user['id']);
        $msg2 = refresh_postcodes($db, $user['id']);
        $message = $msg1 . "<br>" . $msg2;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Refresh - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Data Refresh</h1>
    <p><a href="admin.php">← Back to Admin</a></p>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <p>This tool allows you to refresh live data from external sources.</p>

    <form method="post">
        <h2>Refresh Options</h2>
        <button type="submit" name="refresh_carparks">Refresh Car Park Data</button>
        <p>Updates car park availability from the latest CSV data.</p>

        <button type="submit" name="refresh_postcodes">Refresh Postcode Data</button>
        <p>Fetches fresh postcode data from the postcodes.io API.</p>

        <button type="submit" name="refresh_all">Refresh All Data</button>
        <p>Updates all refreshable data sources.</p>
    </form>

    <h2>Scheduled Refresh</h2>
    <p>To set up automatic refreshing, you can:</p>
    <ul>
        <li>Add a cron job to call this script periodically</li>
        <li>Example cron: <code>0 */6 * * * curl -X POST http://your-domain.com/refresh_data.php -d "refresh_all=1"</code></li>
        <li>Or use Windows Task Scheduler to run the script every few hours</li>
    </ul>
</body>
</html>