<?php
require_once __DIR__ . '/db.php';
$message = "";

if ($conn->connect_error) {
    $message = "Koneksi database gagal: " . htmlspecialchars($conn->connect_error);
} elseif (isset($_POST['submit'])) {
    $nama = trim($_POST['nama'] ?? '');
    $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
    $lon = filter_input(INPUT_POST, 'lon', FILTER_VALIDATE_FLOAT);

    if ($nama === '' || $lat === false || $lon === false) {
        $message = "Nama, latitude, dan longitude wajib diisi dengan benar.";
    } else {
        $stmt = $conn->prepare("INSERT INTO wisata (nama, latitude, longitude, kategori) VALUES (?, ?, ?, 'attraction')");
        $stmt->bind_param("sdd", $nama, $lat, $lon);
        $message = $stmt->execute() ? "Data berhasil ditambahkan!" : "Gagal menyimpan data: " . htmlspecialchars($stmt->error);
        $stmt->close();
    }
}
?>
<?php if ($message): ?>
    <p><?= $message; ?></p>
<?php endif; ?>
<form method="POST">
    Nama: <input type="text" name="nama" required><br>
    Lat: <input type="number" step="any" name="lat" required><br>
    Lon: <input type="number" step="any" name="lon" required><br>
    <button type="submit" name="submit">Simpan</button>
</form>
