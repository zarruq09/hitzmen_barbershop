
        <!-- Page Header -->
        <div class="text-center mb-12 animate-fade-in-up">
            <span class="text-gold text-sm font-bold tracking-widest uppercase mb-2 block">Premium Grooming</span>
            <h1 class="text-4xl md:text-5xl font-bold font-heading text-white mb-4">Book Your Appointment</h1>
            <div class="w-24 h-1 bg-gradient-gold mx-auto rounded-full"></div>
            <p class="text-gray-400 max-w-2xl mx-auto mt-4 text-lg font-light">
                Secure your spot with our master barbers. Efficiency meets excellence.
            </p>
        </div>

        <div class="max-w-4xl mx-auto pb-12">
             <!-- Status Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-fade bg-green-900/20 border-l-4 border-green-500 text-green-400 p-4 rounded-r-lg mb-8 shadow-lg flex items-center">
                    <i class="fas fa-check-circle text-2xl mr-4"></i>
                    <div>
                        <p class="font-bold">Success!</p>
                        <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-fade bg-red-900/20 border-l-4 border-red-500 text-red-400 p-4 rounded-r-lg mb-8 shadow-lg flex items-center">
                    <i class="fas fa-exclamation-circle text-2xl mr-4"></i>
                    <div>
                        <p class="font-bold">Attention Needed</p>
                        <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form action="actions/process_booking.php" method="POST" class="space-y-8">
                <?php csrfField(); ?>
                <!-- IMPORTANT: Redirect back to dashboard view (relative to actions folder) -->
                <input type="hidden" name="redirect_to" value="../dashboard.php?view=history">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- LEFT COLUMN: Details -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- Section: Date & Time -->
                        <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-1 h-full bg-gradient-gold opacity-50 group-hover:opacity-100 transition-opacity"></div>
                            <h3 class="text-xl font-heading font-bold text-white mb-6 flex items-center">
                                <i class="far fa-calendar-alt text-gold mr-3"></i> Date & Time
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="appointment_date" class="block text-gray-400 text-xs uppercase tracking-wider font-bold mb-2">Select Date</label>
                                    <div class="relative">
                                        <input type="date" id="appointment_date" name="appointment_date"
                                               min="<?php echo date('Y-m-d'); ?>" required
                                               class="w-full bg-dark/50 border border-dark-border text-white rounded-xl px-4 py-3 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition-all cursor-pointer">
                                    </div>
                                </div>
                                <div>
                                    <label for="appointment_time" class="block text-gray-400 text-xs uppercase tracking-wider font-bold mb-2">Select Time</label>
                                    <div class="relative">
                                        <input type="time" id="appointment_time" name="appointment_time"
                                               required
                                               class="w-full bg-dark/50 border border-dark-border text-white rounded-xl px-4 py-3 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition-all cursor-pointer">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center"><i class="fas fa-clock mr-1"></i> Select a date to see available hours</p>
                                    <!-- Availability Feedback Message -->
                                    <div id="availability-feedback" class="mt-3 hidden animate-fade-in-up"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Barber -->
                        <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-1 h-full bg-gradient-gold opacity-50 group-hover:opacity-100 transition-opacity"></div>
                            <h3 class="text-xl font-heading font-bold text-white mb-6 flex items-center">
                                <i class="fas fa-user-tie text-gold mr-3"></i> Choose Your Barber
                            </h3>
                            
                            <div class="relative">
                                <select id="barber_id" name="barber_id" required
                                        class="w-full bg-dark/50 border border-dark-border text-white rounded-xl px-4 py-4 pl-12 focus:border-gold focus:ring-1 focus:ring-gold outline-none appearance-none transition-all cursor-pointer text-lg">
                                    <option value="">Select a Professional...</option>
                                    <?php foreach ($barbers as $barber): ?>
                                        <option value="<?php echo htmlspecialchars($barber['id']); ?>">
                                            <?php echo htmlspecialchars($barber['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute left-4 top-5 text-gray-500">
                                    <i class="fas fa-cut"></i>
                                </div>
                                <div class="absolute right-4 top-5 pointer-events-none text-gold">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                         <!-- Section: Notes -->
                         <div class="glass-card rounded-2xl p-6 md:p-8">
                            <h3 class="text-xl font-heading font-bold text-white mb-6 flex items-center">
                                <i class="fas fa-comment-alt text-gold mr-3"></i> Special Requests
                            </h3>
                            <textarea id="notes" name="notes" rows="3"
                                      class="w-full bg-dark/50 border border-dark-border text-white rounded-xl px-4 py-3 focus:border-gold focus:ring-1 focus:ring-gold outline-none transition-all resize-none"
                                      placeholder="Any specific instructions or preferences?"></textarea>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Services & Summary -->
                    <div class="space-y-8">
                        
                        <!-- Section: Services -->
                         <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden h-full flex flex-col">
                            <h3 class="text-xl font-heading font-bold text-white mb-6 flex items-center">
                                <i class="fas fa-list-ul text-gold mr-3"></i> Select Services
                            </h3>
                            
                            <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 max-h-[400px] space-y-3">
                                <?php foreach ($services as $service): ?>
                                    <label class="flex items-start p-3 rounded-xl border border-transparent hover:border-gold/30 hover:bg-dark-hover transition-all cursor-pointer group">
                                        <div class="flex items-center h-5 mt-1">
                                            <input type="checkbox" name="services[]" value="<?php echo htmlspecialchars($service['id']); ?>"
                                               class="service-checkbox w-5 h-5 text-gold bg-dark border-gray-600 rounded focus:ring-gold focus:ring-offset-dark">
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="font-medium text-gray-200 group-hover:text-white transition-colors"><?php echo htmlspecialchars($service['service_name']); ?></span>
                                                <span class="price-tag text-gold font-bold">RM <?php echo number_format($service['price'], 0); ?></span>
                                            </div>
                                            <!-- Optional: Description if available -->
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Dynamic Haircut Selection -->
                            <div id="haircutSelection" class="hidden mt-6 pt-6 border-t border-dark-border animate-fade-in-up">
                                <label for="haircut_id" class="block text-gold text-xs uppercase tracking-wider font-bold mb-2">
                                    <i class="fas fa-cut mr-1"></i> Select Haircut Style
                                </label>
                                <div class="relative">
                                    <select id="haircut_id" name="haircut_id"
                                            class="w-full bg-dark border border-dark-border text-white rounded-xl px-3 py-2 text-base focus:border-gold focus:ring-1 focus:ring-gold outline-none appearance-none cursor-pointer">
                                        <option value="">-- Choose Style --</option>
                                        <?php foreach ($haircuts as $haircut): ?>
                                            <option value="<?php echo htmlspecialchars($haircut['id']); ?>">
                                                <?php echo htmlspecialchars($haircut['style_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute right-3 top-2.5 pointer-events-none text-gray-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>

                <!-- Submit Action -->
                <div class="flex justify-center pt-8">
                    <button type="submit" class="w-full md:w-auto md:min-w-[300px] bg-gradient-to-r from-gold to-gold-light text-dark font-bold text-lg py-4 px-12 rounded-full shadow-lg hover:shadow-gold/20 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center group">
                        <span>Book Now</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                    <!-- Loading Spinner -->
                     <div id="booking-spinner" class="hidden ml-4 text-gold items-center animate-spin">
                        <i class="fas fa-circle-notch text-2xl"></i>
                    </div>
                </div>
            </form>
        </div>

    <script>
        const PHP_HAIRCUT_SERVICE_IDS = <?php echo json_encode($haircutServiceIds); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Haircut Selection Logic
            const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
            const haircutSelectionDiv = document.getElementById('haircutSelection');
            const haircutSelect = document.getElementById('haircut_id');

            function updateHaircutSelection() {
                let showHaircutOption = false;
                serviceCheckboxes.forEach(checkbox => {
                    if (checkbox.checked && PHP_HAIRCUT_SERVICE_IDS.includes(parseInt(checkbox.value))) {
                        showHaircutOption = true;
                    }
                });

                if (showHaircutOption) {
                    haircutSelectionDiv.classList.remove('hidden');
                    haircutSelect.setAttribute('required', 'required');
                } else {
                    haircutSelectionDiv.classList.add('hidden');
                    haircutSelect.removeAttribute('required');
                    haircutSelect.value = '';
                }
            }

            serviceCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateHaircutSelection);
            });

            // Initial check
            updateHaircutSelection();

            // Date Picker Validation & Dynamic Time Restrictions
            const dateInput = document.getElementById('appointment_date');
            const timeInput = document.getElementById('appointment_time');
            const timeHelperText = document.querySelector('label[for="appointment_time"]').nextElementSibling.nextElementSibling; // The p element below the input

            function updateTimeSlots() {
                const dateVal = dateInput.value;
                if (!dateVal) return;

                const date = new Date(dateVal);
                const day = date.getDay(); // 0 = Sun, 1 = Mon, ..., 6 = Sat

                // Wednesday (3) - Closed
                // Wednesday (3) - Closed
                if (day === 3) {
                    // alert('Sorry, the shop is closed on Wednesdays. Please choose another date.'); // Blocking alert removed
                    
                    // Show inline error instead
                    const feedbackDiv = document.getElementById('availability-feedback');
                    feedbackDiv.classList.remove('hidden');
                    feedbackDiv.innerHTML = `
                        <div class="bg-red-900/30 border border-red-500 text-red-200 px-4 py-3 rounded-xl text-sm animate-fade-in-up">
                            <p class="font-bold flex items-center mb-1"><i class="fas fa-store-slash mr-2"></i> Shop Closed</p>
                            <p class="text-xs text-red-300">Sorry, we are closed on Wednesdays. Please choose another date.</p>
                        </div>
                    `;

                    dateInput.value = ''; // Reset date
                    timeInput.value = '';
                    timeInput.disabled = true;
                    if(timeHelperText) timeHelperText.innerHTML = '<i class="fas fa-store-slash mr-1"></i> Closed on Wednesdays';
                    return;
                } else {
                    // Clear error if valid day
                    const feedbackDiv = document.getElementById('availability-feedback');
                    if(feedbackDiv) {
                        feedbackDiv.classList.add('hidden');
                        feedbackDiv.innerHTML = '';
                    }
                }

                timeInput.disabled = false;

                // Friday (5) - 3PM to 11PM
                if (day === 5) {
                    timeInput.min = '15:00';
                    timeInput.max = '23:00';
                    if(timeHelperText) timeHelperText.innerHTML = '<i class="fas fa-clock mr-1"></i> Friday Hours: 3:00 PM - 11:00 PM';
                } 
                // Other Days - 11AM to 11PM
                else {
                    timeInput.min = '11:00';
                    timeInput.max = '23:00';
                    if(timeHelperText) timeHelperText.innerHTML = '<i class="fas fa-clock mr-1"></i> Daily Hours: 11:00 AM - 11:00 PM';
                }

                // CHECK FOR PAST TIME (If valid day)
                const now = new Date();
                const todayStr = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
                
                if (dateInput.value === todayStr) {
                    const currentHours = String(now.getHours()).padStart(2, '0');
                    const currentMinutes = String(now.getMinutes()).padStart(2, '0');
                    const currentTime = `${currentHours}:${currentMinutes}`;
                    
                    // If current time is LATER than the default opening time (min), update min
                    // e.g. Open at 11:00, it's 14:00 -> set min to 14:00
                    if (timeInput.min < currentTime) {
                        timeInput.min = currentTime;
                        
                        // Optional: Clear value if previously selected time is now invalid
                        if (timeInput.value && timeInput.value < currentTime) {
                            timeInput.value = '';
                        }

                        // Update helper text to indicate restriction
                        if(timeHelperText) {
                             timeHelperText.innerHTML += ' <span class="text-gold font-bold ml-2">(Past times blocked)</span>';
                        }
                    }
                }
            }

            dateInput.addEventListener('input', updateTimeSlots);
            dateInput.addEventListener('change', updateTimeSlots); // Handle date picker selection

            // --- REAL-TIME AVAILABILITY CHECK (40-min Gap) ---
            const barberSelect = document.getElementById('barber_id');
            const feedbackDiv = document.getElementById('availability-feedback');
            const submitBtn = document.querySelector('button[type="submit"]');

            async function checkAvailability() {
                const dateVal = dateInput.value;
                const timeVal = timeInput.value;
                const barberVal = barberSelect.value;

                // Reset feedback
                feedbackDiv.classList.add('hidden');
                feedbackDiv.innerHTML = '';
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

                if (!dateVal || !timeVal || !barberVal) return;

                // Create FormData
                const formData = new FormData();
                formData.append('date', dateVal);
                formData.append('time', timeVal);
                formData.append('barber_id', barberVal);

                try {
                    const response = await fetch('actions/check_availability.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    feedbackDiv.classList.remove('hidden');
                    
                    if (data.status === 'conflict') {
                        // Show Error & Suggestion
                        feedbackDiv.innerHTML = `
                            <div class="bg-red-900/30 border border-red-500 text-red-200 px-4 py-3 rounded-xl text-sm">
                                <p class="font-bold flex items-center mb-1"><i class="fas fa-exclamation-triangle mr-2"></i> ${data.message}</p>
                                <p class="text-xs text-red-300 italic">${data.suggestion}</p>
                            </div>
                        `;
                        // Disable Submit
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        
                        // Optional: Actionable Suggestion Click
                        // We could add a button to auto-fill the suggested time, but let's keep it simple for now.
                        
                    } else if (data.status === 'available') {
                        // Show Success
                        feedbackDiv.innerHTML = `
                            <div class="bg-green-900/30 border border-green-500 text-green-200 px-4 py-3 rounded-xl text-sm flex items-center">
                                <i class="fas fa-check-circle text-xl mr-3"></i>
                                <span>${data.message}</span>
                            </div>
                        `;
                    }

                } catch (error) {
                    console.error('Error checking availability:', error);
                }
            }

            // Listen for changes
            timeInput.addEventListener('change', checkAvailability);
            barberSelect.addEventListener('change', checkAvailability);
            dateInput.addEventListener('change', checkAvailability);    

            // Auto-hide alerts after 1 second
            // Auto-hide alerts logic
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-fade');
                alerts.forEach(function(alert) {
                    // Check if alert contains "Add to Calendar" button
                    if (alert.innerText.includes('Add to Calendar') || alert.innerHTML.includes('calendar-plus')) {
                        // Do NOT auto hide, or wait very long (e.g. 30 seconds)
                        setTimeout(function() {
                            alert.style.transition = 'opacity 0.5s ease';
                            alert.style.opacity = '0';
                            setTimeout(() => alert.remove(), 500);
                        }, 30000); 
                    } else {
                        // Normal auto-hide (3 seconds instead of 1 for better readability)
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    }
                });
            }, 3000); // Initial check after 3 seconds (increased from 1s)
        });    </script>

