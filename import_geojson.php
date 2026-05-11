<?php
require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
  die("Koneksi database gagal: " . $conn->connect_error);
}

$geojsonPath = __DIR__ . "/wisata.geojson";
if (!is_readable($geojsonPath)) {
  die("File wisata.geojson tidak ditemukan.");
}

$data = json_decode(file_get_contents($geojsonPath), true);
if (!is_array($data) || !isset($data['features'])) {
  die("Format GeoJSON tidak valid.");
}

$stmt = $conn->prepare("SELECT id FROM wisata WHERE nama = ? AND kategori = ? AND latitude = ? AND longitude = ? LIMIT 1");
$insert = $conn->prepare("INSERT INTO wisata (nama,kategori,latitude,longitude,images,rating,review) VALUES (?, ?, ?, ?, ?, ?, ?)");
$imported = 0;
$skipped = 0;

foreach($data['features'] as $f){
  if (($f['geometry']['type'] ?? '') !== 'Point' || !isset($f['geometry']['coordinates'][0], $f['geometry']['coordinates'][1])) {
    continue;
  }

  $nama = $f['properties']['name'] ?? 'Tidak diketahui';
  $kategori = $f['properties']['tourism'] ?? 'lainnya';

  $lon = (float) $f['geometry']['coordinates'][0];
  $lat = (float) $f['geometry']['coordinates'][1];

  $images = "images/1.jpg,images/2.jpg,images/3.jpg";
  $rating = rand(35,50)/10;
  $review = "Tempat wisata menarik di Bandung.";

  $stmt->bind_param("ssdd", $nama, $kategori, $lat, $lon);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $skipped++;
    continue;
  }

  $insert->bind_param("ssddsds", $nama, $kategori, $lat, $lon, $images, $rating, $review);
  if ($insert->execute()) {
    $imported++;
  }
}

$stmt->close();
$insert->close();

echo "IMPORT SELESAI! Data baru: $imported, dilewati karena sudah ada: $skipped";
?>
