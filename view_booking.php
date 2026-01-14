<?php
// Ensure this file is being included within the admin dashboard context if possible, 
// but since it's fetched via AJAX/fetch in the dashboard, we need to handle session/db if accessed directly 
// OR rely on the dashboard's context if we were using include. 
// However, the fetch implementation in admin_dashboard.php treats it as a separate request initially if we didn't include it.
// Actually, the fetch implementation gets the TEXT of this file. 
// If this file has PHP code, it needs to be processed by the server. 
// The fetch call in admin_dashboard.php is `fetch(${tabName}.php)`. This executes the PHP and returns HTML.
// So we need session start and DB connection here.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If accessed directly and not admin, redirect.
    // If loaded via fetch, this redirect might be handled by the browser or show login page in the div.
    header('Location: login.php');
    exit();
}

require_once 'db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/csrf_token.php';

// 1. Fetch all services for lookup (ID -> Name)
$servicesMap = [];
try {
    $stmtServices = $pdo->query("SELECT id, service_name, price FROM services");
    while ($row = $stmtServices->fetch(PDO::FETCH_ASSOC)) {
        $servicesMap[$row['id']] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
}

// 2. Fetch Barbers for Filter Dropdown
$barbersList = [];
try {
    $stmtBarbers = $pdo->query("SELECT id, name FROM barbers WHERE status != 'Deleted'");
    while ($row = $stmtBarbers->fetch(PDO::FETCH_ASSOC)) {
        $barbersList[] = $row;
    }
} catch (PDOException $e) {
    // Silent fail or log
}

// 3. Build Query with Filters
$where = [];
$params = [];

// Filter: Date Range
if (!empty($_GET['date_from'])) {
    $where[] = "a.appointment_date >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = "a.appointment_date <= ?";
    $params[] = $_GET['date_to'];
}

// Filter: Status
if (!empty($_GET['status']) && $_GET['status'] !== 'All') {
    $where[] = "a.status = ?";
    $params[] = $_GET['status'];
}

// Filter: Barber
if (!empty($_GET['barber_id']) && $_GET['barber_id'] !== 'All') {
    $where[] = "a.barber_id = ?";
    $params[] = $_GET['barber_id'];
}

$sql = "
    SELECT 
        a.id AS appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.services_ids_json,
        a.total_price,
        a.notes,
        u.username AS customer_name,
        u.email AS customer_email,
        b.name AS barber_name,
        h.style_name AS haircut_style
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN barbers b ON a.barber_id = b.id
    LEFT JOIN haircuts h ON a.haircut_id = h.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Default ordering
$sql .= " ORDER BY 
    CASE 
        WHEN a.status = 'Pending' THEN 1 
        WHEN a.status = 'Confirmed' THEN 2 
        ELSE 3 
    END ASC,
    a.appointment_date DESC, a.appointment_time DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
    $error = "Error fetching appointments: " . $e->getMessage();
}

?>


