<?php
/**
 * Bradford Portal - Category Management (Admin)
 * Manage asset categories, colors, and icons
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if not admin
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['is_admin']) {
    header('Location: portal.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#8B3A62');
        $icon = trim($_POST['icon'] ?? 'pin');
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO categories (name, description, color, icon) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $description, $color, $icon]);
                log_activity($_SESSION['user_id'], 'CATEGORY_CREATED', "Category: $name");
                $message = "Category '$name' created successfully!";
            } catch (Exception $e) {
                $error = 'Category already exists or database error.';
            }
        }
        
    } elseif ($action === 'edit_category') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#8B3A62');
        $icon = trim($_POST['icon'] ?? 'pin');
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ?, color = ?, icon = ? WHERE id = ?');
                $stmt->execute([$name, $description, $color, $icon, $category_id]);
                log_activity($_SESSION['user_id'], 'CATEGORY_UPDATED', "Category ID: $category_id");
                $message = "Category updated successfully!";
            } catch (Exception $e) {
                $error = 'Error updating category.';
            }
        }
        
    } elseif ($action === 'delete_category') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        
        // Check if category is in use
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM assets WHERE category_id = ?');
        $stmt->execute([$category_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error = 'Cannot delete category that has assets assigned to it.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
                $stmt->execute([$category_id]);
                log_activity($_SESSION['user_id'], 'CATEGORY_DELETED', "Category ID: $category_id");
                $message = "Category deleted successfully!";
            } catch (Exception $e) {
                $error = 'Error deleting category.';
            }
        }
    }
}

// Get all categories
$stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Bradford Portal Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
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
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-section h2 {
            color: #8B3A62;
            margin-top: 0;
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
        .form-group input[type="color"],
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
        .color-picker {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .color-picker input[type="color"] {
            width: 60px;
            height: 40px;
            cursor: pointer;
        }
        .button-group {
            display: flex;
            gap: 10px;
        }
        .button-group button {
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
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .category-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .category-card-header {
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-color-box {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            flex-shrink: 0;
        }
        .category-card-body {
            padding: 15px;
            border-top: 1px solid #eee;
        }
        .category-card-body p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .category-card-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        .category-card-footer button {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
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
        h1 {
            color: #8B3A62;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="admin.php" class="back-link">← Back to Admin Panel</a>
        
        <h1>Manage Asset Categories</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Add New Category -->
        <div class="form-section">
            <h2>Add New Category</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Category Name:</label>
                    <input type="text" id="name" name="name" placeholder="e.g., Schools" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" placeholder="e.g., Educational institutions"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Color:</label>
                    <div class="color-picker">
                        <input type="color" id="color" name="color" value="#8B3A62">
                        <span id="color-hex">#8B3A62</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="icon">Icon:</label>
                    <select id="icon" name="icon">
                        <option value="pin">📍 Pin</option>
                        <option value="school">🏫 School</option>
                        <option value="park">🌳 Park</option>
                        <option value="hospital">🏥 Hospital</option>
                        <option value="library">📚 Library</option>
                        <option value="building">🏢 Building</option>
                        <option value="home">🏠 Home</option>
                    </select>
                </div>
                
                <input type="hidden" name="action" value="add_category">
                <button type="submit" class="btn-primary">Add Category</button>
            </form>
        </div>
        
        <!-- Existing Categories -->
        <div class="form-section">
            <h2>Existing Categories (<?php echo count($categories); ?>)</h2>
            
            <?php if (empty($categories)): ?>
                <p>No categories created yet.</p>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $cat): ?>
                        <div class="category-card">
                            <div class="category-card-header">
                                <div class="category-color-box" style="background-color: <?php echo htmlspecialchars($cat['color']); ?>"></div>
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($cat['name']); ?></h3>
                            </div>
                            <div class="category-card-body">
                                <p><strong>Icon:</strong> <?php echo htmlspecialchars($cat['icon']); ?></p>
                                <p><strong>Color:</strong> <?php echo htmlspecialchars($cat['color']); ?></p>
                                <?php if ($cat['description']): ?>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($cat['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="category-card-footer">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" class="btn-danger" onclick="return confirm('Delete this category?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update color hex display
        document.getElementById('color').addEventListener('change', function(e) {
            document.getElementById('color-hex').textContent = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>
