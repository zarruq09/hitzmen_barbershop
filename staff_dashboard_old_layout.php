<?php
session_start();
require_once __DIR__ . '/db.php';

// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$today = date('Y-m-d');

// Fetch Barber Profile
$stmt = $pdo->prepare("SELECT * FROM barbers WHERE user_id = ?");
$stmt->execute([$userId]);
$barber = $stmt->fetch(PDO::FETCH_ASSOC);

$errorMsg = null;
$appointments = [];
$totalCuts = 0;
$pendingCuts = 0;
$nextAppt = null;

if (!$barber) {
    $errorMsg = "Your account is not linked to a barber profile. Please contact the administrator.";
} else {
    $barberId = $barber['id'];

    // Fetch Services Map
    $servicesMap = [];
    $stmtServices = $pdo->query("SELECT id, service_name FROM services");
    while ($r = $stmtServices->fetch(PDO::FETCH_ASSOC)) {
        $servicesMap[$r['id']] = $r['service_name'];
    }

    // Fetch Today's Appointments
    $stmtAppt = $pdo->prepare("
        SELECT a.*, u.username as customer_name, u.full_name as customer_real_name, u.phone as customer_phone
        FROM appointments a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.barber_id = ? AND a.appointment_date = ?
        ORDER BY a.appointment_time ASC
    ");
    $stmtAppt->execute([$barberId, $today]);
    $appointments = $stmtAppt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Stats & Find Next Appointment
    $currTime = time(); 
    foreach($appointments as $appt) {
        if($appt['status'] === 'Completed') {
            $totalCuts++;
        } elseif ($appt['status'] === 'Confirmed' || $appt['status'] === 'Pending') {
            $pendingCuts++;
            
            // Logic for "Next Key Appointment"
            // We want the first non-completed, non-cancelled appointment.
            // Since list is ordered by time, the first one we hit here is the "Next" one.
            if(is_null($nextAppt)) {
                $nextAppt = $appt;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Deck | Hitzmen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        'gold-light': '#E5C580',
                        'gold-dark': '#9A7B3E',
                        dark: '#0A0A0A',
                        'dark-card': '#141414',
                        'dark-border': '#2A2A2A',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif']
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'gold-gradient': 'linear-gradient(135deg, #C5A059 0%, #9A7B3E 100%)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { background-color: #050505; background-image: radial-gradient(circle at top right, #1A1A1A 0%, #000000 100%); }
        .glass-panel { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .glass-header { 
            background: rgba(5, 5, 5, 0.8); 
            backdrop-filter: blur(16px); 
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .hero-card {
            background: linear-gradient(160deg, rgba(197, 160, 89, 0.15) 0%, rgba(0,0,0,0) 100%);
            border: 1px solid rgba(197, 160, 89, 0.3);
            position: relative;
            /* overflow: hidden; Removed to allow dropdown */
        }
        .hero-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, #C5A059, transparent);
            opacity: 0.5;
        }
        .appt-card { transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .appt-card:active { transform: scale(0.98); }
    </style>
</head>
<body class="text-gray-100 font-sans min-h-screen pb-24 antialiased selection:bg-gold selection:text-black">

    <!-- Header -->
    <header class="glass-header sticky top-0 z-40 px-5 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-full bg-gold/10 flex items-center justify-center border border-gold/30">
                <i class="fas fa-cut text-gold text-lg"></i>
             </div>
             <div>
                 <h1 class="font-heading font-bold text-lg leading-tight text-white mb-0.5">Staff Deck</h1>
                 <div class="flex items-center gap-2 text-[10px] sm:text-xs text-gray-500 font-medium">
                     <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                     LIVE
                 </div>
             </div>
        </div>
        
        <?php if(isset($barber)): ?>
        <div class="relative">
            <button onclick="toggleStatusMenu()" id="statusBtn" class="relative z-10 flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 bg-white/5 backdrop-blur-md text-xs font-bold transition-all hover:bg-white/10 hover:border-gold/30">
                <span id="statusIndicator" class="w-2 h-2 rounded-full <?= ($barber['status'] === 'Available') ? 'bg-green-500' : ( ($barber['status'] === 'On Break') ? 'bg-yellow-500' : 'bg-red-500' ) ?>"></span>
                <span id="statusText" class="uppercase tracking-wider"><?= $barber['status'] ?></span>
                <i class="fas fa-chevron-down text-[10px] ml-1 opacity-50"></i>
            </button>
            <!-- Dropdown -->
            <div id="statusDropdown" class="absolute right-0 top-full mt-3 w-40 bg-[#151515] border border-white/10 rounded-xl shadow-2xl overflow-hidden hidden transform transition-all duration-200 origin-top-right z-50">
                <button onclick="updateStatus('Available')" class="w-full text-left px-5 py-3 text-xs font-bold text-green-400 hover:bg-white/5 border-b border-white/5 transition flex items-center justify-between">
                    AVAILABLE <i class="fas fa-check opacity-0 group-hover:opacity-100"></i>
                </button>
                <button onclick="updateStatus('On Break')" class="w-full text-left px-5 py-3 text-xs font-bold text-yellow-500 hover:bg-white/5 border-b border-white/5 transition flex items-center justify-between">
                    ON BREAK <i class="fas fa-coffee opacity-0 group-hover:opacity-100"></i>
                </button>
                <button onclick="updateStatus('Unavailable')" class="w-full text-left px-5 py-3 text-xs font-bold text-red-500 hover:bg-white/5 transition flex items-center justify-between">
                    OFF DUTY <i class="fas fa-times opacity-0 group-hover:opacity-100"></i>
                </button>
            </div>
            <!-- Overlay for closing -->
            <div id="dropdownOverlay" onclick="toggleStatusMenu()" class="fixed inset-0 z-40 hidden cursor-default"></div>
        </div>
        <?php endif; ?>
    </header>

    <main class="container mx-auto px-4 mt-6 max-w-xl">
        
        <?php if($errorMsg): ?>
            <div class="p-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-center">
                <i class="fas fa-ban text-3xl text-red-500 mb-2"></i>
                <p class="text-red-200 text-sm font-medium"><?= $errorMsg ?></p>
            </div>
        <?php else: ?>

            <!-- STATUS BAR -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="glass-panel p-4 rounded-2xl flex flex-col items-center justify-center relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gold/5 group-hover:bg-gold/10 transition duration-500"></div>
                    <span class="text-3xl font-heading font-bold text-white relative z-10"><?= $totalCuts ?></span>
                    <span class="text-[10px] uppercase tracking-[0.2em] text-gold font-bold mt-1 relative z-10">Completed</span>
                </div>
                <div class="glass-panel p-4 rounded-2xl flex flex-col items-center justify-center relative overflow-hidden group">
                    <div class="absolute inset-0 bg-white/5 group-hover:bg-white/10 transition duration-500"></div>
                     <span class="text-3xl font-heading font-bold text-gray-300 relative z-10"><?= $pendingCuts ?></span>
                    <span class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold mt-1 relative z-10">Queue</span>
                </div>
            </div>

            <!-- HERO: NEXT CLIENT -->
            <?php if($nextAppt): 
                 $nServiceIds = json_decode($nextAppt['services_ids_json'] ?? '[]', true);
                 $nServiceNames = [];
                 if(is_array($nServiceIds)) foreach($nServiceIds as $sid) if(isset($servicesMap[$sid])) $nServiceNames[] = $servicesMap[$sid];
                 $nServiceStr = implode(', ', $nServiceNames);
                 $nTime = strtotime($nextAppt['appointment_time']);
                 
                 // WhatsApp Link Logic
                 $cleanPhone = preg_replace('/^0/', '60', preg_replace('/[^0-9]/', '', $nextAppt['customer_phone'] ?? ''));
                 $custName = htmlspecialchars($nextAppt['customer_real_name'] ?? $nextAppt['customer_name']);
                 $apptTime = date('h:i A', $nTime);
                 
                 // Standard Contact Link
                 $waLink = "https://wa.me/$cleanPhone";
                 
                 $lateMsg = urlencode("Hi $custName, kami dari Hitzmen Barbershop. Appointment anda pukul $apptTime tadi. Kami dah ready, boleh datang sekarang ya? Terima kasih! 💈");
                 $waLateLink = "https://wa.me/$cleanPhone?text=$lateMsg";

                 // Client Insights Logic
                 $cUserId = $nextAppt['user_id'];
                 $stmtLast = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? AND status = 'Completed' AND id != ? ORDER BY appointment_time DESC LIMIT 1");
                 $stmtLast->execute([$cUserId, $nextAppt['id']]);
                 $lastAppt = $stmtLast->fetch(PDO::FETCH_ASSOC);

                 $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND status = 'Completed'");
                 $stmtCount->execute([$cUserId]);
                 $visitCount = $stmtCount->fetchColumn();
                 
                 $isLoyal = $visitCount >= 5;
                 
                 $lastVisitStr = "First Visit";
                 if($lastAppt) {
                     $lTime = strtotime($lastAppt['appointment_time']);
                     $daysAgo = floor((time() - $lTime) / (60 * 60 * 24));
                     $timeAgo = ($daysAgo == 0) ? "Today" : (($daysAgo == 1) ? "Yesterday" : "$daysAgo days ago");
                     
                     // Get service names for last visit
                     $lServiceIds = json_decode($lastAppt['services_ids_json'] ?? '[]', true);
                     $lServiceNames = [];
                     if(is_array($lServiceIds)) foreach($lServiceIds as $sid) if(isset($servicesMap[$sid])) $lServiceNames[] = $servicesMap[$sid];
                     $lServiceStr = implode(', ', $lServiceNames);
                     $lastVisitStr = "<span class='text-gold'>Last:</span> $timeAgo ($lServiceStr)";
                 }
            ?>
            <div class="mb-4">
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 px-1">Up Next</h2>
                <div class="hero-card glass-panel rounded-3xl p-6 shadow-2xl shadow-gold/5 relative">
                    <div class="flex justify-between items-start mb-6 relative z-20 pt-2">
                         <div>
                             <span class="inline-block px-2 py-1 bg-gold/20 text-gold text-[10px] font-bold rounded uppercase mb-2 border border-gold/20">
                                <i class="far fa-clock mr-1"></i> <span id="countdown">Calculating...</span>
                             </span>
                             <h3 class="text-2xl font-heading font-bold text-white leading-tight flex items-center gap-2">
                                <?= $custName ?>
                                <?php if($isLoyal): ?>
                                    <i class="fas fa-crown text-gold text-sm animate-pulse" title="Loyal Customer (<?= $visitCount ?> visits)"></i>
                                <?php endif; ?>
                             </h3>
                             <p class="text-sm text-gray-400 mt-1 font-medium"><?= $nServiceStr ?></p>
                             
                             <!-- Client Insights (Last Visit) -->
                             <div class="mt-2 text-[10px] text-gray-500 font-mono border-l-2 border-white/10 pl-2">
                                <?= $lastVisitStr ?>
                             </div>
                         </div>
                         <div class="text-right">
                             <div class="text-3xl font-bold text-white font-mono tracking-tighter"><?= date('h:i', $nTime) ?></div>
                             <div class="text-xs text-gray-500 font-bold uppercase"><?= date('A', $nTime) ?></div>
                         </div>
                    </div>

                    <div class="flex items-stretch gap-3">
                        <button onclick="updateAppt(<?= $nextAppt['id'] ?>, 'Completed')" class="flex-1 py-3 px-4 bg-gold hover:bg-gold-light text-black font-bold rounded-xl shadow-lg shadow-gold/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                            <i class="fas fa-check"></i> Complete
                        </button>
                        
                        <!-- Actions Container -->
                        <div class="relative flex-1">
                            <!-- Standard Contact -->
                            <a id="btnContact" href="<?= $waLink ?>" target="_blank" class="w-full h-full py-3 px-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-xl border border-white/10 transition transform active:scale-95 flex items-center justify-center gap-2">
                                 <i class="fab fa-whatsapp"></i> Contact
                            </a>
                            
                            <!-- Late Notification (Hidden initially) -->
                            <a id="btnNotify" href="<?= $waLateLink ?>" target="_blank" class="hidden w-full h-full py-3 px-4 bg-red-500/10 hover:bg-red-500/20 text-red-500 hover:text-red-400 font-bold rounded-xl border border-red-500/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                                 <i class="fas fa-bell animate-bounce"></i> Notify Late
                            </a>
                        </div>

                        <!-- More Options Menu -->
                        <div class="relative">
                            <button onclick="toggleHeroMenu()" class="h-full w-12 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white flex items-center justify-center transition border border-white/5">
                                <i class="fas fa-ellipsis-v text-sm"></i>
                            </button>
                            <div id="heroMenuDropdown" class="absolute right-0 bottom-full mb-2 w-48 bg-dark-card border border-white/10 rounded-xl shadow-2xl overflow-hidden hidden transform transition-all duration-200 origin-bottom-right z-30">
                                <button onclick="updateAppt(<?= $nextAppt['id'] ?>, 'Cancelled')" class="w-full text-left px-5 py-3 text-xs text-orange-400 hover:bg-white/5 border-b border-white/5 font-medium flex items-center justify-between">
                                    Cancelled <i class="fas fa-ban opacity-50"></i>
                                </button>
                                <button onclick="updateAppt(<?= $nextAppt['id'] ?>, 'No Show')" class="w-full text-left px-5 py-3 text-xs text-red-400 hover:bg-white/5 font-medium flex items-center justify-between">
                                    No Show <i class="fas fa-ghost opacity-50"></i>
                                </button>
                            </div>
                            <div id="heroMenuOverlay" onclick="toggleHeroMenu()" class="fixed inset-0 z-20 hidden cursor-default invert"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                // Countdown Logic
                const targetTime = new Date();
                targetTime.setHours(<?= date('H', $nTime) ?>, <?= date('i', $nTime) ?>, 0, 0);
                
                function updateTimer() {
                    const now = new Date();
                    const diff = targetTime - now;
                    const el = document.getElementById('countdown');
                    const btnContact = document.getElementById('btnContact');
                    const btnNotify = document.getElementById('btnNotify');
                    
                    if(diff <= 0) {
                        el.innerText = 'Now / Late';
                        el.classList.remove('text-gold');
                        el.classList.add('text-red-400');
                        
                        // Switch to Notify Button
                        if(btnContact && btnNotify) {
                            btnContact.classList.add('hidden');
                            btnNotify.classList.remove('hidden');
                             btnNotify.classList.add('flex');
                        }
                        return;
                    }
                    
                    const mins = Math.floor(diff / 60000);
                    if(mins > 60) {
                        const hrs = Math.floor(mins/60);
                        el.innerText = `In ${hrs}h ${mins%60}m`;
                    } else {
                        el.innerText = `In ${mins} mins`;
                    }
                }
                setInterval(updateTimer, 1000);
                updateTimer();
            </script>

            <?php else: ?>
                <!-- Empty State Hero -->
                 <div class="mb-8 p-8 glass-panel rounded-3xl text-center border-dashed border-gray-800">
                     <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                         <i class="fas fa-mug-hot text-gray-600 text-xl"></i>
                     </div>
                     <h3 class="text-white font-bold text-lg">All Caught Up!</h3>
                     <p class="text-gray-500 text-sm mt-1">No upcoming appointments right now.</p>
                 </div>
            <?php endif; ?>

            <!-- UPCOMING LIST -->
             <div>
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 px-1 mt-8">Full Schedule</h2>
                <div class="space-y-3">
                    <?php if(empty($appointments)): ?>
                         <p class="text-center text-gray-600 text-sm py-4">No bookings for today.</p>
                    <?php else: ?>
                        <?php foreach($appointments as $appt): 
                            if($nextAppt && $appt['id'] == $nextAppt['id']) continue; // Skip hero item
                            
                            $serviceIds = json_decode($appt['services_ids_json'] ?? '[]', true);
                            $serviceNames = [];
                            if(is_array($serviceIds)) foreach($serviceIds as $sid) if(isset($servicesMap[$sid])) $serviceNames[] = $servicesMap[$sid];
                            $serviceStr = implode(', ', $serviceNames);
                            
                            $isDone = ($appt['status'] === 'Completed');
                            $isCancelled = ($appt['status'] === 'Cancelled' || $appt['status'] === 'No Show');
                            $opacityClass = ($isDone || $isCancelled) ? 'opacity-50 grayscale' : '';
                        ?>
                        <div class="glass-panel p-4 rounded-xl flex items-center justify-between appt-card group <?= $opacityClass ?>">
                            <div class="flex items-center gap-4">
                                <div class="text-center w-12">
                                     <div class="text-sm font-bold text-white"><?= date('h:i', strtotime($appt['appointment_time'])) ?></div>
                                     <div class="text-[10px] items-center text-gray-500 font-bold"><?= date('A', strtotime($appt['appointment_time'])) ?></div>
                                </div>
                                <div class="h-8 w-px bg-white/10"></div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-200"><?= htmlspecialchars($appt['customer_real_name'] ?? $appt['customer_name']) ?></h4>
                                    <p class="text-xs text-gold/80"><?= htmlspecialchars($serviceStr) ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <?php if(!$isDone && !$isCancelled): ?>
                                    <button onclick="updateAppt(<?= $appt['id'] ?>, 'Completed')" class="w-8 h-8 rounded-full bg-white/5 hover:bg-green-500/20 text-gray-400 hover:text-green-500 flex items-center justify-center transition border border-white/5">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                     <div class="relative group/menu">
                                        <button class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 text-gray-400 flex items-center justify-center transition border border-white/5">
                                            <i class="fas fa-ellipsis-v text-xs"></i>
                                        </button>
                                         <div class="absolute right-0 top-8 w-32 bg-dark-card border border-white/10 rounded-lg shadow-xl overflow-hidden hidden group-hover/menu:block z-30">
                                            <button onclick="updateAppt(<?= $appt['id'] ?>, 'No Show')" class="w-full text-left px-3 py-2 text-xs text-red-400 hover:bg-white/5">No Show</button>
                                            <button onclick="updateAppt(<?= $appt['id'] ?>, 'Cancelled')" class="w-full text-left px-3 py-2 text-xs text-gray-400 hover:bg-white/5">Cancel</button>
                                        </div>
                                     </div>
                                <?php else: ?>
                                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded bg-white/5 text-gray-500 border border-white/5"><?= $appt['status'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
             </div>

        <?php endif; ?>
    </main>

    <!-- Bottom Nav / Info -->
    <footer class="fixed bottom-0 w-full glass-header py-4 px-6 flex justify-between items-center text-[10px] text-gray-600 z-50">
         <span>Hitzmen Staff v2.0</span>
         <span class="flex items-center gap-1"><i class="fas fa-circle text-[6px] text-green-500 animate-pulse"></i> Connected</span>
    </footer>

    <script>
        // Auto Refresh every 60s
        setTimeout(() => {
            window.location.reload();
        }, 60000);

        function toggleStatusMenu() {
            const dropdown = document.getElementById('statusDropdown');
            const overlay = document.getElementById('dropdownOverlay');
            
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                dropdown.classList.add('scale-100', 'opacity-100');
                dropdown.classList.remove('scale-95', 'opacity-0');
                overlay.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
                dropdown.classList.remove('scale-100', 'opacity-100');
                dropdown.classList.add('scale-95', 'opacity-0');
                overlay.classList.add('hidden');
            }
        }

        function toggleHeroMenu() {
            const dropdown = document.getElementById('heroMenuDropdown');
            const overlay = document.getElementById('heroMenuOverlay');
            
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                dropdown.classList.add('scale-100', 'opacity-100');
                dropdown.classList.remove('scale-95', 'opacity-0');
                overlay.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
                dropdown.classList.remove('scale-100', 'opacity-100');
                dropdown.classList.add('scale-95', 'opacity-0');
                overlay.classList.add('hidden');
            }
        }

        function updateStatus(status) {
            fetch('actions/toggle_barber_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `status=${status}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
            });
        }

        function updateAppt(id, status) {
            if(!confirm(`Mark as ${status}?`)) return;
            fetch('actions/update_appointment_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `appointment_id=${id}&status=${status}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert(data.message);
            });
        }
    </script>
</body>
</html>