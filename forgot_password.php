<?php
session_start();
require_once __DIR__ . '/includes/csrf_token.php';
require_once __DIR__ . '/includes/auth_functions.php';

redirectIfLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Hitzmen Barbershop</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/Logo.png">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        dark: '#121212',
                        card: '#1E1E1E',
                        cardBorder: '#333333'
                    },
                    fontFamily: {
                        heading: ['Montserrat', 'sans-serif'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-dark text-gray-100 font-sans min-h-screen flex flex-col justify-center items-center py-10 relative overflow-hidden">
    
    <!-- Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-dark opacity-90 z-10"></div>
        <img src="assets/images/background.jpg" alt="Background" class="w-full h-full object-cover">
    </div>

    <div class="z-10 w-full max-w-sm p-4 animate-fade-in">
        <div class="bg-card border border-cardBorder rounded-xl shadow-2xl p-6 relative">
            
            <div class="text-center mb-6">
                <i class="fas fa-lock text-4xl text-gold mb-3"></i>
                <h1 class="text-2xl font-bold font-heading text-white">Reset Password</h1>
                <p class="text-gray-400 text-xs mt-1">Forgot your password? Please enter your email below.</p>
            </div>

            <div id="alert-container"></div>

            <form id="forgotPasswordForm" action="actions/send_reset_link.php" method="POST" class="space-y-4">
                <?php csrfField(); ?>
                
                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">YOUR EMAIL</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-3 text-gray-500 text-sm"></i>
                        <input type="email" name="email" required placeholder="name@example.com"
                               class="w-full bg-[#121212] border border-cardBorder text-white text-sm rounded-lg pl-10 pr-4 py-2.5 focus:border-gold focus:ring-1 focus:ring-gold outline-none placeholder-gray-600 transition-colors">
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                        class="w-full bg-gold hover:bg-[#D4AF37] text-dark py-2.5 rounded-lg font-bold tracking-wide shadow-lg transition-transform transform hover:-translate-y-0.5 btn-gold flex items-center justify-center gap-2">
                    <span>Send Reset Link</span>
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </form>

            <div class="mt-6 text-center border-t border-cardBorder pt-4">
                <a href="index.php" class="text-gold hover:text-white text-xs font-bold transition-colors flex items-center justify-center gap-1">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>

        </div>
        <div class="text-center mt-4 text-gray-600 text-[10px]">
             &copy; <?php echo date('Y'); ?> Hitzmen Barbershop
        </div>
    </div>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalContent = btn.innerHTML;
            const container = document.getElementById('alert-container');
            const formData = new FormData(this);

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            container.innerHTML = '';

            fetch('actions/send_reset_link.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalContent;

                let alertClass = data.success ? 'bg-green-900/30 border-green-500 text-green-200' : 'bg-red-900/30 border-red-500 text-red-200';
                let icon = data.success ? 'check-circle' : 'exclamation-circle';

                container.innerHTML = `
                    <div class="${alertClass} border-l-4 px-4 py-3 rounded mb-4 flex items-start gap-3 text-sm animate-fade-in">
                        <i class="fas fa-${icon} mt-0.5 flex-shrink-0"></i>
                         <div>${data.message}</div>
                    </div>
                `;

                if(data.success) {
                    this.reset();
                    // Optional: If development mode, show the link in log or alert
                    if(data.debug_link) {
                        console.log('Reset Link:', data.debug_link);
                        // For user convenince in this demo
                        container.innerHTML += `
                         <div class="bg-blue-900/30 border-blue-500 text-blue-200 border-l-4 px-4 py-3 rounded mb-4 text-xs animate-fade-in break-all">
                            <strong>DEV MODE:</strong> <a href="${data.debug_link}" class="underline text-blue-300">Click to Reset</a>
                         </div>`;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                btn.innerHTML = originalContent;
                container.innerHTML = `
                    <div class="bg-red-900/30 border-l-4 border-red-500 text-red-200 px-4 py-3 rounded mb-4 gap-3 text-sm flex items-center">
                        <i class="fas fa-bomb"></i> Server error. Try again later.
                    </div>
                `;
            });
        });
    </script>
</body>
</html>
