<?php
/*-------------------------------------------------
  config.php  –  central DB connection
--------------------------------------------------*/
$DB_HOST = 'localhost';
$DB_NAME = 'hitzmen_barbershop';
$DB_USER = 'root';
$DB_PASS = '';

/* ───── 1. MySQLi (for legacy code) ───── */
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('MySQLi connection failed: ' . $conn->connect_error);
}

/* ───── 2. PDO  (recommended for new code) ───── */
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('PDO connection failed: ' . $e->getMessage());
}
