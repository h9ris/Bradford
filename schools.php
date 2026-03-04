<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$filters = [
    'town' => $_GET['town'] ?? '',
    'school_type' => $_GET['school_type'] ?? '',
    'age_range' => $_GET['age_range'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Build query
$query = "SELECT * FROM schools WHERE status = 'Open'";
$params = [];

if ($filters['search']) {
    $query .= " AND (name LIKE ? OR street LIKE ? OR town LIKE ?)";
    $search = '%' . $filters['search'] . '%';
    $params = array_merge($params, [$search, $search, $search]);
}

if ($filters['town']) {
    $query .= " AND town = ?";
    $params[] = $filters['town'];
}

if ($filters['school_type']) {
    $query .= " AND school_type = ?";
    $params[] = $filters['school_type'];
}

$query .= " ORDER BY name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$schools = $stmt->fetchAll();

// Get filter options
$towns = $pdo->query("SELECT DISTINCT town FROM schools WHERE status = 'Open' ORDER BY town")->fetchAll();
$types = $pdo->query("SELECT DISTINCT school_type FROM schools WHERE status = 'Open' ORDER BY school_type")->fetchAll();

// User info for display
$user = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$userName = $user->fetch()['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schools Directory - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        header {
            background: linear-gradient(135deg, #8B3A62 0%, #6B2A4A 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content h1 {
            font-size: 28px;
        }
        
        .user-info {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            font-size: 13px;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #8B3A62;
            box-shadow: 0 0 5px rgba(139, 58, 98, 0.2);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #8B3A62;
            color: white;
        }
        
        .btn-primary:hover {
            background: #6B2A4A;
        }
        
        .btn-secondary {
            background: #ddd;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #bbb;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .results-count {
            color: #666;
            font-size: 14px;
        }
        
        .schools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .school-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid #8B3A62;
        }
        
        .school-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #8B3A62;
            margin-bottom: 10px;
        }
        
        .school-badge {
            display: inline-block;
            background: #D4AF37;
            color: #333;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .school-info {
            margin: 8px 0;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        
        .school-info strong {
            color: #333;
        }
        
        .school-ages {
            background: #f0f0f0;
            padding: 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        
        .no-results h2 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .nav-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .nav-buttons a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #8B3A62;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .nav-buttons a:hover {
            background: #6B2A4A;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>📚 Bradford Schools Directory</h1>
            <div class="user-info">
                Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong><br>
                <a href="portal.php" style="color: white; text-decoration: none;">← Back to Portal</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="filters">
            <form method="GET" style="margin-bottom: 15px;">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">🔍 Search by Name or Address</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="School name, street...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="town">📍 Town</label>
                        <select id="town" name="town">
                            <option value="">All Towns</option>
                            <?php foreach ($towns as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['town']); ?>" <?php echo $filters['town'] === $t['town'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['town']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="school_type">🏫 School Type</label>
                        <select id="school_type" name="school_type">
                            <option value="">All Types</option>
                            <?php foreach ($types as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['school_type']); ?>" <?php echo $filters['school_type'] === $st['school_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($st['school_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔎 Search</button>
                    <a href="schools.php" class="btn btn-secondary">↺ Reset</a>
                </div>
            </form>
        </div>
        
        <div class="results-header">
            <span class="results-count">Found <strong><?php echo count($schools); ?></strong> school<?php echo count($schools) !== 1 ? 's' : ''; ?></span>
        </div>
        
        <?php if (empty($schools)): ?>
            <div class="no-results">
                <h2>No schools found</h2>
                <p>Try adjusting your search filters</p>
            </div>
        <?php else: ?>
            <div class="schools-grid">
                <?php foreach ($schools as $school): ?>
                    <div class="school-card">
                        <div class="school-name"><?php echo htmlspecialchars($school['name']); ?></div>
                        
                        <span class="school-badge">
                            <?php echo htmlspecialchars($school['school_type'] ?? 'School'); ?>
                        </span>
                        
                        <?php if ($school['religious_character'] && $school['religious_character'] !== 'Does not apply'): ?>
                            <span class="school-badge" style="background: #E8D7F1;">
                                <?php echo htmlspecialchars($school['religious_character']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="school-info">
                            <strong>📍 Address:</strong><br>
                            <?php echo htmlspecialchars($school['street'] ?? ''); ?>
                            <?php if ($school['town']): ?><br><?php echo htmlspecialchars($school['town']); endif; ?>
                            <?php if ($school['postcode']): ?><br><?php echo htmlspecialchars($school['postcode']); endif; ?>
                        </div>
                        
                        <div class="school-info">
                            <strong>👥 Gender:</strong> <?php echo htmlspecialchars($school['gender'] ?? 'Mixed'); ?>
                        </div>
                        
                        <?php if ($school['min_age'] || $school['max_age']): ?>
                            <div class="school-ages">
                                <strong>📅 Age Range:</strong> 
                                <?php 
                                    $ageStr = '';
                                    if ($school['min_age']) $ageStr .= $school['min_age'];
                                    if ($school['min_age'] && $school['max_age']) $ageStr .= ' - ';
                                    if ($school['max_age']) $ageStr .= $school['max_age'];
                                    echo $ageStr;
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="school-info" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <strong>🏷️ URN:</strong> <?php echo htmlspecialchars($school['urn']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="nav-buttons">
            <a href="portal.php">🗺️ View Map</a>
            <a href="manage_assets.php">📌 Manage Assets</a>
            <?php if ($_SESSION['is_admin']): ?>
                <a href="admin.php">⚙️ Admin Panel</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
