<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/db.php';

// Ensure user is logged in
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Fetch services map
$servicesMap = [];
try {
    $stmtServices = $pdo->query("SELECT id, service_name FROM services");
    while ($row = $stmtServices->fetch(PDO::FETCH_ASSOC)) {
        $servicesMap[$row['id']] = $row['service_name'];
    }
} catch (PDOException $e) {
    // Silent fail or log
}

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
        b.name AS barber_name,
        h.style_name AS haircut_style
    FROM appointments a
    LEFT JOIN barbers b ON a.barber_id = b.id
    LEFT JOIN haircuts h ON a.haircut_id = h.id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching your appointments. Please try again later.";
    $appointments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | Hitzmen Barbershop</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        dark: '#121212',
                        card: '#1E1E1E',
                        cardBorder: '#333333'
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#121212] flex flex-col min-h-screen text-gray-100 font-sans">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-12 flex-1">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold font-heading text-white mb-4">My Appointments</h1>
            <p class="text-gray-400 max-w-2xl mx-auto text-lg italic font-serif">
                Track your grooming schedule.
            </p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-900/50 border border-green-600 text-green-200 px-4 py-3 rounded relative mb-8 text-center max-w-2xl mx-auto">
                <?php if ($_GET['success'] == 'cancelled') echo 'Appointment cancelled successfully.'; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-900/50 border border-red-600 text-red-200 px-4 py-3 rounded relative mb-8 text-center max-w-2xl mx-auto">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="max-w-6xl mx-auto">
            <?php if (empty($appointments)): ?>
                <div class="bg-[#1E1E1E] border border-[#333] rounded-lg p-12 text-center shadow-lg">
                    <div class="text-[#C5A059] text-5xl mb-4"><i class="far fa-calendar-times"></i></div>
                    <p class="text-xl text-gray-400 mb-8">You have no appointment history.</p>
                    <a href="book_appointment.php" class="btn btn-primary px-8 py-3 rounded-lg font-bold tracking-wide">
                        Book Your First Appointment
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($appointments as $appt): ?>
                        <div class="bg-[#1E1E1E] border border-[#333] rounded-lg p-6 relative overflow-hidden group hover:border-[#C5A059] transition-all duration-300 shadow-md">
                            
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
                                <h3 class="font-serif text-2xl text-[#C5A059] mb-1 italic">
                                    <?php echo date('F d, Y', strtotime($appt['appointment_date'])); ?>
                                </h3>
                                <p class="text-xl text-white font-bold font-heading">
                                    @ <?php echo date('g:i A', strtotime($appt['appointment_time'])); ?>
                                </p>
                            </div>

                            <?php if ($appt['status'] == 'Cancelled' && !empty($appt['rejection_reason'])): ?>
                                <div class="mb-6 p-3 bg-red-900/20 border border-red-800 rounded text-sm text-red-300">
                                    <span class="font-bold block text-red-200 mb-1"><i class="fas fa-exclamation-circle mr-1"></i> Cancellation Reason:</span> 
                                    <?php echo htmlspecialchars($appt['rejection_reason']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="space-y-3 text-sm text-gray-400 mb-8 border-t border-[#333] pt-4">
                                <p class="flex justify-between items-center">
                                    <span><i class="fas fa-user-tie mr-2 w-4"></i>Barber:</span>
                                    <span class="text-gray-200 font-medium"><?php echo htmlspecialchars($appt['barber_name'] ?? 'Not Assigned'); ?></span>
                                </p>
                                <p class="flex justify-between items-center">
                                    <span><i class="fas fa-cut mr-2 w-4"></i>Haircut:</span>
                                    <span class="text-gray-200 font-medium"><?php echo htmlspecialchars($appt['haircut_style'] ?? '-'); ?></span>
                                </p>
                                
                                <div class="pt-2">
                                    <span class="block mb-2 text-xs uppercase tracking-wider text-[#C5A059]">Services Included:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <?php 
                                            $sIds = json_decode($appt['services_ids_json'] ?? '[]', true);
                                            if ($sIds) {
                                                foreach ($sIds as $sid) {
                                                    if (isset($servicesMap[$sid])) {
                                                        echo '<span class="px-2 py-1 bg-[#252525] rounded text-xs text-gray-300 border border-[#333]">' . htmlspecialchars($servicesMap[$sid]) . '</span>';
                                                    }
                                                }
                                            } else {
                                                echo '<span class="text-xs italic text-gray-600">None</span>';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-end border-t border-[#333] pt-4 mb-6">
                                <span class="text-gray-500 text-xs uppercase tracking-wider">Total Price</span>
                                <span class="text-xl font-bold text-white">RM <?php echo number_format($appt['total_price'], 2); ?></span>
                            </div>

                            <!-- Actions -->
                            <?php if ($appt['status'] == 'Pending' || $appt['status'] == 'Confirmed'): ?>
                                <form action="actions/cancel_my_appointment.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <button type="submit" class="w-full py-3 px-4 rounded bg-red-900/80 hover:bg-red-800 text-red-100 text-sm font-bold uppercase tracking-wide transition border border-red-800">
                                        Cancel Appointment
                                    </button>
                                </form>
                            <?php elseif ($appt['status'] == 'Cancelled'): ?>
                                <a href="book_appointment.php" class="block w-full text-center py-3 px-4 rounded border border-[#C5A059] text-[#C5A059] hover:bg-[#C5A059] hover:text-[#121212] text-sm font-bold uppercase tracking-wide transition">
                                    Book New Appointment
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
