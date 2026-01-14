<?php
// Fetch barbers
try {
    $stmt = $pdo->query("SELECT * FROM barbers WHERE status != 'Deleted' ORDER BY name ASC");
    $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching barbers: " . $e->getMessage());
    $barbers = [];
}
?>
<div class="animate-fade-in space-y-8">
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-heading font-bold text-white mb-4">Meet The Team</h2>
        <div class="w-24 h-1 bg-gradient-gold mx-auto rounded-full"></div>
        <p class="text-gray-400 max-w-2xl mx-auto mt-4 text-lg font-light">
            Master barbers dedicated to precision, style, and the art of grooming.
        </p>
    </div>

    <?php if (empty($barbers)): ?>
        <div class="text-center py-12 bg-dark-card rounded-xl border border-dark-border">
            <p class="text-xl text-gray-400">No barbers listed yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($barbers as $barber): ?>
                <div class="glass-card rounded-2xl overflow-hidden group hover:border-gold/50 transition-all duration-300">
                    <div class="h-80 overflow-hidden relative bg-gray-800">
                         <?php if (!empty($barber['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($barber['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($barber['name']); ?>" 
                                 class="w-full h-full object-cover transition duration-700 group-hover:scale-110 grayscale group-hover:grayscale-0">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-dark text-gray-700">
                                <i class="fas fa-user-tie text-6xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-dark via-transparent to-transparent opacity-90"></div>
                        
                        <div class="absolute bottom-0 left-0 w-full p-6 translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                            <h3 class="text-2xl text-white font-heading font-bold mb-1">
                                <?php echo htmlspecialchars($barber['name']); ?>
                            </h3>
                            <p class="text-gold text-sm uppercase tracking-widest font-medium mb-4">
                                <?php echo htmlspecialchars($barber['specialty']); ?>
                            </p>
                            
                             <?php if (($barber['status'] ?? 'Available') === 'Available'): ?>
                                <a href="?view=book" class="inline-block w-full py-2 bg-gold text-dark text-center font-bold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform translate-y-4 group-hover:translate-y-0">
                                    Book Appointment
                                </a>
                            <?php else: ?>
                                <div class="w-full py-2 bg-gray-800 text-gray-500 text-center font-bold rounded-lg text-sm uppercase">
                                    Unavailable
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
