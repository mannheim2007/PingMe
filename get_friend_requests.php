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
    // Függőben lévő barátkérelmek
    $stmt = $pdo->prepare("
        SELECT f.id, u.username as sender_name 
        FROM friends f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.friend_id = :user_id AND f.status = 'pending'
    ");

    $stmt->execute([':user_id' => $user_id]);
    $requests = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'requests' => $requests]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("get_friend_requests hiba: " . $e->getMessage());
}
?>