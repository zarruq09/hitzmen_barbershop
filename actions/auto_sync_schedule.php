<?php
// actions/auto_sync_schedule.php
// Tiada session_start() di sini sebab file ni akan di-include dalam file lain yang dah ada session.
if (session_status() === PHP_SESSION_NONE) {
    // Safety check, usually not needed if included in dashboard
}

require_once __DIR__ . '/../db.php';

try {
    $today = date('Y-m-d');
    $dayOfWeek = date('D'); // Mon, Tue, Wed...

    // 1. Dapatkan semua staff yang ada link dengan barber
    // Kita join users dengan barbers untuk pastikan active barber sahaja
    $stmt = $pdo->prepare("
        SELECT b.id as barber_id, b.user_id, b.status as current_status, u.username 
        FROM barbers b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.status != 'Deleted'
    ");
    $stmt->execute();
    $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($barbers as $barber) {
        $userId = $barber['user_id'];
        
        // 2. Check jadual hari ni
        $schedStmt = $pdo->prepare("SELECT status FROM schedules WHERE user_id = ? AND date = ?");
        $schedStmt->execute([$userId, $today]);
        $schedule = $schedStmt->fetch(PDO::FETCH_ASSOC);

        $targetStatus = 'Available'; // Default

        if ($schedule) {
            // Kalau ada jadual spesifik
            // Status dalam DB schedule: 'available' (huruf kecik biasanya) atau 'off'
            if ($schedule['status'] === 'off') {
                $targetStatus = 'Unavailable';
            } else {
                $targetStatus = 'Available';
            }
        } else {
            // Kalau TIADA jadual spesifik, guna default logic kedai
            // Contoh: Rabu tutup
            if ($dayOfWeek === 'Wed') {
                $targetStatus = 'Unavailable';
            } else {
                $targetStatus = 'Available';
            }
        }

        // 3. Update jika status berbeza
        // Kita bandingkan 'Available'/'Unavailable' dengan current status
        // Note: Barber mungkin tengah 'Busy' atau 'Break' (kalau ada feature tu),
        // tapi user mintak "kalau jadual cuti, dia change status".
        // Jadi kita enforce:
        // - Kalau patut Cuti (Unavailable), kita paksa Unavailable.
        // - Kalau patut Kerja (Available), TAPI status sekarang 'Unavailable', kita tukar jadi Available.
        // - TAPI kalau status sekarang 'Busy' (tengah gunting), jangan kacau jadi Available tiba-tiba?
        //   User request: "status tu berubah sndiri kalau ada tarikh dia cuti"
        //   Implies: Priority on OFF dates.
        
        // Safety: Only auto-switch TO Available if currently Unavailable.
        // If currently Busy, leave it (they are working).
        // BUT if today is OFF, force Unavailable regardless of anything else.

        $shouldUpdate = false;

        if ($targetStatus === 'Unavailable') {
            // Kalau hari ni CUTI, paksa tukar jadi Unavailable tak kira apa pun
            if ($barber['current_status'] !== 'Unavailable') {
                $shouldUpdate = true;
            }
        } else {
            // Kalau hari ni KERJA (Available)
            // JANGAN paksa tukar jadi Available, sebab staff mungkin nak set "Off Duty" untuk rehat.
            // Biarkan status manual kekal.
            // if ($barber['current_status'] === 'Unavailable') {
            //     $shouldUpdate = true;
            // }
        }

        if ($shouldUpdate) {
            $updateStmt = $pdo->prepare("UPDATE barbers SET status = ? WHERE id = ?");
            $updateStmt->execute([$targetStatus, $barber['barber_id']]);
            
            // Log (Optional, for debug)
            // error_log("Auto-Sync: Updated {$barber['username']} to $targetStatus");
        }
    }

} catch (PDOException $e) {
    // Silent fail supaya tak ganggu dashboard loading
    error_log("Auto-Sync Error: " . $e->getMessage());
}
?>
