<?php
session_start();
require '../db.php';
require '../includes/csrf_token.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: ../dashboard.php?error=csrf_failed');
        exit();
    }
}

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

header('Location: ../dashboard.php');
exit();
?>
