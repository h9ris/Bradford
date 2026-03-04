<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Dashboard - Bradford Portal</title>
	<link rel="stylesheet" href="css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
	<h1>Welcome, <?=htmlspecialchars($user['name'] ?: $user['email'])?></h1>
	<div style="margin-bottom: 20px;">
		<?php if ($user['is_admin']): ?>
			<p><a href="admin.php">Go to admin dashboard</a></p>
		<?php endif; ?>
		<p>
			<a href="setup_2fa.php">🔐 Manage Two-Factor Authentication</a> |
			<a href="logout.php">Logout</a>
		</p>
	</div>
	<h2>Upload data</h2>
	<form method="post" enctype="multipart/form-data" action="upload.php">
		<input type="file" name="datafile" accept=".csv,.json">
		<button type="submit">Upload</button>
	</form>

	<h2>Or add a single point manually</h2>
	<form method="post" action="upload.php">
		<label>Latitude:<br><input type="text" name="lat" required></label><br>
		<label>Longitude:<br><input type="text" name="lng" required></label><br>
		<label>Name (optional):<br><input type="text" name="name"></label><br>
		<button type="submit" name="manual" value="1">Add point</button>
	</form>
	<h2>Map view</h2>
	<div id="map" style="width:100%;height:400px;"></div>
	<?php
	// gather marker data from uploads (CSV or JSON)
	// start with some fixed Bradford locations (schools, parks)
	$markers = [
	    ['lat'=>53.7975,'lng'=>-1.7595,'name'=>'City Park'],
	    ['lat'=>53.8090,'lng'=>-1.7610,'name'=>'Bradford Grammar School'],
	    ['lat'=>53.7810,'lng'=>-1.7500,'name'=>'Roberts Park'],
	];
	$db = get_db();
	$stmt = $db->prepare('SELECT filename, data FROM uploads WHERE user_id = ?');
	$stmt->execute([$user['id']]);
	while ($row = $stmt->fetch()) {
		$text = $row['data'];
		$parsed = json_decode($text, true);
		if (is_array($parsed)) {
			foreach ($parsed as $item) {
				if (isset($item['lat'], $item['lng'])) {
					$markers[] = $item;
				}
			}
		} else {
			// try CSV
			$lines = explode("\n", trim($text));
			foreach ($lines as $line) {
				$cols = str_getcsv($line);
				if (count($cols) >= 2 && is_numeric($cols[0]) && is_numeric($cols[1])) {
					$markers[] = ['lat' => (float)$cols[0], 'lng' => (float)$cols[1], 'name' => $cols[2] ?? ''];
				}
			}
		}
	}
	?>
	<script>
		var userMarkers = <?php echo json_encode($markers); ?>;
		function initMap() {
			var map = new google.maps.Map(document.getElementById('map'), {
				center: {lat: 53.795, lng: -1.759},
				zoom: 12
			});
			userMarkers.forEach(function(m) {
				var marker = new google.maps.Marker({
					position: {lat: m.lat, lng: m.lng},
					map: map,
					title: m.name || ''
				});
				if (m.name) {
					var infowindow = new google.maps.InfoWindow({
						content: '<div><strong>' + m.name + '</strong></div>'
					});
					marker.addListener('click', function() {
						infowindow.open(map, marker);
					});
				}
			});
		}
	</script>
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDImJtfn0nnJFFMip7GH31God21pPdsv-4&callback=initMap" async defer></script>
	<!-- optionally include external map.js if any other helpers needed -->
</body>
</html>
