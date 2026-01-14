    <footer class="bg-[#1E1E1E] border-t border-[#333] pt-12 pb-8 mt-auto">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <div class="mb-6 md:mb-0 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-white mb-2">HITZMEN BARBERSHOP</h3>
                    <p class="text-gray-400 italic font-serif">Crafting style since 2023.</p>
                </div>
                
                <div class="flex space-x-6">
                    <a href="https://www.instagram.com/hitzmenbarbershop" target="_blank" class="text-gray-400 hover:text-[#C5A059] transition-colors duration-300">
                        <i class="fab fa-instagram text-2xl"></i>
                    </a>
                    <a href="https://wa.me/60182172159" target="_blank" class="text-gray-400 hover:text-[#C5A059] transition-colors duration-300">
                        <i class="fab fa-whatsapp text-2xl"></i>
                    </a>
                </div>
            </div>
            
            <div class="border-t border-[#333] pt-8 text-center text-gray-500 text-sm">
                <p>&copy; <?php echo date('Y'); ?> Hitzmen Barbershop. All rights reserved.</p>
            </div>
        </div>
    </footer>
    </footer>

    <?php 
    // Include notification sound script globally if not already included (e.g. by dashboard)
    $script_path = __DIR__ . '/customer_notification_script.php';
    // Check if file exists and we are not on dashboard.php (dashboard includes it manually inside body, but let's be safe against duplicate checks if possible, or just rely on PHP_ONCE behavior if we used require_once, but we used include.)
    // Actually, simpler logic: Dashboard is the only one including it manually? 
    // The previous step removed it from dashboard inline and replaced with include.
    // If we include it in footer, it will cover ALL pages that use footer.
    // DOES dashboard use footer? NO. Dashboard.php does NOT include footer.php (it has its own structure).
    // So logic is: Dashboard has it. Other pages (book, services etc) use footer.php.
    
    // So we safely include it here for non-dashboard pages.
    if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php') {
        include_once __DIR__ . '/customer_notification_script.php'; 
    }
    ?>
