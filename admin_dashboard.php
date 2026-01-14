<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'db.php';
require_once 'actions/auto_sync_schedule.php'; // Auto-sync schedule status
require_once 'includes/csrf_token.php';

// Fetch barbers, services, and haircuts
$barbers = $pdo->query("SELECT * FROM barbers WHERE status NOT IN ('Deleted', 'deleted') ORDER BY created_at DESC")->fetchAll();
// For Services/Haircuts, we assume Hard Delete or check schema. Safe to leave as is if we use Hard Delete there, but if Soft Delete, need filter.

$services = $pdo->query("SELECT * FROM services")->fetchAll();
$staffUsers = $pdo->query("SELECT id, username, full_name FROM users WHERE role = 'staff'")->fetchAll(PDO::FETCH_ASSOC);

// Haircut filtering
$conditions = [];
$params = [];
if (!empty($_GET['face_shape'])) {
    $fs = $_GET['face_shape'];
    if (is_array($fs)) {
        foreach ($fs as $shape) {
            $conditions[] = "FIND_IN_SET(?, face_shape)";
            $params[] = $shape;
        }
    } else {
        $conditions[] = "FIND_IN_SET(?, face_shape)";
        $params[] = $fs;
    }
}
if (!empty($_GET['hair_type'])) {
    $conditions[] = "FIND_IN_SET(?, hair_type)";
    $params[] = $_GET['hair_type'];
}
$sql = "SELECT * FROM haircuts";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$haircuts = $stmt->fetchAll();

$username = $_SESSION['username'] ?? 'Guest';
$email = $_SESSION['email'] ?? 'N/A';
$role = $_SESSION['role'] ?? 'N/A';

$face_shapes = isset($_POST['face_shape']) ? implode(',', $_POST['face_shape']) : '';
$hair_types = isset($_POST['hair_type']) ? implode(',', $_POST['hair_type']) : '';

// Fetch today's bookings count
$todayStmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$todaysBookings = $todayStmt->fetchColumn() ?: 0;

