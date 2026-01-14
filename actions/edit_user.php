<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $stmt->execute([$username, $role, $id]);

    header('Location: ../admin_dashboard.php?page=manage_users');
    exit();
}
?>