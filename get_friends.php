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

$user_id = $_SESSION['user_id'];

try {
    // Barátok lekérése
    $stmt = $pdo->prepare("
        SELECT u.id, u.username 
        FROM users u 
        WHERE u.id IN (
            SELECT friend_id FROM friends WHERE user_id = :user_id AND status = 'accepted'
            UNION
            SELECT user_id FROM friends WHERE friend_id = :user_id2 AND status = 'accepted'
        )
        ORDER BY u.username
    ");

    $stmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
    $friends = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'friends' => $friends]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("get_friends hiba: " . $e->getMessage());
}
?>