<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h2 class="text-2xl font-heading font-bold bg-clip-text text-transparent bg-gradient-to-r from-gold to-white">
            📅 Appointment Bookings
        </h2>
        
        <!-- Filter Form -->
        <form id="filterForm" onsubmit="event.preventDefault(); loadFilteredBookings();" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" class="bg-dark border border-dark-border text-white text-xs rounded px-2 py-1.5 focus:border-gold outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">To</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" class="bg-dark border border-dark-border text-white text-xs rounded px-2 py-1.5 focus:border-gold outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="bg-dark border border-dark-border text-white text-xs rounded px-2 py-1.5 focus:border-gold outline-none">
                    <option value="All">All Status</option>
                    <?php foreach(['Pending', 'Confirmed', 'Completed', 'Cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Barber</label>
                <select name="barber_id" class="bg-dark border border-dark-border text-white text-xs rounded px-2 py-1.5 focus:border-gold outline-none">
                    <option value="All">All Barbers</option>
                    <?php foreach($barbersList as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($_GET['barber_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-gold text-dark font-bold text-xs px-4 py-1.5 rounded hover:bg-gold-light transition mt-4 md:mt-0">
                    Filter
                </button>
                <button type="button" onclick="resetFilters()" class="text-gray-400 text-xs hover:text-white ml-2 underline">
                    Clear
                </button>
            </div>
        </form>
    </div>

    <script>
    function loadFilteredBookings() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        // Use the existing content loading mechanism
        // We assume 'view_booking' is the page/tab name.
        // admin_dashboard.php uses fetch to load content.
        // We need to reload THIS tab with new params.
        
        // If we are inside admin_dashboard context, we can just fetch and replace content-area
        fetch('view_booking.php?' + params + '&_t=' + new Date().getTime())
            .then(response => response.text())
            .then(html => {
                document.getElementById('content-area').innerHTML = html;
                // Re-attach scripts if necessary (modals are global so fine)
            })
            .catch(err => console.error(err));
    }

    function resetFilters() {
        // Clear inputs
        document.getElementById('filterForm').reset();
        loadFilteredBookings(); // Reload empty
    }
    </script>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'status_updated'): ?>
        <div class="bg-green-900/30 border border-green-500/50 text-green-400 p-4 rounded-lg mb-6 flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">Appointment status updated.</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-900/30 border border-red-500/50 text-red-400 p-4 rounded-lg mb-6 flex items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-dark-card border border-dark-border rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#181818] border-b border-dark-border">
                    <tr>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Date & Time</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Customer</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Barber</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Service(s)</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Haircut</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Price</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider text-center">Status</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="8" class="py-8 px-6 text-center text-gray-500 italic">
                                <i class="fas fa-calendar-times text-4xl mb-3 opacity-30"></i>
                                <p>No appointments found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr class="hover:bg-dark-hover transition-colors group">
                                <td class="py-3 px-6 whitespace-nowrap">
                                    <div class="font-medium text-white"><?= date('M d, Y', strtotime($appt['appointment_date'])) ?></div>
                                    <div class="text-xs text-gold"><?= date('h:i A', strtotime($appt['appointment_time'])) ?></div>
                                </td>
                                <td class="py-3 px-6">
                                    <div class="font-medium text-white"><?= htmlspecialchars($appt['customer_name'] ?? 'Unknown') ?></div>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($appt['customer_email'] ?? '') ?></div>
                                </td>
                                <td class="py-3 px-6 text-gray-300"><?= htmlspecialchars($appt['barber_name'] ?? 'Any Barber') ?></td>
                                <td class="py-3 px-6 text-sm text-gray-400">
                                    <?php 
                                        $serviceIds = json_decode($appt['services_ids_json'] ?? '[]', true);
                                        $serviceNames = [];
                                        if (is_array($serviceIds)) {
                                            foreach ($serviceIds as $sId) {
                                                if (isset($servicesMap[$sId])) {
                                                    $serviceNames[] = htmlspecialchars($servicesMap[$sId]['service_name']);
                                                }
                                            }
                                        }
                                        echo !empty($serviceNames) ? implode(', ', $serviceNames) : '<span class="text-gray-600">-</span>';
                                    ?>
                                </td>
                                <td class="py-3 px-6 text-sm text-gray-400"><?= htmlspecialchars($appt['haircut_style'] ?? '-') ?></td>
                                <td class="py-3 px-6 font-bold text-gold">RM <?= number_format($appt['total_price'], 2) ?></td>
                                <td class="py-3 px-6 text-center">
                                    <?php 
                                        $statusClass = match($appt['status']) {
                                            'Confirmed' => 'bg-green-900/30 text-green-400 border-green-900/50',
                                            'Cancelled' => 'bg-red-900/30 text-red-400 border-red-900/50',
                                            'Pending' => 'bg-yellow-900/30 text-yellow-400 border-yellow-900/50',
                                            'Completed' => 'bg-blue-900/30 text-blue-400 border-blue-900/50',
                                            default => 'bg-gray-800 text-gray-400 border-gray-700',
                                        };
                                    ?>
                                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full border <?= $statusClass ?>">
                                        <?= htmlspecialchars($appt['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center space-x-2">
                                        <?php if ($appt['status'] == 'Pending'): ?>
                                            <form action="actions/update_appointment_status.php" method="POST" class="inline">
                                                <?php csrfField(); ?>
                                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                                <input type="hidden" name="status" value="Confirmed">
                                                <button type="submit" class="text-green-500 hover:text-green-400 transition-colors" title="Accept">
                                                    <i class="fas fa-check-circle text-lg"></i>
                                                </button>
                                            </form>
                                            <button type="button" onclick="openRejectModal(<?= $appt['appointment_id'] ?>)" class="text-red-500 hover:text-red-400 transition-colors" title="Reject">
                                                <i class="fas fa-times-circle text-lg"></i>
                                            </button>
                                        <?php elseif ($appt['status'] == 'Confirmed'): ?>
                                            <form action="actions/update_appointment_status.php" method="POST" class="inline">
                                                <?php csrfField(); ?>
                                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" class="text-blue-500 hover:text-blue-400 transition-colors" title="Mark as Completed">
                                                    <i class="fas fa-clipboard-check text-lg"></i>
                                                </button>
                                            </form>
                                            <form action="actions/update_appointment_status.php" method="POST" class="inline">
                                                <?php csrfField(); ?>
                                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                                <input type="hidden" name="status" value="Cancelled">
                                                <button type="submit" class="text-red-500 hover:text-red-400 transition-colors" title="Cancel" onclick="return confirm('Cancel this confirmed booking?')">
                                                     <i class="fas fa-ban text-lg"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Actions for Completed/Cancelled -->
                                            <button type="button" 
                                                onclick="showBookingDetails(<?= $appt['appointment_id'] ?>, '<?= htmlspecialchars($appt['customer_name'] ?? 'Customer', ENT_QUOTES) ?>', '<?= date('M d, Y', strtotime($appt['appointment_date'])) ?>', '<?= date('h:i A', strtotime($appt['appointment_time'])) ?>', '<?= htmlspecialchars($appt['barber_name'] ?? 'N/A', ENT_QUOTES) ?>', '<?= htmlspecialchars($appt['notes'] ?? '', ENT_QUOTES) ?>', '<?= $appt['status'] ?>')" 
                                                class="text-gray-400 hover:text-gold transition-colors" title="View Details">
                                                <i class="fas fa-eye text-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeBookingDetailsModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2 flex items-center gap-2">
            <i class="fas fa-calendar-check text-gold"></i> Booking Details
        </h2>
        <div class="space-y-4">
            <div class="flex items-center gap-3 p-3 bg-dark rounded-lg border border-dark-border">
                <div class="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center">
                    <i class="fas fa-user text-gold"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Customer</p>
                    <p class="text-white font-medium" id="modalCustomerName">-</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-dark rounded-lg border border-dark-border">
                    <p class="text-xs text-gray-500">Date</p>
                    <p class="text-white font-medium" id="modalDate">-</p>
                </div>
                <div class="p-3 bg-dark rounded-lg border border-dark-border">
                    <p class="text-xs text-gray-500">Time</p>
                    <p class="text-white font-medium" id="modalTime">-</p>
                </div>
            </div>
            <div class="p-3 bg-dark rounded-lg border border-dark-border">
                <p class="text-xs text-gray-500">Barber</p>
                <p class="text-white font-medium" id="modalBarber">-</p>
            </div>
            <div class="p-3 bg-dark rounded-lg border border-dark-border">
                <p class="text-xs text-gray-500">Status</p>
                <p class="font-medium" id="modalStatus">-</p>
            </div>
            <div class="p-3 bg-dark rounded-lg border border-dark-border" id="modalNotesContainer">
                <p class="text-xs text-gray-500">Notes</p>
                <p class="text-white" id="modalNotes">-</p>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
            <button type="button" onclick="closeBookingDetailsModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Close</button>
        </div>
    </div>
</div>

<script>
    function showBookingDetails(id, customer, date, time, barber, notes, status) {
        document.getElementById('modalCustomerName').textContent = customer;
        document.getElementById('modalDate').textContent = date;
        document.getElementById('modalTime').textContent = time;
        document.getElementById('modalBarber').textContent = barber;
        document.getElementById('modalNotes').textContent = notes || 'No notes';
        
        const statusEl = document.getElementById('modalStatus');
        statusEl.textContent = status;
        
        // Style status
        statusEl.className = 'font-medium ';
        if (status === 'Completed') {
            statusEl.className += 'text-blue-400';
        } else if (status === 'Cancelled') {
            statusEl.className += 'text-red-400';
        } else {
            statusEl.className += 'text-gray-400';
        }
        
        // Hide notes if empty
        const notesContainer = document.getElementById('modalNotesContainer');
        notesContainer.style.display = notes ? 'block' : 'none';
        
        document.getElementById('bookingDetailsModal').classList.remove('hidden');
    }
    
    function closeBookingDetailsModal() {
        document.getElementById('bookingDetailsModal').classList.add('hidden');
    }
    
    // Close on click outside
    document.getElementById('bookingDetailsModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeBookingDetailsModal();
    });
</script>
