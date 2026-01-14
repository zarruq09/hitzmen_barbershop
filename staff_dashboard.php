<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/actions/auto_sync_schedule.php'; // Auto-sync schedule status
require_once __DIR__ . '/includes/csrf_token.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Hitzmen Barbershop</title>
    <link rel="icon" type="image/x-icon" href="assets/images/Logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        'gold-light': '#D4AF37',
                        dark: '#121212',
                        'dark-card': '#1E1E1E',
                        'dark-hover': '#252525',
                        'dark-border': '#333333'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <script>
        const CSRF_TOKEN = "<?= generateCsrfToken() ?>";
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .sidebar-link { 
            transition: all 0.2s ease; 
            border-right: 3px solid transparent; 
        }
        .sidebar-link:hover, .sidebar-link.active-tab { 
            background: linear-gradient(90deg, rgba(197, 160, 89, 0.1) 0%, transparent 100%); 
            border-right-color: #C5A059; 
            color: #C5A059; 
        }
        .glass-header { background: rgba(18, 18, 18, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid #333; }
        .glass-panel { background: rgba(30, 30, 30, 0.4); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .hero-gradient { background: linear-gradient(135deg, rgba(197, 160, 89, 0.1) 0%, rgba(0,0,0,0) 100%); }
        
        /* Force Sidebar Open Mobile */
        .mobile-force-open {
            transform: translateX(0) !important;
        }
        
        /* Force Sidebar Closed Desktop (Negative Margin to collapse space) */
        .desktop-closed {
            margin-left: -16rem !important; /* w-64 = 16rem */
            transform: translateX(-100%) !important;
            opacity: 0 !important;
        }
    </style>
</head>
<body class="bg-dark text-gray-100 font-sans h-[100dvh] overflow-hidden flex">

    <?php
    $view = $_GET['view'] ?? 'dashboard';
    ?>
    <!-- SIDEBAR -->
    <!-- MOBILE OVERLAY -->
    <div id="sidebar-overlay" onclick="toggleStaffSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden glass-panel backdrop-blur-sm transition-opacity"></div>

    <aside class="fixed inset-y-0 left-0 w-64 bg-dark-card border-r border-dark-border flex flex-col z-[9999] transition-all duration-300 transform -translate-x-full md:relative md:translate-x-0" id="sidebar">
        <!-- Logo -->
        <div class="h-20 flex items-center justify-center border-b border-dark-border relative">
            <div class="flex items-center space-x-3 px-6 w-full">
                <img src="assets/images/Logo.png" alt="Logo" class="w-10 h-10 object-contain">
                <h2 class="text-xl font-heading font-bold text-white tracking-wide">HITZ<span class="text-gold">MEN</span></h2>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-6">
            <ul class="space-y-1 px-3">
                <li>
                    <a href="staff_dashboard.php?view=dashboard" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg <?= $view === 'dashboard' ? 'text-gold bg-gold/10 border-r-gold border-r-2 active-tab' : 'text-gray-400 group hover:bg-dark-hover transition-all' ?>">
                        <i class="fas fa-home w-6 text-lg <?= $view === 'dashboard' ? '' : 'group-hover:text-gold' ?>"></i>
                        <span class="ml-3 font-medium <?= $view === 'dashboard' ? 'text-white' : 'group-hover:text-white' ?>">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="staff_dashboard.php?view=history" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg <?= $view === 'history' ? 'text-gold bg-gold/10 border-r-gold border-r-2 active-tab' : 'text-gray-400 group hover:bg-dark-hover transition-all' ?>">
                        <i class="fas fa-history w-6 text-lg <?= $view === 'history' ? '' : 'group-hover:text-gold' ?>"></i>
                        <span class="ml-3 font-medium <?= $view === 'history' ? 'text-white' : 'group-hover:text-white' ?>">My History</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User/Logout -->
        <div class="border-t border-dark-border p-4">
             <div class="flex items-center p-2 rounded-lg bg-dark hover:bg-dark-hover cursor-pointer transition w-full overflow-hidden group">
                <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-dark font-bold text-sm shrink-0">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($username) ?></p>
                    <p class="text-xs text-gray-500 truncate">Staff Member</p>
                </div>
                <a href="logout.php" class="ml-auto text-gray-500 hover:text-red-500 transition px-2" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="bg-dark-card border-b border-dark-border px-8 py-5 flex justify-between items-center sticky top-0 z-30 glass-header">
            <div class="flex items-center gap-4">
                <button class="text-gray-300 hover:text-gold md:hidden" onclick="toggleStaffSidebar()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <button class="text-gray-300 hover:text-gold hidden md:block" onclick="toggleStaffSidebar()">
                     <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-xl md:text-2xl font-heading font-bold text-white tracking-wide">Staff Deck</h1>
                    <p class="text-gray-500 text-xs md:text-sm mt-1">Hello, <?= htmlspecialchars($barber['name'] ?? $username) ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <?php if(isset($barber)): ?>
                <!-- Status Toggle -->
                <div class="relative">
                    <button onclick="toggleStatusMenu()" id="statusBtn" class="flex items-center gap-2 px-4 py-2 rounded-full border border-dark-border bg-dark hover:bg-dark-hover transition text-xs font-bold shadow-lg">
                        <span id="statusIndicator" class="w-2 h-2 rounded-full <?= ($barber['status'] === 'Available') ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                        <span id="statusText" class="uppercase tracking-wider text-gray-300"><?= $barber['status'] ?></span>
                        <i class="fas fa-chevron-down text-[10px] ml-1 opacity-50 text-gray-500"></i>
                    </button>
                    <!-- Status Dropdown -->
                    <div id="statusDropdown" class="absolute right-0 top-full mt-2 w-48 bg-dark-card border border-dark-border rounded-xl shadow-2xl overflow-hidden hidden transform transition-all z-50">
                        <button onclick="updateStatus('Available')" class="w-full text-left px-5 py-3 text-xs font-bold text-green-400 hover:bg-dark-hover border-b border-dark-border transition flex items-center justify-between">
                            AVAILABLE <i class="fas fa-check opacity-0 group-hover:opacity-100"></i>
                        </button>

                        <button onclick="updateStatus('Unavailable')" class="w-full text-left px-5 py-3 text-xs font-bold text-red-500 hover:bg-dark-hover transition flex items-center justify-between">
                            OFF DUTY <i class="fas fa-times opacity-0 group-hover:opacity-100"></i>
                        </button>
                    </div>
                     <!-- Overlay -->
                    <div id="dropdownOverlay" onclick="toggleStatusMenu()" class="fixed inset-0 z-40 hidden cursor-default"></div>
                </div>
                <?php endif; ?>

                <div class="w-px h-8 bg-dark-border mx-2"></div>

                <!-- Simple Clock/Date -->
                <div class="text-right hidden sm:block">
                     <div id="clock-time" class="text-sm font-bold text-white">--:--</div>
                     <div id="clock-date" class="text-[10px] text-gray-500 uppercase">-- --- ----</div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative" id="main-content">
            <div class="max-w-6xl mx-auto space-y-6 animate-fade-in">
                
                <?php if($errorMsg): ?>
                    <div class="p-6 rounded-2xl bg-red-900/20 border border-red-500/20 text-center">
                        <i class="fas fa-ban text-3xl text-red-500 mb-2"></i>
                        <p class="text-red-200 text-sm font-medium"><?= $errorMsg ?></p>
                    </div>
                <?php else: ?>

                <?php if ($view === 'history'): ?>
                    <?php include 'includes/staff_history_partial.php'; ?>
                <?php else: ?>

                <!-- Top Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                     <div class="bg-dark-card p-5 rounded-xl border border-dark-border flex items-center justify-between relative overflow-hidden group">
                        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition">
                             <i class="fas fa-cut text-5xl"></i>
                        </div>
                        <div>
                             <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Completed</p>
                             <h3 class="text-3xl font-heading font-bold text-white mt-1"><?= $totalCuts ?></h3>
                        </div>
                     </div>
                     <div class="bg-dark-card p-5 rounded-xl border border-dark-border flex items-center justify-between relative overflow-hidden group">
                        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition">
                             <i class="fas fa-user-clock text-5xl"></i>
                        </div>
                        <div>
                             <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">In Queue</p>
                             <h3 class="text-3xl font-heading font-bold text-gold mt-1"><?= $pendingCuts ?></h3>
                        </div>
                     </div>
                </div>

                <!-- HERO: Next Appointment -->
                <?php if($nextAppt): 
                        $nServiceIds = json_decode($nextAppt['services_ids_json'] ?? '[]', true);
                        $nServiceNames = [];
                        if(is_array($nServiceIds)) foreach($nServiceIds as $sid) if(isset($servicesMap[$sid])) $nServiceNames[] = $servicesMap[$sid];
                        $nServiceStr = implode(', ', $nServiceNames);
                        $nTime = strtotime($nextAppt['appointment_time']);
                        
                        $cleanPhone = preg_replace('/^0/', '60', preg_replace('/[^0-9]/', '', $nextAppt['customer_phone'] ?? ''));
                        $custName = htmlspecialchars($nextAppt['customer_real_name'] ?? $nextAppt['customer_name']);
                        $apptTime = date('h:i A', $nTime);
                        
                        $waLink = "https://wa.me/$cleanPhone";
                        $lateMsg = urlencode("Hi $custName, kami dari Hitzmen Barbershop. Appointment anda pukul $apptTime tadi. Kami dah ready, boleh datang sekarang ya? Terima kasih! 💈");
                        $waLateLink = "https://wa.me/$cleanPhone?text=$lateMsg";

                        // Client Insights
                        $cUserId = $nextAppt['user_id'];
                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND status = 'Completed'");
                        $stmtCount->execute([$cUserId]);
                        $visitCount = $stmtCount->fetchColumn();
                        $isLoyal = $visitCount >= 5;
                ?>
                <div class="bg-dark-card rounded-2xl border border-gold/30 p-6 md:p-8 relative hero-gradient shadow-2xl relative overflow-hidden">
                     <div class="absolute top-0 right-0 w-64 h-64 bg-gold/5 rounded-full filter blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                     
                     <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div class="flex-1">
                             <div class="inline-flex items-center gap-2 px-3 py-1 bg-gold/10 border border-gold/20 rounded-full text-[10px] font-bold text-gold uppercase tracking-widest mb-3">
                                 <i class="far fa-clock"></i>
                                 <span id="countdown">Calculating...</span>
                             </div>
                             <h2 class="text-3xl md:text-5xl font-heading font-bold text-white mb-2"><?= $custName ?> <?php if($isLoyal): ?><i class="fas fa-crown text-gold text-2xl animate-pulse ml-2" title="Loyal Client"></i><?php endif; ?></h2>
                             <p class="text-lg text-gray-300"><?= $nServiceStr ?></p>
                             <p class="text-xs text-gray-500 mt-2 font-mono">Total Visits: <?= $visitCount ?></p>
                        </div>
                        
                        <div class="text-right">
                             <div class="text-4xl md:text-6xl font-bold text-white font-mono tracking-tighter"><?= date('h:i', $nTime) ?></div>
                             <div class="text-sm text-gray-500 font-bold uppercase tracking-widest"><?= date('A', $nTime) ?></div>
                        </div>
                     </div>

                     <div class="h-px bg-white/5 my-6 relative z-10"></div>

                     <div class="flex flex-col sm:flex-row gap-4 relative z-10">
                         <button onclick="updateAppt(<?= $nextAppt['id'] ?>, 'Completed')" class="flex-1 py-4 bg-gold hover:bg-gold-light text-black font-bold rounded-xl shadow-lg shadow-gold/10 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            <i class="fas fa-check"></i> Complete Job
                         </button>
                         
                         <a id="btnContact" href="<?= $waLink ?>" target="_blank" class="flex-1 py-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-xl border border-white/10 transition flex items-center justify-center gap-2">
                             <i class="fab fa-whatsapp"></i> WhatsApp
                         </a>
                         
                         <a id="btnNotify" href="<?= $waLateLink ?>" target="_blank" class="hidden flex-1 py-4 bg-red-900/20 hover:bg-red-900/30 text-red-500 border border-red-500/30 font-bold rounded-xl transition flex items-center justify-center gap-2">
                             <i class="fas fa-bell"></i> Notify Late
                         </a>

                         <div class="relative">
                            <button onclick="toggleHeroMenu()" class="h-full w-14 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white flex items-center justify-center transition border border-white/5">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <!-- Dropdown -->
                            <div id="heroMenuDropdown" class="absolute right-0 bottom-full mb-2 w-48 bg-dark-card border border-dark-border rounded-xl shadow-xl hidden z-40 overflow-hidden">
                                <button onclick="updateAppt(<?= $nextAppt['id'] ?>, 'Cancelled')" class="w-full text-left px-5 py-3 text-xs text-red-400 hover:bg-white/5">Cancel Appointment</button>
                                <button onclick="updateAppt(<?= $nextAppt['id'] ?>, 'No Show')" class="w-full text-left px-5 py-3 text-xs text-orange-400 hover:bg-white/5">Mark No Show</button>
                            </div>
                            <div id="heroMenuOverlay" onclick="toggleHeroMenu()" class="fixed inset-0 z-30 hidden"></div>
                         </div>
                     </div>
                </div>

                <script>
                    const targetTime = new Date();
                    targetTime.setHours(<?= date('H', $nTime) ?>, <?= date('i', $nTime) ?>, 0, 0);
                    function updateTimer() {
                        const now = new Date();
                        const diff = targetTime - now;
                        const el = document.getElementById('countdown');
                        if(diff <= 0) {
                            el.innerText = 'NOW DUE / LATE';
                            el.parentElement.classList.replace('text-gold', 'text-red-500');
                            el.parentElement.classList.replace('bg-gold/10', 'bg-red-500/10');
                            document.getElementById('btnContact').classList.add('hidden');
                            document.getElementById('btnNotify').classList.remove('hidden');
                            document.getElementById('btnNotify').classList.add('flex');
                            return;
                        }
                        const mins = Math.floor(diff / 60000);
                        el.innerText = (mins > 60) ? `In ${Math.floor(mins/60)}h ${mins%60}m` : `In ${mins} mins`;
                    }
                    setInterval(updateTimer, 1000); updateTimer();
                </script>
                <?php else: ?>
                    <div class="glass-panel p-10 rounded-2xl text-center border-dashed border-dark-border">
                        <div class="w-20 h-20 bg-dark-card rounded-full flex items-center justify-center mx-auto mb-4 border border-dark-border">
                            <i class="fas fa-mug-hot text-gray-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">All Caught Up!</h3>
                        <p class="text-gray-500">No upcoming appointments in the queue.</p>
                    </div>
                <?php endif; ?>

                <!-- Schedule List -->
                <div>
                     <h3 class="text-lg font-heading font-bold text-white mb-4">Today's Schedule</h3>
                     <div class="bg-dark-card border border-dark-border rounded-xl overflow-hidden">
                        <?php if(empty($appointments)): ?>
                            <div class="p-8 text-center text-gray-500">No bookings for today.</div>
                        <?php else: ?>
                            <div class="divide-y divide-dark-border">
                                <?php foreach($appointments as $appt): 
                                    if($nextAppt && $appt['id'] == $nextAppt['id']) continue; 
                                    
                                    $serviceIds = json_decode($appt['services_ids_json'] ?? '[]', true);
                                    $serviceNames = [];
                                    if(is_array($serviceIds)) foreach($serviceIds as $sid) if(isset($servicesMap[$sid])) $serviceNames[] = $servicesMap[$sid];
                                    $serviceStr = implode(', ', $serviceNames);
                                    
                                    $isDone = ($appt['status'] === 'Completed');
                                    $isCancelled = ($appt['status'] === 'Cancelled' || $appt['status'] === 'No Show');
                                    $opacity = ($isDone || $isCancelled) ? 'opacity-50 grayscale' : '';
                                ?>
                                <div class="p-4 flex items-center justify-between hover:bg-dark-hover transition <?= $opacity ?>">
                                    <div class="flex items-center gap-4">
                                         <div class="text-center w-16">
                                             <div class="text-lg font-bold text-white"><?= date('h:i', strtotime($appt['appointment_time'])) ?></div>
                                             <div class="text-xs text-gray-500 font-bold uppercase"><?= date('A', strtotime($appt['appointment_time'])) ?></div>
                                         </div>
                                         <div class="h-10 w-px bg-dark-border"></div>
                                         <div>
                                             <h4 class="font-bold text-white text-sm"><?= htmlspecialchars($appt['customer_real_name'] ?? $appt['customer_name']) ?></h4>
                                             <p class="text-xs text-gold/80"><?= htmlspecialchars($serviceStr) ?></p>
                                         </div>
                                    </div>
                                    <div class="flex items-center">
                                         <?php if(!$isDone && !$isCancelled): ?>
                                             <button onclick="updateAppt(<?= $appt['id'] ?>, 'Completed')" class="p-2 text-gray-400 hover:text-green-500 transition" title="Complete">
                                                 <i class="fas fa-check-circle text-xl"></i>
                                             </button>
                                         <?php else: ?>
                                             <span class="text-[10px] font-bold uppercase px-2 py-1 rounded bg-dark/50 border border-dark-border text-gray-500"><?= $appt['status'] ?></span>
                                         <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                     </div>
                </div>

                <?php endif; ?>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function updateRealTimeClock() {
            const now = new Date();
            // Format time
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            const timeString = `${hours.toString().padStart(2, '0')}:${minutes} ${ampm}`;
            
            // Format date
            const day = now.getDate().toString().padStart(2, '0');
            const month = now.toLocaleString('default', { month: 'short' });
            const year = now.getFullYear();
            const dateString = `${day} ${month} ${year}`;
            
            const timeEl = document.getElementById('clock-time');
            const dateEl = document.getElementById('clock-date');
            
            if(timeEl) timeEl.textContent = timeString;
            if(dateEl) dateEl.textContent = dateString;
        }
        setInterval(updateRealTimeClock, 1000);
        updateRealTimeClock();
    </script>
    <script>
         // Auto Refresh
         setTimeout(() => window.location.reload(), 60000);

         function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                if(overlay) overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                if(overlay) overlay.classList.add('hidden');
            }
        }

        function toggleStatusMenu() {
            const dropdown = document.getElementById('statusDropdown');
            const overlay = document.getElementById('dropdownOverlay');
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                dropdown.classList.add('scale-100', 'opacity-100');
                overlay.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
                dropdown.classList.remove('scale-100', 'opacity-100');
                overlay.classList.add('hidden');
            }
        }

        function toggleHeroMenu() {
            const dropdown = document.getElementById('heroMenuDropdown');
            const overlay = document.getElementById('heroMenuOverlay');
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                overlay.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
                overlay.classList.add('hidden');
            }
        }

        function updateStatus(status) {
            fetch('actions/toggle_barber_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `status=${status}&csrf_token=${CSRF_TOKEN}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update status');
                }
            });
        }

        function updateAppt(id, status) {
            if(!confirm(`Mark as ${status}?`)) return;
            fetch('actions/update_appointment_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `appointment_id=${id}&status=${status}&csrf_token=${CSRF_TOKEN}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert(data.message);
            });
        }
    </script>
    <!-- Sidebar Logic -->
    <script>
    function toggleStaffSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (!sidebar) return;

        // Check if we are in "Mobile" mode (< 768px) or "Desktop/Landscape" mode (>= 768px)
        const isDesktop = window.innerWidth >= 768;

        if (isDesktop) {
            // DESKTOP LOGIC: Toggle Negative Margin to slide it in/out
            sidebar.classList.toggle('desktop-closed');
        } else {
            // MOBILE LOGIC: Use the Force Open class (Overlay style)
            sidebar.classList.toggle('mobile-force-open');
            
            // Sync overlay and base translation
            if (sidebar.classList.contains('mobile-force-open')) {
                // Open
                sidebar.classList.remove('-translate-x-full'); 
                if(overlay) overlay.classList.remove('hidden');
            } else {
                // Close
                sidebar.classList.add('-translate-x-full');
                if(overlay) overlay.classList.add('hidden');
            }
        }
    }
    </script>
</body>
</html>