<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    require_once '../includes/csrf_token.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Security check failed. Please refresh and try again.";
        header("Location: ../admin_dashboard.php?page=manage_users");
        exit();
    }
    $id = $_POST['user_id'] ?? '';
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($email)) {
        try {
            if (!empty($id)) {
                // UPDATE
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $id]);
                }
                $_SESSION['success'] = "User updated successfully!";
            } else {
                // INSERT
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $role, $hashed]);
                    $_SESSION['success'] = "User added successfully!";
                } else {
                    $_SESSION['error'] = "Password is required for new users.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: Email or Username already exists. " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }
}

// Redirect back to the dashboard context
header("Location: ../admin_dashboard.php?page=manage_users");
exit();
