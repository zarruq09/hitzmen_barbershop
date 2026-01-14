<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_token.php';
require_once __DIR__ . '/includes/auth_functions.php';

redirectIfLoggedIn();

$token = $_GET['token'] ?? '';
$validToken = false;
$msg = '';

if (!empty($token)) {
    try {
        // 1. Get user & current DB time to be safe
        $stmt = $pdo->prepare("SELECT id, reset_expiry, NOW() as db_now FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 2. Manual Check expiry (DB Time check)
            if (strtotime($user['reset_expiry']) > strtotime($user['db_now'])) {
                $validToken = true;
            } else {
                $msg = 'This link is no longer valid. It may have expired, or a newer link has been requested. Please use the most recent link sent to your email.';
            }
        } else {
            $msg = 'Invalid usage or link not found.';
        }
    } catch (PDOException $e) {
        $msg = 'Database error.';
    }
} else {
    $msg = 'Missing token. Invalid link.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Hitzmen Barbershop</title>
    <link rel="icon" type="image/x-icon" href="assets/images/Logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { gold: '#C5A059', dark: '#121212', card: '#1E1E1E', cardBorder: '#333333' },
                    fontFamily: { heading: ['Montserrat', 'sans-serif'], sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-dark text-gray-100 font-sans min-h-screen flex flex-col justify-center items-center py-10 relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-dark opacity-90 z-10"></div>
        <img src="assets/images/background.jpg" class="w-full h-full object-cover">
    </div>

    <div class="z-10 w-full max-w-sm p-4 animate-fade-in">
        <div class="bg-card border border-cardBorder rounded-xl shadow-2xl p-6 relative">
            <div class="text-center mb-6">
                <i class="fas fa-key text-4xl text-gold mb-3"></i>
                <h1 class="text-2xl font-bold font-heading text-white">New Password</h1>
                <p class="text-gray-400 text-xs mt-1">Set a secure new password.</p>
            </div>

            <?php if (!$validToken): ?>
                <div class="bg-red-900/30 border-l-4 border-red-500 text-red-200 px-4 py-3 rounded mb-4 text-sm text-center">
                    <i class="fas fa-exclamation-circle mb-1 block text-xl"></i>
                    <?php echo htmlspecialchars($msg); ?>
                    <div class="mt-3">
                        <a href="forgot_password.php" class="text-white underline font-bold">Try Again</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="resetForm" action="actions/update_password.php" method="POST" class="space-y-4">
                    <?php csrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">NEW PASSWORD</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required placeholder="********" class="w-full bg-[#121212] border border-cardBorder text-white text-sm rounded-lg px-4 py-2.5 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition-colors">
                        </div>
                    </div>
                    <div>
                         <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">CONFIRM PASSWORD</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="********" class="w-full bg-[#121212] border border-cardBorder text-white text-sm rounded-lg px-4 py-2.5 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition-colors">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gold hover:bg-[#D4AF37] text-dark py-2.5 rounded-lg font-bold shadow-lg mt-2 transition-all">
                        Update Password
                    </button>
                </form>

                <script>
                    document.getElementById('resetForm').addEventListener('submit', function(e) {
                         const password = document.getElementById('password').value;
                         const confirmPassword = document.getElementById('confirm_password').value;

                         if (password.length < 8) {
                             e.preventDefault();
                             alert("Password must be at least 8 characters.");
                             return false;
                         }

                         if (password !== confirmPassword) {
                             e.preventDefault();
                             alert("Passwords do not match.");
                             return false;
                         }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
