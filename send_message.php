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

if (!$input || !isset($input['conversation_id']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Hiányzó adatok']);
    exit;
}

$conversation_id = intval($input['conversation_id']);
$message = trim($input['message']);

if (empty($message)) {
    echo json_encode(['ok' => false, 'error' => 'Üres üzenet']);
    exit;
}

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

    // Üzenet mentése
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, message, created_at) 
        VALUES (:conversation_id, :sender_id, :message, NOW())
    ");

    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':sender_id' => $_SESSION['user_id'],
        ':message' => $message
    ]);

    // Sikeres válasz
    echo json_encode(['ok' => true, 'message_id' => $pdo->lastInsertId()]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("send_message hiba: " . $e->getMessage());
}
?>