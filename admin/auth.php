<?php
session_start();
header('Content-Type: application/json');

$conn = @new mysqli("localhost", "root", "", "wisata_bandung");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database tidak tersedia']);
    exit;
}

// ── Buat tabel admins jika belum ada ──────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed akun admin default jika belum ada ────────────────────────────────
$res = $conn->query("SELECT id FROM admins WHERE username = 'admin'");
if ($res && $res->num_rows === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (name, username, password) VALUES ('Administrator', 'admin', ?)");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $stmt->close();
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? 'check');

switch ($action) {

    // ── Login ──────────────────────────────────────────────────────────────
    case 'login':
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi']);
            break;
        }

        $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $row['id'];
            $_SESSION['admin_name']      = $row['name'];
            $_SESSION['admin_username']  = $username;
            echo json_encode(['success' => true, 'name' => $row['name']]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
        }
        break;

    // ── Logout ─────────────────────────────────────────────────────────────
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    // ── Check session ──────────────────────────────────────────────────────
    case 'check':
    default:
        if (!empty($_SESSION['admin_logged_in'])) {
            echo json_encode([
                'success'  => true,
                'name'     => $_SESSION['admin_name']     ?? 'Admin',
                'username' => $_SESSION['admin_username'] ?? 'admin',
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false]);
        }
        break;
}

$conn->close();
