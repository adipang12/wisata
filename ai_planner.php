<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$durasi = intval($input['durasi'] ?? 1);
$minat  = implode(', ', (array)($input['minat'] ?? ['Alam']));
$budget = $input['budget'] ?? 'Sedang';
$orang  = intval($input['orang'] ?? 2);

if ($durasi < 1 || $durasi > 3) $durasi = 1;

$prompt = "Kamu adalah pemandu wisata profesional Bandung, Indonesia.

Buatkan itinerary wisata Bandung yang detail dan menarik dengan ketentuan berikut:
- Durasi: $durasi hari
- Jumlah orang: $orang orang
- Minat: $minat
- Budget: $budget

Format WAJIB (gunakan format ini persis, dengan emoji):

## 🗓️ Itinerary Bandung $durasi Hari ($orang Orang)
**Tema:** [tema perjalanan sesuai minat]
**Estimasi Budget:** [range budget per orang]

---

### 📅 Hari 1: [Judul Hari]

**🌅 Pagi (07.00 - 12.00)**
- **07.00** - [Nama Tempat]: [deskripsi singkat aktivitas] *(estimasi: Rp xxx)*
- **09.30** - [Nama Tempat]: [deskripsi singkat aktivitas] *(estimasi: Rp xxx)*

**☀️ Siang (12.00 - 17.00)**
- **12.00** - [Nama Tempat/Resto]: [deskripsi] *(estimasi: Rp xxx)*
- **14.00** - [Nama Tempat]: [deskripsi] *(estimasi: Rp xxx)*

**🌆 Sore/Malam (17.00 - 21.00)**
- **17.00** - [Nama Tempat]: [deskripsi] *(estimasi: Rp xxx)*
- **19.00** - [Nama Tempat/Resto]: [deskripsi makan malam] *(estimasi: Rp xxx)*

[ulangi format hari untuk hari berikutnya jika durasi > 1]

---

### 💡 Tips Perjalanan
- [3-4 tips praktis yang relevan]

### 🚗 Transportasi
- [rekomendasi transportasi sesuai budget]

Setelah itinerary, WAJIB tambahkan blok berikut (format JSON tepat, jangan diubah):

##PLACES_JSON##
[
  {\"jam\": \"07.00\", \"hari\": 1, \"nama\": \"Nama Tempat Persis\"},
  {\"jam\": \"09.30\", \"hari\": 1, \"nama\": \"Nama Tempat Persis\"}
]
##END_PLACES##

Gunakan tempat wisata nyata di Bandung yang populer. Gunakan Bahasa Indonesia yang ramah.";

$apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Groq API key belum dikonfigurasi di config.php']);
    exit;
}

$url  = "https://api.groq.com/openai/v1/chat/completions";
$body = json_encode([
    'model'       => 'llama-3.3-70b-versatile',
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.8,
    'max_tokens'  => 3000,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    $detail = $response ? json_decode($response, true) : null;
    $msg    = $detail['error']['message'] ?? 'Gagal menghubungi AI. Coba lagi.';
    http_response_code(500);
    echo json_encode(['error' => $msg]);
    exit;
}

$data     = json_decode($response, true);
$fullText = $data['choices'][0]['message']['content'] ?? '';

if (!$fullText) {
    http_response_code(500);
    echo json_encode(['error' => 'AI tidak menghasilkan respons.']);
    exit;
}

// ── Pisahkan itinerary text dari places JSON ──────────────────────────────
$placesRaw = [];
$itinerary = $fullText;

if (preg_match('/##PLACES_JSON##\s*([\s\S]*?)\s*##END_PLACES##/i', $fullText, $m)) {
    $itinerary = trim(str_replace($m[0], '', $fullText));
    $decoded   = json_decode(trim($m[1]), true);
    if (is_array($decoded)) $placesRaw = $decoded;
}

// ── Geocode via Nominatim (OpenStreetMap) ─────────────────────────────────
function nominatimGeocode($name) {
    $q   = urlencode($name . ', Bandung, Jawa Barat, Indonesia');
    // Gunakan countrycodes=id saja, hindari viewbox dengan karakter minus yang bisa rusak encoding
    $url = "https://nominatim.openstreetmap.org/search?q={$q}&format=json&limit=3&countrycodes=id&accept-language=id";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['User-Agent: WisataBandung/1.0 (contact@wisatabandung.com)'],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    curl_close($ch);
    if (!$res) return null;
    $data = json_decode($res, true);
    if (!empty($data[0]['lat'])) {
        return ['lat' => (float)$data[0]['lat'], 'lng' => (float)$data[0]['lon']];
    }
    return null;
}

// ── Cari koordinat dari database ─────────────────────────────────────────
$places = [];
if (!$conn->connect_error && count($placesRaw) > 0) {
    foreach ($placesRaw as $p) {
        $nama = trim($p['nama'] ?? '');
        if (!$nama) continue;

        // Coba exact match → LIKE → reverse LIKE (nama DB ada di dalam nama AI)
        $stmt = $conn->prepare(
            "SELECT nama, latitude, longitude, kategori
             FROM wisata
             WHERE nama = ? OR nama LIKE ? OR ? LIKE CONCAT('%', nama, '%')
             ORDER BY CASE WHEN nama = ? THEN 0 WHEN nama LIKE ? THEN 1 ELSE 2 END
             LIMIT 1"
        );
        $like = '%' . $nama . '%';
        $stmt->bind_param('sssss', $nama, $like, $nama, $nama, $like);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $places[] = [
                'jam'       => $p['jam']  ?? '',
                'hari'      => intval($p['hari'] ?? 1),
                'nama'      => $row['nama'],
                'latitude'  => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'kategori'  => $row['kategori'],
            ];
        } else {
            // Tempat tidak ada di DB → coba geocode via Nominatim (gratis)
            $coords = nominatimGeocode($nama);
            $places[] = [
                'jam'      => $p['jam']  ?? '',
                'hari'     => intval($p['hari'] ?? 1),
                'nama'     => $nama,
                'latitude' => $coords ? $coords['lat'] : null,
                'longitude'=> $coords ? $coords['lng'] : null,
                'kategori' => '',
            ];
        }
    }
}

echo json_encode([
    'result' => $itinerary,
    'places' => $places,
    'durasi' => $durasi,
], JSON_UNESCAPED_UNICODE);
