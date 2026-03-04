<?php
/**
 * Data Visualization Dashboard
 * Shows tables, charts, filters, and heatmaps
 * Current: Template with dummy data to show structure
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$db = get_db();
$user = current_user();

// Get user's uploaded data
$stmt = $db->prepare('SELECT * FROM uploads WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$_SESSION['user_id']]);
$uploads = $stmt->fetchAll();

// Get user's assets
$stmt = $db->prepare('
    SELECT a.*, c.color, c.name as category_name,
           COUNT(ai.id) as interaction_count
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN asset_interactions ai ON a.id = ai.asset_id
    GROUP BY a.id
    ORDER BY a.name
');
$stmt->execute();
$assets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Visualization - Bradford Portal</title>
    <link rel="stylesheet" href="css/style.css">
    
    <!-- DataTables for tables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, #8B3A62 0%, #6B2A4A 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-buttons {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav-buttons a {
            padding: 10px 20px;
            background: white;
            color: #8B3A62;
            text-decoration: none;
            border-radius: 4px;
            border: 2px solid #8B3A62;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .nav-buttons a:hover {
            background: #8B3A62;
            color: white;
        }
        
        .section {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #8B3A62;
            margin-top: 0;
            border-bottom: 2px solid #D4AF37;
            padding-bottom: 10px;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .btn {
            padding: 8px 16px;
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
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            position: relative;
            height: 300px;
        }
        
        .chart-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table th {
            background: #8B3A62;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        table tr:hover {
            background: #f5f5f5;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #8B3A62 0%, #6B2A4A 100%);
            color: white;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #1976D2;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>📊 Data Visualization Dashboard</h1>
            <p>Analyze, filter, and export your uploaded data</p>
        </div>
    </div>
    
    <div class="container">
        <div class="nav-buttons">
            <a href="portal.php">← Back to Portal</a>
            <a href="manage_assets.php">Manage Assets</a>
            <a href="upload.php">Upload Data</a>
        </div>
        
        <!-- Statistics Section -->
        <div class="section">
            <h2>📈 Summary Statistics</h2>
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($assets); ?></div>
                    <div class="stat-label">Total Assets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($uploads); ?></div>
                    <div class="stat-label">Uploads</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">
                        <?php 
                        $total_interactions = 0;
                        foreach ($assets as $a) {
                            $total_interactions += $a['interaction_count'];
                        }
                        echo $total_interactions;
                        ?>
                    </div>
                    <div class="stat-label">Interactions</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="section">
            <h2>📊 Charts & Visualizations</h2>
            
            <div class="info-box">
                ℹ️ <strong>Coming Soon:</strong> Charts will display once you upload data. 
                Currently showing template structure.
            </div>
            
            <div class="charts-grid">
                <!-- Bar Chart -->
                <div>
                    <div class="chart-title">📊 Assets by Category</div>
                    <div class="chart-container">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
                
                <!-- Pie Chart -->
                <div>
                    <div class="chart-title">🥧 Category Distribution</div>
                    <div class="chart-container">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
                
                <!-- Line Chart -->
                <div>
                    <div class="chart-title">📈 Assets Over Time</div>
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
                
                <!-- Interaction Chart -->
                <div>
                    <div class="chart-title">🔄 Top Assets by Interactions</div>
                    <div class="chart-container">
                        <canvas id="interactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters & Export Section -->
        <div class="section">
            <h2>🔍 Filter & Export Data</h2>
            
            <form method="GET" style="margin-bottom: 20px;">
                <div class="filters">
                    <div class="filter-group">
                        <label for="category_filter">Category</label>
                        <select id="category_filter" name="category">
                            <option value="">All Categories</option>
                            <!-- Will populate from database -->
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to">
                    </div>
                    
                    <div class="filter-group" style="justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">🔎 Filter</button>
                    </div>
                </div>
            </form>
            
            <div class="export-buttons">
                <button class="btn btn-primary" onclick="exportAsCSV()">📥 Export CSV</button>
                <button class="btn btn-primary" onclick="exportAsJSON()">📥 Export JSON</button>
                <button class="btn btn-secondary" onclick="printTable()">🖨️ Print</button>
            </div>
        </div>
        
        <!-- Data Table Section -->
        <div class="section">
            <h2>📋 Detailed Data Table</h2>
            
            <table id="dataTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Interactions</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($asset['name']); ?></td>
                        <td>
                            <span style="background: <?php echo htmlspecialchars($asset['color'] ?? '#999'); ?>; 
                                                    color: white; 
                                                    padding: 4px 8px; 
                                                    border-radius: 3px;
                                                    font-size: 12px;">
                                <?php echo htmlspecialchars($asset['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo round($asset['latitude'], 4); ?>, <?php echo round($asset['longitude'], 4); ?>
                        </td>
                        <td><?php echo $asset['interaction_count']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></td>
                        <td>
                            <a href="manage_assets.php?edit=<?php echo $asset['id']; ?>" 
                               style="color: #8B3A62; text-decoration: none;">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#dataTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                order: [[0, 'asc']]
            });
            
            // Sample Bar Chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Schools', 'Parks', 'Libraries', 'Healthcare'],
                    datasets: [{
                        label: 'Count',
                        data: [<?php 
                            $counts = [];
                            foreach ($assets as $a) {
                                $cat = $a['category_name'] ?? 'Other';
                                if (!isset($counts[$cat])) $counts[$cat] = 0;
                                $counts[$cat]++;
                            }
                            echo implode(',', array_values($counts)) ?: '0,0,0,0';
                        ?>],
                        backgroundColor: '#8B3A62',
                        borderColor: '#6B2A4A',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            
            // Sample Pie Chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Schools', 'Parks', 'Libraries', 'Healthcare'],
                    datasets: [{
                        data: [<?php echo implode(',', array_values($counts)) ?: '25,25,25,25'; ?>],
                        backgroundColor: ['#8B3A62', '#D4AF37', '#1976D2', '#FF6B6B']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
        
        function exportAsCSV() {
            alert('CSV export coming soon! Will export current filtered data.');
        }
        
        function exportAsJSON() {
            alert('JSON export coming soon! Will export current filtered data.');
        }
        
        function printTable() {
            window.print();
        }
    </script>
</body>
</html>
