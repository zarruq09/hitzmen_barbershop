<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/csrf_token.php';
require_once __DIR__ . '/db.php';

// Only allow customers
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] !== 'customer') {
        header('Location: index.php?error=access_denied');
        exit();
    }
}
redirectIfNotLoggedIn();

$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username'] ?? 'guest');
$email = htmlspecialchars($_SESSION['email'] ?? 'N/A');

// --- FETCH NOTIFICATIONS ---
$notifCount = 0;
$notifications = [];
if (isset($_SESSION['user_id'])) {
    try {
        // Get unread count
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmtCount->execute([$_SESSION['user_id']]);
        $notifCount = $stmtCount->fetchColumn();

        // Get latest 5 notifications
        $stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmtNotifs->execute([$_SESSION['user_id']]);
        $notifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* Silent fail */ }
}
// ---------------------------

// Determine current view
$view = $_GET['view'] ?? 'dashboard';

// Fetch Service Lookup Map
$servicesMap = [];
try {
    $stmtServices = $pdo->query("SELECT id, service_name FROM services");
    while ($row = $stmtServices->fetch(PDO::FETCH_ASSOC)) {
        $servicesMap[$row['id']] = $row['service_name'];
    }
} catch (PDOException $e) {}

// Fetch recent activity for Dashboard View
$recentActivity = [];
if ($view === 'dashboard') {
    try {
        $stmtRecent = $pdo->prepare("
            SELECT a.appointment_date, a.appointment_time, a.status, a.services_ids_json, b.name as barber_name, h.style_name
            FROM appointments a
            LEFT JOIN barbers b ON a.barber_id = b.id
            LEFT JOIN haircuts h ON a.haircut_id = h.id
            WHERE a.user_id = ?
            ORDER BY 
                CASE 
                    WHEN a.status = 'Pending' THEN 1 
                    WHEN a.status = 'Confirmed' THEN 2 
                    ELSE 3 
                END ASC,
                a.appointment_date DESC, a.appointment_time DESC
            LIMIT 3
        ");
        $stmtRecent->execute([$_SESSION['user_id']]);
        $recentActivity = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        // Process services for each appointment
        foreach ($recentActivity as &$activity) {
            $serviceNames = [];
            $ids = json_decode($activity['services_ids_json'] ?? '[]', true);
            if (is_array($ids)) {
                foreach ($ids as $sid) {
                    if (isset($servicesMap[$sid])) {
                        $serviceNames[] = $servicesMap[$sid];
                    }
                }
            }
            $activity['service_list'] = !empty($serviceNames) ? implode(', ', $serviceNames) : '-';
        }
    } catch (PDOException $e) { /* Silent fail */ }
}

// Prepare data for Booking View check
if ($view === 'book') {
    require_once __DIR__ . '/includes/booking_data.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | Hitzmen Barbershop</title>
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
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #1a1a1a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #C5A059; }
        .service-checkbox:checked + span { color: #C5A059; }
        .glass-card { background: rgba(30, 30, 30, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(197, 160, 89, 0.1); }
        
        /* Force Sidebar Open Mobile */
        .mobile-force-open {
            transform: translateX(0) !important;
        }
        
        /* Force Sidebar Closed Desktop (Negative Margin to collapse space) */
        .desktop-closed {
            margin-left: -16rem !important; /* w-64 = 16rem */
            transform: translateX(-100%) !important; /* Double ensure it's gone */
            opacity: 0 !important; /* Visual hide */
        }
    </style>
</head>
<body class="bg-dark text-gray-100 font-sans h-[100dvh] overflow-hidden flex">


    <!-- OVERLAY FOR MOBILE SIDEBAR -->
    <div id="sidebar-overlay" onclick="toggleCustomerSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden glass-panel backdrop-blur-sm transition-opacity"></div>

    <aside class="fixed md:static inset-y-0 left-0 w-64 bg-dark-card border-r border-dark-border flex flex-col z-40 transition-all duration-300 md:translate-x-0 -translate-x-full" id="sidebar">
        <!-- Logo -->
        <div class="h-20 flex items-center justify-center border-b border-dark-border relative bg-dark-card">
            <div class="flex items-center space-x-3 px-6 w-full">
                <img src="assets/images/Logo.png" alt="Logo" class="w-10 h-10 object-contain">
                <h2 class="text-xl font-heading font-bold text-white tracking-wide">HITZ<span class="text-gold">MEN</span></h2>
                <button class="md:hidden absolute right-4 text-gray-400 hover:text-white" onclick="toggleCustomerSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-6 bg-dark-card">
            <ul class="space-y-1 px-3">
                <li>
                    <a href="?view=dashboard" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'dashboard' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-home w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="?view=book" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'book' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-calendar-plus w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Book Now</span>
                    </a>
                </li>
                <li>
                    <a href="?view=history" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'history' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-history w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">My History</span>
                    </a>
                </li>
                <li>
                    <a href="?view=services" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'services' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-cut w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Services</span>
                    </a>
                </li>
                <li>
                    <a href="?view=barbers" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'barbers' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-user-tie w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Barbers</span>
                    </a>
                </li>
                <li>
                    <a href="?view=haircuts" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'haircuts' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-images w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Haircut</span>
                    </a>
                </li>
                <li>
                    <a href="?view=contact" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all <?= $view === 'contact' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-envelope w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Contact</span>
                    </a>
                </li>
                
                 <li>
                    <a href="?view=profile" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover transition-all mt-4 <?= $view === 'profile' ? 'active-tab bg-gold/10 text-gold' : '' ?>">
                        <i class="fas fa-user-circle w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium group-hover:text-white transition-colors">Profile</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User/Logout -->
        <div class="border-t border-dark-border p-4 bg-dark-card">
             <div class="flex items-center p-2 rounded-lg bg-dark hover:bg-dark-hover cursor-pointer transition w-full overflow-hidden group">
                <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-dark font-bold text-sm shrink-0">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($username) ?></p>
                    <p class="text-xs text-gray-500 truncate">Member</p>
                </div>
                <a href="logout.php" class="ml-auto text-gray-500 hover:text-red-500 transition px-2" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="bg-dark-card border-b border-dark-border px-4 md:px-8 py-4 md:py-5 flex justify-between items-center sticky top-0 z-30 glass-panel">
            <div class="flex items-center gap-4">
                <button class="text-gray-300 hover:text-gold md:hidden focus:outline-none" onclick="toggleCustomerSidebar()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <button class="text-gray-300 hover:text-gold hidden md:block focus:outline-none mr-2" onclick="toggleCustomerSidebar()">
                     <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-lg md:text-2xl font-heading font-bold text-white tracking-wide">Dashboard</h1>
                    <p class="text-gray-500 text-[10px] md:text-sm mt-0.5 md:mt-1">Welcome back, <?= htmlspecialchars($username) ?>.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Notification Bell with Dropdown -->
                <div class="relative" id="notif-container">
                    <button onclick="toggleNotifications()" class="p-2 text-gray-400 hover:text-gold transition relative" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifCount > 0): ?>
                             <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-dark-card text-[10px] text-white flex items-center justify-center font-bold animate-pulse">
                                <?= $notifCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notification Panel -->
                    <div id="notif-dropdown" class="hidden absolute right-0 top-12 w-80 bg-dark-card border border-dark-border rounded-xl shadow-2xl z-50 overflow-hidden">
                        <div class="p-4 border-b border-dark-border bg-[#181818] flex justify-between items-center">
                            <h3 class="font-bold text-gold flex items-center gap-2">
                                <i class="fas fa-bell"></i> Notifications
                            </h3>
                             <?php if ($notifCount > 0): ?>
                                <form action="actions/mark_read.php" method="POST">
                                    <?php csrfField(); ?>
                                    <button type="submit" class="text-xs text-gold hover:text-white transition">Mark all read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="max-h-80 overflow-y-auto custom-scrollbar">
                             <?php if (empty($notifications)): ?>
                                <div class="p-8 text-center text-gray-500">
                                    <i class="fas fa-check-circle text-3xl mb-2 text-green-500"></i>
                                    <p class="text-sm">No pending bookings!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="p-3 border-b border-dark-border hover:bg-dark-hover transition flex gap-3 <?= $notif['is_read'] ? 'opacity-60' : '' ?>">
                                        <div class="mt-1">
                                            <?php if ($notif['type'] == 'success'): ?>
                                                <i class="fas fa-check-circle text-green-500"></i>
                                            <?php elseif ($notif['type'] == 'error'): ?>
                                                <i class="fas fa-times-circle text-red-500"></i>
                                            <?php else: ?>
                                                <i class="fas fa-info-circle text-blue-500"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-300"><?= htmlspecialchars($notif['message']) ?></p>
                                            <span class="text-[10px] text-gray-600 block mt-1"><?= time_elapsed_string($notif['created_at']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative" id="main-content">
            
            <!-- GLOBAL SUCCESS FLASH + CALENDAR BTN -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-fade max-w-6xl mx-auto mb-8 bg-green-900/20 border-l-4 border-green-500 text-green-400 p-4 rounded-r-lg shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4 animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-2xl mr-4"></i>
                        <div>
                            <p class="font-bold">Success</p>
                            <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                        </div>
                    </div>
                    <?php if(isset($_SESSION['last_booking'])): 
                        $lb = $_SESSION['last_booking'];
                        $gTitle = urlencode("Barber Appointment with " . $lb['barber']);
                        $startStr = date('Ymd\THis', strtotime($lb['date'] . ' ' . $lb['time']));
                        $endStr = date('Ymd\THis', strtotime($lb['date'] . ' ' . $lb['time']) + 3600);
                        $gDates = $startStr . '/' . $endStr;
                        $gDetails = urlencode("Haircut at Hitzmen Barbershop.\nServices: " . implode(', ', $lb['service_names']));
                        $gLocation = urlencode("Hitzmen Barbershop, Merlimau, Malacca");
                        $gLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$gTitle&dates=$gDates&details=$gDetails&location=$gLocation";
                        unset($_SESSION['last_booking']);
                    ?>
                    <a href="<?= $gLink ?>" target="_blank" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-lg flex items-center gap-2 transition shadow-lg whitespace-nowrap">
                        <i class="far fa-calendar-plus"></i> Add to Calendar
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($view === 'dashboard'): ?>
                <!-- DASHBOARD HOME VIEW -->
                <div id="section-dashboard" class="max-w-6xl mx-auto space-y-8 animate-fade-in">
                    
                    <!-- Welcome Banner -->
                    <div class="bg-gradient-to-r from-dark-card to-dark-hover border border-dark-border rounded-2xl p-8 relative overflow-hidden">
                        <div class="relative z-10">
                            <h1 class="text-3xl md:text-4xl font-heading font-bold text-white mb-2">
                                Welcome back, <span class="text-gold"><?= $username ?></span>
                            </h1>
                            <p class="text-gray-400">Ready for your next fresh look?</p>
                        </div>
                    </div>

                    <!-- Stats/Quick Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-dark-card p-6 rounded-xl border border-dark-border">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-gray-400 text-xs uppercase font-bold tracking-wider">Total Bookings</p>
                                    <?php
                                        // Fetch actual total count
                                        $totalBookings = 0;
                                        try {
                                            $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
                                            $stmtTotal->execute([$_SESSION['user_id']]);
                                            $totalBookings = $stmtTotal->fetchColumn();
                                        } catch (PDOException $e) {}
                                    ?>
                                    <h3 class="text-2xl font-bold text-white mt-1"><?= $totalBookings ?></h3>
                                </div>
                                <div class="p-3 bg-blue-900/20 rounded-lg text-blue-400"><i class="fas fa-calendar-check"></i></div>
                            </div>
                        </div>
                        <div class="bg-dark-card p-6 rounded-xl border border-dark-border">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-gray-400 text-xs uppercase font-bold tracking-wider">Member Status</p>
                                    <h3 class="text-2xl font-bold text-gold mt-1">Active</h3>
                                </div>
                                <div class="p-3 bg-green-900/20 rounded-lg text-green-400"><i class="fas fa-id-card"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Table -->
                    <div class="bg-dark-card rounded-xl border border-dark-border overflow-hidden">
                        <div class="p-6 border-b border-dark-border flex justify-between items-center">
                            <h3 class="font-heading font-bold text-lg text-white">Recent Activity</h3>
                            <a href="?view=history" class="text-sm text-gold hover:text-white">View All History</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-xs text-gray-500 uppercase border-b border-dark-border">
                                        <th class="px-6 py-4 font-bold">Date</th>
                                        <th class="px-6 py-4 font-bold">Service</th>
                                        <th class="px-6 py-4 font-bold">Barber</th>
                                        <th class="px-6 py-4 font-bold text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    <?php if (empty($recentActivity)): ?>
                                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">No recent appointments found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentActivity as $activity): 
                                            $statusColor = 'bg-gray-800 text-gray-300';
                                            if($activity['status'] === 'Confirmed') $statusColor = 'bg-green-900/30 text-green-400 border border-green-900';
                                            if($activity['status'] === 'Pending') $statusColor = 'bg-yellow-900/30 text-yellow-400 border border-yellow-900';
                                            if($activity['status'] === 'Cancelled') $statusColor = 'bg-red-900/30 text-red-400 border border-red-900';
                                        ?>
                                        <tr class="border-b border-dark-border last:border-0 hover:bg-dark-hover transition-colors">
                                            <td class="px-6 py-4 text-white font-medium">
                                                <?= date('M d, Y', strtotime($activity['appointment_date'])) ?>
                                                <span class="text-xs text-gray-500 block"><?= date('h:i A', strtotime($activity['appointment_time'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-300"><?= htmlspecialchars($activity['service_list']) ?></td>
                                            <td class="px-6 py-4 text-gray-300"><?= htmlspecialchars($activity['barber_name'] ?? '-') ?></td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusColor ?>"><?= $activity['status'] ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($view === 'book'): ?>
                <!-- BOOKING VIEW -->
                <div id="section-book" class="animate-fade-in">
                    <?php include 'includes/booking_form_partial.php'; ?>
                </div>

            <?php elseif ($view === 'history'): ?>
                 <!-- HISTORY VIEW -->
                 <?php include 'includes/history_partial.php'; ?>
            
            <?php elseif ($view === 'services'): ?>
                 <!-- SERVICES VIEW -->
                 <?php include 'includes/services_partial.php'; ?>

             <?php elseif ($view === 'barbers'): ?>
                 <!-- BARBERS VIEW -->
                 <?php include 'includes/barbers_partial.php'; ?>

             <?php elseif ($view === 'haircuts'): ?>
                 <!-- HAIRCUTS VIEW -->
                 <?php include 'includes/haircuts_partial.php'; ?>

             <?php elseif ($view === 'contact'): ?>
                 <!-- CONTACT VIEW -->
                 <div class="animate-fade-in text-center py-20">
                     <h2 class="text-3xl font-heading font-bold text-white mb-4">Contact Us</h2>
                     <p class="text-gray-400 mb-8">Visit us at the shop or give us a call.</p>
                     
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        <div class="glass-card p-8 rounded-2xl text-left">
                            <h3 class="text-xl font-bold text-gold mb-4">Opening Hours</h3>
                            <ul class="space-y-2 text-gray-300 text-sm">
                                <li class="flex justify-between"><span>Mon, Tue, Thu, Sat, Sun</span> <span>11:00 AM - 11:00 PM</span></li>
                                <li class="flex justify-between"><span>Friday</span> <span>03:00 PM - 11:00 PM</span></li>
                                <li class="flex justify-between"><span>Wednesday</span> <span class="text-red-400">Closed</span></li>
                            </ul>
                            
                            <div class="mt-6 pt-6 border-t border-dark-border flex gap-3">
                                 <a href="https://www.instagram.com/hitzmenbarbershop?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" class="flex-1 flex items-center justify-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-2 rounded-lg font-bold text-sm hover:opacity-90 transition shadow-lg">
                                     <i class="fab fa-instagram text-lg"></i> Instagram
                                 </a>
                                 <a href="https://www.tiktok.com/@hitzmenthebarbershop" target="_blank" class="flex-1 flex items-center justify-center gap-2 bg-black text-white py-2 rounded-lg font-bold text-sm hover:opacity-80 transition shadow-lg border border-gray-700">
                                     <i class="fab fa-tiktok text-lg"></i> TikTok
                                 </a>
                                 <a href="https://wa.me/60182172159?text=Hello%20saya%20<?= urlencode($username) ?>%2C%20saya%20ada%20soalan" target="_blank" class="flex-1 flex items-center justify-center gap-2 bg-green-500 text-white py-2 rounded-lg font-bold text-sm hover:bg-green-600 transition shadow-lg">
                                     <i class="fab fa-whatsapp text-lg"></i> WhatsApp
                                 </a>
                            </div>
                        </div>
                        <div class="glass-card p-8 rounded-2xl text-left">
                            <h3 class="text-xl font-bold text-gold mb-4">Location</h3>
                            <p class="text-gray-300 mb-4 font-medium">Hitzmen Barbershop</p>
                            <p class="text-gray-400 mb-6 text-sm leading-relaxed">
                                Jc 35, 1, Jalan BMU 3,<br>
                                BANDAR BARU MERLIMAU UTARA,<br>
                                77300 Merlimau, Malacca
                            </p>
                            <div class="w-full h-40 bg-gray-800 rounded-lg flex items-center justify-center overflow-hidden relative group">
                                <!-- Placeholder Map Image/Link -->
                                <img src="https://maps.googleapis.com/maps/api/staticmap?center=2.146,102.426&zoom=15&size=400x200&sensor=false&key=AIzaSy..." alt="Map" class="w-full h-full object-cover opacity-50 group-hover:opacity-75 transition" onerror="this.style.display='none'">
                                <a href="https://maps.google.com/?q=Jc+35,+1,+Jalan+BMU+3,+BANDAR+BARU+MERLIMAU+UTARA,+77300+Merlimau,+Malacca" target="_blank" class="absolute inset-0 flex items-center justify-center text-white font-bold bg-black/40 hover:bg-black/20 transition">
                                    <i class="fas fa-map-marked-alt mr-2"></i> View on Google Maps
                                </a>
                            </div>
                        </div>
                     </div>


                 </div>

             <?php elseif ($view === 'profile'): ?>
                <!-- PROFILE VIEW (Simple Placeholder) -->
                <div class="animate-fade-in max-w-2xl mx-auto">
                     <h2 class="text-3xl font-heading font-bold text-white mb-8 border-b border-dark-border pb-4">My Profile</h2>
                     <div class="bg-dark-card p-8 rounded-xl border border-dark-border">
                         <div class="space-y-6">
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Full Name</label>
                                <p class="text-white text-lg font-medium"><?= $fullName ?></p>
                            </div>
                             <div>
                                <label class="block text-gray-500 text-sm mb-1">Username</label>
                                <p class="text-white text-lg font-medium"><?= $username ?></p>
                            </div>
                             <div>
                                <label class="block text-gray-500 text-sm mb-1">Email</label>
                                <p class="text-white text-lg font-medium"><?= $email ?></p>
                            </div>
                            <div class="pt-6">
                                <a href="?view=edit_profile" class="btn btn-primary bg-gold text-dark font-bold py-2 px-6 rounded hover:bg-gold-light">Edit Profile</a>
                            </div>
                         </div>
                 </div>
                </div>
            
            <?php elseif ($view === 'edit_profile'): ?>
                <!-- EDIT PROFILE VIEW -->
                <?php 
                    // Need to fetch extra user data like phone if not in session
                    // Re-fetch user to get latest phone
                    try {
                        $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $uData = $stmt->fetch(PDO::FETCH_ASSOC);
                        $user = []; // Initialize array to avoid collision with $user string from db.php
                        $user['phone'] = $uData['phone'];
                    } catch(PDOException $e) {}
                    
                    include 'includes/profile_edit_partial.php'; 
                ?>

            <?php else: ?>
                <div class="text-center py-20">
                    <h2 class="text-2xl text-red-500">Page Not Found</h2>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Centralized Notification Logic -->
    <?php include 'includes/customer_notification_script.php'; ?>

    <script>
        function toggleNotifications() {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.classList.toggle('hidden');
        }

        function toggleCustomerSidebar() {
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

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('notif-container');
            const dropdown = document.getElementById('notif-dropdown');
            if (container && !container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

<?php
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime)) return "Just now";
    
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'Just now';
}
?></html>

