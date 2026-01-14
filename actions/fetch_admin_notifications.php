<?php
require '../db.php';
session_start();

header('Content-Type: application/json');

// Validasi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // 1. Get Count of Pending Appointments
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'");
    $count = intval($stmtCount->fetchColumn());

    // 2. Get Top 5 Recent Pending Appointments for the details
    $stmtRecent = $pdo->query("
        SELECT a.id, a.appointment_date, a.appointment_time, u.username as customer_name, u.full_name as customer_real_name 
        FROM appointments a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.status = 'Pending' 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $notifications = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Today's Bookings Count
    $stmtToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
    $today_count = intval($stmtToday->fetchColumn());

    echo json_encode([
        'count' => $count,
        'notifications' => $notifications,
        'today_count' => $today_count
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
