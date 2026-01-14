<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/db.php';

$username = $_SESSION['username'] ?? 'Guest';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Hitzmen Barbershop</title>
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
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#121212] flex flex-col min-h-[100dvh] text-gray-100 font-sans">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-12 flex-1">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold font-heading text-white mb-4">Get in Touch</h1>
            <p class="text-gray-400 max-w-2xl mx-auto text-lg italic font-serif">
                 Have questions or ready to book your next cut? We're here to help.
            </p>
        </div>

        <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Contact Info -->
            <div class="bg-[#1E1E1E] border border-[#333] rounded-lg p-8 shadow-xl h-full">
                <h3 class="text-2xl font-heading font-bold text-white mb-6 border-b border-[#C5A059] pb-3 inline-block">
                    Visit Us
                </h3>
                
                <div class="space-y-8">
                    <div class="flex items-start">
                        <div class="bg-[#252525] p-3 rounded-full mr-4 text-[#C5A059]">
                            <i class="fas fa-map-marker-alt text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-white text-lg mb-1">Location</h4>
                            <p class="text-gray-400 leading-relaxed">
                                Jc 35, 1, Jalan BMU 3,<br>
                                BANDAR BARU MERLIMAU UTARA,<br>
                                77300 Merlimau, Malacca
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="bg-[#252525] p-3 rounded-full mr-4 text-[#C5A059]">
                            <i class="fas fa-phone-alt text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-white text-lg mb-1">Phone</h4>
                            <p class="text-gray-400">018-2172159</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="bg-[#252525] p-3 rounded-full mr-4 text-[#C5A059]">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-white text-lg mb-1">Hours</h4>
                            <p class="text-gray-400">
                                <span class="block">Mon - Sun: 11am - 10pm</span>
                                <span class="block text-[#C5A059] mt-1 font-semibold">Wednesday: Closed</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-[#1E1E1E] border border-[#333] rounded-lg p-8 shadow-xl h-full">
                 <h3 class="text-2xl font-heading font-bold text-white mb-6 border-b border-[#C5A059] pb-3 inline-block">
                    WhatsApp Us
                </h3>
                <form id="whatsappForm" class="space-y-6" onsubmit="sendToWhatsapp(event)">
                    <div>
                        <label for="name" class="block text-[#C5A059] text-sm font-bold mb-2">Name</label>
                        <input type="text" id="name" name="name" placeholder="Your Name" required
                               class="w-full bg-[#121212] border border-[#333] text-white rounded p-3 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none">
                    </div>
                    <div>
                         <label for="message" class="block text-[#C5A059] text-sm font-bold mb-2">Message</label>
                        <textarea id="message" name="message" rows="4" placeholder="How can we help?" required
                                  class="w-full bg-[#121212] border border-[#333] text-white rounded p-3 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none"></textarea>
                    </div>
                    <button type="submit" class="w-full btn btn-primary py-4 font-bold tracking-wide flex items-center justify-center shadow-lg transform transition hover:-translate-y-1">
                        <i class="fab fa-whatsapp text-xl mr-2"></i> Send to WhatsApp
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function sendToWhatsapp(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const message = document.getElementById('message').value;
            const phoneNumber = '60182172159';
            
            const text = `Hi, I'm ${name}. ${message}`;
            const encodedText = encodeURIComponent(text);
            
            window.open(`https://wa.me/${phoneNumber}?text=${encodedText}`, '_blank');
        }
    </script>
</body>
</html>
