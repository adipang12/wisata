<?php
session_start();
header('Content-Type: application/json');

// ── Auth check ─────────────────────────────────────────────────────────────
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi']);
    exit;
}

// ── DB ─────────────────────────────────────────────────────────────────────
$conn = @new mysqli("localhost", "root", "", "wisata_bandung");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database tidak tersedia']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':   handleList($conn);                        break;
    case 'add':    handleAdd($conn);                         break;
    case 'edit':   handleEdit($conn, $_GET['id'] ?? null);   break;
    case 'delete': handleDelete($conn, $_GET['id'] ?? null); break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}

$conn->close();

// ── Handlers ───────────────────────────────────────────────────────────────

function handleList($conn) {
    $result = $conn->query("SELECT * FROM wisata ORDER BY id ASC");
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['latitude']  = (float) $row['latitude'];
            $row['longitude'] = (float) $row['longitude'];
            $data[] = $row;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function handleAdd($conn) {
    $input    = json_decode(file_get_contents('php://input'), true);
    $nama     = trim($input['nama']     ?? '');
    $kategori = trim($input['kategori'] ?? 'lainnya');
    $lat      = isset($input['latitude'])  ? (float)$input['latitude']  : null;
    $lng      = isset($input['longitude']) ? (float)$input['longitude'] : null;
    $review   = trim($input['review'] ?? '');
    $hasRat   = isset($input['rating']) && $input['rating'] !== '';
    $rating   = $hasRat ? (float)$input['rating'] : null;

    if (!$nama)              { sendErr(400, 'Nama wisata wajib diisi'); return; }
    if ($lat===null||$lng===null) { sendErr(400, 'Koordinat wajib diisi'); return; }

    if ($hasRat) {
        // s s d d d s  (6 params)
        $stmt = $conn->prepare(
            "INSERT INTO wisata (nama,kategori,latitude,longitude,rating,review) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param("ssddds", $nama, $kategori, $lat, $lng, $rating, $review);
    } else {
        // s s d d s  (5 params)
        $stmt = $conn->prepare(
            "INSERT INTO wisata (nama,kategori,latitude,longitude,review) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param("ssdds", $nama, $kategori, $lat, $lng, $review);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Wisata berhasil ditambahkan', 'id' => $conn->insert_id]);
    } else {
        sendErr(500, 'Gagal menyimpan: ' . $conn->error);
    }
    $stmt->close();
}

function handleEdit($conn, $id) {
    if (!$id || !is_numeric($id)) { sendErr(400, 'ID tidak valid'); return; }
    $id = (int)$id;

    $input    = json_decode(file_get_contents('php://input'), true);
    $nama     = trim($input['nama']     ?? '');
    $kategori = trim($input['kategori'] ?? 'lainnya');
    $lat      = (float)($input['latitude']  ?? 0);
    $lng      = (float)($input['longitude'] ?? 0);
    $review   = trim($input['review'] ?? '');
    $hasRat   = isset($input['rating']) && $input['rating'] !== '';
    $rating   = $hasRat ? (float)$input['rating'] : null;

    if (!$nama) { sendErr(400, 'Nama wisata wajib diisi'); return; }

    if ($hasRat) {
        // s s d d d s i  (7 params)
        $stmt = $conn->prepare(
            "UPDATE wisata SET nama=?,kategori=?,latitude=?,longitude=?,rating=?,review=? WHERE id=?"
        );
        $stmt->bind_param("ssdddsi", $nama, $kategori, $lat, $lng, $rating, $review, $id);
    } else {
        // s s d d s i  (6 params)
        $stmt = $conn->prepare(
            "UPDATE wisata SET nama=?,kategori=?,latitude=?,longitude=?,rating=NULL,review=? WHERE id=?"
        );
        $stmt->bind_param("ssddsi", $nama, $kategori, $lat, $lng, $review, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Wisata berhasil diperbarui']);
    } else {
        sendErr(500, 'Gagal memperbarui: ' . $conn->error);
    }
    $stmt->close();
}

function handleDelete($conn, $id) {
    if (!$id || !is_numeric($id)) { sendErr(400, 'ID tidak valid'); return; }
    $id = (int)$id;

    $stmt = $conn->prepare("DELETE FROM wisata WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Wisata berhasil dihapus']);
    } else {
        sendErr(500, 'Gagal menghapus: ' . $conn->error);
    }
    $stmt->close();
}

function sendErr($code, $msg) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
}
