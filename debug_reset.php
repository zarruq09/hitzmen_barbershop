<?php
// debug_reset.php - Upload to hosting to debug
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';
date_default_timezone_set('Asia/Kuala_Lumpur'); // Force logic to KL time for display

echo "<html><body style='font-family:sans-serif; padding:20px;'>";
echo "<h2>Reset Password Debugger</h2>";
echo "<p><strong>PHP Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // 1. Check DB Time
    $stmt = $pdo->query("SELECT NOW() as db_time");
    $dbTime = $stmt->fetchColumn();
    echo "<p><strong>DB Time (NOW):</strong> " . $dbTime . "</p>";

    if (empty($token)) {
        echo "<p style='color:red;'>No token provided in URL. Add ?token=YOUR_TOKEN_HERE to the URL.</p>";
    } else {
        echo "<p><strong>Token Provided:</strong> " . htmlspecialchars($token) . " (Length: " . strlen($token) . ")</p>";

        // 2. Search for exact token
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<p style='color:green;'>✅ Token Found in DB!</p>";
            echo "<ul>";
            echo "<li>User ID: " . $user['id'] . "</li>";
            echo "<li>Username: " . $user['username'] . "</li>";
            echo "<li>Reset Expiry: " . $user['reset_expiry'] . "</li>";
            
            // Check expiry
            if (strtotime($user['reset_expiry']) > strtotime($dbTime)) {
                 echo "<li style='color:green;'>Token is VALID (Not expired).</li>";
            } else {
                 echo "<li style='color:red;'>Token is EXPIRED.</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:red;'>❌ Token NOT Found in DB.</p>";
            
            // 3. Debug Partial Check (Truncation?)
            $prefix = substr($token, 0, 10);
            echo "<p>Searching for tokens starting with '$prefix'...</p>";
            $stmt = $pdo->prepare("SELECT id, username, reset_token, reset_expiry FROM users WHERE reset_token LIKE ?");
            $stmt->execute([$prefix . '%']);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($matches) {
                echo "<p>Found similar tokens (Possible Truncation Issue?):</p>";
                echo "<ul>";
                foreach ($matches as $m) {
                    echo "<li>DB Token: " . $m['reset_token'] . " (Length: " . strlen($m['reset_token']) . ")<br>";
                    echo "Expiry: " . $m['reset_expiry'] . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No similar tokens found. Did you request a new link after this one?</p>";
            }
        }
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
echo "</body></html>";
?>
