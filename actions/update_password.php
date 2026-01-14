<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf_token.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Security token expired. Please try again.");
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($password !== $confirm) {
    die("Passwords do not match.");
}

if (strlen($password) < 8) {
    die("Password must be at least 8 characters.");
}

try {
    // Verify token again just in case
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        $update->execute([$hashed, $user['id']]);

        // Set session flash message
        $_SESSION['success_msg'] = "Password successfully updated! You can now login.";
        header("Location: ../index.php");
        exit;
    } else {
        die("Invalid or expired token.");
    }

} catch (PDOException $e) {
    die("Database error.");
}
?>