// Fetch pending appointments for notifications
$notifStmt = $pdo->query("
    SELECT a.id, a.appointment_date, a.appointment_time, u.username as customer_name 
    FROM appointments a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.status = 'Pending' 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count($notifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Hitzmen Barbershop</title>
    <link rel="icon" type="image/x-icon" href="assets/images/Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- GLOBAL MODALS -->
    <?php include 'modals.php'; ?>


    <script>
    // --- Helper Functions for Modals ---
    function openEditModal(id, name, specialty, image, userId) {
        document.getElementById('editBarberId').value = id;
        document.getElementById('editBarberName').value = name;
        // document.getElementById('editBarberStatus').value = status; // Managed by schedule now
        
        // Select logic for linked user
        const userSelect = document.getElementById('editBarberUserId');
        if(userSelect) {
             userSelect.value = userId || '';
        }

        const currentImagePreview = document.getElementById('currentImagePreview');
        if(image && image !== '') {
            currentImagePreview.src = 'uploads/' + image; 
            currentImagePreview.style.display = 'block';
        } else {
             currentImagePreview.style.display = 'none';
        }
        openModal('editBarberModal');
    }

    function openEditServiceModal(id, name, price, description, image) { 
        document.getElementById('editServiceId').value = id; 
        document.getElementById('editServiceName').value = name; 
        document.getElementById('editServicePrice').value = price; 
        document.getElementById('editServiceDescription').value = description; 
        const currentImage = document.getElementById('currentServiceImage');
         if(image && image !== '') {
            currentImage.src = 'uploads/' + image;
            currentImage.style.display = 'block';
        } else {
             currentImage.style.display = 'none';
        }
        openModal('editServiceModal'); 
    }

    function openEditHaircutModal(id, name, description, image, faceShape, hairType) {
        document.getElementById('editHaircutId').value = id;
        document.getElementById('editHaircutName').value = name;
        document.getElementById('editHaircutDescription').value = description;
        
        // Reset all checkboxes first
        document.querySelectorAll('#editFaceShapeContainer input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('#editHairTypeContainer input[type="checkbox"]').forEach(cb => cb.checked = false);

        // Populate Face Shapes
        if (faceShape) {
            const shapes = faceShape.split(',').map(s => s.trim());
            shapes.forEach(shape => {
                const cb = document.querySelector(`#editFaceShapeContainer input[value="${shape}"]`);
                if(cb) cb.checked = true;
            });
        }

        // Populate Hair Types
        if (hairType) {
            const types = hairType.split(',').map(t => t.trim());
            types.forEach(type => {
                const cb = document.querySelector(`#editHairTypeContainer input[value="${type}"]`);
                if(cb) cb.checked = true;
            });
        }
        
        const currentImagePreview = document.getElementById('currentHaircutImage');
        const noImageText = document.getElementById('noCurrentImageTextHaircut');
        const currentImageHiddenInput = document.getElementById('editHaircutCurrentImageHidden');

        if (image && image !== '') {
            currentImagePreview.src = 'uploads/' + image; 
            currentImagePreview.style.display = 'block';
            if(noImageText) noImageText.style.display = 'none';
            currentImageHiddenInput.value = image;
        } else {
            currentImagePreview.src = '';
            currentImagePreview.style.display = 'none';
            if(noImageText) noImageText.style.display = 'block';
            currentImageHiddenInput.value = '';
        }
        openModal('editHaircutModal');
    }

    function openRejectModal(id) {
        document.getElementById('rejectAppointmentId').value = id;
        openModal('rejectModal');
    }

    // AJAX Handler for Rejection Form to avoid redirect issues
    function handleRejectForm(event) {
        event.preventDefault(); // Prevent standard form submit
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        fetch('actions/update_appointment_status.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Important for PHP to detect Ajax
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            if (data.success) {
                closeModal();
                showToast('Booking rejected successfully', 'success');
                // Force reload of the booking table
                if (typeof fetchAndLoadTab === 'function') {
                    fetchAndLoadTab('view_booking'); 
                } else {
                    window.location.reload();
                }
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            alert('A network error occurred.');
        });
        
        return false;
    }

    function toggleAdminSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (!sidebar) {
            return;
        }

        // Check if we are in "Mobile" mode (< 768px) or "Desktop/Landscape" mode (>= 768px)
        // This matches Tailwind's 'md' breakpoint default
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


        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        'gold-light': '#D4AF37',
                        dark: '#121212',
                        'dark-card': '#1E1E1E',
                        'dark-border': '#333333',
                        'dark-hover': '#252525'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- PDF and Chart Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #121212;
            color: #F3F4F6;
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #121212; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #C5A059; }

        /* Force Sidebar Open Mobile */
        .mobile-force-open {
            transform: translateX(0) !important;
        }
        
        /* Force Sidebar Closed Desktop (Negative Margin to collapse space) */
        .desktop-closed {
            margin-left: -16rem !important; /* w-64 = 16rem */
        }

        /* Utility Classes */
        .glass-panel { 
            background: rgba(30, 30, 30, 0.95); 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(197, 160, 89, 0.1); 
        }
        
        .sidebar-link {
            transition: all 0.2s ease;
            border-right: 3px solid transparent;
        }
        
        .sidebar-link:hover, .sidebar-link.active-tab {
            background: linear-gradient(90deg, rgba(197, 160, 89, 0.1) 0%, transparent 100%);
            border-right-color: #C5A059;
            color: #C5A059;
        }

        .btn-gold {
            background: linear-gradient(135deg, #C5A059 0%, #B08D45 100%);
            color: #121212;
            font-weight: 600;
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .btn-gold:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(197, 160, 89, 0.2);
        }

        /* Modal Transitions */
        .modal { transition: opacity 0.25s ease; }
    </style>
</head>
<body class="bg-dark text-gray-100 font-sans antialiased overflow-hidden">
<div class="flex h-[100dvh] overflow-hidden relative">

    <!-- MOBILE OVERLAY -->
    <div id="sidebar-overlay" onclick="toggleAdminSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden glass-panel backdrop-blur-sm transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="sidebar" style="z-index: 9999;" class="fixed inset-y-0 left-0 w-64 bg-dark-card border-r border-dark-border flex flex-col transition-all duration-300 transform -translate-x-full md:relative md:translate-x-0">
        <!-- Logo Area -->
        <div class="h-20 flex items-center justify-center border-b border-dark-border relative">
            <div class="flex items-center space-x-3 px-6 w-full">
                <img src="assets/images/Logo.png" alt="Logo" class="w-10 h-10 object-contain sidebar-logo">
                <span class="text-xl font-heading font-bold text-white tracking-wide sidebar-link-text">HITZ<span class="text-gold">MEN</span></span>
            </div>
            

        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-6">
            <ul class="space-y-1 px-3">
                <li>
                    <button onclick="fetchAndLoadTab('admin_dashboard')" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover focus:outline-none" data-tooltip="Dashboard">
                        <i class="fas fa-home w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium sidebar-link-text group-hover:text-white transition-colors">Dashboard</span>
                    </button>
                </li>
                <li>
                    <button onclick="fetchAndLoadTab('manage_users')" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover focus:outline-none" data-tooltip="Users">
                        <i class="fas fa-users w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium sidebar-link-text group-hover:text-white transition-colors">Manage Users</span>
                    </button>
                </li>
                <li>
                    <button onclick="fetchAndLoadTab('manage_schedule')" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover focus:outline-none" data-tooltip="Schedule">
                        <i class="far fa-calendar-alt w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium sidebar-link-text group-hover:text-white transition-colors">Schedule</span>
                    </button>
                </li>
                <li>
                    <button onclick="fetchAndLoadTab('view_booking')" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover focus:outline-none" data-tooltip="Bookings">
                        <i class="fas fa-book-open w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium sidebar-link-text group-hover:text-white transition-colors">Bookings</span>
                    </button>
                </li>
                <li>
                    <button onclick="fetchAndLoadTab('reports')" class="sidebar-link w-full flex items-center px-4 py-3 rounded-lg text-gray-400 group hover:bg-dark-hover focus:outline-none" data-tooltip="Reports">
                        <i class="fas fa-chart-line w-6 text-lg group-hover:text-gold transition-colors"></i>
                        <span class="ml-3 font-medium sidebar-link-text group-hover:text-white transition-colors">Reports</span>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- User Profile / Logout -->
        <div class="border-t border-dark-border p-4">
            <div class="flex items-center p-2 rounded-lg bg-dark hover:bg-dark-hover cursor-pointer transition w-full overflow-hidden group">
                <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-dark font-bold text-sm shrink-0">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="ml-3 overflow-hidden sidebar-link-text">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($username) ?></p>
                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($role) ?></p>
                </div>
                <a href="logout.php" class="ml-auto text-gray-500 hover:text-red-500 transition px-2 sidebar-link-text" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>
    <!-- Main Content -->
    <main id="main-content" class="flex-1 bg-dark overflow-y-auto relative">
        <!-- Top Header -->
        <header class="bg-dark-card border-b border-dark-border px-8 py-5 flex justify-between items-center sticky top-0 z-30 glass-panel">
            <div class="flex items-center gap-4">
                <button onclick="toggleAdminSidebar()" class="text-gray-400 hover:text-gold transition focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-heading font-bold text-white tracking-wide">Dashboard</h1>
                    <p class="text-gray-500 text-sm mt-1">Welcome back, Administrator.</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <!-- Notification Bell with Dropdown -->
                <div class="relative" id="notificationContainer">
                    <button onclick="toggleNotificationPanel()" class="p-2 text-gray-400 hover:text-gold transition relative" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <?php if ($pendingCount > 0): ?>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full border-2 border-dark-card text-xs text-white flex items-center justify-center font-bold"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notification Panel -->
                    <div id="notificationPanel" class="hidden absolute right-0 top-12 w-80 bg-dark-card border border-dark-border rounded-xl shadow-2xl z-50 overflow-hidden">
                        <div class="p-4 border-b border-dark-border bg-[#181818]">
                            <h3 class="font-bold text-gold flex items-center gap-2">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if ($pendingCount > 0): ?>
                                <span class="bg-red-500/20 text-red-400 text-xs px-2 py-0.5 rounded-full"><?= $pendingCount ?> pending</span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $notif): ?>
                                <div class="p-4 border-b border-dark-border hover:bg-dark-hover transition-colors cursor-pointer" onclick="fetchAndLoadTab('view_booking')">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-yellow-900/30 text-yellow-400 flex items-center justify-center shrink-0">
                                            <i class="fas fa-calendar-plus text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-white font-medium truncate">New booking from <?= htmlspecialchars($notif['customer_name'] ?? 'Customer') ?></p>
                                            <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($notif['appointment_date'])) ?> at <?= date('h:i A', strtotime($notif['appointment_time'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-8 text-center text-gray-500">
                                    <i class="fas fa-check-circle text-3xl mb-2 text-green-500"></i>
                                    <p class="text-sm">No pending bookings!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($pendingCount > 0): ?>
                        <div class="p-3 border-t border-dark-border bg-[#181818]">
                            <button onclick="fetchAndLoadTab('view_booking'); toggleNotificationPanel();" class="w-full text-center text-sm text-gold hover:text-gold-light transition-colors font-medium">
                                View All Bookings →
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto space-y-8">
            
            <!-- Dashboard Widgets (Visible only on Dashboard Tab) -->
            <div id="dashboard-view">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg relative overflow-hidden group hover:border-gold transition-colors">
                        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fas fa-users text-6xl text-gold"></i>
                        </div>
                        <h3 class="text-gray-400 text-sm font-medium uppercase tracking-wider">Total Barbers</h3>
                        <p class="text-3xl font-bold text-white mt-1"><?= count($barbers) ?></p>
                        <div class="mt-4 text-xs text-gold flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i> Active Staff
                        </div>
                    </div>

                    <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg relative overflow-hidden group hover:border-gold transition-colors">
                        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fas fa-cut text-6xl text-gold"></i>
                        </div>
                        <h3 class="text-gray-400 text-sm font-medium uppercase tracking-wider">Services</h3>
                        <p class="text-3xl font-bold text-white mt-1"><?= count($services) ?></p>
                        <div class="mt-4 text-xs text-gray-500">
                            Available Treatments
                        </div>
                    </div>

                    <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg relative overflow-hidden group hover:border-gold transition-colors cursor-pointer" onclick="fetchAndLoadTab('view_booking')">
                        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fas fa-calendar-check text-6xl text-gold"></i>
                        </div>
                        <h3 class="text-gray-400 text-sm font-medium uppercase tracking-wider">Today's Bookings</h3>
                        <p id="todays-booking-count" class="text-3xl font-bold text-white mt-1"><?= $todaysBookings ?></p>
                        <div class="mt-4 text-xs text-green-400 flex items-center">
                            <i class="fas fa-clock mr-1"></i> View Schedule ->
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Profile -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Quick Actions -->
                    <div class="lg:col-span-2 bg-dark-card border border-dark-border rounded-xl p-6 shadow-lg">
                        <h3 class="text-lg font-bold text-white mb-6 border-b border-dark-border pb-2">
                            <i class="fas fa-bolt text-gold mr-2"></i> Quick Actions
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button onclick="openModal('barberModal')" class="flex flex-col items-center justify-center p-6 rounded-lg bg-dark border border-dark-border hover:border-gold hover:bg-dark-hover transition group">
                                <div class="w-12 h-12 rounded-full bg-gold/10 flex items-center justify-center mb-3 group-hover:bg-gold/20 transition">
                                    <i class="fas fa-user-plus text-gold text-xl"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-300 group-hover:text-white">Add Barber</span>
                            </button>
                            <button onclick="openModal('addServiceModal')" class="flex flex-col items-center justify-center p-6 rounded-lg bg-dark border border-dark-border hover:border-gold hover:bg-dark-hover transition group">
                                <div class="w-12 h-12 rounded-full bg-gold/10 flex items-center justify-center mb-3 group-hover:bg-gold/20 transition">
                                    <i class="fas fa-cut text-gold text-xl"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-300 group-hover:text-white">Add Service</span>
                            </button>
                            <button onclick="openModal('addHaircutModal')" class="flex flex-col items-center justify-center p-6 rounded-lg bg-dark border border-dark-border hover:border-gold hover:bg-dark-hover transition group">
                                <div class="w-12 h-12 rounded-full bg-gold/10 flex items-center justify-center mb-3 group-hover:bg-gold/20 transition">
                                    <i class="fas fa-camera text-gold text-xl"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-300 group-hover:text-white">Add Haircut</span>
                            </button>
                            

                        </div>
                    </div>

                    <!-- Profile Warning/Info -->
                    <div class="bg-dark-card border border-gold/30 rounded-xl p-6 relative overflow-hidden">
                         <div class="relative z-10">
                             <h3 class="text-lg font-bold text-white mb-2">Admin Profile</h3>
                             <p class="text-gray-400 text-sm mb-4">Logged in as <span class="text-gold"><?= htmlspecialchars($username) ?></span></p>
                             <div class="text-xs text-gray-500 bg-neutral-900 p-3 rounded border border-dark-border mt-4">
                                Please ensure 2FA is enabled for high-privilege accounts.
                             </div>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation (Tabs for Sub-pages) -->
            <div id="content-tabs-nav" class="border-b border-dark-border flex space-x-6">
                <button onclick="showTab('barbers')" id="tab-barbers" class="pb-3 text-sm font-medium border-b-2 border-gold text-gold active-tab transition-colors">
                    Manage Barbers
                </button>
                <button onclick="showTab('services')" id="tab-services" class="pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-white transition-colors">
                    Services
                </button>
                <button onclick="showTab('haircuts')" id="tab-haircuts" class="pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-white transition-colors">
                    Haircut Styles
                </button>
            </div>
    
            <!-- Tab Contents Wrapper -->
            <div id="tab-contents-card" class="bg-dark-card border border-dark-border rounded-xl shadow-lg p-0 overflow-hidden min-h-[500px]">

        <div id="tab-content-barbers" class="tab-content p-6">
            <div class="overflow-auto max-h-[600px] rounded-lg border border-dark-border">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[#181818] sticky top-0 z-10">
                        <tr>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Image</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Name</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Specialty</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Status</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        <?php foreach ($barbers as $barber): ?>
                        <tr class="hover:bg-dark-hover transition-colors group">
                            <td class="py-3 px-4">
                                <?php if ($barber['image']): ?>
                                    <img src="uploads/<?= htmlspecialchars($barber['image']) ?>" class="h-10 w-10 object-cover rounded-full border border-dark-border group-hover:border-gold transition-colors" alt="Barber">
                                <?php else: ?>
                                    <div class="h-10 w-10 rounded-full bg-dark-card border border-dark-border flex items-center justify-center text-gray-500 text-xs">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 font-medium text-white"><?= htmlspecialchars($barber['name']) ?></td>
                            <td class="py-3 px-4 text-gray-400"><?= htmlspecialchars($barber['specialty']) ?></td>
                            <td class="py-3 px-4">
                                <?php if (($barber['status'] ?? 'Available') === 'Available'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/30 text-green-400 border border-green-900/50">
                                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span> Available
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/30 text-red-400 border border-red-900/50">
                                        <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></span> Unavailable
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    <button onclick="openEditModal(<?= $barber['id'] ?>, '<?= htmlspecialchars($barber['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($barber['specialty'], ENT_QUOTES) ?>', '<?= htmlspecialchars($barber['image'], ENT_QUOTES) ?>', '<?= htmlspecialchars($barber['user_id'] ?? '', ENT_QUOTES) ?>')" class="text-gray-400 hover:text-gold transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="actions/delete_barber.php" method="POST" class="inline" onsubmit="return confirm('Delete <?= htmlspecialchars($barber['name'], ENT_QUOTES) ?>?');">
                                        <input type="hidden" name="id" value="<?= $barber['id'] ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-content-services" class="tab-content hidden p-6">
            <div class="overflow-auto max-h-[600px] rounded-lg border border-dark-border">
                <table class="min-w-full text-left text-sm">
                        <thead class="bg-[#181818] sticky top-0 z-10">
                        <tr>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Image</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Name</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Price</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Description</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        <?php foreach ($services as $service): ?>
                        <tr class="hover:bg-dark-hover transition-colors group">
                            <td class="py-3 px-4">
                                <?php if ($service['image']): ?>
                                    <img src="uploads/<?= htmlspecialchars($service['image']) ?>" class="h-10 w-10 object-cover rounded border border-dark-border group-hover:border-gold transition-colors" alt="Service">
                                <?php else: ?>
                                    <div class="h-10 w-10 rounded bg-dark-card border border-dark-border flex items-center justify-center text-gray-500 text-xs">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 font-medium text-white"><?= htmlspecialchars($service['service_name']) ?></td>
                            <td class="py-3 px-4 text-gold font-semibold">RM <?= number_format($service['price'], 2) ?></td>
                            <td class="py-3 px-4 text-gray-400 truncate max-w-xs"><?= htmlspecialchars($service['description']) ?></td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="#" onclick="openEditServiceModal(<?= $service['id'] ?>, '<?= htmlspecialchars($service['service_name'], ENT_QUOTES) ?>', <?= $service['price'] ?>, '<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>', '<?= htmlspecialchars($service['image'], ENT_QUOTES) ?>')" class="text-gray-400 hover:text-gold transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="actions/delete_service.php" method="POST" class="inline" onsubmit="return confirm('Delete <?= htmlspecialchars($service['service_name'], ENT_QUOTES) ?>?');">
                                        <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-content-haircuts" class="tab-content hidden p-6">
            <h3 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gold to-white mb-6">✂️ Manage Haircuts</h3>
            
            <!-- Filters -->
            <form method="GET" action="admin_dashboard.php#tab-content-haircuts" class="mb-6 flex flex-wrap gap-4 items-center p-4 bg-dark border border-dark-border rounded-lg"> 
                <input type="hidden" name="tab" value="haircuts"> 
                <div class="relative">
                    <select name="face_shape" class="appearance-none bg-dark-card border border-dark-border text-gray-300 px-4 py-2 pr-8 rounded focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold transition-colors">
                        <option value="">All Face Shapes</option>
                        <?php foreach (['oval', 'round', 'square', 'heart', 'diamond', 'long', 'triangle'] as $shape): ?>
                            <option value="<?= $shape ?>" <?= ($_GET['face_shape'] ?? '') === $shape ? 'selected' : '' ?>>
                                <?= ucfirst($shape) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gold">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>

                <div class="relative">
                    <select name="hair_type" class="appearance-none bg-dark-card border border-dark-border text-gray-300 px-4 py-2 pr-8 rounded focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold transition-colors">
                        <option value="">All Hair Types</option>
                        <?php foreach (['straight', 'wavy', 'curly'] as $type): ?>
                            <option value="<?= $type ?>" <?= ($_GET['hair_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= ucfirst($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gold">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>

                <button type="submit" class="bg-gold text-dark font-bold px-6 py-2 rounded hover:bg-gold-light transition-colors shadow-lg shadow-gold/20">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="admin_dashboard.php?tab=haircuts" class="text-gray-400 hover:text-white transition-colors text-sm underline undecoration-dotted">Clear Filters</a> 
            </form>

            <div class="overflow-auto max-h-[600px] rounded-lg border border-dark-border">
                <table class="min-w-full text-left text-sm">
                        <thead class="bg-[#181818] sticky top-0 z-10">
                        <tr>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Image</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Style</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Attributes</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Description</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        <?php foreach ($haircuts as $haircut): ?>
                        <tr class="hover:bg-dark-hover transition-colors group">
                            <td class="py-3 px-4">
                                <?php if ($haircut['image']): ?>
                                    <img src="uploads/<?= htmlspecialchars($haircut['image']) ?>" class="h-12 w-12 object-cover rounded border border-dark-border group-hover:border-gold transition-colors" alt="Haircut">
                                <?php else: ?>
                                    <div class="h-12 w-12 rounded bg-dark-card border border-dark-border flex items-center justify-center text-gray-500 text-xs">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 font-medium text-white text-lg"><?= htmlspecialchars($haircut['style_name']) ?></td>
                            <td class="py-3 px-4">
                                <div class="space-y-1">
                                    <div class="flex items-center text-xs text-gray-400">
                                        <i class="fas fa-smile mr-2 w-4 text-center text-gold"></i>
                                        <?php 
                                            // Process Face Shapes (Simple comma list)
                                            $shapes = array_filter(array_map('trim', explode(',', $haircut['face_shape'] ?? '')));
                                            echo $shapes ? implode(', ', array_map('ucfirst', $shapes)) : '-';
                                        ?>
                                    </div>
                                    <div class="flex items-center text-xs text-gray-400">
                                        <i class="fas fa-wave-square mr-2 w-4 text-center text-blue-400"></i>
                                        <?php 
                                            // Process Hair Types
                                            $types = array_filter(array_map('trim', explode(',', $haircut['hair_type'] ?? '')));
                                            echo $types ? implode(', ', array_map('ucfirst', $types)) : '-';
                                        ?>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-gray-400 truncate max-w-xs"><?= htmlspecialchars($haircut['description']) ?></td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    <button onclick="openEditHaircutModal(
                                    <?= $haircut['id'] ?>,
                                    '<?= htmlspecialchars($haircut['style_name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($haircut['description'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($haircut['image'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($haircut['face_shape'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($haircut['hair_type'], ENT_QUOTES) ?>'
                                        )" class="text-gray-400 hover:text-gold transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="actions/delete_haircut.php" method="POST" class="inline" onsubmit="return confirm('Delete <?= htmlspecialchars($haircut['style_name'], ENT_QUOTES) ?>?');">
                                        <input type="hidden" name="id" value="<?= $haircut['id'] ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
            </div><!-- End Tab Wrapper -->
            
            <!-- Container for AJAX-loaded content (Manage Users, Schedule, Bookings, etc.) -->
            <div id="content-area" class="hidden"></div>
            
        </div><!-- End Max-W Container -->
    </main>
</div><!-- End Flex Container -->





<script>
/**
 * UI CONTROLLER
 * Handles Sidebar, Modals, Tabs, and AJAX Loading
 */

// --- Sidebar Logic ---
function toggleSidebar() { 
    const sidebar = document.getElementById('sidebar'); 
    const iconPath = document.getElementById('sidebarToggleIconPath'); 
    sidebar.classList.toggle('w-64'); 
    sidebar.classList.toggle('w-20'); // Changed to w-20 for better collapsed view
    
    // Toggle visibility of text elements
    sidebar.querySelectorAll('.sidebar-link-text, .sidebar-logo').forEach(el => {
        if(sidebar.classList.contains('w-20')) {
             el.classList.add('hidden');
        } else {
             el.classList.remove('hidden');
        }
    });

    // Rotate/Change Icon
    iconPath.setAttribute('d', sidebar.classList.contains('w-20') ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'); 
}

// --- Modal Logic ---
function openModal(modalId) { 
    const overlay = document.getElementById('modalOverlay');
    const modal = document.getElementById(modalId);
    if(overlay && modal) {
        overlay.classList.remove('hidden'); 
        modal.classList.remove('hidden');
        // Animation class can be added here
    }
}

function closeModal() { 
    document.getElementById('modalOverlay').classList.add('hidden'); 
    document.querySelectorAll('[id$="Modal"]').forEach(modal => modal.classList.add('hidden')); 
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

// --- Tab Logic ---
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(div => div.classList.add('hidden'));
    
    // Deactivate all buttons
    document.querySelectorAll('.tab-button, #content-tabs-nav button').forEach(btn => {
        // Remove active styles (gold border/text)
        btn.classList.remove('border-gold', 'text-gold');
        btn.classList.add('border-transparent', 'text-gray-500');
    });

    // Show target tab
    const contentToShow = document.getElementById(`tab-content-${tabName}`);
    if (contentToShow) contentToShow.classList.remove('hidden');

    // Activate target button
    const activeBtn = document.getElementById(`tab-${tabName}`);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
        activeBtn.classList.add('border-gold', 'text-gold');
    }

    // Update URL without reload
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    url.searchParams.delete('page');
    window.history.pushState({ path: url.href }, '', url.href);
}


// --- Content Loading Logic (SPA Feel) ---
// --- Content Loading Logic (SPA Feel) ---
function fetchAndLoadTab(tabName) {
    // Handle 'Dashboard' internal view
    if (tabName === 'admin_dashboard') {
        const mainContent = document.getElementById("main-content");
        const dashboardView = document.getElementById("dashboard-view");
        
        // Ensure main layout is visible
        if (mainContent) mainContent.style.display = ""; 
        
        // If we have a dedicated wrapper for dashboard widgets vs loaded content, toggle it
        // For now, assuming admin_dashboard.php is the base, so we just reset URL
        const url = new URL(window.location.href);
        url.search = '';
        window.history.pushState({ path: url.href }, '', url.href);
        // Reload page to reset state if needed, or just handle UI
        window.location.href = 'admin_dashboard.php';
        return;
    }
    
    // Fetch external content (manage_users, etc.)
    fetch(`${tabName}.php`)
        .then(response => {
            if (!response.ok) throw new Error("Failed to load content for " + tabName);
            return response.text();
        })
        .then(data => {
            // Create temp container to parse HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;
            
            // We only want the MAIN content from the fetched page, not its sidebar/header if present
            // But since legacy pages might have full structure, we need to extract body or main
            // Best bet: extract #main-content or body content if #main-content doesn't exist
            
            // Strategy: Replace the entire #main-content INNER HTML with the fetched body
            // But we want to keep our Sidebar.
            
            const loadedSidebar = tempDiv.querySelector('#sidebar');
            if (loadedSidebar) loadedSidebar.remove();

            let mainContent = document.getElementById("main-content");
            if (!mainContent) {
                console.error("Main content container missing!");
                return;
            }
            
            // Replace content
            mainContent.innerHTML = tempDiv.innerHTML;
            
            // Re-Execute Scripts
            const scripts = mainContent.querySelectorAll("script");
            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.body.appendChild(newScript);
            });

            // Update URL
            const url = new URL(window.location.href);
            url.searchParams.set('page', tabName);
            url.searchParams.delete('tab');
            window.history.pushState({ path: url.href }, '', url.href);

        })
        .catch((error) => {
            console.error("Error fetching tab:", error);
            alert("Error loading content. Please refresh.");
        });
}

// --- Specific Modal Helpers (Pre-filling forms) ---


function openEditServiceModal(id, name, price, description, image) { 
    document.getElementById('editServiceId').value = id; 
    document.getElementById('editServiceName').value = name; 
    document.getElementById('editServicePrice').value = price; 
    document.getElementById('editServiceDescription').value = description; 
    document.getElementById('currentServiceImage').src = image ? 'uploads/' + image : ''; 
    openModal('editServiceModal'); 
}

function openEditHaircutModal(id, name, description, image, faceShape, hairType) {
    document.getElementById('editHaircutId').value = id;
    document.getElementById('editHaircutName').value = name;
    document.getElementById('editHaircutDescription').value = description;
    document.getElementById('editHaircutFaceShape').value = faceShape; 
    document.getElementById('editHaircutHairType').value = hairType;

    const currentImagePreview = document.getElementById('currentHaircutImage');
    const noImageText = document.getElementById('noCurrentImageTextHaircut');
    const currentImageHiddenInput = document.getElementById('editHaircutCurrentImageHidden');

    if (image && image !== '') {
        currentImagePreview.src = 'uploads/' + image; 
        currentImagePreview.style.display = 'block';
        if(noImageText) noImageText.style.display = 'none';
        currentImageHiddenInput.value = image;
    } else {
        currentImagePreview.src = '';
        currentImagePreview.style.display = 'none';
        if(noImageText) noImageText.style.display = 'block';
        currentImageHiddenInput.value = '';
    }

    openModal('editHaircutModal');
}

function openRejectModal(id) {
    document.getElementById('rejectAppointmentId').value = id;
    openModal('rejectModal');
}


// --- Specific Modal Helpers (Pre-filling forms) ---


function openEditServiceModal(id, name, price, description, image) { 
    document.getElementById('editServiceId').value = id; 
    document.getElementById('editServiceName').value = name; 
    document.getElementById('editServicePrice').value = price; 
    document.getElementById('editServiceDescription').value = description; 
    document.getElementById('currentServiceImage').src = image ? 'uploads/' + image : ''; 
    openModal('editServiceModal'); 
}

function openEditHaircutModal(id, name, description, image, faceShape, hairType) {
    document.getElementById('editHaircutId').value = id;
    document.getElementById('editHaircutName').value = name;
    document.getElementById('editHaircutDescription').value = description;
    document.getElementById('editHaircutFaceShape').value = faceShape; 
    document.getElementById('editHaircutHairType').value = hairType;

    const currentImagePreview = document.getElementById('currentHaircutImage');
    const noImageText = document.getElementById('noCurrentImageTextHaircut');
    const currentImageHiddenInput = document.getElementById('editHaircutCurrentImageHidden');

    if (image && image !== '') {
        currentImagePreview.src = 'uploads/' + image; 
        currentImagePreview.style.display = 'block';
        if(noImageText) noImageText.style.display = 'none';
        currentImageHiddenInput.value = image;
    } else {
        currentImagePreview.src = '';
        currentImagePreview.style.display = 'none';
        if(noImageText) noImageText.style.display = 'block';
        currentImageHiddenInput.value = '';
    }

    openModal('editHaircutModal');
}

function openRejectModal(id) {
    document.getElementById('rejectAppointmentId').value = id;
    openModal('rejectModal');
}
</script>



<script>
    // --- Mobile Sidebar Toggle ---
    // (Removed duplicate toggleSidebar function)

    // --- Notification Panel Toggle ---
    function toggleNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        if (panel) {
            panel.classList.toggle('hidden');
        }
    }

    // Close notification panel when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('notificationContainer');
        const panel = document.getElementById('notificationPanel');
        if (container && panel && !container.contains(event.target)) {
            panel.classList.add('hidden');
        }
    });

    // =============================================
    // SCHEDULE PAGE FUNCTIONS (for AJAX-loaded content)
    // =============================================
    
    // Handle status change for barber schedule
    window.handleStatusChange = function(selectElem, uid) {
        const timeContainer = document.getElementById('time-inputs-' + uid);
        const formRow = document.getElementById('form-' + uid);
        
        if (!timeContainer || !formRow) {
            console.error('Schedule elements not found for uid:', uid);
            return;
        }
        
        const inputs = timeContainer.querySelectorAll('input[type="time"]');
        
        // Highlight row to show it was modified
        formRow.classList.add('bg-dark-hover');
        formRow.style.borderLeftColor = "#C5A059";
        
        // Enable/Disable Inputs based on status
        if (selectElem.value !== 'off') {
            // Enable time inputs for 'available' and 'rest'
            timeContainer.style.opacity = '1';
            timeContainer.style.pointerEvents = 'auto';
            inputs.forEach(function(input) {
                input.disabled = false;
                input.classList.remove('cursor-not-allowed', 'opacity-50');
            });
            
            // Auto-fill Defaults for available status
            if (selectElem.value === 'available') {
                if (!inputs[0].value) inputs[0].value = '14:00';
                if (!inputs[1].value) inputs[1].value = '00:00';
            }
            
            // Update row border color based on status
            formRow.classList.remove('border-l-gray-600', 'border-l-yellow-500', 'border-l-green-500');
            if (selectElem.value === 'available') {
                formRow.classList.add('border-l-green-500');
            } else if (selectElem.value === 'rest') {
                formRow.classList.add('border-l-yellow-500');
            }
        } else {
            // Disable time inputs for 'off' status
            timeContainer.style.opacity = '0.5';
            timeContainer.style.pointerEvents = 'none';
            inputs.forEach(function(input) {
                input.disabled = true;
                input.classList.add('cursor-not-allowed', 'opacity-50');
            });
            
            // Update row border color
            formRow.classList.remove('border-l-yellow-500', 'border-l-green-500');
            formRow.classList.add('border-l-gray-600');
        }
        
        console.log('Status changed to:', selectElem.value, 'for uid:', uid);
    };
    
    // Update schedule date via AJAX
    window.updateScheduleDate = function(date) {
        fetch('manage_schedule.php?date=' + date)
            .then(function(response) { return response.text(); })
            .then(function(html) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newContent = tempDiv.querySelector('.p-6'); 
                const contentArea = document.getElementById('content-area');
                if (newContent && contentArea) {
                    contentArea.innerHTML = '';
                    contentArea.appendChild(newContent);
                }
            })
            .catch(function(err) { console.error('Error updating date:', err); });
    };
    
    // Handle schedule form update via AJAX
    window.handleScheduleUpdate = function(event, form) {
        event.preventDefault();
        const formData = new FormData(form);

        fetch('manage_schedule.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newContent = tempDiv.querySelector('.p-6');
            const contentArea = document.getElementById('content-area');
            if (newContent && contentArea) {
                contentArea.innerHTML = '';
                contentArea.appendChild(newContent);
                const alert = document.getElementById('success-alert');
                if (alert) setTimeout(function() { alert.style.display = 'none'; }, 1500);
            }
        })
        .catch(function(err) { console.error('Error updating schedule:', err); });
        return false;
    };

    // --- Tab Switching Logic (AJAX) ---
    function fetchAndLoadTab(tabName, queryParams = '') {
        // Auto-close sidebar on mobile if open
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('mobile-force-open')) {
            toggleAdminSidebar();
        }

        // Highlight Sidebar (only if naive tab match)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active-tab', 'bg-gradient-to-r', 'from-gold/10', 'to-transparent', 'text-gold', 'border-r-gold');
            link.classList.add('text-gray-400');
            // Check if this link corresponds to the clicked tab
            if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(tabName) && !queryParams) {
                link.classList.add('active-tab', 'bg-gradient-to-r', 'from-gold/10', 'to-transparent', 'text-gold', 'border-r-gold');
                link.classList.remove('text-gray-400');
            }
        });

        const dashboardView = document.getElementById('dashboard-view');
        const tabsNav = document.getElementById('content-tabs-nav');
        const tabContentsCard = document.getElementById('tab-contents-card');
        const contentArea = document.getElementById('content-area');

        // If dashboard, show default view
        if (tabName === 'admin_dashboard') {
            if (dashboardView) dashboardView.style.display = 'block';
            if (tabsNav) tabsNav.style.display = 'flex';
            if (tabContentsCard) tabContentsCard.style.display = 'block';
            if (contentArea) {
                contentArea.innerHTML = '';
                contentArea.classList.add('hidden');
            }
            
            // Update URL without reload
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            url.searchParams.delete('tab');
            window.history.pushState({}, '', url);
            return;
        }

        // Hide Dashboard Widgets, tabs nav, AND the tab contents card
        if (dashboardView) dashboardView.style.display = 'none';
        if (tabsNav) tabsNav.style.display = 'none';
        if (tabContentsCard) tabContentsCard.style.display = 'none';
        
        // Show and prepare content area
        if (contentArea) {
            contentArea.classList.remove('hidden');
            contentArea.innerHTML = '<div class="flex justify-center items-center h-64"><i class="fas fa-circle-notch fa-spin text-4xl text-gold"></i></div>';
        }

        // Append timestamp to prevent caching
        const sep = queryParams.startsWith('?') ? '&' : '?';
        const CacheBust = sep + '_t=' + new Date().getTime();
        
        fetch(`${tabName}.php${queryParams}${CacheBust}`)
            .then(response => {
                if (!response.ok) throw new Error("Failed to load content for " + tabName);
                return response.text();
            })
            .then(data => {
                // Create temp container to parse HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data;
                
                // Try to find the main content wrapper (.p-6)
                const mainWrapper = tempDiv.querySelector('.p-6');
                
                if (mainWrapper && contentArea) {
                    contentArea.innerHTML = '';
                    contentArea.appendChild(mainWrapper);
                } else if (contentArea) {
                    // Fallback: just dump everything
                    contentArea.innerHTML = data; 
                }
                
                // Re-Execute Scripts
                if (contentArea) {
                    const scripts = contentArea.querySelectorAll("script");
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement("script");
                        if (oldScript.src) {
                            newScript.src = oldScript.src;
                        } else {
                            newScript.textContent = oldScript.textContent;
                        }
                        document.body.appendChild(newScript);
                    });
                }

                // Update URL
                const url = new URL(window.location.href);
                url.searchParams.set('page', tabName);
                url.searchParams.delete('tab');
                
                // Merge queryParams if present
                if (queryParams && queryParams.startsWith('?')) {
                    const params = new URLSearchParams(queryParams);
                    params.forEach((value, key) => {
                        url.searchParams.set(key, value);
                    });
                }
                
                window.history.pushState({ path: url.href }, '', url.href);

            })
            .catch((error) => {
                console.error("Error fetching tab:", error);
                if (contentArea) {
                    contentArea.innerHTML = `<div class="text-center text-red-500 p-8"><i class="fas fa-exclamation-triangle text-4xl mb-4"></i><br>Error loading content.</div>`;
                }
            });
    }

    // --- Modal Logic ---
    function openModal(modalId) { 
        const modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.remove('hidden');
            // Animate in
            setTimeout(() => {
                modal.classList.remove('opacity-0');
            }, 10);
        }
    }

    function closeModal() { 
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
             modal.classList.add('hidden');
        }); 
    }

    // Close on click outside
    window.onclick = function(event) {
        if (event.target.classList.contains('fixed') && event.target.classList.contains('z-50')) {
            closeModal();
        }
    }

    // --- Specific Modal Helpers (Pre-filling forms) ---


    function openEditServiceModal(id, name, price, description, image) { 
        document.getElementById('editServiceId').value = id; 
        document.getElementById('editServiceName').value = name; 
        document.getElementById('editServicePrice').value = price; 
        document.getElementById('editServiceDescription').value = description; 
        const currentImage = document.getElementById('currentServiceImage');
        if(image) {
            currentImage.src = 'uploads/' + image; 
        } else {
            currentImage.src = 'assets/images/default-service.png';
        }
        openModal('editServiceModal'); 
    }

    function openEditHaircutModal(id, name, description, image, faceShape, hairType) {
        document.getElementById('editHaircutId').value = id;
        document.getElementById('editHaircutName').value = name;
        document.getElementById('editHaircutDescription').value = description;
        
        // Handle Multi-selects or Single selects? The modal has single select in my new code, but DB might have JSON logic or multiple?
        // My new Add/Edit Haircut modals have SINGLE selects for Face Shape and Hair Type in the HTML I just wrote (oops, original `modals.php` had MULTIPLE).
        // Let's stick to SINGLE for now as per my template, or assuming layout simplicity.
        // Wait, `modals.php` had `multiple`. If the DB stores JSON array `["Oval", "Round"]`, my single select will break that or only allow one.
        // For the Prestige design, I simplified it to Single select in the HTML I wrote.
        // If the user needs multiple, I should have used multiple.
        // Given I already wrote Single, I will assume Single is acceptable for this redesign phase or update it if I see errors.
        // However, `view_booking.php` doesn't show hair type/face shape intricacies.
        // Let's assume Single is fine for now to keep the UI clean, or I can update HTML later.
        
        // Reset and populate Face Shape checkboxes
        const faceShapeContainer = document.getElementById('editFaceShapeContainer');
        const faceCb = faceShapeContainer.querySelectorAll('input[type="checkbox"]');
        faceCb.forEach(cb => cb.checked = false); // Reset
        
        if (faceShape) {
            const shapes = faceShape.split(',').map(s => s.trim().toLowerCase());
            faceCb.forEach(cb => {
                if (shapes.includes(cb.value.toLowerCase())) {
                    cb.checked = true;
                }
            });
        }

        // Reset and populate Hair Type checkboxes
        const hairTypeContainer = document.getElementById('editHairTypeContainer');
        const hairCb = hairTypeContainer.querySelectorAll('input[type="checkbox"]');
        hairCb.forEach(cb => cb.checked = false); // Reset

        if (hairType) {
            const types = hairType.split(',').map(t => t.trim().toLowerCase());
            hairCb.forEach(cb => {
                if (types.includes(cb.value.toLowerCase())) {
                    cb.checked = true;
                }
            });
        }

        const currentImagePreview = document.getElementById('currentHaircutImage');
        const noImageText = document.getElementById('noCurrentImageTextHaircut');
        const currentImageHiddenInput = document.getElementById('editHaircutCurrentImageHidden');

        if (image && image !== '') {
            currentImagePreview.src = 'uploads/' + image; 
            currentImagePreview.style.display = 'block';
            if(noImageText) noImageText.classList.add('hidden');
            currentImageHiddenInput.value = image;
        } else {
            currentImagePreview.src = '';
            currentImagePreview.style.display = 'none';
            if(noImageText) noImageText.classList.remove('hidden');
            currentImageHiddenInput.value = '';
        }

        openModal('editHaircutModal');
    }



    // --- SHOW TAB LOGIC (Inline Content) ---
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('hidden');
        });

        // Show specific tab content
        const targetTab = document.getElementById('tab-content-' + tabName);
        if (targetTab) {
            targetTab.classList.remove('hidden');
        }

        // Update Inline Tab Buttons (Manage Barbers, Services, Haircuts)
        const tabs = ['barbers', 'services', 'haircuts'];
        tabs.forEach(t => {
            const btn = document.getElementById('tab-' + t);
            if (btn) {
                if (t === tabName) {
                    btn.classList.add('border-gold', 'text-gold', 'active-tab');
                    btn.classList.remove('border-transparent', 'text-gray-500');
                    btn.classList.remove('hover:text-white'); // Optional style cleanup
                } else {
                    btn.classList.remove('border-gold', 'text-gold', 'active-tab');
                    btn.classList.add('border-transparent', 'text-gray-500');
                    btn.classList.add('hover:text-white');
                }
            }
        });

        // Highlight Sidebar Links (if any match)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active-tab', 'bg-gradient-to-r', 'from-gold/10', 'to-transparent', 'text-gold', 'border-r-gold');
            link.classList.add('text-gray-400');
            // Check if this link corresponds to the clicked tab
            if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(tabName)) {
                link.classList.add('active-tab', 'bg-gradient-to-r', 'from-gold/10', 'to-transparent', 'text-gold', 'border-r-gold');
                link.classList.remove('text-gray-400');
            }
        });

        // Update URL
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        url.searchParams.delete('page');
        window.history.pushState({ path: url.href }, '', url.href);
    }

    // --- Initialization ---
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const pageToLoad = urlParams.get('page');
        const tabToLoad = urlParams.get('tab');

        // Auto-dismiss alerts
        const alerts = document.querySelectorAll('[role="alert"]');
        if (alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 1500);
        }

        if (pageToLoad && pageToLoad !== 'admin_dashboard') {
            fetchAndLoadTab(pageToLoad);
        } else {
            const validTabs = ['barbers', 'services', 'haircuts'];
            
            if (tabToLoad && validTabs.includes(tabToLoad)) {
                // If a specific tab is requested via URL
                // If a specific tab is requested via URL, make sure dashboard view is visible
                document.getElementById('dashboard-view').style.display = 'block';
                showTab(tabToLoad);
            } else {
                // Default: Show Dashboard Widgets AND Manage Barbers Tab
                document.getElementById('dashboard-view').style.display = 'block'; 
                showTab('barbers'); // Show Barbers Tab by default
            }
        }
    });

    // ============================================
    // GLOBAL FUNCTIONS FOR AJAX-LOADED PAGES
    // These are called by view_booking.php and reports.php
    // ============================================

    // --- VIEW BOOKING MODAL FUNCTIONS ---
    function showBookingDetails(id, customer, date, time, barber, notes, status) {
        const modal = document.getElementById('bookingDetailsModal');
        if (!modal) {
            console.error('Booking details modal not found');
            return;
        }
        document.getElementById('modalCustomerName').textContent = customer;
        document.getElementById('modalDate').textContent = date;
        document.getElementById('modalTime').textContent = time;
        document.getElementById('modalBarber').textContent = barber;
        document.getElementById('modalNotes').textContent = notes || 'No notes';
        
        const statusEl = document.getElementById('modalStatus');
        statusEl.textContent = status;
        statusEl.className = 'font-medium ';
        if (status === 'Completed') {
            statusEl.className += 'text-blue-400';
        } else if (status === 'Cancelled') {
            statusEl.className += 'text-red-400';
        } else {
            statusEl.className += 'text-gray-400';
        }
        
        const notesContainer = document.getElementById('modalNotesContainer');
        if (notesContainer) notesContainer.style.display = notes ? 'block' : 'none';
        
        modal.classList.remove('hidden');
    }
    
    function closeBookingDetailsModal() {
        const modal = document.getElementById('bookingDetailsModal');
        if (modal) modal.classList.add('hidden');
    }

    // --- REPORTS PDF DOWNLOAD FUNCTION ---

    function downloadPDF(btnElement) {
        try {
            console.log('Starting PDF generation...');
            
            // Target the HIDDEN PRINT TEMPLATE instead of the raw content
            const template = document.getElementById('printTemplate');
            console.log('Template element:', template);
            
            if (!template) {
                console.error('Template not found!');
                alert('Ralat: Template report (printTemplate) tidak dijumpai. Sila pastikan page report dah load habis.');
                return;
            }
            
            if (typeof html2pdf === 'undefined') {
                console.error('html2pdf library is undefined');
                alert('Maaf boh, library PDF tak backup load lagi. Sila refresh page dulu atau tunggu sekejap.');
                return;
            }

            // Capture Charts as Images
            const revenueCanvas = document.getElementById('revenueChart');
            const statusCanvas = document.getElementById('statusChart');
            
            if (revenueCanvas) {
                document.getElementById('printRevenueChart').src = revenueCanvas.toDataURL('image/png');
            }
            if (statusCanvas) {
                document.getElementById('printStatusChart').src = statusCanvas.toDataURL('image/png');
            }

            // Show template temporarily for html2pdf to render it
            template.classList.remove('hidden');

            const opt = {
                margin:       [0.5, 0.5, 0.5, 0.5], // Top, Right, Bottom, Left
                filename:     'Hitzmen_Report_' + new Date().toISOString().slice(0, 10) + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, backgroundColor: '#ffffff' }, // White BG for report
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' } // Portrait for document style
            };

            const btn = btnElement || (typeof event !== 'undefined' ? event.target.closest('button') : null);
            if (btn) {
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
                btn.disabled = true;

                html2pdf().set(opt).from(template).toPdf().get('pdf').then(function (doc) {
                    const pdfBlob = doc.output('bloburl');
                    window.open(pdfBlob, '_blank');
                    
                    // Cleanup
                    template.classList.add('hidden');
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }).catch((err) => {
                    console.error('PDF generation error:', err);
                    alert('PDF Error: ' + err.message);
                    template.classList.add('hidden'); // Ensure hidden on error
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
            } else {
                 html2pdf().set(opt).from(template).toPdf().get('pdf').then(function (doc) {
                     window.open(doc.output('bloburl'), '_blank');
                     template.classList.add('hidden');
                });
            }
        } catch (e) {
            console.error('Critical downloadPDF error:', e);
            alert('Kesalahan kritikal: ' + e.message);
            // Ensure template is hidden if it was shown
            const t = document.getElementById('printTemplate');
            if(t) t.classList.add('hidden');
        }
    }
    
    // --- REPORTS CHART INITIALIZATION ---
    function initializeReportCharts() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded yet');
            return;
        }

        const ctxRevenue = document.getElementById('revenueChart');
        if (ctxRevenue && !ctxRevenue.chartInstance) {
            const revenueLabels = ctxRevenue.dataset.labels ? JSON.parse(ctxRevenue.dataset.labels) : [];
            const revenueData = ctxRevenue.dataset.values ? JSON.parse(ctxRevenue.dataset.values) : [];
            ctxRevenue.chartInstance = new Chart(ctxRevenue.getContext('2d'), {
                type: 'line',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        label: 'Revenue (RM)',
                        data: revenueData,
                        borderColor: '#C5A059',
                        backgroundColor: 'rgba(197, 160, 89, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#121212',
                        pointBorderColor: '#C5A059',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#333' }, ticks: { color: '#9CA3AF' } },
                        x: { grid: { display: false }, ticks: { color: '#9CA3AF' } }
                    }
                }
            });
        }

        const ctxStatus = document.getElementById('statusChart');
        if (ctxStatus && !ctxStatus.chartInstance) {
            const statusLabels = ctxStatus.dataset.labels ? JSON.parse(ctxStatus.dataset.labels) : [];
            const statusData = ctxStatus.dataset.values ? JSON.parse(ctxStatus.dataset.values) : [];
            const statusColors = ctxStatus.dataset.colors ? JSON.parse(ctxStatus.dataset.colors) : [];
            ctxStatus.chartInstance = new Chart(ctxStatus.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: statusColors,
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 20, color: '#9CA3AF' } } },
                    cutout: '70%'
                }
            });
        }
    }


