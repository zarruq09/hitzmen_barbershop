<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/db.php';

// Only allow customers
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] !== 'customer') {
        $_SESSION['error_message'] = "Access denied.";
        header('Location: index.php');
        exit();
    }
}
redirectIfNotLoggedIn();

$fullName = htmlspecialchars($_SESSION['full_name'] ?? '');
$username = htmlspecialchars($_SESSION['username'] ?? '');
$email = htmlspecialchars($_SESSION['email'] ?? '');
$userId = $_SESSION['user_id'] ?? null;

// Fetch Data Using Helper
require_once __DIR__ . '/includes/booking_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | Hitzmen Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        'gold-light': '#D4AF37',
                        dark: '#121212',
                        'dark-card': '#1E1E1E',
                        'dark-hover': '#252525',
                        'dark-border': '#333333'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    },
                    backgroundImage: {
                        'gradient-gold': 'linear-gradient(135deg, #C5A059 0%, #E5C07B 100%)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #1a1a1a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #C5A059; }
        .glass-card { background: rgba(30, 30, 30, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(197, 160, 89, 0.1); }
        .service-checkbox:checked + span { color: #C5A059; }
        .service-checkbox:checked + span .price-tag { color: white; }
    </style>
</head>
<body class="bg-dark text-gray-100 font-sans min-h-[100dvh] flex flex-col relative overflow-x-hidden">
    <div class="fixed top-0 left-0 w-full h-full pointer-events-none z-0 overflow-hidden">
        <div class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-gold/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-10%] left-[-5%] w-96 h-96 bg-blue-900/10 rounded-full blur-3xl"></div>
    </div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8 md:py-12 flex-1 relative z-10">
        <?php include 'includes/booking_form_partial.php'; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
