<?php
session_start();

// Require admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial;">
        <h2>Admin Only</h2>
        <p>Only administrators can geocode schools.</p>
    </div>');
}

require_once 'includes/db.php';

// Function to geocode address using OpenStreetMap Nominatim (free)
function geocodeAddress($street, $town, $postcode) {
    $address = trim("$street, $town, $postcode, UK");
    $address = urlencode($address);
    
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=$address&limit=1";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'BradfordPortal/1.0'
        ]
    ]);
    
    try {
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            return [
                'lat' => floatval($data[0]['lat']),
                'lng' => floatval($data[0]['lon']),
                'display_name' => $data[0]['display_name'] ?? ''
            ];
        }
    } catch (Exception $e) {
        error_log("Geocoding error: " . $e->getMessage());
    }
    
    return null;
}

// Bradford center as fallback
$bradfordCenter = ['lat' => 53.7949, 'lng' => -1.7635];

$message = '';
$success = false;
$stats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (!is_uploaded_file($file)) {
        $message = "Error: Invalid file upload.";
    } else {
        try {
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle);
            
            $geocoded = 0;
            $fallback = 0;
            $failed = 0;
            $closed = 0;
            
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                $urn = $data['URN'] ?? null;
                
                if (!$urn) continue;
                
                // Skip closed schools
                if ($data['SCHSTATUS'] !== 'Open') {
                    $closed++;
                    continue;
                }
                
                // Try geocoding
                $coords = geocodeAddress(
                    $data['STREET'] ?? '',
                    $data['TOWN'] ?? '',
                    $data['POSTCODE'] ?? ''
                );
                
                if (!$coords) {
                    $coords = $bradfordCenter;
                    $fallback++;
                }
                
                // Update database
                $stmt = $pdo->prepare("
                    UPDATE schools 
                    SET latitude = ?, longitude = ?
                    WHERE urn = ?
                ");
                
                if ($stmt->execute([$coords['lat'], $coords['lng'], $urn])) {
                    if ($coords !== $bradfordCenter) {
                        $geocoded++;
                    } else {
                        $fallback++;
                    }
                } else {
                    $failed++;
                }
                
                // Respectful rate limiting
                usleep(300000); // 0.3 second delay
            }
            
            fclose($handle);
            
            $success = true;
            $stats = [
                'geocoded' => $geocoded,
                'fallback' => $fallback,
                'failed' => $failed,
                'closed' => $closed,
                'total' => $geocoded + $fallback + $failed
            ];
            $message = "✓ Geocoding complete! {$stats['geocoded']} with coordinates, {$stats['fallback']} using fallback.";
            
            log_activity($_SESSION['user_id'], 'geocode_schools', "Geocoded {$stats['total']} schools");
            
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geocode Schools - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container h1 {
            color: #8B3A62;
            margin-bottom: 10px;
        }
        .form-group {
            margin: 20px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input[type="submit"] {
            background: #8B3A62;
            color: white;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }
        .form-group input[type="submit"]:hover {
            background: #6B2A4A;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .stats {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #8B3A62;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌍 Geocode Schools</h1>
        <p>Add latitude/longitude coordinates to schools using OpenStreetMap. Requires schools to be imported first.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if (!empty($stats)): ?>
                <div class="stats">
                    <strong>📊 Statistics:</strong><br>
                    ✓ Geocoded: <?php echo $stats['geocoded']; ?><br>
                    📍 Fallback (Bradford center): <?php echo $stats['fallback']; ?><br>
                    ✗ Failed: <?php echo $stats['failed']; ?><br>
                    ⊘ Closed schools skipped: <?php echo $stats['closed']; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">📄 Select CSV File (380_school_information.csv)</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="🌍 Start Geocoding">
            </div>
        </form>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 20px; font-size: 13px; color: #666;">
            <strong>ℹ️ How it works:</strong><br>
            1. Upload your school CSV file<br>
            2. Each school address is sent to OpenStreetMap's free Nominatim service<br>
            3. Coordinates are automatically saved to the database<br>
            4. If geocoding fails, Bradford city center is used as fallback<br>
            <br>
            <strong>⚠️ Note:</strong> Process takes ~30 seconds. Do not refresh the page!
        </div>
        
        <a href="admin.php" class="back-link">← Back to Admin</a>
    </div>
</body>
</html>
