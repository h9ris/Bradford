<?php
require_once __DIR__ . '/includes/auth.php';
enforce_https();
require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Bradford Council Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .portal-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .sidebar { display: flex; flex-direction: column; gap: 16px; }
        @media(max-width:900px){ .portal-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<a href="#main" class="skip-link">Skip to content</a>

<!-- ACCESSIBILITY BAR (Top) -->
<div class="accessibility-bar-top">
    <div class="accessibility-bar-inner">
        <strong>Accessibility:</strong>
        <button onclick="toggleHighContrast()">High Contrast</button>
        <button onclick="setFontSize('normal')">Normal Text</button>
        <button onclick="setFontSize('large')">Large Text</button>
        <button onclick="setFontSize('extra-large')">Extra Large</button>
    </div>
</div>

<!-- HEADER -->
<header class="site-header">
    <div class="header-inner">
        <a class="header-brand" href="portal.php">
            <div class="header-crest">&#9632;</div>
            <div class="header-brand-text">
                <h1>Bradford Council Portal</h1>
                <p>Metropolitan District Council</p>
            </div>
        </a>
        <nav class="header-nav" aria-label="Main navigation">
            <a href="portal.php" class="active">🗺️ Map</a>
            <a href="table_view.php">📊 Table View</a>
            <a href="share_data.php">📤 Share Data</a>
            <a href="bulk_import.php">📥 Bulk Import</a>
            <a href="export.php?type=csv">📋 Export CSV</a>
            <?php if ($user['is_admin']): ?><a href="admin.php">⚙️ Admin</a><?php endif; ?>
        </nav>
        
        <!-- USER PROFILE MENU -->
        <div class="user-profile-menu">
            <button class="user-profile-btn" onclick="toggleUserMenu()" aria-label="User menu">
                <span class="user-icon">👤</span>
                <span class="user-name"><?php echo htmlspecialchars($user['display_name'] ?? $user['email']); ?></span>
                <span class="dropdown-arrow">▼</span>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php">⚙️ Settings</a>
                <a href="logout.php">🚪 Sign Out</a>
                <?php if ($user['is_admin']): ?>
                <div class="dropdown-divider"></div>
                <a href="admin.php">🔧 Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main class="portal-body" id="main">

    <div class="portal-grid">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <!-- Upload card -->
            <div class="card">
                <div class="card-title">&#128190; Upload Data</div>
                <form method="post" enctype="multipart/form-data" action="upload.php">
                    <label for="datafile">CSV / JSON file:</label>
                    <input type="file" id="datafile" name="datafile" accept=".csv,.json">
                    <button type="submit">Upload File</button>
                </form>
            </div>

            <!-- Manual point card -->
            <div class="card">
                <div class="card-title">&#128205; Add Point Manually</div>
                <form method="post" action="upload.php">
                    <label>Latitude:<input type="text" name="lat" required placeholder="e.g. 53.7950"></label>
                    <label>Longitude:<input type="text" name="lng" required placeholder="e.g. -1.7590"></label>
                    <label>Name:<input type="text" name="name" placeholder="Optional label"></label>
                    <button type="submit" name="manual" value="1">Add Point</button>
                </form>
            </div>

            <!-- Map mode card -->
            <div class="card">
                <div class="card-title">&#128506; Map Options</div>
                <label><input type="radio" name="map_mode" value="markers" checked onclick="toggleMapMode('markers')"> Show Markers</label>
                <label style="margin-top:8px;"><input type="radio" name="map_mode" value="heatmap" onclick="toggleMapMode('heatmap')"> Heatmap</label>
            </div>

            <!-- Legend -->
            <div class="card">
                <div class="card-title">&#128065; Map Legend</div>
                <div class="map-legend" style="flex-direction:column; gap:10px; padding:0; border:none; background:transparent;">
                    <div class="legend-item"><div class="legend-dot school"></div> Schools</div>
                    <div class="legend-item"><div class="legend-dot park"></div> Parks</div>
                    <div class="legend-item"><div class="legend-dot carpark"></div> Car Parks</div>
                    <div class="legend-item"><div class="legend-dot custom"></div> Uploaded Data</div>
                </div>
            </div>

            <!-- 2FA setup link -->
            <div class="card">
                <div class="card-title">&#128274; Security</div>
                <p style="font-size:13px; color:#6b7280; margin:0 0 10px;">
                    2FA: <?=!empty($user['two_factor_secret']) ? '<span style="color:#16a34a;">&#10003; Enabled</span>' : '<span style="color:#dc2626;">&#9888; Not enabled</span>'?>
                </p>
                <a href="twofactor_setup.php" class="btn" style="display:inline-block; text-decoration:none; font-size:13px; padding:7px 14px;">Manage 2FA</a>
            </div>
        </aside>

        <!-- MAIN MAP AREA -->
        <div style="width: 100%; display: flex; flex-direction: column; height: 600px;">
            <div class="card" style="padding: 16px; flex: 1; display: flex; flex-direction: column; height: 100%;">
                <div id="map-status" style="font-size: 13px; color: #6b7280; margin-bottom: 10px;">
                    Loading map&hellip; <span id="marker-count">0</span> markers loaded.
                </div>
                <div id="map" style="flex: 1; height: 100%; border-radius: 8px; background: #f5f5f5;"></div>
            </div>
        </div>
    </div>
</main>

<?php
// Build markers array
$markers = [
    // Parks - green
    ['lat'=>53.7975,'lng'=>-1.7595,'name'=>'City Park','category'=>'park'],
    ['lat'=>53.7810,'lng'=>-1.7500,'name'=>'Roberts Park','category'=>'park'],
    ['lat'=>53.8300,'lng'=>-1.7800,'name'=>'Lister Park','category'=>'park'],
    // Car parks - amber
    ['lat'=>53.796291,'lng'=>-1.759143,'name'=>'Westgate Car Park','category'=>'carpark','capacity'=>'116','empty_places'=>'88','status'=>'Faulty'],
    ['lat'=>53.795739,'lng'=>-1.744756,'name'=>'Burnett St Car Park','category'=>'carpark','capacity'=>'122','empty_places'=>'95','status'=>'Faulty'],
    ['lat'=>53.792179,'lng'=>-1.748466,'name'=>'Crown Court Car Park','category'=>'carpark','capacity'=>'142','empty_places'=>'29','status'=>'Spaces'],
    // Schools - blue
    ['lat'=>53.8090,'lng'=>-1.7610,'name'=>'Bradford Grammar School','category'=>'school'],
    ['lat'=>53.8020,'lng'=>-1.7680,'name'=>'Belle Vue Girls Academy','category'=>'school'],
    ['lat'=>53.7810,'lng'=>-1.7250,'name'=>'Bradford Academy','category'=>'school'],
    ['lat'=>53.7860,'lng'=>-1.7500,'name'=>'Dixons City Academy','category'=>'school'],
    ['lat'=>53.7900,'lng'=>-1.7850,'name'=>'Hanson Academy','category'=>'school'],
    ['lat'=>53.8300,'lng'=>-1.7220,'name'=>'Immanuel College','category'=>'school'],
    ['lat'=>53.8450,'lng'=>-1.8310,'name'=>'Beckfoot School','category'=>'school'],
    ['lat'=>53.9250,'lng'=>-1.8230,'name'=>'Ilkley Grammar School','category'=>'school'],
    ['lat'=>53.8510,'lng'=>-1.7690,'name'=>'Titus Salt School','category'=>'school'],
    ['lat'=>53.7960,'lng'=>-1.7780,'name'=>'St Bede & St Joseph Catholic College','category'=>'school'],
];

$db = get_db();
try {
    $db->query('SELECT 1 FROM shared_data LIMIT 1');
} catch (PDOException $e) {}

try {
    $stmt = $db->prepare('SELECT data FROM uploads WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([$user['id']]);
    while ($row = $stmt->fetch()) {
        $data = json_decode($row['data'], true);
        if (is_array($data)) {
            foreach ($data as $pt) {
                if (isset($pt['lat'], $pt['lng'])) {
                    $pt['category'] = 'custom';
                    $markers[] = $pt;
                }
            }
        }
    }
} catch (PDOException $e) {}

$markersJson = json_encode($markers);
?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        (function(){
            // Ensure Leaflet is loaded
            if (typeof L === 'undefined') {
                console.error('Leaflet library not loaded');
                return;
            }

            const mapContainer = document.getElementById('map');
            if (!mapContainer) {
                console.error('Map container not found');
                return;
            }

            // Colour-coded marker icons
            function makeIcon(colour) {
                return L.divIcon({
                    className: '',
                    html: '<div style="width:14px;height:14px;border-radius:50%;background:'+colour+';border:2.5px solid rgba(0,0,0,0.25);box-shadow:0 1px 4px rgba(0,0,0,0.3);"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                    popupAnchor: [0, -8]
                });
            }

            const colours = { school:'#3b82f6', park:'#22c55e', carpark:'#f59e0b', custom:'#ef4444' };

            const map = L.map('map').setView([53.7950, -1.7590], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const markersData = <?=$markersJson?>;
            const leafletMarkers = [];

            markersData.forEach(function(m) {
                const cat = m.category || 'custom';
                const colour = colours[cat] || '#ef4444';
                let popup = '<strong>' + (m.name || 'Point') + '</strong>';
                if (m.capacity)    popup += '<br>Capacity: '     + m.capacity;
                if (m.empty_places) popup += '<br>Empty: '      + m.empty_places;
                if (m.status)      popup += '<br>Status: '      + m.status;
                popup += '<br><em style="text-transform:capitalize;color:#6b7280;">' + cat + '</em>';

                const marker = L.marker([m.lat, m.lng], { icon: makeIcon(colour) })
                    .addTo(map)
                    .bindPopup(popup);
                leafletMarkers.push(marker);
            });

            // Invalidate map size after DOM is ready
            setTimeout(function() {
                map.invalidateSize();
            }, 100);

            document.getElementById('marker-count').textContent = leafletMarkers.length;
            document.getElementById('map-status').innerHTML = '&#10003; Map loaded — <strong>' + leafletMarkers.length + '</strong> markers shown.';

            window.toggleMapMode = function(mode) {
                if (mode === 'heatmap') {
                    alert('Heatmap mode requires the leaflet-heat plugin. Currently showing markers.');
                }
            };

            // Accessibility helpers
            window.toggleHighContrast = function() { document.body.classList.toggle('high-contrast'); };
            window.setFontSize = function(size) {
                document.body.classList.remove('large-text','extra-large-text');
                if (size === 'large') document.body.classList.add('large-text');
                if (size === 'extra-large') document.body.classList.add('extra-large-text');
            };
            
            // User menu toggle
            window.toggleUserMenu = function() {
                const dropdown = document.getElementById('userDropdown');
                const button = document.querySelector('.user-profile-btn');
                dropdown.classList.toggle('show');
                button.classList.toggle('active');
                
                // Close dropdown when clicking outside
                if (dropdown.classList.contains('show')) {
                    document.addEventListener('click', function closeMenu(e) {
                        if (!e.target.closest('.user-profile-menu')) {
                            dropdown.classList.remove('show');
                            button.classList.remove('active');
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }
            };
        })();
    } catch(error) {
        console.error('Map initialization error:', error);
    }
});
</script>
</body>
</html>
