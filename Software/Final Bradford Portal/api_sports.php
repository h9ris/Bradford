<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
if (!$user['is_admin']) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

$db = get_db();

$sports_centres = [
    ['name' => 'Bowling Pool and Gym', 'address' => 'Flockton Road, East Bowling, Bradford', 'postcode' => 'BD4 7RY'],
    ['name' => 'Eccleshill Pool', 'address' => 'Harrogate Road, Bradford', 'postcode' => 'BD10 0QE'],
    ['name' => 'Ilkley Pool and Lido', 'address' => 'Denton Road, Ilkley', 'postcode' => 'LS29 0BZ'],
    ['name' => 'Manningham Sports Centre', 'address' => 'Carlisle Road, Bradford', 'postcode' => 'BD8 8BA'],
    ['name' => 'Marley Activities and Coaching Centre', 'address' => 'Aireworth Road, Keighley', 'postcode' => 'BD21 4DB'],
    ['name' => 'Sedbergh Sports and Leisure Centre', 'address' => 'Cleckheaton Road, Low Moor, Bradford', 'postcode' => 'BD12 0HQ'],
    ['name' => 'Shipley Pool and Gym', 'address' => 'Alexandra Road, Shipley', 'postcode' => 'BD18 3ER'],
    ['name' => 'The Leisure Centre, Keighley', 'address' => 'Hard Ings Road, Victoria Park, Keighley', 'postcode' => 'BD21 3JN'],
    ['name' => 'Thornton Recreation Centre', 'address' => 'Leaventhorpe Lane, Bradford', 'postcode' => 'BD13 3BH'],
    ['name' => 'Wyke Community Sports Village', 'address' => 'Wilson Road, Bradford', 'postcode' => 'BD12 9HA']
];

$geocoded = [];
foreach ($sports_centres as $centre) {
    $postcode = str_replace(' ', '', $centre['postcode']);
    $url = "https://api.postcodes.io/postcodes/$postcode";
    $response = file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data['status'] == 200) {
            $centre['lat'] = $data['result']['latitude'];
            $centre['lng'] = $data['result']['longitude'];
            $geocoded[] = $centre;
        }
    }
    sleep(1); // rate limit
}

$data = json_encode($geocoded);
$stmt = $db->prepare('INSERT INTO uploads (user_id, filename, data) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], 'sports_centres.json', $data]);

log_activity($user['id'], 'api_fetch', 'sports centres imported');
echo "Sports centres data imported successfully.";
?>