</script>
<!-- Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeBookingDetailsModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-dark-card rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-dark-border">
            <div class="bg-dark-card px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-gold/10 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-calendar-check text-gold"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Booking Details</h3>
                        <div class="mt-4 space-y-3">
                            <div class="flex justify-between border-b border-dark-border pb-2">
                                <span class="text-gray-400 text-sm">Customer:</span>
                                <span id="modalCustomerName" class="text-white font-medium"></span>
                            </div>
                            <div class="flex justify-between border-b border-dark-border pb-2">
                                <span class="text-gray-400 text-sm">Date:</span>
                                <span id="modalDate" class="text-white"></span>
                            </div>
                            <div class="flex justify-between border-b border-dark-border pb-2">
                                <span class="text-gray-400 text-sm">Time:</span>
                                <span id="modalTime" class="text-white"></span>
                            </div>
                            <div class="flex justify-between border-b border-dark-border pb-2">
                                <span class="text-gray-400 text-sm">Barber:</span>
                                <span id="modalBarber" class="text-gold"></span>
                            </div>
                            <div class="flex justify-between border-b border-dark-border pb-2">
                                <span class="text-gray-400 text-sm">Status:</span>
                                <span id="modalStatus" class="font-medium"></span>
                            </div>
                             <div id="modalNotesContainer" class="pt-2">
                                <span class="block text-gray-400 text-sm mb-1">Notes:</span>
                                <p id="modalNotes" class="text-gray-300 text-sm bg-dark p-3 rounded italic"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-dark px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-dark-border">
                <button type="button" onclick="closeBookingDetailsModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gold text-base font-medium text-dark hover:bg-gold-light focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Real-time Admin Notifications Script -->
    <audio id="admin-notif-sound" src="assets/audio/customer_notification.mp3" preload="auto"></audio>
    <script>
        // Toggle Panel Logic (adapted from existing onclick)
        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            panel.classList.toggle('hidden');
        }

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('notificationContainer');
            const panel = document.getElementById('notificationPanel');
            const bell = document.getElementById('notificationBell');
            
            // If click is outside container and panel is not hidden
            if (container && !container.contains(e.target) && !panel.classList.contains('hidden')) {
                panel.classList.add('hidden');
            }
        });

        // --- POLLING LOGIC ---
        let lastAdminCount = <?php echo $pendingCount; ?>;
        let lastTodayCount = <?php echo $todaysBookings ?? 0; ?>;

        function fetchAdminNotifications() {
            fetch('actions/fetch_admin_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const bellBtn = document.getElementById('notificationBell');
                    const panelContent = document.querySelector('#notificationPanel .max-h-80');
                    const headerBadge = document.querySelector('#notificationPanel h3 span'); 
                    
                    // 1. Update Bell Badge
                    let badge = bellBtn.querySelector('span.absolute');
                    if (data.count > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full border-2 border-dark-card text-xs text-white flex items-center justify-center font-bold';
                            bellBtn.appendChild(badge);
                        }
                        badge.textContent = data.count;
                    } else if (badge) {
                        badge.remove();
                    }

                    // 2. Play Sound & Toast on Increase
                    if (data.count > lastAdminCount) {
                         const adminAudioEl = document.getElementById('admin-notif-sound');
                         if (adminAudioEl) {
                             adminAudioEl.currentTime = 0;
                             adminAudioEl.play().catch(e => console.log('Audio autoplay blocked:', e));
                         }
                         
                         // Get latest booking name for toast
                         const latest = data.notifications[0];
                         const bookerName = latest ? (latest.customer_real_name || latest.customer_name || 'Customer') : 'New Customer';
                         showToast(`New Booking from ${bookerName}`, "success");
                    }
                    lastAdminCount = data.count;

                    // 3. Update Today's Booking Count
                    const todayCounter = document.getElementById('todays-booking-count');
                    if (todayCounter) {
                        const newToday = parseInt(data.today_count);
                        if (newToday !== lastTodayCount) {
                             todayCounter.textContent = newToday;
                             todayCounter.style.color = '#fffda3'; 
                             setTimeout(() => todayCounter.style.color = '', 500);
                        }
                         todayCounter.textContent = newToday;
                    }
                    lastTodayCount = parseInt(data.today_count);

                    // 4. Update Panel Header Badge
                    if (headerBadge) {
                        if(data.count > 0) {
                            headerBadge.textContent = data.count + ' pending';
                            headerBadge.style.display = 'inline-block';
                        } else {
                            headerBadge.style.display = 'none';
                        }
                    }

                    // 5. Update Dropdown List
                    if (data.notifications.length === 0) {
                        panelContent.innerHTML = `
                            <div class="p-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-3xl mb-2 text-green-500"></i>
                                <p class="text-sm">No pending bookings!</p>
                            </div>`;
                    } else {
                        let html = '';
                        data.notifications.forEach(notif => {
                            const dateObj = new Date(notif.appointment_date + 'T' + notif.appointment_time);
                            const timeStr = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const dateStr = dateObj.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                            const dName = notif.customer_real_name || notif.customer_name || 'Customer';
                            
                            html += `
                                <div class="p-4 border-b border-dark-border hover:bg-dark-hover transition-colors cursor-pointer" onclick="fetchAndLoadTab('view_booking')">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-yellow-900/30 text-yellow-400 flex items-center justify-center shrink-0">
                                            <i class="fas fa-calendar-plus text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-white font-medium truncate">New booking from ${dName}</p>
                                            <p class="text-xs text-gray-500">${dateStr} at ${timeStr}</p>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        panelContent.innerHTML = html;
                    }
                })
                .catch(err => console.error('Admin notif sync error:', err));
        }

        // Poll every 5 seconds
        setInterval(fetchAdminNotifications, 5000);

        function showToast(message, type = 'success') {
            const existingToast = document.querySelector('.custom-toast');
            if(existingToast) existingToast.remove(); 

            const toast = document.createElement('div');
            const borderClass = type === 'warning' ? 'border-yellow-500' : 'border-gold';
            const iconClass = type === 'warning' ? 'text-yellow-500 fa-exclamation-triangle' : 'text-gold fa-bell';
            
            toast.className = `custom-toast fixed bottom-4 right-4 bg-dark-card ${borderClass} border text-white px-6 py-4 rounded-lg shadow-2xl z-50 transform transition-all duration-300 translate-y-10 opacity-0 flex items-center gap-3`;
            toast.innerHTML = `<i class="fas ${iconClass} text-xl"></i> <div><h4 class="font-bold text-sm">Notification</h4><p class="text-xs text-gray-300">${message}</p></div>`;
            document.body.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

    </script>
</body>
</html>
