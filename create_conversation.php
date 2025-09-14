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
    echo json_encode(['ok' => false, 'error' => 'Hozzáférés megtagadva. Kérjük, jelentkezz be.']);
    exit;
}

// JSON adatok beolvasása
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Érvénytelen barát azonosító.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$friend_id = intval($input['friend_id']);

try {
    // Ellenőrizzük, hogy barátok-e
    $stmt = $pdo->prepare("
        SELECT 1 FROM friends 
        WHERE ((user_id = :user_id AND friend_id = :friend_id) 
        OR (user_id = :friend_id2 AND friend_id = :user_id2)) 
        AND status = 'accepted'
    ");
    
    $stmt->execute([
        ':user_id' => $current_user_id,
        ':friend_id' => $friend_id,
        ':friend_id2' => $friend_id,
        ':user_id2' => $current_user_id
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'A felhasználó nem a barátaid között van.']);
        exit;
    }

    // Ellenőrizzük, hogy létezik-e már beszélgetés
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM conversations c
        INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
        INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
        WHERE cp1.user_id = :user_id1 AND cp2.user_id = :user_id2
        LIMIT 1
    ");
    
    $stmt->execute([
        ':user_id1' => $current_user_id,
        ':user_id2' => $friend_id
    ]);
    
    $existing_conversation = $stmt->fetch();
    
    if ($existing_conversation) {
        echo json_encode(['ok' => true, 'conversation_id' => $existing_conversation['id']]);
        exit;
    }

    // Új beszélgetés létrehozása
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO conversations (created_at) VALUES (NOW())");
    $stmt->execute();
    $conversation_id = $pdo->lastInsertId();
    
    // Résztvevők hozzáadása
    $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (:conversation_id, :user_id)");
    $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $current_user_id]);
    $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $friend_id]);
    
    $pdo->commit();
    
    echo json_encode(['ok' => true, 'conversation_id' => $conversation_id]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Hiba a beszélgetés létrehozása során.']);
    error_log("create_conversation hiba: " . $e->getMessage());
}
?>