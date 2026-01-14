<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// 2. Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header('Location: ../dashboard.php?view=history');
    exit();
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;
$barber_id = $_POST['barber_id'] ?? null; // Can be null (or empty string) if not selected in JS, but usually passed
$shop_rating = (int)($_POST['shop_rating'] ?? 0);
$service_rating = (int)($_POST['service_rating'] ?? 0);
$staff_rating = (int)($_POST['staff_rating'] ?? 0);
$comments = trim($_POST['comments'] ?? '');

// 3. Validation
if (!$appointment_id || $shop_rating < 1 || $shop_rating > 5 || $service_rating < 1 || $service_rating > 5 || $staff_rating < 1 || $staff_rating > 5) {
    $_SESSION['error_message'] = "Please provide a valid rating (1-5 stars) for all categories.";
    header('Location: ../dashboard.php?view=history');
    exit();
}

try {
    // 4. Verify Appointment Ownership & Status
    // Must be 'Completed' and belong to this user.
    $stmtCheck = $pdo->prepare("SELECT id, status, barber_id FROM appointments WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$appointment_id, $user_id]);
    $appt = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        $_SESSION['error_message'] = "Appointment not found or access denied.";
        header('Location: ../dashboard.php?view=history');
        exit();
    }

    if ($appt['status'] !== 'Completed') {
         // Security: Only completed appointments can be reviewed
         $_SESSION['error_message'] = "You can only rate completed appointments.";
         header('Location: ../dashboard.php?view=history');
         exit();
    }
    
    // Ensure accurate barber_id from DB record, not just trust POST if possible, 
    // but the modal might pass it. Safest is to use the one from DB check above.
    $real_barber_id = $appt['barber_id'];

    // 5. Check if already rated
    $stmtExists = $pdo->prepare("SELECT id FROM feedback WHERE appointment_id = ?");
    $stmtExists->execute([$appointment_id]);
    if ($stmtExists->fetch()) {
        $_SESSION['error_message'] = "You have already rated this appointment.";
        header('Location: ../dashboard.php?view=history');
        exit();
    }

    // 6. Insert Feedback
    $stmtInsert = $pdo->prepare("
        INSERT INTO feedback (appointment_id, user_id, barber_id, shop_rating, service_rating, staff_rating, comments)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $appointment_id,
        $user_id,
        $real_barber_id,
        $shop_rating,
        $service_rating,
        $staff_rating,
        $comments
    ]);

    $_SESSION['success_message'] = "Thanks for the vibe check! Your feedback helps us level up. 🔥";
    header('Location: ../dashboard.php?view=history');
    exit();

} catch (PDOException $e) {
    // Log error in real app
    $_SESSION['error_message'] = "Database error: Failed to submit feedback.";
    header('Location: ../dashboard.php?view=history');
    exit();
}
?>
