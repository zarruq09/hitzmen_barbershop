<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf_token.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format. Please check again.']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate Token
        $token = bin2hex(random_bytes(32));
        
        // Save to DB using MySQL time to prevent timezone mismatch
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $update->execute([$token, $user['id']]);

        // Determine protocol
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];

        // Determine base path based on environment
        // If localhost, keep the subfolder for development
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
             $baseUrl = $protocol . $host . "/hitzmen_barbershop";
        } else {
             // Production: Assume domain points to root
             $baseUrl = $protocol . $host;
        }

        $resetLink = $baseUrl . "/reset_password.php?token=" . $token;

        $whitelist_local = array('127.0.0.1', '::1', 'localhost');
        
        if (in_array($_SERVER['HTTP_HOST'], $whitelist_local)) {
            // DEVELOPER MODE: Return link in JSON
            echo json_encode([
                'success' => true, 
                'message' => 'Reset link sent successfully! (DEV MODE active)',
                'debug_link' => $resetLink
            ]);
        } else {
            // PRODUCTION MODE: Send Email
            $to = $email;
            $subject = "Reset Your Password - Hitzmen Barbershop";
            $message = "
            <html>
            <head>
              <title>Reset Password</title>
            </head>
            <body>
              <h2>Forgot your password?</h2>
              <p>No worries! Click the link below to set a new password:</p>
              <p><a href='" . $resetLink . "'>Reset Password Now</a></p>
              <p>This link is valid for 1 hour only.</p>
              <br>
              <p>Hitzmen Barbershop Team</p>
            </body>
            </html>
            ";

            // Always set content-type when sending HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: <no-reply@hitzmenbarbershop.com>' . "\r\n";

            if(mail($to, $subject, $message, $headers)) {
                 echo json_encode([
                    'success' => true, 
                    'message' => 'Reset link sent to your email! Please check your inbox or spam folder.'
                ]);
            } else {
                 echo json_encode([
                    'success' => false, 
                    'message' => 'Server error: Unable to send email. Please contact the administrator.'
                ]);
            }
        }
    } else {
        // Generic message for security, or specific if lenient
        echo json_encode(['success' => false, 'message' => 'This email is not registered in our system.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error code 99. Try again later.']);
}
?>
