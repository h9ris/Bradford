<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

$message = '';
$success_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $message = "CSRF validation failed.";
    } else {
        $data_type = $_POST['data_type'];
        $raw_data = trim($_POST['raw_data']);
        $filename = trim($_POST['filename']) ?: 'bulk_import_' . date('Y-m-d_H-i-s');

        if (empty($raw_data)) {
            $message = "Please enter some data to import.";
        } else {
            try {
                $parsed_data = [];
                $is_valid = false;

                if ($data_type === 'csv') {
                    // Parse CSV
                    $lines = explode("\n", $raw_data);
                    $header = str_getcsv(array_shift($lines));
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        $cols = str_getcsv($line);
                        if (count($cols) >= 2 && is_numeric($cols[0]) && is_numeric($cols[1])) {
                            // Assume lat,lng,name format
                            $parsed_data[] = [
                                'lat' => (float)$cols[0],
                                'lng' => (float)$cols[1],
                                'name' => $cols[2] ?? ''
                            ];
                            $is_valid = true;
                        }
                    }
                } elseif ($data_type === 'json') {
                    // Parse JSON
                    $json_data = json_decode($raw_data, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                        foreach ($json_data as $item) {
                            if (isset($item['lat'], $item['lng'])) {
                                $parsed_data[] = $item;
                                $is_valid = true;
                            }
                        }
                    }
                }

                if (!$is_valid || empty($parsed_data)) {
                    $message = "No valid location data found. Please check your data format.";
                } else {
                    // Save the data
                    $data_json = json_encode($parsed_data);
                    $stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
                    $stmt->execute([$user['id'], $filename . '.' . $data_type, $data_json]);
                    $upload_id = $db->lastInsertId();
                    
                    // Create version 1
                    $stmt = $db->prepare('INSERT INTO upload_versions (upload_id, user_id, version_number, filename, data, change_description) VALUES (?, ?, 1, ?, ?, ?)');
                    $stmt->execute([$upload_id, $user['id'], $filename . '.' . $data_type, $data_json, 'Bulk import from web interface']);
                    
                    log_activity($user['id'], 'bulk_import', "Imported " . count($parsed_data) . " locations");
                    $message = "Successfully imported " . count($parsed_data) . " locations!";
                    $success_count = count($parsed_data);
                }
            } catch (Exception $e) {
                $message = "Import failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bulk Data Import - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .data-input {
            width: 100%;
            height: 300px;
            font-family: monospace;
            font-size: 12px;
        }
        .format-examples {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>Bulk Data Import</h1>
    <p><a href="portal.php">← Back to Portal</a></p>

    <?php if ($message): ?>
        <div class="message <?php echo $success_count > 0 ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <p>Import location data directly by pasting CSV or JSON data. This is useful for copying data from spreadsheets, databases, or other sources.</p>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <label for="filename">Filename (optional):</label>
        <input type="text" name="filename" id="filename" placeholder="my_locations">
        
        <label for="data_type">Data Format:</label>
        <select name="data_type" id="data_type" required>
            <option value="csv">CSV (lat,lng,name)</option>
            <option value="json">JSON Array</option>
        </select>
        
        <label for="raw_data">Data:</label>
        <textarea name="raw_data" id="raw_data" class="data-input" required placeholder="Paste your data here..."></textarea>
        
        <button type="submit">Import Data</button>
    </form>

    <h2>Format Examples</h2>
    
    <h3>CSV Format</h3>
    <div class="format-examples">
        <strong>Expected format:</strong> latitude,longitude,name<br>
        <strong>Example:</strong><br>
        <pre>53.796, -1.759, City Centre
53.800, -1.745, University
53.790, -1.765, Train Station</pre>
    </div>
    
    <h3>JSON Format</h3>
    <div class="format-examples">
        <strong>Expected format:</strong> Array of objects with lat/lng properties<br>
        <strong>Example:</strong><br>
        <pre>[
    {"lat": 53.796, "lng": -1.759, "name": "City Centre"},
    {"lat": 53.800, "lng": -1.745, "name": "University"},
    {"lat": 53.790, "lng": -1.765, "name": "Train Station"}
]</pre>
    </div>

    <h2>Tips</h2>
    <ul>
        <li>CSV data should have latitude and longitude as the first two columns</li>
        <li>JSON data should be an array of objects with "lat" and "lng" properties</li>
        <li>Extra columns/properties are preserved and can be used for custom markers</li>
        <li>Data is validated before import - invalid rows are skipped</li>
        <li>Imported data appears immediately on your map</li>
    </ul>
</body>
</html>