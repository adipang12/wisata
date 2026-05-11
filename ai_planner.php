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

// Daftar tempat yang ADA di database (untuk membantu AI memakai nama persis)
$db_places_hint = "
TEMPAT WISATA tersedia di sistem kami (gunakan nama PERSIS seperti ini):

Alam & Pemandangan: Tangkuban Perahu, Kawah Putih, Tebing Keraton, Bukit Moko, Bukit Bintang Bandung, Puncak Bintang, Situ Lembang, Curug Cimahi, Curug Malela, Stone Garden Citatah, The Lodge Maribaya, Sari Ater Hot Spring, Ciwidey Valley Hot Spring, Situ Patengan, Gunung Patuha

Hiburan & Taman: Trans Studio Bandung, De Ranch Lembang, Dusun Bambu, Floating Market Lembang, The Peak Resort, The Valley Bistro Cafe

Museum & Sejarah: Museum Geologi, Museum Konfrensi Asia Afrika, Gedung Sate, Gedung Merdeka

Seni & Budaya: Saung Angklung Udjo, Jalan Braga, Alun Alun Bandung, Jalan Cihampelas

Camping & Alam Terbuka: Ranca Upas, Bumi Perkemahan Cikole, Glamping Lakeside Rancabali

Kebun Binatang: Kebun Binatang Bandung

Kuliner & Restoran: Batagor Kingsley, Warung Nasi Bancakan, Sindang Reret Naripan, Mie Kocok Mang Dadeng, Cendol Elizabeth, Sate Hadori, Warung Nasi Ampera, Surabi Enhaii, Kopi Progo, Warung Sudi Mampir, Cafe D Pakar, Resto Kampung Daun, Nanny s Pavillon, Philosophy Coffee Bandung, Warung Bu Eha, Warung Daun, Batagor Riri, Laksana Restaurant, Es Oyen, Nasi Goreng Mafia, Sindang Reret Naripan

Belanja: Pasar Baru Trade Center, Cihampelas Walk CiWalk, Paris Van Java Mall
";

$prompt = "Kamu adalah pemandu wisata profesional Bandung, Indonesia dengan pengetahuan mendalam tentang jarak dan waktu tempuh antar lokasi.

Buatkan itinerary wisata Bandung yang detail, REALISTIS, dan menarik dengan ketentuan berikut:
- Durasi: $durasi hari
- Jumlah orang: $orang orang
- Minat yang HARUS dicakup: $minat
- Budget: $budget

═══════════════════════════════════════
🗺️ ZONA GEOGRAFIS BANDUNG (WAJIB PATUHI)
═══════════════════════════════════════
Kelompokkan tempat berdasarkan zona yang sama per hari. JANGAN gabungkan zona UTARA + SELATAN dalam 1 hari!

ZONA UTARA - Lembang (±45 menit dari pusat kota):
→ Tangkuban Perahu, De Ranch Lembang, Floating Market Lembang, The Lodge Maribaya, Situ Lembang, Bumi Perkemahan Cikole, Sari Ater Hot Spring, Dusun Bambu, Bukit Moko

ZONA SELATAN - Ciwidey/Rancabali (±1,5-2 jam dari pusat kota):
→ Kawah Putih, Situ Patengan, Ciwidey Valley Hot Spring, Ranca Upas, Gunung Patuha, Glamping Lakeside Rancabali

ZONA BARAT - Padalarang (±30-45 menit):
→ Stone Garden Citatah, Goa Pawon

ZONA TIMUR ATAS - Dago/Punclut (±30 menit):
→ Tebing Keraton, Bukit Bintang Bandung, Puncak Bintang, Cafe D Pakar, Resto Kampung Daun

ZONA KOTA - Pusat Bandung (dalam kota):
→ Gedung Sate, Museum Geologi, Museum Konfrensi Asia Afrika, Alun Alun Bandung, Trans Studio Bandung, Jalan Braga, Jalan Cihampelas, Kebun Binatang Bandung, Saung Angklung Udjo, Pasar Baru Trade Center, Cihampelas Walk CiWalk, Paris Van Java Mall, semua kuliner kota

═══════════════════════════════════════
⏱️ ATURAN WAKTU TEMPUH (SANGAT PENTING)
═══════════════════════════════════════
Hitung REALISTIS: waktu kunjungan + waktu perjalanan ke tempat berikutnya.

Estimasi waktu KUNJUNGAN per tempat:
- Tangkuban Perahu, Kawah Putih, Situ Patengan: 2-3 jam
- Trans Studio Bandung, Dusun Bambu, De Ranch: 3-4 jam
- Museum, Gedung bersejarah: 1-1,5 jam
- Kuliner/Restoran: 1-1,5 jam
- Belanja (mall/pasar): 1,5-2 jam
- Curug/Air terjun: 2-3 jam (termasuk jalan kaki)
- Taman/Kebun: 2 jam

