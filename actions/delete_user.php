<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    require_once '../includes/csrf_token.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Security check failed. Please try again.";
        header("Location: ../admin_dashboard.php?page=manage_users");
        exit();
    }
    $id_to_delete = $_POST['id'];

    if ($id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $_SESSION['success'] = "User deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Redirect back to the dashboard context
header("Location: ../admin_dashboard.php?page=manage_users");
exit();
