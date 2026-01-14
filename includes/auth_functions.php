<?php
require_once __DIR__ . '/../config/database.php';

function registerUser($username, $email, $password, $full_name, $phone, $role = 'customer') {
    global $pdo;

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        return false; // Username or email already exists
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $role]);
}

function loginUser($username, $password, $desired_role) {
    global $pdo; // Assuming $pdo is available globally from your database connection setup

    // Determine if the input username is an email or a username
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM users WHERE email = ? AND role = ?";
    } else {
        $sql = "SELECT * FROM users WHERE username = ? AND role = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $desired_role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        return $user; // Return the full user array
    }
    return false; // Login failed
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        // You might want to redirect based on role here too
        switch ($_SESSION['role']) {
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            case 'staff':
                header('Location: staff_dashboard.php');
                break;
            case 'customer':
            default:
                header('Location: dashboard.php');
                break;
        }
        exit();
    }
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerGoogleUser($username, $email, $google_id, $full_name, $phone = null, $role = 'customer') {
    global $pdo;

    // Optional: Ensure username is unique if generated simply
    $original_username = $username;
    $i = 0;
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() == 0) {
            break; // Username is unique
        }
        $i++;
        $username = $original_username . $i; // Append number if not unique
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $google_id, $full_name, $phone, $role])) {
        return $pdo->lastInsertId();
    }
    return false;
}
?>