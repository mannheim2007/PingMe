<?php
header('Content-Type: application/json');
require_once 'config.php';

// JSON adatok beolvasása
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Hiányzó felhasználónév vagy jelszó']);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

// Validáció
if (strlen($username) < 3) {
    echo json_encode(['ok' => false, 'error' => 'A felhasználónév túl rövid (minimum 3 karakter)']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['ok' => false, 'error' => 'A jelszó túl rövid (minimum 6 karakter)']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['ok' => false, 'error' => 'A felhasználónév csak betűket, számokat és aláhúzást tartalmazhat']);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Ellenőrizzük, hogy létezik-e már a felhasználónév
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'A felhasználónév már foglalt']);
        exit;
    }

    // Új felhasználó létrehozása
    $stmt = $pdo->prepare("INSERT INTO users (username, password, created_at) VALUES (:username, :password, NOW())");
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashed_password
    ]);

    echo json_encode(['ok' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Regisztrációs hiba']);
    error_log("Register hiba: " . $e->getMessage());
}
?>