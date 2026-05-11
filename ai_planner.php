<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$durasi  = intval($input['durasi']  ?? 1);
$minat   = implode(', ', (array)($input['minat']  ?? ['Alam']));
$budget  = $input['budget']  ?? 'Sedang';
$orang   = intval($input['orang']   ?? 2);

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

Gunakan tempat wisata nyata di Bandung. Sesuaikan rekomendasi dengan minat dan budget yang diminta. Gunakan Bahasa Indonesia yang ramah dan informatif.";

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Gemini API key belum dikonfigurasi di config.php']);
    exit;
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($apiKey);

$body = json_encode([
    'contents' => [
        ['parts' => [['text' => $prompt]]]
    ],
    'generationConfig' => [
        'temperature'     => 0.8,
        'maxOutputTokens' => 2048,
    ]
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    $detail = $response ? json_decode($response, true) : null;
    $msg = $detail['error']['message'] ?? 'Gagal menghubungi AI. Coba lagi.';
    http_response_code(500);
    echo json_encode(['error' => $msg]);
    exit;
}

$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$text) {
    http_response_code(500);
    echo json_encode(['error' => 'AI tidak menghasilkan respons.']);
    exit;
}

echo json_encode(['result' => $text]);
