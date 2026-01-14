<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming Composer autoloader is available.
// If not, we might need to include PHPMailer manually if installed via zip, 
// but user has composer.json indicating it might be manageable via composer.
// If vendor/autoload.php exists, we use it.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function getMailConfig() {
    return require __DIR__ . '/../config/mail.php';
}

function sendBookingConfirmation($toEmail, $toName, $bookingDetails) {
    $config = getMailConfig();
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];

        //Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($toEmail, $toName);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - Hitzmen Barbershop';
        
        $body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-top: 5px solid #C5A059;'>
            <div style='padding: 20px; text-align: center; background-color: #121212;'>
                <h2 style='color: #C5A059; margin: 0;'>Booking Confirmed</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Hi <strong>$toName</strong>,</p>
                <p>Your appointment has been successfully booked. We look forward to seeing you!</p>
                
                <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Date:</strong> {$bookingDetails['date']}</p>
                    <p style='margin: 5px 0;'><strong>Time:</strong> {$bookingDetails['time']}</p>
                    <p style='margin: 5px 0;'><strong>Barber:</strong> {$bookingDetails['barber']}</p>
                    <p style='margin: 5px 0;'><strong>Services:</strong> " . implode(', ', $bookingDetails['services']) . "</p>
                    <p style='margin: 5px 0;'><strong>Total Price:</strong> RM {$bookingDetails['price']}</p>
                </div>

                <p style='font-size: 12px; color: #777;'>If you need to cancel or reschedule, please log in to your dashboard.</p>
            </div>
            <div style='background-color: #eee; padding: 10px; text-align: center; font-size: 11px; color: #666;'>
                &copy; " . date('Y') . " Hitzmen Barbershop
            </div>
        </div>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Booking Confirmed\nDate: {$bookingDetails['date']}\nTime: {$bookingDetails['time']}\nBarber: {$bookingDetails['barber']}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error but don't break the app
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
