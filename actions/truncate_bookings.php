<?php
require_once '../db.php';

try {
    $stmt = $pdo->prepare("TRUNCATE TABLE appointments");
    $stmt->execute();
    echo "SEMUA BOOKING DAH KOSONG!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
