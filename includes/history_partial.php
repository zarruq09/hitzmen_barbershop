<?php
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch services map
$servicesMap = [];
try {
    $stmtServices = $pdo->query("SELECT id, service_name FROM services");
    while ($row = $stmtServices->fetch(PDO::FETCH_ASSOC)) {
        $servicesMap[$row['id']] = $row['service_name'];
    }
} catch (PDOException $e) { /* Silent fail */ }

// Fetch user's appointments
$sql = "
    SELECT 
        a.id AS appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.services_ids_json,
        a.total_price,
        a.notes,
        a.rejection_reason,
        a.barber_id,
        b.name AS barber_name,
        h.style_name AS haircut_style,
        (f.id IS NOT NULL) as is_rated
    FROM appointments a
    LEFT JOIN barbers b ON a.barber_id = b.id
    LEFT JOIN haircuts h ON a.haircut_id = h.id
    LEFT JOIN feedback f ON a.id = f.appointment_id
    WHERE a.user_id = ?
    ORDER BY 
        CASE 
            WHEN a.status = 'Pending' THEN 1 
            WHEN a.status = 'Confirmed' THEN 2 
            ELSE 3 
        END ASC,
        a.appointment_date DESC, a.appointment_time DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching your appointments.";
    $appointments = [];
}
?>

