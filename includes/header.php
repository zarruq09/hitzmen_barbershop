<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$user_logged_in = isset($_SESSION['user_id']);
?>
    <!-- Header / Navigation -->
<nav class="navbar relative z-50">
    <div class="container mx-auto px-4 flex justify-between items-center h-16">
        <!-- Logo -->
        <a href="dashboard.php" class="flex items-center space-x-3 group">
            <img src="assets/images/Logo.png" alt="Hitzmen Logo" class="h-10 w-10 object-contain transition-transform group-hover:scale-105">
            <span class="font-bold text-xl tracking-tight text-white group-hover:text-[#C5A059] transition-colors">HITZMEN</span>
        </a>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center space-x-1">
            <?php
            $nav_items = [
                'dashboard.php' => 'Dashboard',
                'book_appointment.php' => 'Book Now',
                'services.php' => 'Services',
                'barbers.php' => 'Barbers',
                'gallery.php' => 'Gallery',
                'contact.php' => 'Contact'
            ];

            foreach ($nav_items as $url => $label):
                $active_class = ($current_page === $url) ? 'active' : '';
            ?>
                <a href="<?php echo $url; ?>" class="nav-link <?php echo $active_class; ?> transition-colors duration-200">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- User Menu / Mobile Toggle -->
        <div class="flex items-center space-x-4">
            <?php if ($user_logged_in): ?>
                <div class="hidden md:flex items-center space-x-4">
                    <span class="text-sm text-gray-400">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </span>
                    <a href="logout.php" class="text-sm text-red-400 hover:text-red-300 font-medium transition-colors">Logout</a>
                </div>
                <!-- Mobile Logout Icon -->
                <a href="logout.php" class="md:hidden text-red-400 hover:text-red-300 mr-2" title="Logout">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            <?php else: ?>
                <div class="hidden md:block">
                    <a href="index.php" class="bg-[#C5A059] hover:bg-[#D4AF37] text-[#121212] font-bold text-sm py-2 px-4 rounded-md transition-all duration-300 shadow-md hover:-translate-y-0.5">Login</a>
                </div>
                <!-- Mobile Login Icon -->
                <a href="index.php" class="md:hidden text-[#C5A059] hover:text-white mr-2" title="Login">
                    <i class="fas fa-sign-in-alt text-lg"></i>
                </a>
            <?php endif; ?>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-300 hover:text-white focus:outline-none p-2 rounded-md hover:bg-gray-800 transition">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="hidden md:hidden bg-[#1E1E1E] border-t border-[#333]">
        <div class="px-4 py-3 space-y-2">
            <?php foreach ($nav_items as $url => $label): 
                 $active_class = ($current_page === $url) ? 'text-[#C5A059] bg-[#252525]' : 'text-gray-300 hover:bg-[#252525] hover:text-[#C5A059]';
            ?>
                <a href="<?php echo $url; ?>" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $active_class; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
            
            <div class="border-t border-[#333] mt-2 pt-2">
                <?php if ($user_logged_in): ?>
                    <div class="px-3 py-2 text-sm text-gray-400">
                        Signed in as <span class="text-white font-bold"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </div>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-400 hover:bg-[#252525]">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-[#C5A059] hover:bg-[#252525]">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    
    if(btn && menu) {
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            const icon = btn.querySelector('i');
            if(menu.classList.contains('hidden')) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            } else {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        });
    }
});
</script>
