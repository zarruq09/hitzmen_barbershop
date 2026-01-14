<?php
session_start();
require '../db.php';

// Check if user is admin or staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
         echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
         exit;
    }
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

    require_once '../includes/csrf_token.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
         if($isAjax) { echo json_encode(['success'=>false, 'message'=>'Security Token Invalid']); exit; }
         header('Location: ../index.php'); // Or appropriate error page
         exit();
    }

    if ($appointment_id && $status) {
        
        // Security check for Staff: properly linked barber only
        if ($_SESSION['role'] === 'staff') {
            try {
                // Get barber_id for this user
                $stmtBarber = $pdo->prepare("SELECT id FROM barbers WHERE user_id = ?");
                $stmtBarber->execute([$_SESSION['user_id']]);
                $barberId = $stmtBarber->fetchColumn();

                if (!$barberId) {
                    if($isAjax) { echo json_encode(['success'=>false, 'message'=>'No linked barber profile']); exit; }
                    header('Location: ../staff_dashboard.php?error=no_profile'); exit();
                }

                // Verify appointment belongs to this barber
                $stmtCheck = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND barber_id = ?");
                $stmtCheck->execute([$appointment_id, $barberId]);
                if ($stmtCheck->rowCount() == 0) {
                     if($isAjax) { echo json_encode(['success'=>false, 'message'=>'Access denied for this appointment']); exit; }
                     header('Location: ../staff_dashboard.php?error=access_denied'); exit();
                }
            } catch (PDOException $e) {
                if($isAjax) { echo json_encode(['success'=>false, 'message'=>'DB Error']); exit; }
                header('Location: ../staff_dashboard.php?error=db_error'); exit();
            }
        }
        
        // Validate status
        $allowed_statuses = ['Confirmed', 'Cancelled', 'Pending', 'Completed', 'No Show'];
        if (!in_array($status, $allowed_statuses)) {
             if($isAjax) { echo json_encode(['success'=>false, 'message'=>'Invalid status']); exit; }
             header('Location: ../admin_dashboard.php?page=view_booking&error=invalid_status');
             exit();
        }

        try {
            $reason = $_POST['rejection_reason'] ?? null;
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $reason, $appointment_id]);
            
            // --- NOTIFICATION LOGIC ---
            // 1. Get User ID associated with this appointment
            $stmtUser = $pdo->prepare("SELECT user_id, appointment_date, appointment_time FROM appointments WHERE id = ?");
            $stmtUser->execute([$appointment_id]);
            $apptData = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($apptData) {
                $uId = $apptData['user_id'];
                $dateStr = date('d M', strtotime($apptData['appointment_date']));
                $timeStr = date('h:i A', strtotime($apptData['appointment_time']));
                
                $notifMsg = "";
                $notifType = "info";

                if ($status === 'Confirmed') {
                    $notifMsg = "Booking anda pada $dateStr ($timeStr) telah DITERIMA! 🎉";
                    $notifType = "success";
                } elseif ($status === 'Cancelled') {
                    $notifMsg = "Maaf, booking pada $dateStr ($timeStr) telah DIBATALKAN. Sila check history. ❌";
                    $notifType = "error";
                } elseif ($status === 'Completed') {
                     $notifMsg = "Terima kasih! Sesi gunting rambut pada $dateStr telah selesai. ✨";
                     $notifType = "success";
                }
                 elseif ($status === 'No Show') {
                     $notifMsg = "Anda tidak hadir untuk booking pada $dateStr ($timeStr). Sila hubungi kami. ⚠️";
                     $notifType = "error";
                }

                if (!empty($notifMsg)) {
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
                    $stmtNotif->execute([$uId, $notifMsg, $notifType]);
                }
            }
            // --------------------------
            
            if ($isAjax) {
                echo json_encode(['success' => true]);
                exit;
            }

            // Redirect based on role
             if ($_SESSION['role'] === 'staff') {
                header('Location: ../staff_dashboard.php?success=updated');
            } else {
                header('Location: ../admin_dashboard.php?page=view_booking&success=status_updated');
            }
            exit();
        } catch (PDOException $e) {
            error_log("Error updating appointment status: " . $e->getMessage());
            if ($isAjax) { echo json_encode(['success'=>false, 'message'=>'DB Error']); exit; }
            header('Location: ../admin_dashboard.php?page=view_booking&error=db_error');
            exit();
        }
    } else {
        if ($isAjax) { echo json_encode(['success'=>false, 'message'=>'Missing data']); exit; }
        header('Location: ../admin_dashboard.php?page=view_booking&error=missing_data');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
