<?php
// Fetch all services
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
    $services = [];
}
?>
<div class="animate-fade-in space-y-8">
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-heading font-bold text-white mb-4">Our Premium Services</h2>
        <div class="w-24 h-1 bg-gradient-gold mx-auto rounded-full"></div>
        <p class="text-gray-400 max-w-2xl mx-auto mt-4 text-lg font-light">
            Experience premium grooming with our wide range of professional services.
        </p>
    </div>

    <?php if (empty($services)): ?>
        <div class="text-center py-12 bg-dark-card rounded-xl border border-dark-border">
            <p class="text-xl text-gray-400">No services currently listed.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($services as $service): ?>
                <div class="glass-card rounded-2xl overflow-hidden flex flex-col h-full group hover:border-gold/50 transition-all duration-300">
                    <div class="relative h-48 bg-gray-800 overflow-hidden">
                        <?php if (!empty($service['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($service['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                 class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-dark text-gray-700">
                                <i class="fas fa-cut text-5xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="absolute top-4 right-4 bg-gold text-dark font-bold px-3 py-1 rounded shadow-lg text-sm">
                            RM <?php echo number_format($service['price'], 2); ?>
                        </div>
                        <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-all duration-300"></div>
                    </div>
                    
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="text-xl font-bold font-heading text-white mb-2">
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </h3>
                        <p class="text-gray-400 mb-6 flex-1 text-sm leading-relaxed line-clamp-3">
                            <?php echo htmlspecialchars($service['description'] ?? 'No description available.'); ?>
                        </p>
                        
                        <a href="?view=book" class="block w-full py-3 rounded-xl border border-gold text-gold text-center font-bold hover:bg-gold hover:text-dark transition-all">
                            Book This
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
