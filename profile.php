<?php
session_start();
require_once __DIR__ . '/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);

    if (empty($full_name)) {
        $_SESSION['error_msg'] = "Full Name is required.";
    } elseif (empty($username)) {
        $_SESSION['error_msg'] = "Username cannot be empty.";
    } elseif (empty($phone)) {
        $_SESSION['error_msg'] = "Phone Number is required.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $_SESSION['error_msg'] = "Invalid phone number. Use 10-11 digits.";
    } else {
        try {
            // Check for duplicate username (excluding self)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username, $user_id]);
            if ($stmtCheck->fetchColumn() > 0) {
                $_SESSION['error_msg'] = "Sorry, the username '$username' is already taken. Please try another one.";
            } else {
                // Update Profile
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, phone = ? WHERE id = ?");
                if ($stmt->execute([$full_name, $username, $phone, $user_id])) {
                    $_SESSION['success_msg'] = "Profile updated successfully!";
                    
                    // Update session variables
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['username'] = $username;
                } else {
                    $_SESSION['error_msg'] = "Failed to update profile.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Database error: " . $e->getMessage();
        }
    }
}

// Redirect back to edit profile in dashboard
header('Location: dashboard.php?view=edit_profile');
exit();
?>
