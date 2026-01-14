<?php
/**
 * Database Connection Script
 * Automatically detects environment (Localhost vs Hostinger)
 */

date_default_timezone_set('Asia/Kuala_Lumpur');

$host = 'localhost'; // Usually 'localhost' for both XAMPP and Hostinger
$charset = 'utf8mb4';

// Check if we are running on local XAMPP or Live Server
$whitelist_local = array('127.0.0.1', '::1', 'localhost');
$is_cli = (php_sapi_name() === 'cli');

if ($is_cli || (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $whitelist_local))) {
    // --- LOCALHOST (XAMPP) ---
    $db   = 'hitzmen_barbershop';
    $user = 'root';
    $pass = '';
} else {
    // --- LIVE SERVER (HOSTINGER) ---
    // User provided credentials:
    // DB: hitzmen
    // User: hitzmen
    // Pass: Zarruq09
    
    // NOTE: On shared hosting, database and user often have a prefix (e.g., u123456789_hitzmen)
    // If this fails, check the actual Database Name in Hostinger Dashboard.
    $db   = 'u759566405_hitzmen'; 
    $user = 'u759566405_hitzmen';
    $pass = 'Zarruq09';
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Determine detailed error message visibility
    if (in_array($_SERVER['HTTP_HOST'], $whitelist_local)) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    } else {
        // Simple error for live site to avoid leaking sensitive info, 
        // but including message for now to help you debug newly deployed site.
        die("<h3>Database Connection Failed</h3><p>Verify your Database Name, Username, and Password in <code>db.php</code>.</p><p>Error: " . $e->getMessage() . "</p>");
    }
}
?>