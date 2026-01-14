<!-- Add Barber Modal -->
<div id="barberModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">➕ Add New Barber</h2>
        <form method="POST" action="actions/add_barber.php" enctype="multipart/form-data" class="space-y-4">
            <?php csrfField(); ?>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Full Name</label>
                <input name="name" type="text" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Specialty</label>
                <input name="specialty" type="text" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <input name="image" type="file" accept="image/*" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gold file:text-dark hover:file:bg-gold-light cursor-pointer">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Link Staff Account</label>
                <select name="user_id" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
                    <option value="">-- Select Staff Account --</option>
                    <?php if(!empty($staffUsers)): foreach($staffUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?><?= !empty($u['full_name']) ? ' - ' . htmlspecialchars($u['full_name']) : '' ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Save Barber</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">➕ Add New Service</h2>
        <form action="actions/add_service.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php csrfField(); ?>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Service Name</label>
                <input type="text" name="service_name" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Price (RM)</label>
                <input type="number" step="0.01" name="price" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Image</label>
                <input type="file" name="image" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gold file:text-dark hover:file:bg-gold-light cursor-pointer">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Add Service</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Haircut Modal -->
<div id="addHaircutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">➕ Add New Haircut</h2>
        <form action="actions/add_haircut.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php csrfField(); ?>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Style Name</label>
                <input type="text" name="style_name" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Face Shape (Select multiple)</label>
                <div class="grid grid-cols-2 gap-2 bg-dark border border-dark-border rounded-lg p-3">
                    <?php 
                    $shapes = ['oval'=>'Oval', 'round'=>'Round', 'square'=>'Square', 'heart'=>'Heart', 'diamond'=>'Diamond', 'long'=>'Long', 'triangle'=>'Triangle'];
                    foreach($shapes as $val => $label): 
                    ?>
                    <label class="flex items-center space-x-2 cursor-pointer group">
                        <input type="checkbox" name="face_shape[]" value="<?= $val ?>" class="form-checkbox h-4 w-4 text-gold rounded border-gray-600 bg-dark focus:ring-gold focus:ring-offset-dark">
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
             <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Hair Type (Select multiple)</label>
                <div class="grid grid-cols-2 gap-2 bg-dark border border-dark-border rounded-lg p-3">
                    <?php 
                    $types = ['straight'=>'Straight', 'wavy'=>'Wavy', 'curly'=>'Curly'];
                    foreach($types as $val => $label): 
                    ?>
                    <label class="flex items-center space-x-2 cursor-pointer group">
                        <input type="checkbox" name="hair_type[]" value="<?= $val ?>" class="form-checkbox h-4 w-4 text-gold rounded border-gray-600 bg-dark focus:ring-gold focus:ring-offset-dark">
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Style Image</label>
                <input type="file" name="image" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gold file:text-dark hover:file:bg-gold-light cursor-pointer">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Add Haircut</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Barber Modal -->
<div id="editBarberModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">✏️ Edit Barber</h2>
        <form action="actions/update_barber.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php csrfField(); ?>
            <input type="hidden" id="editBarberId" name="id">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Name</label>
                <input type="text" id="editBarberName" name="name" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Specialty</label>
                <input type="text" id="editBarberSpecialty" name="specialty" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                <div class="px-4 py-2 bg-dark-hover border border-dark-border rounded-lg text-gray-500 text-sm italic">
                    Managed via Schedule
                </div>
            </div>



            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Link Staff Account</label>
                <select id="editBarberUserId" name="user_id" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
                    <option value="">-- Select Staff Account --</option>
                    <?php if(!empty($staffUsers)): foreach($staffUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?><?= !empty($u['full_name']) ? ' - ' . htmlspecialchars($u['full_name']) : '' ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            
            <div class="flex items-center gap-4">
                <img id="currentImagePreview" src="" class="h-16 w-16 object-cover rounded-full border border-dark-border">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-400 mb-1">Change Photo</label>
                    <input type="file" name="new_image" accept="image/*" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-gray-300 file:mr-4 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gold file:text-dark hover:file:bg-gold-light cursor-pointer text-sm">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editServiceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">✏️ Edit Service</h2>
        <form action="actions/edit_service.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php csrfField(); ?>
            <input type="hidden" name="id" id="editServiceId">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Service Name</label>
                <input name="service_name" id="editServiceName" type="text" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Price (RM)</label>
                <input name="price" id="editServicePrice" type="number" step="0.01" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Description</label>
                <textarea name="description" id="editServiceDescription" rows="2" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors"></textarea>
            </div>
            <div class="flex items-center gap-4">
                <img id="currentServiceImage" src="" class="h-16 w-16 object-cover rounded-md border border-dark-border">
                <div class="flex-1">
                     <label class="block text-sm font-medium text-gray-400 mb-1">Change Image</label>
                    <input name="service_image" type="file" accept="image/*" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-gray-300 file:mr-4 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gold file:text-dark hover:file:bg-gold-light cursor-pointer text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Haircut Modal -->
<div id="editHaircutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">✏️ Edit Haircut</h2>
        <form action="actions/edit_haircut.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php csrfField(); ?>
            <input type="hidden" name="haircut_id" id="editHaircutId">
            <input type="hidden" name="current_image" id="editHaircutCurrentImageHidden"> 
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Style Name</label>
                <input type="text" name="style_name" id="editHaircutName" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Description</label>
                <textarea name="description" id="editHaircutDescription" rows="2" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Face Shape</label>
                <div class="grid grid-cols-2 gap-2 bg-dark border border-dark-border rounded-lg p-3" id="editFaceShapeContainer">
                     <?php 
                    $shapes = ['oval'=>'Oval', 'round'=>'Round', 'square'=>'Square', 'heart'=>'Heart', 'diamond'=>'Diamond', 'long'=>'Long', 'triangle'=>'Triangle'];
                    foreach($shapes as $val => $label): 
                    ?>
                    <label class="flex items-center space-x-2 cursor-pointer group">
                        <input type="checkbox" name="face_shape[]" value="<?= $val ?>" class="form-checkbox h-4 w-4 text-gold rounded border-gray-600 bg-dark focus:ring-gold focus:ring-offset-dark">
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Hair Type</label>
               <div class="grid grid-cols-2 gap-2 bg-dark border border-dark-border rounded-lg p-3" id="editHairTypeContainer">
                    <?php 
                    $types = ['straight'=>'Straight', 'wavy'=>'Wavy', 'curly'=>'Curly'];
                    foreach($types as $val => $label): 
                    ?>
                    <label class="flex items-center space-x-2 cursor-pointer group">
                        <input type="checkbox" name="hair_type[]" value="<?= $val ?>" class="form-checkbox h-4 w-4 text-gold rounded border-gray-600 bg-dark focus:ring-gold focus:ring-offset-dark">
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                 <img id="currentHaircutImage" src="" class="h-16 w-16 object-cover rounded-md border border-dark-border hidden">
                 <span id="noCurrentImageTextHaircut" class="text-gray-500 italic text-sm">No image set</span>
                 <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-400 mb-1">Change Image</label>
                    <input type="file" name="image" id="editHaircutImage" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-gray-300 file:mr-4 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gold file:text-dark hover:file:bg-gold-light cursor-pointer text-sm">
                 </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Appointment Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-sm relative transform transition-all">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-red-500 mb-6 border-b border-dark-border pb-2">Reject Booking</h2>
        <form action="actions/update_appointment_status.php" method="POST" class="space-y-4" onsubmit="return handleRejectForm(event)">
            <?php csrfField(); ?>
            <input type="hidden" name="appointment_id" id="rejectAppointmentId">
            <input type="hidden" name="status" value="Cancelled">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Reason for Rejection</label>
                <textarea name="rejection_reason" rows="3" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:outline-none transition-colors" placeholder="e.g., Barber unavailable..."></textarea>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="bg-red-900/50 hover:bg-red-900 text-red-500 hover:text-red-100 border border-red-900 px-6 py-2 rounded-lg shadow-lg transition font-bold">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>