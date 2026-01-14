<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/db.php';

$username = $_SESSION['username'] ?? 'Guest';
$isLoggedIn = isset($_SESSION['user_id']);

try {
    $stmt = $pdo->query("SELECT * FROM barbers WHERE status != 'Deleted' ORDER BY name ASC");
    $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching barbers: " . $e->getMessage());
    $barbers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Barbers | Hitzmen Barbershop</title>
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
                        card: '#252525'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#121212] flex flex-col min-h-[100dvh] text-gray-100">

    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12 flex-1">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold font-heading text-white mb-4">Meet the Team</h1>
            <p class="text-gray-400 max-w-2xl mx-auto text-lg font-serif italic">
                Our master barbers are dedicated to precision, style, and the art of grooming.
            </p>
        </div>

        <?php if (empty($barbers)): ?>
            <div class="text-center py-12 bg-[#252525] rounded-lg border border-[#333]">
                <p class="text-xl text-gray-400">No barbers listed yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($barbers as $barber): ?>
                    <div class="card bg-[#1E1E1E] border border-[#333] rounded-lg overflow-hidden group hover:border-[#C5A059] transition-all duration-300 p-0">
                        <div class="h-72 overflow-hidden relative bg-gray-800">
                             <?php if (!empty($barber['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($barber['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($barber['name']); ?>" 
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110 grayscale group-hover:grayscale-0">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-[#252525] text-gray-600">
                                    <i class="fas fa-user text-6xl"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-[#121212] to-transparent opacity-80"></div>
                            
                            <div class="absolute bottom-0 left-0 w-full p-6">
                                <h3 class="text-2xl text-white font-heading font-bold mb-1">
                                    <?php echo htmlspecialchars($barber['name']); ?>
                                </h3>
                                <p class="text-[#C5A059] text-sm uppercase tracking-widest font-medium">
                                    <?php echo htmlspecialchars($barber['specialty']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <?php if (($barber['status'] ?? 'Available') === 'Available'): ?>
                                <a href="book_appointment.php" class="block w-full text-center btn btn-primary">
                                    Book Appointment
                                </a>
                            <?php else: ?>
                                <div class="w-full py-3 bg-[#333] text-gray-500 text-center rounded text-sm uppercase font-bold tracking-wider cursor-not-allowed">
                                    Temporarily Unavailable
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
</body>
</html>

