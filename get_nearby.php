<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$lat   = floatval($input['lat']   ?? -6.9175);
$lng   = floatval($input['lng']   ?? 107.6191);
$limit = min(intval($input['limit'] ?? 6), 12);

if ($conn->connect_error) {
    echo json_encode(['places' => []]);
    exit;
}

// Haversine formula — urutkan berdasarkan jarak ke user
$sql = "SELECT nama, kategori, latitude, longitude, rating, review,
        ROUND(6371 * acos(
            LEAST(1, cos(radians(?)) * cos(radians(latitude))
            * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude)))
        ), 2) AS jarak_km
        FROM wisata
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        ORDER BY jarak_km ASC
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('dddi', $lat, $lng, $lat, $limit);
$stmt->execute();
$rows   = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$places = array_map(function($r) {
    return [
        'nama'     => $r['nama'],
        'kategori' => $r['kategori'],
        'latitude' => (float)$r['latitude'],
        'longitude'=> (float)$r['longitude'],
        'rating'   => $r['rating'],
        'jarak_km' => (float)$r['jarak_km'],
        'review'   => $r['review'] ?? '',
    ];
}, $rows);

echo json_encode(['places' => $places], JSON_UNESCAPED_UNICODE);
