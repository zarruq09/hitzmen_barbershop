<?php
// includes/customer_notification_script.php

// Ensure session is active and user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only run for logged-in users (customers)
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // FETCH INITIAL COUNT (Like Admin Dashboard)
    // We need to ensure we have a DB connection. 
    // Usually db.php is required by the parent page. 
    // If not, we might need to handle it, but for now we assume $pdo exists 
    // or we fetch it if missing (safest approach for an include).
    
    $initialNotifCount = 0;
    
    // Check if $pdo exists, if not try to include db.php
    if (!isset($pdo)) {
        // Attempt to find db.php relative to current script (this script is in includes/, db.php is in root)
        $dbPath = __DIR__ . '/../db.php'; 
        if (file_exists($dbPath)) {
            require_once $dbPath; 
        }
    }
    
    if (isset($pdo)) {
        try {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmtCount->execute([$userId]);
            $initialNotifCount = $stmtCount->fetchColumn();
        } catch (Exception $e) {
            // Settle for 0 if DB error
        }
    }
?>

<!-- Notification Sound Element -->
<audio id="notif-sound" src="assets/audio/customer_notification.mp3" preload="auto"></audio>

<script>
    // Audio Autoplay Policy Fix
    const audioEl = document.getElementById('notif-sound');
    const unlockAudio = () => {
        if(audioEl) {
            // Use volume instead of muted (sometimes safer for browser policies)
            const originalVolume = audioEl.volume;
            audioEl.volume = 0; // Silent
            
            audioEl.play().then(() => {
                audioEl.pause();
                audioEl.currentTime = 0;
                // Restore volume
                setTimeout(() => { audioEl.volume = 1; }, 200);
            }).catch(() => {});
        }
        document.body.removeEventListener('click', unlockAudio);
        document.body.removeEventListener('keydown', unlockAudio);
    };
    document.body.addEventListener('click', unlockAudio);
    document.body.addEventListener('keydown', unlockAudio);

    // NOTIFICATION LOGIC (Admin Style)
    // Initialize lastCount with PHP value from page load
    let lastNotifCount = <?php echo intval($initialNotifCount); ?>;

    // CHECK FOR USER ACTION ON LOAD
    // We check this ONCE when the page loads, because the alert might auto-hide 
    // before the first poll (5s) triggers.
    const successAlert = document.querySelector('.alert-fade');
    // Check if alert exists AND contains success keywords
    let suppressSound = successAlert && (
        successAlert.textContent.includes('Success') || 
        successAlert.textContent.includes('Berjaya') ||
        successAlert.textContent.includes('cancelled') ||
        successAlert.textContent.includes('booked')
    );
    
    if (suppressSound) {
        // console.log('Notification Sound: SUPPRESSED for this session due to user action.');
    }
    
    function fetchGlobalNotifications() {
        const notifUrl = 'actions/fetch_notifications.php'; 
        
        fetch(notifUrl)
            .then(response => response.json())
            .then(data => {
                // 1. Play Sound & Logic checks
                const currentCount = parseInt(data.count) || 0;
                
                // Only play if current count is GREATER than the last known count
                if (currentCount > lastNotifCount) {
                    
                    if (suppressSound) {
                        // User just performed an action, so we ignore this specific increment
                        // We do nothing (silence)
                        // console.log('Sound blocked by suppression flag.');
                        
                        // IMPORTANT: Reset the flag so future notifications DO sound
                        suppressSound = false;
                    } else {
                        const audio = document.getElementById('notif-sound');
                        if (audio) {
                            // Reset current time to allow rapid re-plays if needed
                            audio.currentTime = 0;
                            const playPromise = audio.play();
                            if (playPromise !== undefined) {
                                playPromise.catch(error => {
                                    // console.log('Autoplay blocked');
                                });
                            }
                        }
                    }
                }
                
                // Update tracker
                lastNotifCount = currentCount;

                // 2. Update Bell Badge
                const bellBtn = document.querySelector('#notif-container button');
                if (bellBtn) {
                     let badge = bellBtn.querySelector('span');
                     if (currentCount > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-dark-card text-[10px] text-white flex items-center justify-center font-bold animate-pulse';
                            bellBtn.appendChild(badge);
                        }
                        badge.textContent = currentCount;
                    } else if (badge) {
                        badge.remove();
                    }
                }

                // 3. Update Dropdown Content
                const dropdownContent = document.querySelector('#notif-dropdown .overflow-y-auto');
                if (dropdownContent && data.notifications) {
                     if (data.notifications.length === 0) {
                        if(!dropdownContent.innerHTML.includes('No pending bookings') && !dropdownContent.innerHTML.includes('No new notifications')) {
                             dropdownContent.innerHTML = '<div class="p-6 text-center text-gray-500 text-sm italic">No new notifications.</div>';
                        }
                    } else {
                         let html = '';
                        data.notifications.forEach(notif => {
                            const isReadClass = notif.is_read == 1 ? 'opacity-60' : '';
                            let icon = '<i class="fas fa-info-circle text-blue-500"></i>';
                            if (notif.type === 'success') icon = '<i class="fas fa-check-circle text-green-500"></i>';
                            if (notif.type === 'error') icon = '<i class="fas fa-times-circle text-red-500"></i>';

                            html += `
                                <div class="p-3 border-b border-dark-border hover:bg-dark-hover transition flex gap-3 ${isReadClass}">
                                    <div class="mt-1">${icon}</div>
                                    <div>
                                        <p class="text-xs text-gray-300">${notif.message}</p>
                                        <span class="text-[10px] text-gray-600 block mt-1">Just now</span>
                                    </div>
                                </div>
                            `;
                        });
                        dropdownContent.innerHTML = html;
                    }
                }

            })
            .catch(err => { });
    }

    // Poll every 5 seconds
    setInterval(fetchGlobalNotifications, 5000);
</script>
<?php } ?>
