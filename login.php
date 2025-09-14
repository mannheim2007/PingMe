<?php
header('Content-Type: application/json');
require_once 'config.php';

// Session indítása
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ellenőrizzük, hogy már be van-e jelentkezve
if (isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => true]);
    exit;
}

// JSON adatok beolvasása
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Hiányzó felhasználónév vagy jelszó']);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

// Ellenőrzés
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Hiányzó felhasználónév vagy jelszó']);
    exit;
}

try {
    // Felhasználó keresése
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Sikeres bejelentkezés
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Hibás felhasználónév vagy jelszó']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("Login hiba: " . $e->getMessage());
}
?>