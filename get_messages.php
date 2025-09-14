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

$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$since = isset($_GET['since']) ? intval($_GET['since']) : 0;

if ($conversation_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Érvénytelen beszélgetés azonosító']);
    exit;
}

try {
    // Ellenőrizzük, hogy a felhasználó része-e a beszélgetésnek
    $stmt = $pdo->prepare("
        SELECT 1 FROM conversation_participants 
        WHERE conversation_id = :conversation_id AND user_id = :user_id
    ");
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Hozzáférés megtagadva']);
        exit;
    }

    // Üzenetek lekérése
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, u.username as sender_name, m.message, m.created_at 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.conversation_id = :conversation_id 
        AND m.id > :since 
        ORDER BY m.created_at ASC
    ");

    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':since' => $since
    ]);
    
    $messages = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'messages' => $messages]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("get_messages hiba: " . $e->getMessage());
}
?>