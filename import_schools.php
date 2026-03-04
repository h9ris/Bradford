<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial; color: #8B3A62;">
        <h2>Access Denied</h2>
        <p>Please <a href="index.php">login</a> first.</p>
    </div>');
}

// Only admin can import
if (!$_SESSION['is_admin']) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial; color: #8B3A62;">
        <h2>Admin Only</h2>
        <p>Only administrators can import school data.</p>
    </div>');
}

require_once 'includes/db.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (!is_uploaded_file($file)) {
        $message = "Error: Invalid file upload.";
    } else {
        try {
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle);
            
            // Map CSV columns
            $headerMap = array_flip($header);
            $imported = 0;
            $skipped = 0;
            
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                
                // Skip closed schools unless specified
                if ($data['SCHSTATUS'] !== 'Open') {
                    $skipped++;
                    continue;
                }
                
                // Check if already exists
                $check = $pdo->prepare("SELECT id FROM schools WHERE urn = ?");
                $check->execute([$data['URN']]);
                
                if ($check->rowCount() > 0) {
                    // Update existing
                    $stmt = $pdo->prepare("
                        UPDATE schools 
                        SET name = ?, street = ?, town = ?, postcode = ?, 
                            school_type = ?, religious_character = ?, gender = ?,
                            min_age = ?, max_age = ?, status = ?
                        WHERE urn = ?
                    ");
                    $stmt->execute([
                        $data['SCHNAME'] ?? '',
                        $data['STREET'] ?? '',
                        $data['TOWN'] ?? '',
                        $data['POSTCODE'] ?? '',
                        $data['SCHOOLTYPE'] ?? '',
                        $data['RELCHAR'] ?? '',
                        $data['GENDER'] ?? '',
                        $data['AGELOW'] ?? null,
                        $data['AGEHIGH'] ?? null,
                        $data['SCHSTATUS'] ?? 'Open',
                        $data['URN']
                    ]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare("
                        INSERT INTO schools 
                        (urn, name, street, town, postcode, school_type, religious_character, 
                         gender, min_age, max_age, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $data['URN'],
                        $data['SCHNAME'] ?? '',
                        $data['STREET'] ?? '',
                        $data['TOWN'] ?? '',
                        $data['POSTCODE'] ?? '',
                        $data['SCHOOLTYPE'] ?? '',
                        $data['RELCHAR'] ?? '',
                        $data['GENDER'] ?? '',
                        $data['AGELOW'] ?? null,
                        $data['AGEHIGH'] ?? null,
                        $data['SCHSTATUS'] ?? 'Open'
                    ]);
                }
                
                $imported++;
            }
            
            fclose($handle);
            $success = true;
            $message = "✓ Successfully imported $imported schools ($skipped closed schools skipped).";
            
            // Log activity
            log_activity($_SESSION['user_id'], 'import_schools', "Imported $imported school records");
            
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Schools - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .import-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .import-container h1 {
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
        .form-group input[type="file"],
        .form-group input[type="submit"] {
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
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #8B3A62;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="import-container">
        <h1>📚 Import Schools</h1>
        <p>Upload the Bradford school information CSV file to populate the schools database.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">📄 Select CSV File</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Import Schools">
            </div>
        </form>
        
        <div class="instructions">
            <strong>📋 Expected CSV Format:</strong><br>
            The CSV should have columns: URN, SCHNAME, STREET, TOWN, POSTCODE, SCHOOLTYPE, RELCHAR, GENDER, AGELOW, AGEHIGH, SCHSTATUS<br><br>
            <strong>✓ Features:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Automatically skips closed schools</li>
                <li>Updates existing schools by URN</li>
                <li>Preserves all school information</li>
            </ul>
        </div>
        
        <a href="admin.php" class="back-link">← Back to Admin</a>
    </div>
</body>
</html>
