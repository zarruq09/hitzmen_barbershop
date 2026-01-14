<?php
require_once __DIR__ . '/../libs/google-api-php-client/vendor/autoload.php';

$googleClient = new Google_Client();
$googleClient->setClientId('YOUR_GOOGLE_CLIENT_ID');
$googleClient->setClientSecret('YOUR_GOOGLE_CLIENT_SECRET');

// Dynamic Redirect URI Detection
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'];
$path = '/hitzmen_barbershop'; // Default XAMPP path

// Adjust path for Live Server if it's at root or different subfolder
// Assuming Live Server runs at root or user can modify this.
// Determining environment:
$whitelist_local = array('127.0.0.1', '::1', 'localhost');

if (in_array($domain, $whitelist_local)) {
    // Localhost
    $redirect_uri = "http://localhost/hitzmen_barbershop/google-auth.php";
} else {
    // Live Server (Hostinger)
    // Automatically construct URL. NOTE: Ensure this matches what you set in Google Console.
    // If your site is "https://hitzmenbarber.com", this becomes "https://hitzmenbarber.com/google-auth.php"
    // If currently hosted in a subfolder like 'public_html', adjust accordingly via user input if needed.
    // For now, assuming root domain or correct relative path.
    
    // We'll trust the current protocol/host.
    // Clean up path: remove 'index.php' or query params if present in PHP_SELF logic, 
    // but here we just need the domain + filename usually.
    
    $redirect_uri = "$protocol://$domain/google-auth.php";
}

$googleClient->setRedirectUri($redirect_uri);

$googleClient->addScope('email');
$googleClient->addScope('profile');
// $googleClient->addScope('https://www.googleapis.com/auth/calendar'); 
$googleClient->setAccessType('offline'); 
$googleClient->setPrompt('consent'); 
?>
