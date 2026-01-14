<?php
require_once __DIR__ . '/config/google_config.php';
require_once __DIR__ . '/includes/auth_functions.php';

if (isset($_GET['code'])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $googleClient->setAccessToken($token['access_token']);
        $googleService = new Google_Service_Oauth2($googleClient);
        $data = $googleService->userinfo->get();
        
        // Check if user already exists
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
        $stmt->execute([$data['id'], $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // User exists
            // 1. Update Google ID if missing
            if (empty($user['google_id'])) {
                $stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $stmt->execute([$data['id'], $user['id']]);
            }

            // 2. SELF-CORRECTION: Fix missing role if empty (prevents redirect loop)
            if (empty($user['role'])) {
                $stmt = $pdo->prepare("UPDATE users SET role = 'customer' WHERE id = ?");
                $stmt->execute([$user['id']]);
                $user['role'] = 'customer'; // Update local variable for session
            }
            
            // Try to store tokens for calendar access (if columns exist)
            try {
                $accessToken = json_encode($token);
                $refreshToken = $token['refresh_token'] ?? null;
                $expiresAt = date('Y-m-d H:i:s', time() + ($token['expires_in'] ?? 3600));
                
                $stmt = $pdo->prepare("UPDATE users SET google_access_token = ?, google_refresh_token = ?, token_expires_at = ? WHERE id = ?");
                $stmt->execute([$accessToken, $refreshToken, $expiresAt, $user['id']]);
            } catch (PDOException $e) {
                // Calendar columns don't exist yet - that's okay, login will still work
                error_log("Calendar token storage failed (run database migration): " . $e->getMessage());
            }
        } else {
            // Register new user
            // NEW: Clean username generation logic
            $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['givenName'] . $data['familyName']));
            // Fallback to email prefix if name is empty
            if (empty($baseUsername)) {
                $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $data['email'])[0]);
            }
            
            $username = $baseUsername;
            $counter = 1;
            
            // Ensure uniqueness
            while (true) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmtCheck->execute([$username]);
                if ($stmtCheck->fetchColumn() == 0) {
                    break;
                }
                $username = $baseUsername . $counter;
                $counter++;
            }
            $full_name = $data['givenName'] . ' ' . $data['familyName'];
            $email = $data['email'];
            $google_id = $data['id'];
            
            // Try with calendar fields first, fall back to basic fields if migration not run
            try {
                $accessToken = json_encode($token);
                $refreshToken = $token['refresh_token'] ?? null;
                $expiresAt = date('Y-m-d H:i:s', time() + ($token['expires_in'] ?? 3600));
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, full_name, google_access_token, google_refresh_token, token_expires_at, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'customer')");
                $stmt->execute([$username, $email, $google_id, $full_name, $accessToken, $refreshToken, $expiresAt]);
            } catch (PDOException $e) {
                // Calendar columns don't exist - use basic insert
                error_log("Falling back to basic user creation: " . $e->getMessage());
                $stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
                $stmt->execute([$username, $email, $google_id, $full_name]);
            }
            
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Login the user
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        // Ensure role is never empty string
        $_SESSION['role'] = !empty($user['role']) ? $user['role'] : 'customer';
        
        header('Location: dashboard.php');
        exit();
    } else {
        // OAuth error occurred
        error_log("Google OAuth error: " . print_r($token, true));
        $_SESSION['error'] = "Google login failed. Please try again.";
        header('Location: login.php');
        exit();
    }
}

header('Location: login.php');
exit();