<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Check if columns exist first to avoid errors on re-run
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    if ($stmt->fetch()) {
        echo "Column 'reset_token' already exists.\n";
    } else {
        $sql = "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER role";
        $pdo->exec($sql);
        echo "Column 'reset_token' added.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_expiry'");
    if ($stmt->fetch()) {
        echo "Column 'reset_expiry' already exists.\n";
    } else {
        $sql = "ALTER TABLE users ADD COLUMN reset_expiry DATETIME NULL AFTER reset_token";
        $pdo->exec($sql);
        echo "Column 'reset_expiry' added.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
