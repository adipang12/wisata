<?php
session_start();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database tidak tersedia']);
    exit;
}

// ── Buat tabel users jika belum ada ───────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Buat tabel admins jika belum ada + seed akun default ─────────────────
$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$res = $conn->query("SELECT id FROM admins WHERE username = 'admin'");
if ($res && $res->num_rows === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (name, username, password) VALUES ('Administrator', 'admin', ?)");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $stmt->close();
}

switch ($action) {

    // ── Register (hanya untuk user biasa) ────────────────────────────────
    case 'register':
        $name     = trim($input['name']     ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password']      ?? '';

        if (!$name || !$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit;
        }
        $stmt->close();

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $username, $hash);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Akun berhasil dibuat']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat akun']);
        }
        $stmt->close();
        break;

    // ── Login (cek users dulu, lalu admins) ──────────────────────────────
    case 'login':
        $username = trim($input['username'] ?? '');
        $password = $input['password']      ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi']);
            break;
        }

        // 1. Cek tabel users
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            echo json_encode(['success' => true, 'name' => $row['name'], 'role' => 'user']);
            break;
        }

        // 2. Cek tabel admins
        $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, $row['password'])) {
            // Set sesi user DAN admin sekaligus
            $_SESSION['user_id']         = $row['id'];
            $_SESSION['user_name']       = $row['name'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $row['id'];
            $_SESSION['admin_name']      = $row['name'];
            $_SESSION['admin_username']  = $username;
            echo json_encode(['success' => true, 'name' => $row['name'], 'role' => 'admin']);
            break;
        }

        echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
        break;

    // ── Logout ─────────────────────────────────────────────────────────────
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    // ── Check session ──────────────────────────────────────────────────────
    case 'check':
        if (!empty($_SESSION['user_id'])) {
            $role = !empty($_SESSION['admin_logged_in']) ? 'admin' : 'user';
            echo json_encode(['success' => true, 'name' => $_SESSION['user_name'], 'role' => $role]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}

$conn->close();
