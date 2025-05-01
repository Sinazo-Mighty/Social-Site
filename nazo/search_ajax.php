<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['query'])) {
    echo json_encode([]);
    exit();
}

$search_query = trim($_GET['query']);

if (strlen($search_query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT user_id, username, profile_picture
        FROM users 
        WHERE username LIKE ? 
        AND user_id != ?
        LIMIT 10
    ");
    $stmt->execute(["%$search_query%", $_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Search failed']);
}
?>