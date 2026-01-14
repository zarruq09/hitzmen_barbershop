<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'db.php';

// --- DATA FETCHING ---

// --- DATE FILTER LOGIC ---
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default: First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t');     // Default: Last day of current month

// 1. KPI Cards
$totalRevenueStmt = $pdo->prepare("SELECT SUM(total_price) FROM appointments WHERE status = 'Completed' AND appointment_date BETWEEN ? AND ?");
$totalRevenueStmt->execute([$startDate, $endDate]);
$totalRevenue = $totalRevenueStmt->fetchColumn() ?: 0;

$totalApptStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ?");
$totalApptStmt->execute([$startDate, $endDate]);
$totalAppointments = $totalApptStmt->fetchColumn() ?: 0;

$pendingApptStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'Pending' AND appointment_date BETWEEN ? AND ?");
$pendingApptStmt->execute([$startDate, $endDate]);
$pendingAppointments = $pendingApptStmt->fetchColumn() ?: 0;

$cancelledApptStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled' AND appointment_date BETWEEN ? AND ?");
$cancelledApptStmt->execute([$startDate, $endDate]);
$cancelledAppointments = $cancelledApptStmt->fetchColumn() ?: 0;
$cancellationRate = $totalAppointments > 0 ? ($cancelledAppointments / $totalAppointments) * 100 : 0;

// 2. Charts Data

