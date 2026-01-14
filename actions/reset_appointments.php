<?php
session_start();
require '../db.php';

// Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

try {
    // Disable foreign key checks to allow truncation if there are constraints (though usually appointments are the child)
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // TRUNCATE is faster and resets auto-increment
    $pdo->exec("TRUNCATE TABLE appointments");
    
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    header('Location: ../admin_dashboard.php?success=appointments_cleared&tab=admin_dashboard');
    exit();
} catch (PDOException $e) {
    error_log("Error clearing appointments: " . $e->getMessage());
    header('Location: ../admin_dashboard.php?error=db_error&message=' . urlencode($e->getMessage()));
    exit();
}
?>
