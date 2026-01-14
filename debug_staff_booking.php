<?php
// debug_staff_booking_v2.php
require_once __DIR__ . '/db.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#1e1e1e; color:#fff;'>";
echo "<h2>🕵️‍♂️ Barber Data Detective</h2>";

// 1. Find all barbers named 'Haikal Akma' (or duplicates in general)
$sql = "SELECT b.id, b.name, b.user_id, count(a.id) as appointment_count 
        FROM barbers b 
        LEFT JOIN appointments a ON b.id = a.barber_id 
        GROUP BY b.id";
$barbers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%; border-color:#333;'>";
echo "<tr style='background:#C5A059; color:#000;'><th>Barber ID</th><th>Name</th><th>Linked User ID</th><th>Total Appointments</th><th>Action Recommendation</th></tr>";

foreach ($barbers as $b) {
    echo "<tr>";
    echo "<td>" . $b['id'] . "</td>";
    echo "<td>" . ($b['name'] ? htmlspecialchars($b['name']) : '<em>(Empty Name)</em>') . "</td>";
    echo "<td>" . ($b['user_id'] ? $b['user_id'] : '<em>(Not Linked)</em>') . "</td>";
    echo "<td style='text-align:center; font-weight:bold; font-size:1.2em;'>" . $b['appointment_count'] . "</td>";
    
    $status = "";
    if ($b['appointment_count'] > 0) {
        $status = "<span style='color:#4ade80'>✅ KEEP THIS (Has Booking Data)</span>";
    } else {
        $status = "<span style='color:#f87171'>🗑️ SAFE TO DELETE (No Data)</span>";
    }
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><hr><br>";
echo "<h3>Diagnosis:</h3>";
echo "<ul>";
echo "<li>Isi sistem kau sekarang pening sebab ada banyak barber yang sama nama atau kosong.</li>";
echo "<li>Masa booking dibuat, dia mungkin masuk ke Barber ID lain, tapi masa kau login, kau 'load' Barber ID yang kosong/salah.</li>";
echo "<li><strong>FIX:</strong> Delete barber yang tak ada appointment, dan pastikan cuma SATU je barber 'Haikal Akma' yang tinggal (yang ada appointment tu).</li>";
echo "</ul>";

echo "</body></html>";
?>
