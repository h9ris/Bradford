<?php
/**
 * Bradford Portal - Asset Management
 * View, filter, edit, and manage assets/locations
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];

$message = '';
$error = '';
$asset_id = (int)($_GET['asset_id'] ?? 0);
$show_form = !empty($_GET['edit']) || !empty($_GET['new']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_asset') {
        $asset_id = (int)($_POST['asset_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $latitude = (float)($_POST['latitude'] ?? 0);
        $longitude = (float)($_POST['longitude'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? null);
        $category_id = $category_id > 0 ? $category_id : null;
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name) || $latitude == 0 || $longitude == 0) {
            $error = 'Name, latitude, and longitude are required.';
        } else {
            try {
                if ($asset_id > 0) {
                    // Update existing asset
                    $stmt = $pdo->prepare('UPDATE assets SET name = ?, latitude = ?, longitude = ?, category_id = ?, description = ? WHERE id = ? AND (created_by = ? OR ? = (SELECT is_admin FROM users WHERE id = ?))');
                    $stmt->execute([$name, $latitude, $longitude, $category_id, $description, $asset_id, $user_id, 1, $user_id]);
                    log_activity($user_id, 'ASSET_UPDATED', "Asset: $name");
                    $message = "Asset updated successfully!";
                } else {
                    // Create new asset
                    $stmt = $pdo->prepare('INSERT INTO assets (name, latitude, longitude, category_id, description, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $latitude, $longitude, $category_id, $description, $user_id]);
                    log_activity($user_id, 'ASSET_CREATED', "Asset: $name");
                    $message = "Asset created successfully!";
                }
                $show_form = false;
                $asset_id = 0;
            } catch (Exception $e) {
                $error = 'Error saving asset: ' . $e->getMessage();
            }
        }
        
    } elseif ($action === 'delete_asset') {
        $asset_id = (int)($_POST['asset_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare('DELETE FROM assets WHERE id = ? AND (created_by = ? OR ? = (SELECT is_admin FROM users WHERE id = ?))');
            $stmt->execute([$asset_id, $user_id, 1, $user_id]);
            log_activity($user_id, 'ASSET_DELETED', "Asset ID: $asset_id");
            $message = "Asset deleted successfully!";
            $show_form = false;
            $asset_id = 0;
        } catch (Exception $e) {
            $error = 'Error deleting asset.';
        }
    }
}

// Get asset for editing
$current_asset = null;
if ($asset_id > 0 && $show_form) {
    $stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
    $stmt->execute([$asset_id]);
    $current_asset = $stmt->fetch();
}

// Get all categories
$stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll();

// Get filter category
$filter_category = (int)($_GET['category'] ?? 0);

// Get all assets (with optional filtering)
$query = 'SELECT a.*, c.name as category_name, c.color as category_color, 
                 (SELECT COUNT(*) FROM asset_interactions WHERE asset_id = a.id) as interaction_count
          FROM assets a
          LEFT JOIN categories c ON a.category_id = c.id';

if ($filter_category > 0) {
    $query .= ' WHERE a.category_id = ' . $filter_category;
}

$query .= ' ORDER BY a.name';
$stmt = $pdo->query($query);
$assets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Management - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .asset-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #8B3A62;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        h1 {
            color: #8B3A62;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .toolbar a, .toolbar button {
            padding: 10px 20px;
            background: #8B3A62;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .toolbar a:hover, .toolbar button:hover {
            background: #6d2d4a;
        }
        .filter-section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-section select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .button-group {
            display: flex;
            gap: 10px;
        }
        .button-group button {
            flex: 1;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background: #8B3A62;
            color: white;
        }
        .btn-primary:hover {
            background: #6d2d4a;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .assets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .asset-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .asset-card-header {
            padding: 15px;
            border-left: 5px solid #8B3A62;
            background: #f9f9f9;
        }
        .asset-card-header h3 {
            margin: 0 0 5px 0;
        }
        .asset-category {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
            margin-bottom: 10px;
        }
        .asset-card-body {
            padding: 15px;
        }
        .asset-card-body p {
            margin: 8px 0;
            color: #666;
            font-size: 14px;
        }
        .asset-coords {
            background: #f0f0f0;
            padding: 8px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
        .asset-card-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        .asset-card-footer a, .asset-card-footer form {
            flex: 1;
        }
        .asset-card-footer button, .asset-card-footer a {
            width: 100%;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .asset-card-footer a {
            background: #8B3A62;
            color: white;
        }
        .asset-card-footer a:hover {
            background: #6d2d4a;
        }
        .asset-card-footer button {
            background: #dc3545;
            color: white;
        }
        .asset-card-footer button:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="asset-container">
        <a href="portal.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Asset Management</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
            <!-- Asset Edit/Create Form -->
            <div class="form-section">
                <h2><?php echo $current_asset ? 'Edit Asset' : 'Create New Asset'; ?></h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Asset Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo $current_asset ? htmlspecialchars($current_asset['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="latitude">Latitude:</label>
                            <input type="number" id="latitude" name="latitude" step="0.00001" value="<?php echo $current_asset ? $current_asset['latitude'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude:</label>
                            <input type="number" id="longitude" name="longitude" step="0.00001" value="<?php echo $current_asset ? $current_asset['longitude'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- No Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($current_asset && $current_asset['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description"><?php echo $current_asset ? htmlspecialchars($current_asset['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="button-group">
                        <input type="hidden" name="action" value="save_asset">
                        <input type="hidden" name="asset_id" value="<?php echo $current_asset ? $current_asset['id'] : '0'; ?>">
                        <button type="submit" class="btn-primary">Save Asset</button>
                        <a href="manage_assets.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Assets List -->
            <div class="toolbar">
                <a href="?new=1">+ Add New Asset</a>
            </div>
            
            <div class="filter-section">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <label for="category">Filter by Category:</label>
                    <select id="category" name="category" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($filter_category == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if (empty($assets)): ?>
                <p>No assets found. <a href="?new=1">Create the first asset</a></p>
            <?php else: ?>
                <p><strong>Total Assets:</strong> <?php echo count($assets); ?></p>
                <div class="assets-grid">
                    <?php foreach ($assets as $asset): ?>
                        <div class="asset-card">
                            <div class="asset-card-header">
                                <h3><?php echo htmlspecialchars($asset['name']); ?></h3>
                                <?php if ($asset['category_name']): ?>
                                    <span class="asset-category" style="background-color: <?php echo htmlspecialchars($asset['category_color']); ?>">
                                        <?php echo htmlspecialchars($asset['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="asset-card-body">
                                <div class="asset-coords">
                                    Lat: <?php echo round($asset['latitude'], 6); ?><br>
                                    Lng: <?php echo round($asset['longitude'], 6); ?>
                                </div>
                                <?php if ($asset['description']): ?>
                                    <p><?php echo htmlspecialchars($asset['description']); ?></p>
                                <?php endif; ?>
                                <p><small>📊 Activity: <?php echo $asset['interaction_count']; ?> interactions</small></p>
                                <p><small>Created: <?php echo date('M d, Y', strtotime($asset['created_at'])); ?></small></p>
                            </div>
                            <div class="asset-card-footer">
                                <a href="?edit=1&asset_id=<?php echo $asset['id']; ?>">Edit</a>
                                <form method="POST" onsubmit="return confirm('Delete this asset?');">
                                    <input type="hidden" name="action" value="delete_asset">
                                    <input type="hidden" name="asset_id" value="<?php echo $asset['id']; ?>">
                                    <button type="submit" class="btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
