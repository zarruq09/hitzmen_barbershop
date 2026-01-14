<?php
require_once '../db.php';

header('Content-Type: application/json');

try {
    // 1. Get Pending Count (for Notifications)
    $stmtPending = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'");
    $pendingCount = $stmtPending->fetchColumn();

    // 2. Get Today's Bookings Count (for Widget)
    $stmtToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
    $todayCount = $stmtToday->fetchColumn();

    echo json_encode([
        'status' => 'success', 
        'pending_count' => (int)$pendingCount,
        'today_count' => (int)$todayCount
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
