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

if (!$input || !isset($input['request_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Hiányzó adatok']);
    exit;
}

$request_id = intval($input['request_id']);
$action = $input['action'] === 'accept' ? 'accepted' : 'rejected';
$current_user_id = $_SESSION['user_id'];

try {
    // Ellenőrizzük, hogy a kérés létezik-e és a jelenlegi felhasználó a címzett
    $stmt = $pdo->prepare("
        SELECT id, user_id, friend_id FROM friends 
        WHERE id = :request_id AND friend_id = :current_user_id AND status = 'pending'
    ");
    
    $stmt->execute([
        ':request_id' => $request_id,
        ':current_user_id' => $current_user_id
    ]);
    
    $request = $stmt->fetch();
    
    if (!$request) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Érvénytelen barátkérés']);
        exit;
    }
    
    // Barátkérés frissítése
    $stmt = $pdo->prepare("
        UPDATE friends SET status = :status, updated_at = NOW() 
        WHERE id = :request_id
    ");
    
    $stmt->execute([
        ':status' => $action,
        ':request_id' => $request_id
    ]);
    
    echo json_encode(['ok' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("respond_friend_request hiba: " . $e->getMessage());
}
?>