// Revenue Trend (Daily within range)
$revenueTrendStmt = $pdo->prepare("
    SELECT DATE(appointment_date) as date, SUM(total_price) as daily_revenue 
    FROM appointments 
    WHERE status = 'Completed' AND appointment_date BETWEEN ? AND ?
    GROUP BY DATE(appointment_date) 
    ORDER BY date ASC
");
$revenueTrendStmt->execute([$startDate, $endDate]);
$revenueData = $revenueTrendStmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$revenues = [];
foreach ($revenueData as $row) {
    $dates[] = date('M d', strtotime($row['date']));
    $revenues[] = $row['daily_revenue'];
}

// Appointment Status Distribution (within range)
$statusDistStmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY status");
$statusDistStmt->execute([$startDate, $endDate]);
$statusData = $statusDistStmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [];
$statusCounts = [];
$statusColors = [];
foreach ($statusData as $row) {
    $statusLabels[] = $row['status'];
    $statusCounts[] = $row['count'];
    switch ($row['status']) {
        case 'Confirmed': $statusColors[] = '#10B981'; break; // Green
        case 'Pending': $statusColors[] = '#F59E0B'; break; // Yellow
        case 'Cancelled': $statusColors[] = '#EF4444'; break; // Red
        case 'Completed': $statusColors[] = '#3B82F6'; break; // Blue
        default: $statusColors[] = '#6B7280'; // Gray
    }
}

// 3. Top Barbers (within range)
$topBarbersStmt = $pdo->prepare("
    SELECT b.name, COUNT(a.id) as count, SUM(a.total_price) as revenue 
    FROM appointments a 
    JOIN barbers b ON a.barber_id = b.id 
    WHERE a.status = 'Completed' AND a.appointment_date BETWEEN ? AND ?
    GROUP BY a.barber_id 
    ORDER BY revenue DESC 
    LIMIT 5
");
$topBarbersStmt->execute([$startDate, $endDate]);
$topBarbers = $topBarbersStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Feedback Stats (within range)
$feedbackStatsStmt = $pdo->prepare("
    SELECT 
        AVG(shop_rating) as avg_shop,
        AVG(service_rating) as avg_service,
        AVG(staff_rating) as avg_staff,
        COUNT(*) as total_count
    FROM feedback 
    WHERE created_at BETWEEN ? AND ?
");
$feedbackStatsStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$feedbackStats = $feedbackStatsStmt->fetch(PDO::FETCH_ASSOC);

// 5. Feedback List (within range)
$feedbackListStmt = $pdo->prepare("
    SELECT f.*, u.username as customer_name, b.name as barber_name 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.id 
    LEFT JOIN barbers b ON f.barber_id = b.id 
    WHERE f.created_at BETWEEN ? AND ?
    ORDER BY f.created_at DESC
");
$feedbackListStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$feedbacks = $feedbackListStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="p-6" id="reportContent">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <h2 class="text-2xl font-heading font-bold bg-clip-text text-transparent bg-gradient-to-r from-gold to-white">
                    📈 Business Reports
                </h2>
                <p class="text-xs text-gray-500 mt-1">Showing data from <span class="text-gold"><?= date('d M Y', strtotime($startDate)) ?></span> to <span class="text-gold"><?= date('d M Y', strtotime($endDate)) ?></span></p>
            </div>
            <button onclick="downloadPDF(this)" class="btn-gold px-4 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition flex items-center gap-2 text-xs font-bold text-dark h-fit">
                <i class="fas fa-download"></i> PDF
            </button>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3">
             <!-- Date Filter Form -->
             <form id="reportFilterForm" onsubmit="event.preventDefault(); loadFilteredReport();" class="flex flex-wrap items-end gap-2 bg-dark-card p-2 rounded-lg border border-dark-border">
                <div>
                    <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>" class="bg-dark border border-dark-border text-white text-xs rounded px-2 py-1.5 focus:border-gold outline-none">
                </div>
                <div>
                    <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">End Date</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>" class="bg-dark border border-dark-border text-white text-xs rounded px-2 py-1.5 focus:border-gold outline-none">
                </div>
                <button type="submit" class="bg-gold text-dark font-bold text-xs px-3 py-1.5 rounded hover:bg-gold-light transition h-[30px]">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <script>
    function loadFilteredReport() {
        const form = document.getElementById('reportFilterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        fetch('reports.php?' + params)
            .then(response => response.text())
            .then(html => {
                // We need to parse the HTML to get just the content if it returns full page, 
                // but reports.php seems to be a fragment? 
                // Wait, reports.php starts with session_start. If fetched directly, it returns the whole response.
                // admin_dashboard.php uses fetch to put it in content-area.
                // So replacing content-area is correct.
                document.getElementById('content-area').innerHTML = html;
                
                // Re-initialize charts
                if (typeof initializeReportCharts === 'function') {
                    setTimeout(initializeReportCharts, 100);
                }
            })
            .catch(err => console.error(err));
    }
    </script>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Revenue -->
        <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg border-l-4 border-l-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase font-bold">Total Revenue</p>
                    <p class="text-2xl font-bold text-white">RM <?= number_format($totalRevenue, 2) ?></p>
                </div>
                <div class="p-3 bg-green-900/30 rounded-full text-green-400">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Appointments -->
        <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase font-bold">Total Appointments</p>
                    <p class="text-2xl font-bold text-white"><?= $totalAppointments ?></p>
                </div>
                <div class="p-3 bg-blue-900/30 rounded-full text-blue-400">
                    <i class="fas fa-calendar-check text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg border-l-4 border-l-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase font-bold">Pending</p>
                    <p class="text-2xl font-bold text-white"><?= $pendingAppointments ?></p>
                </div>
                <div class="p-3 bg-yellow-900/30 rounded-full text-yellow-400">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Cancellation Rate -->
        <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg border-l-4 border-l-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase font-bold">Cancellation Rate</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($cancellationRate, 1) ?>%</p>
                </div>
                <div class="p-3 bg-red-900/30 rounded-full text-red-400">
                    <i class="fas fa-times-circle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Revenue Chart -->
        <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg lg:col-span-2">
            <h3 class="text-xl font-bold mb-4 text-white">Revenue Trend (Last 30 Days)</h3>
            <div class="relative h-72 w-full">
                <canvas id="revenueChart" data-labels='<?= json_encode($dates) ?>' data-values='<?= json_encode($revenues) ?>'></canvas>
            </div>
        </div>

        <!-- Status Chart -->
        <div class="bg-dark-card border border-dark-border p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-bold mb-4 text-white">Appointment Status</h3>
            <div class="relative h-72 w-full flex justify-center">
                <canvas id="statusChart" data-labels='<?= json_encode($statusLabels) ?>' data-values='<?= json_encode($statusCounts) ?>' data-colors='<?= json_encode($statusColors) ?>'></canvas>
            </div>
        </div>
    </div>

    <!-- Top Barbers Table -->
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-border">
            <h3 class="text-xl font-bold text-white">🏆 Top Performing Barbers</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-[#181818]">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gold uppercase tracking-wider">Barber Name</th>
                        <th class="px-6 py-3 text-xs font-bold text-gold uppercase tracking-wider text-center">Completed Appointments</th>
                        <th class="px-6 py-3 text-xs font-bold text-gold uppercase tracking-wider text-right">Revenue Generated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    <?php if (count($topBarbers) > 0): ?>
                        <?php foreach ($topBarbers as $barber): ?>
                        <tr class="hover:bg-dark-hover transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-white"><?= htmlspecialchars($barber['name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-gray-400"><?= $barber['count'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-green-400">RM <?= number_format($barber['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">No completed appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Feedback Section -->
    <div class="mt-8">
        <h3 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gold to-white mb-6">💬 Customer Feedback Analysis</h3>
        
        <!-- Feedback Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
             <div class="bg-dark-card border border-dark-border p-4 rounded-lg flex items-center justify-between shadow-lg">
                <div>
                    <p class="text-gray-400 text-xs uppercase font-bold">Average Shop Vibe</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($feedbackStats['avg_shop'] ?? 0, 1) ?> <span class="text-sm text-gold">★</span></p>
                </div>
                <div class="p-2 bg-yellow-900/20 rounded-full text-gold">
                    <i class="fas fa-store"></i>
                </div>
            </div>
            <div class="bg-dark-card border border-dark-border p-4 rounded-lg flex items-center justify-between shadow-lg">
                <div>
                    <p class="text-gray-400 text-xs uppercase font-bold">Average Service</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($feedbackStats['avg_service'] ?? 0, 1) ?> <span class="text-sm text-gold">★</span></p>
                </div>
                 <div class="p-2 bg-blue-900/20 rounded-full text-blue-400">
                    <i class="fas fa-concierge-bell"></i>
                </div>
            </div>
             <div class="bg-dark-card border border-dark-border p-4 rounded-lg flex items-center justify-between shadow-lg">
                <div>
                    <p class="text-gray-400 text-xs uppercase font-bold">Average Staff Skill</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($feedbackStats['avg_staff'] ?? 0, 1) ?> <span class="text-sm text-gold">★</span></p>
                </div>
                 <div class="p-2 bg-green-900/20 rounded-full text-green-400">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>

        <!-- Feedback Table -->
        <div class="bg-dark-card border border-dark-border rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-dark-border">
                <h3 class="text-lg font-bold text-white">Latest Reviews</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[#181818]">
                        <tr>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Date</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Customer</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Barber</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border text-center">Ratings (Shop, Service, Staff)</th>
                            <th class="py-3 px-4 font-bold text-gold border-b border-dark-border">Comments</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        <?php if (empty($feedbacks)): ?>
                            <tr><td colspan="5" class="py-8 text-center text-gray-500 italic">No feedback found for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $f): ?>
                            <tr class="hover:bg-dark-hover transition-colors">
                                <td class="py-3 px-4 text-gray-400 text-xs">
                                    <?= date('M d', strtotime($f['created_at'])) ?>
                                </td>
                                <td class="py-3 px-4 font-medium text-white">
                                    <?= htmlspecialchars($f['customer_name']) ?>
                                </td>
                                <td class="py-3 px-4 text-gray-300">
                                    <?= htmlspecialchars($f['barber_name'] ?? 'Shop') ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center gap-1 text-[10px]">
                                        <span class="bg-gray-800 px-1.5 py-0.5 rounded text-gold border border-gray-700" title="Shop"><?= $f['shop_rating'] ?></span>
                                        <span class="bg-gray-800 px-1.5 py-0.5 rounded text-gold border border-gray-700" title="Service"><?= $f['service_rating'] ?></span>
                                        <span class="bg-gray-800 px-1.5 py-0.5 rounded text-gold border border-gray-700" title="Staff"><?= $f['staff_rating'] ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-300 italic max-w-xs truncate text-xs">
                                    <?= htmlspecialchars($f['comments'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- printTemplate was here, but ending div moved to bottom -->

<!-- HIDDEN PRINT TEMPLATE -->
<div id="printTemplate" class="hidden bg-white text-black p-6 max-w-[210mm] mx-auto text-xs">
    <!-- Header -->
    <div class="border-b-2 border-gold pb-2 mb-4 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold font-heading text-black uppercase tracking-wider">Business Report</h1>
            <p class="text-xs text-gray-600">Hitzmen Barbershop Management System</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-bold text-gray-800">Report Period</p>
            <p class="text-xs text-gray-600"><?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></p>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-4 gap-3 mb-6">
        <div class="p-2 border border-gray-200 rounded bg-gray-50 flex flex-col justify-center text-center">
            <p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Total Revenue</p>
            <p class="text-lg font-bold text-green-700">RM <?= number_format($totalRevenue, 2) ?></p>
        </div>
        <div class="p-2 border border-gray-200 rounded bg-gray-50 flex flex-col justify-center text-center">
            <p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Total Appointments</p>
            <p class="text-lg font-bold text-gray-800"><?= $totalAppointments ?></p>
        </div>
        <div class="p-2 border border-gray-200 rounded bg-gray-50 flex flex-col justify-center text-center">
            <p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Pending</p>
            <p class="text-lg font-bold text-yellow-600"><?= $pendingAppointments ?></p>
        </div>
        <div class="p-2 border border-gray-200 rounded bg-gray-50 flex flex-col justify-center text-center">
            <p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Cancel Rate</p>
            <p class="text-lg font-bold text-red-600"><?= number_format($cancellationRate, 1) ?>%</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="mb-6 grid grid-cols-2 gap-4">
        <div>
            <p class="text-xs font-bold text-gray-600 mb-1 text-center">Revenue Trend</p>
            <div class="border border-gray-200 p-1 rounded h-32 flex items-center justify-center bg-gray-50">
                <img id="printRevenueChart" class="max-h-full max-w-full object-contain" src="" alt="Revenue Chart">
            </div>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-600 mb-1 text-center">Appointment Status</p>
            <div class="border border-gray-200 p-1 rounded h-32 flex items-center justify-center bg-gray-50">
                <img id="printStatusChart" class="max-h-full max-w-full object-contain" src="" alt="Status Chart">
            </div>
        </div>
    </div>

    <!-- Two Column Layout for Lists -->
    <div class="grid grid-cols-2 gap-6 mb-4">
        <!-- Top Barbers -->
        <div>
            <h3 class="text-sm font-bold border-b border-gray-200 pb-1 mb-2">Top Performing Barbers</h3>
            <table class="w-full text-[10px] text-left">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-2 py-1 border border-gray-300">Name</th>
                        <th class="px-2 py-1 border border-gray-300 text-center">Jobs</th>
                        <th class="px-2 py-1 border border-gray-300 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($topBarbers) > 0): ?>
                        <?php foreach ($topBarbers as $barber): ?>
                        <tr>
                            <td class="px-2 py-1 border border-gray-300 font-medium"><?= htmlspecialchars($barber['name']) ?></td>
                            <td class="px-2 py-1 border border-gray-300 text-center"><?= $barber['count'] ?></td>
                            <td class="px-2 py-1 border border-gray-300 text-right font-bold text-gray-800">RM <?= number_format($barber['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-2 py-1 border border-gray-300 text-center text-gray-500">No data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Feedback Stats -->
        <div>
            <h3 class="text-sm font-bold border-b border-gray-200 pb-1 mb-2">Feedback Ratings</h3>
            <table class="w-full text-[10px] text-left">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-2 py-1 border border-gray-300">Category</th>
                        <th class="px-2 py-1 border border-gray-300 text-right">Average Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-2 py-1 border border-gray-300">Shop Vibe</td>
                        <td class="px-2 py-1 border border-gray-300 text-right font-bold"><?= number_format($feedbackStats['avg_shop'] ?? 0, 1) ?> / 5.0</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border border-gray-300">Service Quality</td>
                        <td class="px-2 py-1 border border-gray-300 text-right font-bold"><?= number_format($feedbackStats['avg_service'] ?? 0, 1) ?> / 5.0</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border border-gray-300">Staff Skill</td>
                        <td class="px-2 py-1 border border-gray-300 text-right font-bold"><?= number_format($feedbackStats['avg_staff'] ?? 0, 1) ?> / 5.0</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Printable Feedback List -->
    <div class="mb-2 avoid-break">
        <h3 class="text-sm font-bold border-b border-gray-200 pb-1 mb-2">Latest Customer Feedback</h3>
        <table class="w-full text-[10px] text-left">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-2 py-1 border border-gray-300 w-16">Date</th>
                    <th class="px-2 py-1 border border-gray-300 w-24">Customer</th>
                    <th class="px-2 py-1 border border-gray-300 w-24 text-center">Ratings (Shop/Srv/Stf)</th>
                    <th class="px-2 py-1 border border-gray-300">Comment</th>
                </tr>
            </thead>
            <tbody>
                 <?php if (count($feedbacks) > 0): ?>
                    <?php 
                        // Show only top 8 to save space
                        $printFeedbacks = array_slice($feedbacks, 0, 8);
                    ?>
                    <?php foreach ($printFeedbacks as $f): ?>
                    <tr>
                        <td class="px-2 py-1 border border-gray-300"><?= date('d/m', strtotime($f['created_at'])) ?></td>
                        <td class="px-2 py-1 border border-gray-300"><?= htmlspecialchars($f['customer_name']) ?></td>
                        <td class="px-2 py-1 border border-gray-300 text-center font-bold">
                            <?= $f['shop_rating'] ?> / <?= $f['service_rating'] ?> / <?= $f['staff_rating'] ?>
                        </td>
                         <td class="px-2 py-1 border border-gray-300 truncate max-w-[200px]"><?= htmlspecialchars($f['comments'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="px-2 py-1 border border-gray-300 text-center">No reviews.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
         <?php if (count($feedbacks) > 8): ?>
            <p class="text-[10px] text-gray-500 italic mt-1 text-center">* Showing recent 8 of <?= count($feedbacks) ?> reviews for brevity.</p>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="mt-4 text-center text-[10px] text-gray-400 border-t border-gray-200 pt-2">
        <p>Generated on <?= date('d M Y h:i A') ?> | Hitzmen Barbershop</p>
    </div>

<!-- Libraries are now loaded in admin_dashboard.php head to ensure global availability -->
<script>
    // Initialize charts when this content is loaded (both directly and via AJAX)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initializeReportCharts === 'function') initializeReportCharts();
        });
    } else {
        // DOM already loaded (AJAX case) - wait a tick for global function to be available
        setTimeout(function() {
            if (typeof initializeReportCharts === 'function') initializeReportCharts();
        }, 300); // Increased timeout slightly to ensure scripts are ready
    }
</script>
</div> <!-- End of #reportContent (moved here) -->