Estimasi waktu PERJALANAN (dari pusat kota, kondisi normal):
- Ke Zona Utara (Lembang): 45-60 menit
- Ke Zona Selatan (Ciwidey): 1,5-2 jam
- Ke Zona Barat (Padalarang): 30-45 menit
- Ke Zona Timur Atas (Dago): 30-45 menit
- Dalam Kota: 15-30 menit antar tempat

ATURAN WAJIB:
1. ❌ DILARANG: Tangkuban Perahu + Kawah Putih dalam 1 hari (jarak 2,5-3 jam)
2. ❌ DILARANG: Jarak antar tempat dalam 1 hari > 45 menit perjalanan (kecuali destinasi utama tunggal)
3. ✅ Berangkat ke Zona Selatan: WAJIB jam 06.00-07.00 (perjalanan panjang)
4. ✅ Berangkat ke Zona Utara: WAJIB jam 06.30-07.00 (hindari macet pagi)
5. ✅ Maksimal 4-5 destinasi per hari (termasuk makan siang + makan malam)
6. ✅ Tambahkan waktu tempuh perjalanan ke destinasi berikutnya dalam deskripsi

═══════════════════════════════════════
🎯 ATURAN MINAT
═══════════════════════════════════════
Kamu WAJIB memasukkan minimal 1 tempat dari SETIAP kategori minat yang disebutkan.
Distribusikan merata namun tetap patuhi aturan zona geografis.

PENTING: Utamakan nama tempat dari daftar berikut (sudah ada di sistem peta kami):
$db_places_hint

═══════════════════════════════════════
📋 FORMAT WAJIB
═══════════════════════════════════════

## 🗓️ Itinerary Bandung $durasi Hari ($orang Orang)
**Tema:** [tema perjalanan sesuai minat]
**Estimasi Budget:** [range budget per orang]
**Zona Hari Ini:** [sebutkan zona utama yang dikunjungi]

---

### 📅 Hari 1: [Judul Hari] — Zona [nama zona]

**🌅 Pagi (06.00 - 12.00)**
- **06.30** - Berangkat dari penginapan menuju [zona] *(perjalanan ±XX menit)*
- **07.30** - [Nama Tempat]: [deskripsi aktivitas] *(kunjungan ±X jam, estimasi: Rp xxx)*
- **10.00** - [Nama Tempat terdekat]: [deskripsi] *(kunjungan ±X jam, estimasi: Rp xxx)*

**☀️ Siang (12.00 - 17.00)**
- **12.30** - [Nama Resto/Warung terdekat]: [deskripsi makan siang] *(estimasi: Rp xxx)*
- **14.00** - [Nama Tempat terdekat]: [deskripsi] *(kunjungan ±X jam, estimasi: Rp xxx)*

**🌆 Sore/Malam (17.00 - 21.00)**
- **17.30** - Perjalanan kembali ke kota *(±XX menit)*
- **19.00** - [Nama Resto]: [deskripsi makan malam] *(estimasi: Rp xxx)*

[ulangi format hari untuk hari berikutnya — gunakan zona berbeda jika memungkinkan]

---

### 💡 Tips Perjalanan
- [3-4 tips praktis termasuk tips kondisi jalan/kemacetan]

### 🚗 Transportasi
- [rekomendasi transportasi sesuai budget dan jumlah orang]

Setelah itinerary, WAJIB tambahkan blok berikut (format JSON tepat, jangan diubah):

##PLACES_JSON##
[
  {\"jam\": \"07.30\", \"hari\": 1, \"nama\": \"Nama Tempat Persis\"},
  {\"jam\": \"10.00\", \"hari\": 1, \"nama\": \"Nama Tempat Persis\"}
]
##END_PLACES##

Jangan sertakan tempat makan/restoran lokal yang tidak ada di daftar sistem ke PLACES_JSON.
Gunakan nama tempat PERSIS seperti di daftar jika tersedia. Gunakan Bahasa Indonesia yang ramah dan antusias.";

$apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Groq API key belum dikonfigurasi di config.php']);
    exit;
}

$url  = "https://api.groq.com/openai/v1/chat/completions";
$body = json_encode([
    'model'       => 'llama-3.1-8b-instant',   // 500K TPD vs 100K TPD llama-3.3-70b
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.8,
    'max_tokens'  => 2500,
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
    $rawMsg = $detail['error']['message'] ?? '';
    // Terjemahkan pesan rate-limit ke bahasa yang ramah
    if ($httpCode === 429 || stripos($rawMsg, 'rate limit') !== false || stripos($rawMsg, 'tokens per day') !== false) {
        // Coba ekstrak waktu tunggu dari pesan
        $wait = '';
        if (preg_match('/try again in ([\d]+m[\d.]+s)/i', $rawMsg, $wm)) {
            $wait = ' Coba lagi dalam ±' . $wm[1] . '.';
        }
        $msg = '⏳ Kuota AI harian sedang penuh.' . $wait . ' Silakan tunggu beberapa menit lalu coba lagi.';
    } else {
        $msg = $rawMsg ?: 'Gagal menghubungi AI. Coba lagi.';
    }
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
            // Nominatim mensyaratkan max 1 req/detik — wajib jeda
            usleep(1200000); // 1.2 detik
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