<div class="animate-fade-in space-y-8">
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-heading font-bold text-white mb-4">My Appointments</h2>
        <div class="w-24 h-1 bg-gradient-gold mx-auto rounded-full"></div>
        <p class="text-gray-400 max-w-2xl mx-auto mt-4 text-lg font-light">
            Track your grooming schedule and history.
        </p>
    </div>

    <!-- Status Messages (Session based) -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-fade bg-green-900/20 border-l-4 border-green-500 text-green-400 p-4 rounded-r-lg mb-8 shadow-lg flex items-center max-w-4xl mx-auto">
            <i class="fas fa-check-circle text-2xl mr-4"></i>
            <div>
                <p class="font-bold">Success</p>
                <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-fade bg-red-900/20 border-l-4 border-red-500 text-red-400 p-4 rounded-r-lg mb-8 shadow-lg flex items-center max-w-4xl mx-auto">
            <i class="fas fa-exclamation-circle text-2xl mr-4"></i>
            <div>
                <p class="font-bold">Error</p>
                <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($appointments)): ?>
        <div class="bg-dark-card border border-dark-border rounded-xl p-12 text-center shadow-lg max-w-2xl mx-auto">
            <div class="text-gold text-5xl mb-4"><i class="far fa-calendar-times"></i></div>
            <p class="text-xl text-gray-400 mb-8">You have no appointment history.</p>
            <a href="?view=book" class="inline-block bg-gradient-to-r from-gold to-gold-light text-dark font-bold py-3 px-8 rounded-full shadow-lg hover:shadow-gold/20 transform hover:-translate-y-1 transition-all">
                Book Your First Appointment
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($appointments as $appt): ?>
                <div class="glass-card rounded-2xl p-6 relative overflow-hidden group hover:border-gold/50 transition-all duration-300">
                    
                    <!-- Status Badge -->
                    <?php 
                        $statusClass = 'bg-gray-700 text-gray-300';
                        $statusIcon = 'fa-clock';
                        if ($appt['status'] == 'Confirmed') { $statusClass = 'bg-green-900/50 text-green-200 border border-green-800'; $statusIcon = 'fa-check-circle'; }
                        elseif ($appt['status'] == 'Pending') { $statusClass = 'bg-yellow-900/50 text-yellow-200 border border-yellow-800'; $statusIcon = 'fa-hourglass-half'; }
                        elseif ($appt['status'] == 'Cancelled') { $statusClass = 'bg-red-900/50 text-red-200 border border-red-800'; $statusIcon = 'fa-times-circle'; }
                        elseif ($appt['status'] == 'Completed') { $statusClass = 'bg-blue-900/50 text-blue-200 border border-blue-800'; $statusIcon = 'fa-check-double'; }
                    ?>
                    <div class="absolute top-4 right-4 px-3 py-1 text-xs font-bold uppercase rounded-full flex items-center gap-1 <?php echo $statusClass; ?>">
                        <i class="fas <?php echo $statusIcon; ?>"></i>
                        <?php echo htmlspecialchars($appt['status']); ?>
                    </div>

                    <div class="mb-6 mt-2">
                        <h3 class="text-xl text-gold mb-1 font-bold">
                            <?php echo date('F d, Y', strtotime($appt['appointment_date'])); ?>
                        </h3>
                        <p class="text-white font-bold font-heading flex items-center">
                            <i class="far fa-clock mr-2 text-gray-500"></i>
                            <?php echo date('g:i A', strtotime($appt['appointment_time'])); ?>
                        </p>
                    </div>

                    <?php if ($appt['status'] == 'Cancelled' && !empty($appt['rejection_reason'])): ?>
                        <div class="mb-6 p-3 bg-red-900/20 border border-red-800 rounded-lg text-sm text-red-300">
                            <span class="font-bold block text-red-200 mb-1"><i class="fas fa-exclamation-circle mr-1"></i> Reason:</span> 
                            <?php echo htmlspecialchars($appt['rejection_reason']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-3 text-sm text-gray-400 mb-8 border-t border-dark-border pt-4">
                        <p class="flex justify-between items-center">
                            <span><i class="fas fa-user-tie mr-2 w-4 text-gold"></i>Barber</span>
                            <span class="text-gray-200 font-medium"><?php echo htmlspecialchars($appt['barber_name'] ?? 'Not Assigned'); ?></span>
                        </p>
                        <p class="flex justify-between items-center">
                            <span><i class="fas fa-cut mr-2 w-4 text-gold"></i>Haircut</span>
                            <span class="text-gray-200 font-medium"><?php echo htmlspecialchars($appt['haircut_style'] ?? '-'); ?></span>
                        </p>
                        
                        <div class="pt-2">
                            <span class="block mb-2 text-xs uppercase tracking-wider text-gray-500 font-bold">Services</span>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                    $sIds = json_decode($appt['services_ids_json'] ?? '[]', true);
                                    if ($sIds) {
                                        foreach ($sIds as $sid) {
                                            if (isset($servicesMap[$sid])) {
                                                echo '<span class="px-2 py-1 bg-dark rounded text-[10px] text-gray-300 border border-dark-border">' . htmlspecialchars($servicesMap[$sid]) . '</span>';
                                            }
                                        }
                                    } else {
                                        echo '<span class="text-xs italic text-gray-600">None</span>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-end border-t border-dark-border pt-4 mb-6">
                        <span class="text-gray-500 text-xs uppercase tracking-wider font-bold">Total</span>
                        <span class="text-xl font-bold text-gold">RM <?php echo number_format($appt['total_price'], 2); ?></span>
                    </div>

                    <!-- Actions -->
                    <?php if ($appt['status'] == 'Pending' || $appt['status'] == 'Confirmed'): ?>
                        <form action="actions/cancel_my_appointment.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                            <button type="submit" class="w-full py-2 px-4 rounded-lg bg-red-900/20 hover:bg-red-900/40 text-red-400 hover:text-red-200 text-sm font-bold uppercase tracking-wide transition border border-red-900/50">
                                Cancel
                            </button>
                        </form>
                    <?php elseif ($appt['status'] == 'Cancelled'): ?>
                        <a href="?view=book" class="block w-full text-center py-2 px-4 rounded-lg border border-gold text-gold hover:bg-gold hover:text-dark text-sm font-bold uppercase tracking-wide transition">
                            Book Again
                        </a>
                    <?php elseif ($appt['status'] == 'Completed'): ?>
                        <?php if ($appt['is_rated']): ?>
                             <div class="block w-full text-center py-2 px-4 rounded-lg border border-green-500/30 text-green-400 bg-green-900/10 text-sm font-bold uppercase tracking-wide cursor-default">
                                <i class="fas fa-check-circle mr-2"></i> Rated
                            </div>
                        <?php else: ?>
                            <button onclick="openFeedbackModal(<?= $appt['appointment_id'] ?>, <?= $appt['barber_id'] ?? 'null' ?>, '<?= addslashes($appt['barber_name'] ?? 'Shop') ?>')" class="w-full py-2 px-4 rounded-lg bg-gradient-to-r from-gold to-gold-light hover:opacity-90 text-dark text-sm font-bold uppercase tracking-wide transition shadow-lg">
                                Rate Experience <i class="fas fa-star ml-1"></i>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php include __DIR__ . '/feedback_modal.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
