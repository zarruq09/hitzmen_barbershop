<?php
session_start();
require '../db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    
    if (!$id) {
        header('Location: ../admin_dashboard.php?error=invalid_id&tab=barbers');
        exit();
    }

    try {
        // Soft delete: Update status to 'Deleted' so we don't break appointment history
        $stmt = $pdo->prepare("UPDATE barbers SET status = 'Deleted' WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../admin_dashboard.php?success=barber_deleted&tab=barbers');
        exit();
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        header('Location: ../admin_dashboard.php?error=db_error&tab=barbers');
        exit();
    }
} else {
    header('Location: ../admin_dashboard.php?error=invalid_request&tab=barbers');
    exit();
}