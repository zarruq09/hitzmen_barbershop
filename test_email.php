<?php
require_once 'vendor/autoload.php';
require_once 'includes/email_functions.php';

echo "Attempting to create mailer...\n";

try {
    $config = require 'config/mail.php';
    echo "Config loaded. Host: " . $config['host'] . "\n";
    echo "Username: " . $config['username'] . "\n";
    // echo "Password: " . $config['password'] . "\n"; // Don't expose password

    $toEmail = 'zarruq09@gmail.com'; // Send to self to test
    $toName = 'Test User';
    $bookingDetails = [
        'date' => date('Y-m-d'),
        'time' => date('H:i'),
        'barber' => 'Test Barber',
        'services' => ['Test Service'],
        'price' => '15.00'
    ];

    echo "Sending email to $toEmail...\n";
    if (sendBookingConfirmation($toEmail, $toName, $bookingDetails)) {
        echo "SUCCESS: Email sent successfully.\n";
    } else {
        echo "FAILURE: Email returned false (check error_log).\n";
    }

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
