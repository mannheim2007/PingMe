<?php
header('Content-Type: application/json');
require_once 'config.php';

// Session indítása és megsemmisítése
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Minden session változó törlése
$_SESSION = array();

// Session cookie törlése
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session megsemmisítése
session_destroy();

echo json_encode(['ok' => true]);
?>