<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$barberId = $_POST['barber_id'] ?? '';

if (empty($date) || empty($time) || empty($barberId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Prevent checking past times
$currentTimestamp = time();
$requestedTimestamp = strtotime("$date $time");

if ($requestedTimestamp < $currentTimestamp) {
    echo json_encode([
        'status' => 'conflict',
        'message' => "You cannot book a time in the past.",
        'suggestion' => "Please select a future date and time."
    ]);
    exit;
}

try {
    // 0. CHECK Roster/Schedule for Specific Day Availability
    // Get Barber's Linked User ID
    $barberStmt = $pdo->prepare("SELECT user_id, name FROM barbers WHERE id = ?");
    $barberStmt->execute([$barberId]);
    $barberData = $barberStmt->fetch(PDO::FETCH_ASSOC);

    if ($barberData && $barberData['user_id']) {
        $userId = $barberData['user_id'];
        
        // Check Schedules Table
        $schedStmt = $pdo->prepare("SELECT status, start_time, end_time FROM schedules WHERE user_id = ? AND date = ?");
        $schedStmt->execute([$userId, $date]);
        $schedule = $schedStmt->fetch(PDO::FETCH_ASSOC);

        $dayOfWeek = date('N', strtotime($date)); // 1 (Mon) - 7 (Sun)
        
        // Logic Source of Truth: manage_schedule.php default logic
        if ($schedule) {
            // Explicit Schedule Found
            if ($schedule['status'] === 'off') {
                echo json_encode([
                    'status' => 'conflict',
                    'message' => "Barber " . $barberData['name'] . " is OFF on this date.",
                    'suggestion' => "Please choose another barber or another date."
                ]);
                exit;
            }
            if ($schedule['status'] === 'rest') {
                echo json_encode([
                    'status' => 'conflict',
                    'message' => "Barber " . $barberData['name'] . " is on leave/rest.",
                    'suggestion' => "Please choose another barber or another date."
                ]);
                exit;
            }
            // If Available, enforce hours
            $workStart = $schedule['start_time'] ? strtotime("$date " . $schedule['start_time']) : strtotime("$date 11:00");
            $workEnd = $schedule['end_time'] ? strtotime("$date " . $schedule['end_time']) : strtotime("$date 23:00");

        } else {
            // Default Logic (No explicit schedule)
            // Wednesday = Closed
            if ($dayOfWeek == 3) {
                 echo json_encode([
                    'status' => 'conflict',
                    'message' => "We are closed on Wednesdays.",
                    'suggestion' => "Please choose another date."
                ]);
                exit;
            }

            // Default Hours
            if ($dayOfWeek == 5) { // Friday
                $workStart = strtotime("$date 15:00");
                $workEnd = strtotime("$date 23:00");
            } else {
                $workStart = strtotime("$date 11:00");
                $workEnd = strtotime("$date 23:00");
            }
        }

        // Validate Time Range
        $requestedTimeTs = strtotime("$date $time");
        if ($requestedTimeTs < $workStart || $requestedTimeTs >= $workEnd) {
             echo json_encode([
                'status' => 'conflict',
                'message' => "Selected time is outside working hours (" . date('g:i A', $workStart) . " - " . date('g:i A', $workEnd) . ").",
                'suggestion' => "Please select a time within the working hours."
            ]);
            exit;
        }
    }

    // 1. Get all booked slots for this barber on this date
    $stmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE barber_id = ? 
        AND appointment_date = ? 
        AND status IN ('Pending', 'Confirmed')
    ");
    $stmt->execute([$barberId, $date]);
    $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requestedTimeTs = strtotime("$date $time");
    $conflict = false;
    $conflictTime = null;

    // 2. Check for 40-minute gap violation
    foreach ($bookedTimes as $bookedTime) {
        $bookedTimeTs = strtotime("$date $bookedTime");
        $diff = abs($requestedTimeTs - $bookedTimeTs);
        
        // 40 minutes = 2400 seconds
        if ($diff < 2400) { 
            $conflict = true;
            $conflictTime = $bookedTimeTs;
            break;
        }
    }

    if ($conflict) {
        // 3. Current slot is invalid. Find next/previous valid slots.
        // Simple logic: Suggest time after the conflict booking + 40 mins
        
        $suggestion1 = date('H:i', $conflictTime + 2400); // +40 mins
        
        $msg = "Sorry, the slot at " . date('g:i A', $requestedTimeTs) . " is no longer available. Someone else booked it first.";
        $suggestionMsg = "How about " . date('g:i A', strtotime($suggestion1)) . "? That time slot works better.";

        echo json_encode([
            'status' => 'conflict',
            'message' => $msg,
            'suggestion' => $suggestionMsg,
            'suggested_time' => $suggestion1
        ]);
    } else {
        // 4. Slot is available
        echo json_encode([
            'status' => 'available', 
            'message' => "Great! The slot at " . date('g:i A', $requestedTimeTs) . " is available. You can proceed."
        ]);
    }

} catch (Exception $e) {
    error_log("Availability Check Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error check log']);
}
?>
