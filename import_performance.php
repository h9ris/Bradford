<?php
session_start();

// Require admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial;">
        <h2>Admin Only</h2>
        <p>Only administrators can import performance data.</p>
    </div>');
}

require_once 'includes/db.php';

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
            
            $imported = 0;
            $skipped = 0;
            $failed = 0;
            
            // Determine academic year from filename or default to 2024-2025
            $filename = $_FILES['csv_file']['name'];
            $academic_year = '2024-2025';
            if (preg_match('/ks(\d)/', $filename, $m)) {
                $academic_year = '2024-2025 (KS' . $m[1] . ')';
            }
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) continue;
                
                $data = array_combine($header, $row);
                $urn = trim($data['URN'] ?? '');
                
                if (!$urn || $urn === 'URN') continue;
                
                // Check if school exists
                $check = $pdo->prepare("SELECT id FROM schools WHERE urn = ?");
                $check->execute([$urn]);
                $school = $check->fetch();
                
                if (!$school) {
                    $skipped++;
                    continue;
                }
                
                // Extract performance metrics from KS4 data
                $attainment_8 = null;
                $progress_8 = null;
                
                // Common column names for attainment and progress
                if (isset($data['ATTAIN8']) || isset($data['Attainment 8'])) {
                    $val = $data['ATTAIN8'] ?? $data['Attainment 8'] ?? null;
                    $attainment_8 = is_numeric($val) ? floatval($val) : null;
                }
                
                if (isset($data['PROG8']) || isset($data['Progress 8'])) {
                    $val = $data['PROG8'] ?? $data['Progress 8'] ?? null;
                    $progress_8 = is_numeric($val) ? floatval($val) : null;
                }
                
                // Insert or update performance record
                $stmt = $pdo->prepare("
                    INSERT INTO school_performance 
                    (school_id, urn, academic_year, attainment_8, progress_8)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    attainment_8 = VALUES(attainment_8),
                    progress_8 = VALUES(progress_8)
                ");
                
                if ($stmt->execute([
                    $school['id'],
                    $urn,
                    $academic_year,
                    $attainment_8,
                    $progress_8
                ])) {
                    $imported++;
                } else {
                    $failed++;
                }
            }
            
            fclose($handle);
            
            $success = true;
            $stats = [
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'total' => $imported + $skipped
            ];
            $message = "✓ Successfully imported {$stats['imported']} performance records.";
            
            log_activity($_SESSION['user_id'], 'import_performance', "Imported {$stats['imported']} performance records");
            
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
    <title>Import Performance Data - Bradford Portal</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Import School Performance Data</h1>
        <p>Import KS4 (GCSE) performance metrics from CSV files. Links data to schools by URN.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if (!empty($stats)): ?>
                <div class="stats">
                    <strong>📊 Statistics:</strong><br>
                    ✓ Imported: <?php echo $stats['imported']; ?><br>
                    ⊘ Skipped (school not found): <?php echo $stats['skipped']; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">📄 Select CSV File (KS4 Data)</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="📥 Import Performance Data">
            </div>
        </form>
        
        <div class="instructions">
            <strong>📋 Expected CSV Format:</strong><br>
            Must include: URN, and performance columns (Attainment 8, Progress 8, etc.)<br><br>
            <strong>✓ Supported Formats:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>KS4 Revised / Provisional</li>
                <li>KS4 Pupil Destinations</li>
                <li>Any CSV with URN column</li>
            </ul>
            <strong>⚠️ Note:</strong> Schools must be imported first via "Import Schools"
        </div>
        
        <a href="admin.php" class="back-link">← Back to Admin</a>
    </div>
</body>
</html>
