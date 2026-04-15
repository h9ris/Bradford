<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

$db = get_db();

// Get all data for table view
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Build query
$query = "SELECT u.filename, u.created_at, u.id, us.email as uploader_email 
          FROM uploads u 
          LEFT JOIN users us ON u.user_id = us.id 
          ORDER BY $sort $order 
          LIMIT $per_page OFFSET $offset";
$stmt = $db->query($query);
$uploads = $stmt->fetchAll();

// Get total count for pagination
$total_stmt = $db->query("SELECT COUNT(*) as total FROM uploads");
$total = $total_stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Process data for display
$table_data = [];
foreach ($uploads as $upload) {
    $data = json_decode($upload['data'], true);
    if (is_array($data)) {
        foreach ($data as $item) {
            $table_data[] = [
                'filename' => $upload['filename'],
                'uploader' => $upload['uploader_email'],
                'created_at' => $upload['created_at'],
                'name' => $item['name'] ?? '',
                'lat' => $item['lat'] ?? $item['latitude'] ?? '',
                'lng' => $item['lng'] ?? $item['longitude'] ?? '',
                'type' => $this->detect_type($item)
            ];
        }
    }
}

function detect_type($item) {
    if (isset($item['capacity'])) return 'Car Park';
    if (isset($item['address']) && strpos($item['address'], 'Pool') !== false) return 'Sports Centre';
    if (isset($item['address']) && strpos($item['address'], 'School') !== false) return 'School';
    return 'Location';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Table View - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #005ea5; color: white; cursor: pointer; }
        th:hover { background-color: #003d70; }
        .pagination { margin: 20px 0; text-align: center; }
        .pagination a, .pagination span { padding: 8px 12px; margin: 0 2px; border: 1px solid #005ea5; text-decoration: none; color: #005ea5; }
        .pagination a:hover { background-color: #005ea5; color: white; }
        .pagination .current { background-color: #005ea5; color: white; }
        .filter-form { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .filter-form input, .filter-form select { margin: 0 10px 0 0; padding: 5px; }
    </style>
</head>
<body>
    <h1>Data Table View</h1>
    <p><a href="portal.php">← Back to Dashboard</a></p>

    <div class="filter-form">
        <form method="get">
            <label>Search: <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search names..."></label>
            <label>Type: 
                <select name="type">
                    <option value="">All Types</option>
                    <option value="Car Park" <?php echo ($_GET['type'] ?? '') === 'Car Park' ? 'selected' : ''; ?>>Car Parks</option>
                    <option value="Sports Centre" <?php echo ($_GET['type'] ?? '') === 'Sports Centre' ? 'selected' : ''; ?>>Sports Centres</option>
                    <option value="School" <?php echo ($_GET['type'] ?? '') === 'School' ? 'selected' : ''; ?>>Schools</option>
                    <option value="Location" <?php echo ($_GET['type'] ?? '') === 'Location' ? 'selected' : ''; ?>>Other Locations</option>
                </select>
            </label>
            <button type="submit">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th onclick="sortTable('name')">Name <?php echo $sort === 'name' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?></th>
                <th onclick="sortTable('type')">Type <?php echo $sort === 'type' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?></th>
                <th onclick="sortTable('lat')">Latitude <?php echo $sort === 'lat' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?></th>
                <th onclick="sortTable('lng')">Longitude <?php echo $sort === 'lng' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?></th>
                <th onclick="sortTable('uploader')">Uploaded By <?php echo $sort === 'uploader' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?></th>
                <th onclick="sortTable('created_at')">Date <?php echo $sort === 'created_at' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($table_data as $row): ?>
                <?php
                // Apply filters
                $search = $_GET['search'] ?? '';
                $type_filter = $_GET['type'] ?? '';
                
                if ($search && stripos($row['name'], $search) === false) continue;
                if ($type_filter && $row['type'] !== $type_filter) continue;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($row['lat']); ?></td>
                    <td><?php echo htmlspecialchars($row['lng']); ?></td>
                    <td><?php echo htmlspecialchars($row['uploader']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>">« Previous</a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>">Next »</a>
        <?php endif; ?>
    </div>

    <script>
        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort');
            const currentOrder = urlParams.get('order') || 'desc';
            
            let newOrder = 'asc';
            if (currentSort === column && currentOrder === 'asc') {
                newOrder = 'desc';
            }
            
            urlParams.set('sort', column);
            urlParams.set('order', newOrder);
            
            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>