<?php
require_once __DIR__ . '/db.php';

try {
    $pdo->exec("ALTER TABLE appointments ADD COLUMN rejection_reason TEXT NULL AFTER status");
    echo "Column 'rejection_reason' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'rejection_reason' already exists.";
    } else {
        echo "Error adding column: " . $e->getMessage();
    }
}
?>
