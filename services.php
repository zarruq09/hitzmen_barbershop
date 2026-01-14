<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/db.php';

// Check if user is logged in for personalized greeting, but page is public accessible
$username = $_SESSION['username'] ?? 'Guest';
$isLoggedIn = isset($_SESSION['user_id']);

try {
    // Fetch all services
    $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
    $services = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | Hitzmen Barbershop</title>
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
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Roboto:wght@300;500;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#121212] flex flex-col min-h-[100dvh] text-gray-100">

    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12 flex-1">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold font-heading text-white mb-4">Our Services</h1>
            <p class="text-gray-400 max-w-2xl mx-auto text-lg font-serif italic">
                Experience premium grooming with our wide range of professional services designed for the modern gentleman.
            </p>
        </div>

        <?php if (empty($services)): ?>
            <div class="text-center py-12 bg-[#252525] rounded-lg border border-[#333]">
                <p class="text-xl text-gray-400">No services currently listed. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($services as $service): ?>
                    <div class="card bg-[#1E1E1E] border border-[#333] rounded-lg overflow-hidden flex flex-col h-full hover:border-[#C5A059] transition-all duration-300 p-0">
                        <div class="relative h-56 bg-gray-800 overflow-hidden group">
                            <?php if (!empty($service['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($service['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-[#252525] text-gray-600">
                                    <i class="fas fa-cut text-5xl"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-4 right-4 bg-[#C5A059] text-[#121212] font-bold px-4 py-1 rounded shadow-lg">
                                RM <?php echo number_format($service['price'], 2); ?>
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-30 group-hover:bg-opacity-10 transition-all duration-300"></div>
                        </div>
                        
                        <div class="p-8 flex-1 flex flex-col">
                            <h3 class="text-2xl font-bold font-heading text-white mb-3">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </h3>
                            <div class="w-12 h-1 bg-[#C5A059] mb-4"></div>
                            <p class="text-gray-400 mb-8 flex-1 leading-relaxed">
                                <?php echo htmlspecialchars($service['description'] ?? 'No description available.'); ?>
                            </p>
                            
                            <a href="book_appointment.php" class="block w-full py-3 text-center btn btn-primary font-bold tracking-wide">
                                Book Now
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>

