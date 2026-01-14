<?php
session_start();
require_once __DIR__ . '/../db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;

    if ($appointment_id) {
        try {
            // Check if the appointment belongs to the user AND is cancellable
            // (Pending or Confirmed). We don't want them cancelling 'Completed' or already 'Cancelled' ones repeatedly.
            $checkStmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$appointment_id, $user_id]);
            $appt = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($appt) {
                if (in_array($appt['status'], ['Pending', 'Confirmed'])) {
                    // Update to Cancelled
                    $updateStmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
                    $updateStmt->execute([$appointment_id]);

                    $_SESSION['success_message'] = 'Appointment cancelled successfully.';
                    header('Location: ../dashboard.php?view=history');
                    exit();
                } else {
                    // Appointment exists but status is not cancellable (e.g., already completed)
                    $_SESSION['error_message'] = 'Cannot cancel this appointment.';
                    header('Location: ../dashboard.php?view=history');
                    exit();
                }
            } else {
                // Appointment does not exist or does not belong to user
                $_SESSION['error_message'] = 'Invalid appointment.';
                header('Location: ../dashboard.php?view=history');
                exit();
            }

        } catch (PDOException $e) {
            error_log("Error cancelling appointment: " . $e->getMessage());
            $_SESSION['error_message'] = 'Database error.';
            header('Location: ../dashboard.php?view=history');
            exit();
        }
    } else {
        $_SESSION['error_message'] = 'Missing appointment ID.';
        header('Location: ../dashboard.php?view=history');
        exit();
    }
} else {
    // If accessed directly without POST
    header('Location: ../dashboard.php?view=history');
    exit();
}
