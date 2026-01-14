<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $status = $_POST['status'] ?? 'Available';
    
    // Verify Schedule Constraint
    // If attempting to go 'Available', check if today is marked as OFF/Rest
    if ($status === 'Available') {
        $today = date('Y-m-d');
        $dayOfWeek = date('D');
        
        // Check DB Schedule
        $schedStmt = $pdo->prepare("SELECT status FROM schedules WHERE user_id = ? AND date = ?");
        $schedStmt->execute([$userId, $today]);
        $schedule = $schedStmt->fetch(PDO::FETCH_ASSOC);

        $isOff = false;
        
        if ($schedule) {
            // Priority: Schedule Table
            if ($schedule['status'] === 'off') {
                $isOff = true;
            }
        } else {
            // Fallback: Default Shop Rules (e.g. Wednesday Closed)
            if ($dayOfWeek === 'Wed') {
                 $isOff = true;
            }
        }

        if ($isOff) {
            echo json_encode([
                'success' => false, 
                'message' => 'You are scheduled OFF today by Admin. Cannot switch to Available.'
            ]);
            exit();
        }
    }

    // Ensure this user IS linked to a barber.
    
    try {
        $stmt = $pdo->prepare("UPDATE barbers SET status = ? WHERE user_id = ?");
        $stmt->execute([$status, $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'new_status' => $status]);
        } else {
            // Maybe they are not linked?
            echo json_encode(['success' => false, 'message' => 'No linked barber profile found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
