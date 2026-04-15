<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

// count uploads by user
$stmt = $db->query('SELECT u.name, COUNT(up.id) AS cnt
                     FROM users u
                     LEFT JOIN uploads up ON up.user_id = u.id
                     GROUP BY u.id');
$data = [];
while ($row = $stmt->fetch()) {
    $data[] = ['name' => decrypt_data($row['name'] ?? '' ) ?: $row['name'], 'count' => $row['cnt']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Visualization - Bradford Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Upload Counts by User</h1>
    <canvas id="uploadChart" width="800" height="400"></canvas>
    <p><a href="portal.php">Back</a></p>
    <script>
        const labels = <?= json_encode(array_column($data, 'name')) ?>;
        const counts = <?= json_encode(array_column($data, 'count')) ?>;
        const ctx = document.getElementById('uploadChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '# of uploads',
                    data: counts,
                    backgroundColor: 'rgba(0,94,165,0.6)',
                    borderColor: 'rgba(0,94,165,1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>