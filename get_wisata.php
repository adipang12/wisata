<?php
header('Content-Type: application/json');
ini_set('serialize_precision', '-1');

function geojsonFallback($path) {
    if (!is_readable($path)) {
        http_response_code(500);
        return ["error" => "Data wisata tidak ditemukan"];
    }

    $geojson = json_decode(file_get_contents($path), true);
    if (!is_array($geojson) || !isset($geojson['features'])) {
        http_response_code(500);
        return ["error" => "Format GeoJSON tidak valid"];
    }

    $data = [];
    foreach ($geojson['features'] as $feature) {
        $properties = $feature['properties'] ?? [];
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        if (($feature['geometry']['type'] ?? '') !== 'Point' || !is_array($coordinates) || count($coordinates) < 2) {
            continue;
        }

        $data[] = [
            "nama" => $properties['name'] ?? $properties['name:id'] ?? "Tidak diketahui",
            "kategori" => $properties['tourism'] ?? "lainnya",
            "latitude" => (float) $coordinates[1],
            "longitude" => (float) $coordinates[0],
            "rating" => null,
            "review" => $properties['description'] ?? "Tempat wisata menarik di Bandung."
        ];
    }

    return $data;
}

$data = [];
require_once __DIR__ . '/db.php';

if (!$conn->connect_error) {
    $result = $conn->query("SELECT * FROM wisata");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['latitude'] = (float) $row['latitude'];
            $row['longitude'] = (float) $row['longitude'];
            $data[] = $row;
        }
    }
}

if (count($data) === 0) {
    $data = geojsonFallback(__DIR__ . "/wisata.geojson");
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
