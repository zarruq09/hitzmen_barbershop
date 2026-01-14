<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../db.php';
require_once '../includes/email_functions.php';
require_once '../includes/csrf_token.php';

// Only allow customers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = $_POST['appointment_time'] ?? '';
    $barberId = filter_var($_POST['barber_id'] ?? null, FILTER_VALIDATE_INT);
    $selectedServiceIds = $_POST['services'] ?? []; 
    $selectedHaircutId = !empty($_POST['haircut_id']) ? filter_var($_POST['haircut_id'], FILTER_VALIDATE_INT) : null;
    $notes = htmlspecialchars($_POST['notes'] ?? '');
    
    // Redirect logic
    $redirectTo = $_POST['redirect_to'] ?? '../dashboard.php?view=history';
    $errorRedirect = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php?view=booking';

    // 1. CSRF Verification
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Security check failed (CSRF). Please try again.";
        header("Location: $errorRedirect");
        exit();
    }

    // Basic validation
    if (empty($appointmentDate) || empty($appointmentTime) || !$barberId || empty($selectedServiceIds)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: $errorRedirect");
        exit();
    }

    // Prevent past bookings
    $currentTimestamp = time();
    $bookingTimestamp = strtotime("$appointmentDate $appointmentTime");
    
    if ($bookingTimestamp < $currentTimestamp) {
        $_SESSION['error_message'] = "Unable to book: The selected time has already passed.";
        header("Location: $errorRedirect");
        exit();
    }

    // Check for Wednesday (Shop Closed)
    $dayOfWeek = date('N', strtotime($appointmentDate)); // 1 (Mon) to 7 (Sun)
    
    if ($dayOfWeek == 3) {
        $_SESSION['error_message'] = "Sorry, the shop is closed on Wednesdays. Please choose another date.";
        header("Location: $errorRedirect");
        exit();
    }

    // Check Operating Hours
    $bookingTime = strtotime($appointmentTime);
    
    // Friday (5): 3:00 PM - 11:00 PM
    if ($dayOfWeek == 5) {
        $startTime = strtotime('15:00'); // 3:00 PM
        $endTime = strtotime('23:00');   // 11:00 PM
        
        if ($bookingTime < $startTime || $bookingTime > $endTime) {
             $_SESSION['error_message'] = "On Fridays, the shop operates from 3:00 PM to 11:00 PM.";
             header("Location: $errorRedirect");
             exit();
        }
    } 
    // Other Days (Mon, Tue, Thu, Sat, Sun): 11:00 AM - 11:00 PM
    else {
        $startTime = strtotime('11:00'); // 11:00 AM
        $endTime = strtotime('23:00');   // 11:00 PM
        
        if ($bookingTime < $startTime || $bookingTime > $endTime) {
             $_SESSION['error_message'] = "The shop operates from 11:00 AM to 11:00 PM.";
             header("Location: $errorRedirect");
             exit();
        }
    }

    try {
        // Start Transaction
        $pdo->beginTransaction();

        // Check Barber Availability with LOCK
        $stmtCheckBarber = $pdo->prepare("SELECT status, name FROM barbers WHERE id = ? FOR UPDATE");
        $stmtCheckBarber->execute([$barberId]);
        $barber = $stmtCheckBarber->fetch(PDO::FETCH_ASSOC);

        if (!$barber || $barber['status'] === 'Deleted') {
            throw new Exception("The selected barber is currently unavailable.");
        }
        $barberName = $barber['name'];

        // Check Schedule & LOCK
        $stmtUID = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtUID->execute([$barberName]);
        $staffUserId = $stmtUID->fetchColumn();

        if ($staffUserId) {
            $stmtSched = $pdo->prepare("SELECT status FROM schedules WHERE user_id = ? AND date = ? FOR UPDATE");
            $stmtSched->execute([$staffUserId, $appointmentDate]);
            $scheduleStatus = $stmtSched->fetchColumn();

            if ($scheduleStatus === 'off' || $scheduleStatus === 'rest') {
                throw new Exception("The selected barber is not available on this date.");
            }
        }
        
        // Check for double booking
        $stmtCollision = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('Pending', 'Confirmed') FOR UPDATE");
        $stmtCollision->execute([$barberId, $appointmentDate, $appointmentTime]);
        if ($stmtCollision->fetchColumn() > 0) {
            throw new Exception("This time slot has just been booked by another user. Please choose another time.");
        }

        // Fetch Services
        $servicesStmt = $pdo->query("SELECT id, service_name, price FROM services");
        $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalPrice = 0;
        $selectedServiceNames = [];
        $requiresHaircutSelection = false;
        
        $haircutServiceIds = [];
        foreach ($services as $svc) {
             if (stripos($svc['service_name'], 'haircut') !== false || stripos($svc['service_name'], 'cut') !== false || stripos($svc['service_name'], 'shampoo') !== false) {
                $haircutServiceIds[] = $svc['id'];
            }
        }

        foreach ($selectedServiceIds as $sId) {
            foreach ($services as $svc) {
                if ($svc['id'] == $sId) {
                    $totalPrice += $svc['price'];
                    $selectedServiceNames[] = $svc['service_name'];
                    if (in_array($svc['id'], $haircutServiceIds)) {
                        $requiresHaircutSelection = true;
                    }
                    break;
                }
            }
        }

        if ($requiresHaircutSelection && !$selectedHaircutId) {
            throw new Exception("You selected a haircut service, please choose a style.");
        }

        // Get Haircut Name
        if ($selectedHaircutId) {
             $stmtHaircut = $pdo->prepare("SELECT style_name FROM haircuts WHERE id = ?");
             $stmtHaircut->execute([$selectedHaircutId]);
             $selectedHaircutStyle = $stmtHaircut->fetchColumn();
        }

        // Insert Appointment
        $servicesJson = json_encode($selectedServiceIds);
        
        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, barber_id, appointment_date, appointment_time, services_ids_json, haircut_id, total_price, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $success = $stmt->execute([$userId, $barberId, $appointmentDate, $appointmentTime, $servicesJson, $selectedHaircutId, $totalPrice, $notes]);

        if (!$success) {
            throw new Exception("Failed to save booking.");
        }

        // Commit Transaction
        $pdo->commit();

        try {
            // Fetch User Details for Email
            $stmtUser = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                // Send Email Notification
                $bookingDetails = [
                    'date' => $appointmentDate,
                    'time' => $appointmentTime,
                    'barber' => $barberName,
                    'services' => $selectedServiceNames,
                    'price' => number_format($totalPrice, 2)
                ];
                sendBookingConfirmation($userData['email'], $userData['full_name'], $bookingDetails);
            }
        } catch (Throwable $emailEx) {
            // Suppress email errors so we don't confuse the user if booking succeeded
            error_log("Email sending failed: " . $emailEx->getMessage());
        }

        $_SESSION['success_message'] = "Booking Confirmed! $appointmentDate @ $appointmentTime with $barberName.";
        $_SESSION['last_booking'] = [
            'date' => $appointmentDate,
            'time' => $appointmentTime,
            'barber' => $barberName,
            'service_names' => $selectedServiceNames
        ];
        header("Location: $redirectTo");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Booking Process Error: " . $e->getMessage());
        header("Location: $errorRedirect");
        exit();
    }

} else {
    // Not a POST request
    header('Location: ../dashboard.php');
}
?>
