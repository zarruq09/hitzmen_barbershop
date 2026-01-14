<!-- Feedback Modal -->
<div id="feedbackModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm hidden transition-opacity duration-300">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all scale-100">
        <button onclick="closeFeedbackModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <div class="text-center mb-6">
            <h2 class="text-2xl font-heading font-bold text-transparent bg-clip-text bg-gradient-to-r from-gold to-white mb-2">Vibe Check! 💈</h2>
            <p class="text-gray-400 text-sm" id="feedbackApptDetails">How was your grooming session?</p>
        </div>

        <form action="actions/submit_feedback.php" method="POST" class="space-y-6">
            <input type="hidden" name="appointment_id" id="feedbackApptId">
            <input type="hidden" name="barber_id" id="feedbackBarberId">
            
            <!-- 1. Barber/Staff Rating -->
            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-300 uppercase tracking-wider">
                    <i class="fas fa-user-tie text-gold mr-2"></i>Barber Skill
                </label>
                <div class="flex justify-center gap-2 star-rating" data-input="staff_rating">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="fas fa-star text-2xl cursor-pointer text-gray-700 hover:text-gold transition-colors star-btn" data-value="<?= $i ?>"></i>
                    <?php endfor; ?>
                    <input type="hidden" name="staff_rating" required>
                </div>
            </div>

            <!-- 2. Shop/Environment Rating -->
             <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-300 uppercase tracking-wider">
                    <i class="fas fa-store text-gold mr-2"></i>Shop Vibe
                </label>
                <div class="flex justify-center gap-2 star-rating" data-input="shop_rating">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="fas fa-star text-2xl cursor-pointer text-gray-700 hover:text-gold transition-colors star-btn" data-value="<?= $i ?>"></i>
                    <?php endfor; ?>
                    <input type="hidden" name="shop_rating" required>
                </div>
            </div>

            <!-- 3. Overall Service Rating -->
             <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-300 uppercase tracking-wider">
                    <i class="fas fa-concierge-bell text-gold mr-2"></i>Overall Service
                </label>
                <div class="flex justify-center gap-2 star-rating" data-input="service_rating">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="fas fa-star text-2xl cursor-pointer text-gray-700 hover:text-gold transition-colors star-btn" data-value="<?= $i ?>"></i>
                    <?php endfor; ?>
                    <input type="hidden" name="service_rating" required>
                </div>
            </div>

            <!-- Comments -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Any words?</label>
                <textarea name="comments" rows="3" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors text-sm" placeholder="Spill the tea... (optional)"></textarea>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-gold to-gold-light text-dark py-3 rounded-lg shadow-lg hover:opacity-90 transition font-bold text-lg uppercase tracking-wide">
                Send Feedback 🚀
            </button>
        </form>
    </div>
</div>

<script>
function openFeedbackModal(apptId, barberId, barberName) {
    document.getElementById('feedbackModal').classList.remove('hidden');
    document.getElementById('feedbackApptId').value = apptId;
    document.getElementById('feedbackBarberId').value = barberId;
    document.getElementById('feedbackApptDetails').innerText = `Rate your cut with ${barberName}`;
    
    // Reset stars
    document.querySelectorAll('.star-rating input').forEach(i => i.value = '');
    document.querySelectorAll('.star-btn').forEach(s => s.classList.remove('text-gold'));
    document.querySelectorAll('.star-btn').forEach(s => s.classList.add('text-gray-700'));
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
}

// Star Rating Logic
document.querySelectorAll('.star-rating').forEach(container => {
    const inputName = container.dataset.input;
    const input = container.querySelector('input');
    const stars = container.querySelectorAll('.star-btn');

    stars.forEach(star => {
        // Hover effect
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.dataset.value);
            stars.forEach(s => {
                if (parseInt(s.dataset.value) <= val) {
                    s.classList.add('text-gold');
                    s.classList.remove('text-gray-700');
                } else {
                    s.classList.remove('text-gold');
                    s.classList.add('text-gray-700');
                }
            });
        });

        // Reset hover on mouse leave (if not clicked)
        container.addEventListener('mouseleave', () => {
            const currentVal = parseInt(input.value || 0);
            updateStars(stars, currentVal);
        });

        // Click to set value
        star.addEventListener('click', () => {
            input.value = star.dataset.value;
            updateStars(stars, parseInt(input.value));
        });
    });
});

function updateStars(stars, value) {
    stars.forEach(s => {
        if (parseInt(s.dataset.value) <= value) {
            s.classList.add('text-gold');
            s.classList.remove('text-gray-700');
        } else {
            s.classList.remove('text-gold');
            s.classList.add('text-gray-700');
        }
    });
}
</script>
