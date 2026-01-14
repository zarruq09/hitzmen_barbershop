<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get unread count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$user_id]);
    $notifCount = $stmtCount->fetchColumn();

    // Get latest 5 notifications
    $stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmtNotifs->execute([$user_id]);
    $notifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'count' => intval($notifCount),
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
