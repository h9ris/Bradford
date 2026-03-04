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
			<a href="manage_assets.php">🗺️ Manage Assets</a> |
			<a href="schools.php">📚 Schools Directory</a> |
			<a href="setup_2fa.php">🔐 Two-Factor Authentication</a> |
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
	// gather marker data from uploads (CSV or JSON) and from assets database
	$markers = [];
	$db = get_db();
	
	// Get assets from database with categories
	$stmt = $db->prepare('
		SELECT a.id, a.name, a.latitude, a.latitude as lat, a.longitude, a.longitude as lng, 
		       c.color, c.name as category, a.description
		FROM assets a
		LEFT JOIN categories c ON a.category_id = c.id
		ORDER BY a.name
	');
	$stmt->execute();
	while ($row = $stmt->fetch()) {
		$markers[] = [
			'lat' => (float)$row['lat'],
			'lng' => (float)$row['lng'],
			'name' => $row['name'],
			'description' => $row['description'],
			'category' => $row['category'],
			'color' => $row['color'] ?? '#8B3A62',
			'type' => 'asset'
		];
	}
	
	// Get schools from database
	$stmt = $db->prepare('
		SELECT id, name, latitude as lat, longitude as lng, street, postcode, school_type, status
		FROM schools
		WHERE status = "Open" AND latitude IS NOT NULL AND longitude IS NOT NULL
		ORDER BY name
	');
	$stmt->execute();
	while ($row = $stmt->fetch()) {
		$markers[] = [
			'lat' => (float)$row['lat'],
			'lng' => (float)$row['lng'],
			'name' => $row['name'],
			'description' => $row['school_type'] . ' | ' . $row['street'] . ', ' . $row['postcode'],
			'category' => 'Schools',
			'color' => '#1976D2',
			'type' => 'school'
		];
	}
	
	// Get data from uploads (CSV or JSON)
	$stmt = $db->prepare('SELECT filename, data FROM uploads WHERE user_id = ?');
	$stmt->execute([$user['id']]);
	while ($row = $stmt->fetch()) {
		$text = $row['data'];
		$parsed = json_decode($text, true);
		if (is_array($parsed)) {
			foreach ($parsed as $item) {
				if (isset($item['lat'], $item['lng'])) {
					$markers[] = array_merge($item, ['type' => 'upload']);
				}
			}
		} else {
			// try CSV
			$lines = explode("\n", trim($text));
			foreach ($lines as $line) {
				$cols = str_getcsv($line);
				if (count($cols) >= 2 && is_numeric($cols[0]) && is_numeric($cols[1])) {
					$markers[] = [
						'lat' => (float)$cols[0],
						'lng' => (float)$cols[1],
						'name' => $cols[2] ?? '',
						'type' => 'upload'
					];
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
				var color = m.color || '#8B3A62';
				var marker = new google.maps.Marker({
					position: {lat: m.lat, lng: m.lng},
					map: map,
					title: m.name || '',
					icon: getMarkerIcon(color)
				});
				
				var infoContent = '<div style="font-size: 12px;"><strong>' + (m.name || 'Location') + '</strong>';
				if (m.category) {
					infoContent += '<br><span style="color: ' + color + '; font-weight: bold;">📁 ' + m.category + '</span>';
				}
				if (m.description) {
					infoContent += '<br><em>' + m.description + '</em>';
				}
				infoContent += '<br><small>Lat: ' + m.lat.toFixed(4) + ', Lng: ' + m.lng.toFixed(4) + '</small>';
				infoContent += '</div>';
				
				var infowindow = new google.maps.InfoWindow({
					content: infoContent
				});
				marker.addListener('click', function() {
					infowindow.open(map, marker);
				});
			});
		}
		
		function getMarkerIcon(color) {
			return {
				path: google.maps.SymbolPath.CIRCLE,
				scale: 8,
				fillColor: color,
				fillOpacity: 0.8,
				strokeColor: '#fff',
				strokeWeight: 2
			};
		}
	</script>
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDImJtfn0nnJFFMip7GH31God21pPdsv-4&callback=initMap" async defer></script>
	<!-- optionally include external map.js if any other helpers needed -->
</body>
</html>
