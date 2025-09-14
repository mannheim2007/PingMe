<?php
header('Content-Type: application/json');
require_once 'config.php';

// Session indítása
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ellenőrizzük, hogy be van-e jelentkezve
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Hozzáférés megtagadva']);
    exit;
}

// JSON adatok beolvasása
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Érvénytelen barát azonosító']);
    exit;
}

$friend_id = intval($input['friend_id']);
$current_user_id = $_SESSION['user_id'];

if ($friend_id == $current_user_id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Nem küldhetsz barátkérelmet saját magadnak']);
    exit;
}

try {
    // Ellenőrizzük, hogy létezik-e a felhasználó
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :friend_id");
    $stmt->execute([':friend_id' => $friend_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'A felhasználó nem létezik']);
        exit;
    }
    
    // Ellenőrizzük, hogy már van-e függőben lévő kérés vagy barátság
    $stmt = $pdo->prepare("
        SELECT id, status FROM friends 
        WHERE (user_id = :user_id AND friend_id = :friend_id) 
        OR (user_id = :friend_id2 AND friend_id = :user_id2)
    ");
    
    $stmt->execute([
        ':user_id' => $current_user_id,
        ':friend_id' => $friend_id,
        ':friend_id2' => $friend_id,
        ':user_id2' => $current_user_id
    ]);
    
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['status'] === 'pending') {
            echo json_encode(['ok' => false, 'error' => 'Már van függőben lévő barátkérelmed']);
        } else if ($existing['status'] === 'accepted') {
            echo json_encode(['ok' => false, 'error' => 'Már barátok vagytok']);
        } else {
            echo json_encode(['ok' => false, 'error' => 'A barátkérelmet már elutasították']);
        }
        exit;
    }
    
    // Új barátkérés küldése
    $stmt = $pdo->prepare("
        INSERT INTO friends (user_id, friend_id, status, created_at) 
        VALUES (:user_id, :friend_id, 'pending', NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $current_user_id,
        ':friend_id' => $friend_id
    ]);
    
    echo json_encode(['ok' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("send_friend_request hiba: " . $e->getMessage());
}
?>