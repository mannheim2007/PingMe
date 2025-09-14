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

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($q)) {
    echo json_encode(['ok' => true, 'users' => []]);
    exit;
}

try {
    // Felhasználók keresése (kivéve saját magunkat és már barátokat)
    $stmt = $pdo->prepare("
        SELECT u.id, u.username 
        FROM users u 
        WHERE u.username LIKE :query 
        AND u.id != :current_user_id
        AND u.id NOT IN (
            SELECT friend_id FROM friends WHERE user_id = :current_user_id2
            UNION
            SELECT user_id FROM friends WHERE friend_id = :current_user_id3
        )
        LIMIT 10
    ");
    
    $search_term = '%' . $q . '%';
    $stmt->execute([
        ':query' => $search_term,
        ':current_user_id' => $_SESSION['user_id'],
        ':current_user_id2' => $_SESSION['user_id'],
        ':current_user_id3' => $_SESSION['user_id']
    ]);
    
    $users = $stmt->fetchAll();
    
    echo json_encode(['ok' => true, 'users' => $users]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Szerver hiba']);
    error_log("search_users hiba: " . $e->getMessage());
}
?>