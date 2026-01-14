<?php
// Ensure this is included within staff_dashboard.phpcontext
if (!isset($barberId)) {
    echo "Error: Barber context missing.";
    return;
}

// Fetch Completed Appointments for this Barber
// We want ALL time completed jobs to show history log
$sqlHistory = "
    SELECT 
        a.id, 
        a.appointment_date, 
        a.appointment_time, 
        a.services_ids_json, 
        a.total_price,
        u.full_name as customer_name,
        u.phone as customer_phone
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.barber_id = ? 
    AND a.status = 'Completed'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

$stmtHist = $pdo->prepare($sqlHistory);
$stmtHist->execute([$barberId]);
$history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

// Calculate simplified total earnings (just a sum of completed jobs)
$totalEarnings = 0;
foreach($history as $job) {
    if(is_numeric($job['total_price'])) {
        $totalEarnings += $job['total_price'];
    }
}
?>

<div class="space-y-6 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-end gap-4 border-b border-white/10 pb-6">
        <div>
            <h2 class="text-3xl font-heading font-bold text-white mb-2">My Logbook</h2>
            <p class="text-gray-400">Track your completed jobs and performance.</p>
        </div>
        <div class="bg-gradient-to-r from-gold/20 to-gold/5 border border-gold/30 rounded-xl p-4 flex items-center gap-4">
            <div class="p-3 bg-gold/20 rounded-full text-gold">
                <i class="fas fa-coins text-xl"></i>
            </div>
            <div>
                <p class="text-xs uppercase font-bold text-gold tracking-wider">Total Earnings</p>
                <h3 class="text-2xl font-bold text-white">RM <?= number_format($totalEarnings, 2) ?></h3>
            </div>
        </div>
    </div>

    <!-- History List -->
    <div class="bg-dark-card border border-dark-border rounded-xl overflow-hidden shadow-xl">
        <?php if(empty($history)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-clipboard-list text-4xl mb-4 opacity-30"></i>
                <p>No completed jobs found yet. Time to get to work! ✂️</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b border-dark-border bg-white/5">
                            <th class="px-6 py-4 font-bold">Date & Time</th>
                            <th class="px-6 py-4 font-bold">Client</th>
                            <th class="px-6 py-4 font-bold">Services</th>
                            <th class="px-6 py-4 font-bold text-right">Price</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-dark-border">
                        <?php foreach($history as $job): 
                            $serviceIds = json_decode($job['services_ids_json'] ?? '[]', true);
                            $serviceNames = [];
                            if(is_array($serviceIds)) foreach($serviceIds as $sid) if(isset($servicesMap[$sid])) $serviceNames[] = $servicesMap[$sid];
                            $serviceStr = implode(', ', $serviceNames);
                        ?>
                        <tr class="hover:bg-dark-hover transition group">
                            <td class="px-6 py-4 text-white font-medium">
                                <span class="block text-gold"><?= date('d M Y', strtotime($job['appointment_date'])) ?></span>
                                <span class="text-xs text-gray-500"><?= date('h:i A', strtotime($job['appointment_time'])) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-200 font-bold"><?= htmlspecialchars($job['customer_name']) ?></span>
                                <div class="text-[10px] text-gray-500 mt-1">
                                    <i class="fas fa-phone-alt mr-1"></i> <?= $job['customer_phone'] ?? '-' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-400">
                                <?= htmlspecialchars($serviceStr ?: '-') ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="inline-block px-3 py-1 rounded bg-green-500/10 text-green-400 border border-green-500/20 font-bold">
                                    RM <?= number_format($job['total_price'], 2) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
