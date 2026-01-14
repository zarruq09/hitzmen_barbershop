<?php
// Filters
$search = $_GET['search'] ?? '';
$hairType = $_GET['hair_type'] ?? '';
$faceShape = $_GET['face_shape'] ?? '';

try {
    $sql = "SELECT * FROM haircuts WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (style_name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($hairType)) {
        $sql .= " AND hair_type LIKE ?";
        $params[] = "%$hairType%";
    }
    if (!empty($faceShape)) {
        $sql .= " AND face_shape LIKE ?";
        $params[] = "%$faceShape%";
    }

    $sql .= " ORDER BY style_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $haircuts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching haircuts: " . $e->getMessage());
    $haircuts = [];
}
?>
<div class="animate-fade-in space-y-8">
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-heading font-bold text-white mb-4">Style Gallery</h2>
        <div class="w-24 h-1 bg-gradient-gold mx-auto rounded-full"></div>
        <p class="text-gray-400 max-w-2xl mx-auto mt-4 text-lg font-light mb-8">
             Browse our collection of signature styles and find your inspiration.
        </p>

        <!-- SEARCH & FILTER FORM -->
        <form method="GET" action="dashboard.php" class="max-w-4xl mx-auto bg-dark-card p-4 rounded-xl border border-dark-border shadow-lg flex flex-col md:flex-row gap-4 items-center">
            <input type="hidden" name="view" value="haircuts">
            
            <!-- Search -->
            <div class="flex-1 w-full relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Find a style..." 
                       class="w-full bg-dark border border-dark-border text-white rounded-lg pl-10 pr-4 py-3 focus:border-gold focus:outline-none transition">
            </div>

            <!-- Hair Type -->
            <div class="w-full md:w-48 relative">
                <select name="hair_type" onchange="this.form.submit()"
                        class="w-full bg-dark border border-dark-border text-white rounded-lg px-4 py-3 appearance-none cursor-pointer focus:border-gold focus:outline-none">
                    <option value="">All Hair Types</option>
                    <option value="straight" <?php echo $hairType == 'straight' ? 'selected' : ''; ?>>Straight</option>
                    <option value="wavy" <?php echo $hairType == 'wavy' ? 'selected' : ''; ?>>Wavy</option>
                    <option value="curly" <?php echo $hairType == 'curly' ? 'selected' : ''; ?>>Curly</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>

            <!-- Face Shape -->
            <div class="w-full md:w-48 relative">
                <select name="face_shape" onchange="this.form.submit()"
                        class="w-full bg-dark border border-dark-border text-white rounded-lg px-4 py-3 appearance-none cursor-pointer focus:border-gold focus:outline-none">
                    <option value="">All Face Shapes</option>
                    <option value="oval" <?php echo $faceShape == 'oval' ? 'selected' : ''; ?>>Oval</option>
                    <option value="round" <?php echo $faceShape == 'round' ? 'selected' : ''; ?>>Round</option>
                    <option value="square" <?php echo $faceShape == 'square' ? 'selected' : ''; ?>>Square</option>
                    <option value="heart" <?php echo $faceShape == 'heart' ? 'selected' : ''; ?>>Heart</option>
                    <option value="diamond" <?php echo $faceShape == 'diamond' ? 'selected' : ''; ?>>Diamond</option>
                    <option value="long" <?php echo $faceShape == 'long' ? 'selected' : ''; ?>>Long</option>
                    <option value="triangle" <?php echo $faceShape == 'triangle' ? 'selected' : ''; ?>>Triangle</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>

            <!-- Reset Button -->
            <?php if(!empty($search) || !empty($hairType) || !empty($faceShape)): ?>
                <a href="dashboard.php?view=haircuts" class="text-red-400 hover:text-red-300 px-4 py-2 text-sm font-bold uppercase tracking-wider whitespace-nowrap">
                    <i class="fas fa-times mr-1"></i> Reset
                </a>
            <?php endif; ?>

        </form>
    </div>

    <?php if (empty($haircuts)): ?>
        <div class="text-center py-12 bg-dark-card rounded-xl border border-dark-border">
            <p class="text-xl text-gray-400">No styles listed yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($haircuts as $cut): ?>
                <div class="glass-card rounded-2xl overflow-hidden flex flex-col h-full group hover:border-gold/50 transition-all duration-300">
                    <div class="h-64 overflow-hidden relative bg-gray-800 cursor-pointer" onclick="openGalleryModal('uploads/<?php echo htmlspecialchars($cut['image']); ?>', '<?php echo htmlspecialchars($cut['style_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cut['description'], ENT_QUOTES); ?>')">
                         <?php if (!empty($cut['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($cut['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($cut['style_name']); ?>" 
                                 class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-dark text-gray-700">
                                <i class="fas fa-cut text-6xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent opacity-80 transition-opacity duration-300 group-hover:opacity-90"></div>
                        
                        <!-- Zoom Icon Overlay -->
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="w-12 h-12 bg-gold/80 rounded-full flex items-center justify-center text-dark shadow-xl backdrop-blur-sm">
                                <i class="fas fa-search-plus text-xl"></i>
                            </div>
                        </div>

                        <div class="absolute bottom-0 left-0 w-full p-6">
                            <h3 class="text-2xl text-white font-heading font-bold mb-1">
                                <?php echo htmlspecialchars($cut['style_name']); ?>
                            </h3>
                        </div>
                    </div>
                    
                    <div class="p-6 flex-1 flex flex-col">
                        <!-- Badges -->
                        <div class="mb-4 flex flex-wrap gap-2">
                            <?php if (!empty($cut['face_shape'])): ?>
                                <?php foreach(explode(',', $cut['face_shape']) as $tag): ?>
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border border-gold text-gold bg-gold/10">
                                        <?php echo htmlspecialchars(trim($tag)); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                             <?php if (!empty($cut['hair_type'])): ?>
                                <?php foreach(explode(',', $cut['hair_type']) as $tag): ?>
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border border-gray-600 text-gray-400 bg-gray-800">
                                        <?php echo htmlspecialchars(trim($tag)); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <p class="text-gray-400 mb-6 flex-1 text-sm leading-relaxed line-clamp-3">
                            <?php echo htmlspecialchars($cut['description']); ?>
                        </p>
                        
                        <a href="?view=book" class="w-full py-2 text-center rounded border border-gray-600 hover:border-gold hover:text-gold transition-colors text-sm font-bold uppercase tracking-wider">
                            Book This Style
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- GALLERY MODAL -->
<div id="galleryModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/95 backdrop-blur-md transition-opacity" onclick="closeGalleryModal()"></div>
    
    <div class="relative z-10 w-full h-full flex flex-col items-center justify-center p-4">
        <!-- Close Button -->
        <button onclick="closeGalleryModal()" class="absolute top-4 right-4 md:top-8 md:right-8 text-white/50 hover:text-white transition-colors p-2 z-50">
            <i class="fas fa-times text-2xl md:text-4xl"></i>
        </button>

        <!-- Image Container -->
        <div class="max-w-5xl w-full max-h-[90vh] flex flex-col items-center">
            <img id="modalImage" src="" alt="Zoom" class="max-w-full max-h-[50vh] md:max-h-[60vh] object-contain rounded-lg shadow-2xl border border-white/10 shrink">
            
            <div class="mt-4 text-center max-w-xl px-4 animate-fade-in-up flex flex-col items-center">
                <h3 id="modalTitle" class="text-2xl md:text-3xl font-heading font-bold text-white mb-2 shrink-0"></h3>
                <div class="relative w-full">
                    <p id="modalDesc" class="text-gray-400 text-sm md:text-base max-h-[120px] overflow-y-auto text-justify pr-2 custom-scrollbar"></p>
                </div>
                <a href="?view=book" class="inline-block mt-4 px-8 py-3 bg-gold text-dark font-bold rounded-lg hover:bg-white transition shadow-lg shadow-gold/20 shrink-0">
                    Book Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function openGalleryModal(imgSrc, title, desc) {
        document.getElementById('modalImage').src = imgSrc;
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalDesc').textContent = desc;
        
        const modal = document.getElementById('galleryModal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function closeGalleryModal() {
        const modal = document.getElementById('galleryModal');
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
        
        // Cleanup src to prevent ghosting
        setTimeout(() => {
            document.getElementById('modalImage').src = '';
        }, 300);
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGalleryModal();
        }
    });
</script>
<style>
    /* Custom Scrollbar for Description */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #C5A059;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #D4AF37;
    }
</style>
