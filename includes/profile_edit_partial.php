<div class="max-w-4xl mx-auto animate-fade-in">
    <div class="flex items-center justify-between mb-8 pb-4 border-b border-dark-border">
        <div>
            <h1 class="text-3xl font-bold font-heading text-white">Edit Profile</h1>
            <p class="text-gray-400 text-sm mt-1">Update your personal information.</p>
        </div>
        <a href="?view=profile" class="text-gray-400 hover:text-gold transition flex items-center group">
            <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition"></i> Back to Profile
        </a>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="bg-green-900/30 border border-green-600 text-green-400 px-4 py-3 rounded relative mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-xl"></i>
            <span><?= htmlspecialchars($_SESSION['success_msg']) ?></span>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="bg-red-900/30 border border-red-600 text-red-400 px-4 py-3 rounded relative mb-6 flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <span><?= htmlspecialchars($_SESSION['error_msg']) ?></span>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <div class="bg-dark-card border border-dark-border rounded-xl p-8 shadow-xl">
        <form action="profile.php" method="POST" class="space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-gold mb-2 text-xs font-bold uppercase tracking-widest">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" value="<?= $username ?>" 
                            class="w-full bg-[#121212] border border-dark-border text-white rounded-lg py-3 pl-10 pr-3 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition" required>
                    </div>
                </div>
                <div>
                    <label class="block text-gold mb-2 text-xs font-bold uppercase tracking-widest">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500"><i class="fas fa-envelope"></i></span>
                        <input type="email" value="<?= $email ?>" 
                            class="w-full bg-[#121212] border border-dark-border text-gray-500 rounded-lg py-3 pl-10 pr-3 opacity-70 cursor-not-allowed" disabled>
                    </div>
                    <p class="text-xs text-gray-600 mt-2 italic flex items-center gap-1"><i class="fas fa-lock text-[10px]"></i> Email cannot be changed.</p>
                </div>
            </div>

            <div class="border-t border-dark-border pt-6">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="full_name" class="block text-white mb-2 text-sm font-bold uppercase tracking-wide">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?= $fullName ?>" 
                                class="w-full bg-[#121212] border border-dark-border text-white rounded-lg p-3 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition placeholder-gray-600" required>
                    </div>

                    <div>
                        <label for="phone" class="block text-white mb-2 text-sm font-bold uppercase tracking-wide">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') // Fetch user data again if needed or pass it ?>" 
                                class="w-full bg-[#121212] border border-dark-border text-white rounded-lg p-3 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition placeholder-gray-600" required>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="bg-gold text-dark font-bold py-3 px-8 rounded-lg shadow-lg hover:bg-gold-light hover:shadow-gold/20 transition-all transform hover:-translate-y-0.5">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-dismiss alerts after 1 second
    document.addEventListener('DOMContentLoaded', () => {
        const alerts = document.querySelectorAll('.bg-green-900\\/30, .bg-red-900\\/30');
        if (alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 1000);
        }
    });
</script>
