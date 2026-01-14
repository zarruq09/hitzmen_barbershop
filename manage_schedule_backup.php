<?php
// 1. Output Buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'includes/calendar_service.php'; // Calendar integration

// 2. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header('Location: login.php');
    exit();
}

// 3. Handle Date Selection
if (isset($_POST['date'])) {
    $selected_date = $_POST['date'];
} elseif (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// 4. Handle Schedule Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    // If inputs are disabled (status=off), these POST values wont be set, defaults to NULL
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $date = $_POST['date'];

    $check = $pdo->prepare("SELECT id FROM schedules WHERE user_id = ? AND date = ?");
    $check->execute([$user_id, $date]);
    $exists = $check->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE schedules SET status = ?, start_time = ?, end_time = ? WHERE id = ?");
        $stmt->execute([$status, $start_time, $end_time, $exists['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO schedules (user_id, date, status, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $date, $status, $start_time, $end_time]);
    }

    $_SESSION['success'] = "Schedule updated successfully.";
    
    // REDIRECT LOGIC: Always redirect to manage_schedule.php (Standalone)
    ob_end_clean();
    header("Location: manage_schedule.php?date=" . $date);
    exit();
}

// 5. Fetch Data
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE role = 'staff' AND id != ? ORDER BY username ASC");
$stmt->execute([$_SESSION['user_id']]);
$staff_members = $stmt->fetchAll();

$schedules_stmt = $pdo->prepare("SELECT * FROM schedules WHERE date = ?");
$schedules_stmt->execute([$selected_date]);
$schedules_raw = $schedules_stmt->fetchAll();

$daily_schedules = [];
foreach ($schedules_raw as $s) {
    $daily_schedules[$s['user_id']] = $s;
}

ob_end_flush(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule | Hitzmen Barbershop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        h1, h2 { font-family: 'Playfair Display', serif; letter-spacing: 1px; }
        .text-gold { color: #e3c77b; }
        .bg-gold { background-color: #e3c77b; }
        .border-gold { border-color: #e3c77b; }
        .bg-dark-blue { background-color: #232946; }
        .text-dark-blue { color: #232946; }
        input[type="date"] { border: 1px solid #e2e8f0; padding: 0.5rem; border-radius: 0.375rem; }
        .schedule-row { transition: background-color 0.3s ease; }
        input:disabled { background-color: #e5e7eb; color: #9ca3af; cursor: not-allowed; border-color: #d1d5db; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 font-roboto">

<div class="max-w-6xl mx-auto p-6">
    
    <!-- Header -->
    <div class="mb-8 border-b-2 border-gold pb-4 flex flex-col md:flex-row justify-between items-start md:items-center">
<?php
// 1. Output Buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 2. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header('Location: login.php');
    exit();
}

// 3. Handle Date Selection
if (isset($_POST['date'])) {
    $selected_date = $_POST['date'];
} elseif (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// 4. Handle Schedule Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    // If inputs are disabled (status=off), these POST values wont be set, defaults to NULL
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $date = $_POST['date'];

    $check = $pdo->prepare("SELECT id FROM schedules WHERE user_id = ? AND date = ?");
    $check->execute([$user_id, $date]);
    $exists = $check->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE schedules SET status = ?, start_time = ?, end_time = ? WHERE id = ?");
        $stmt->execute([$status, $start_time, $end_time, $exists['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO schedules (user_id, date, status, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $date, $status, $start_time, $end_time]);
    }

    $_SESSION['success'] = "Schedule updated successfully.";
    
    // REDIRECT LOGIC: Always redirect to manage_schedule.php (Standalone)
    ob_end_clean();
    header("Location: manage_schedule.php?date=" . $date);
    exit();
}

// 5. Fetch Data
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE role = 'staff' AND id != ? ORDER BY username ASC");
$stmt->execute([$_SESSION['user_id']]);
$staff_members = $stmt->fetchAll();

$schedules_stmt = $pdo->prepare("SELECT * FROM schedules WHERE date = ?");
$schedules_stmt->execute([$selected_date]);
$schedules_raw = $schedules_stmt->fetchAll();

$daily_schedules = [];
foreach ($schedules_raw as $s) {
    $daily_schedules[$s['user_id']] = $s;
}

ob_end_flush(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule | Hitzmen Barbershop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        h1, h2 { font-family: 'Playfair Display', serif; letter-spacing: 1px; }
        .text-gold { color: #e3c77b; }
        .bg-gold { background-color: #e3c77b; }
        .border-gold { border-color: #e3c77b; }
        .bg-dark-blue { background-color: #232946; }
        .text-dark-blue { color: #232946; }
        input[type="date"] { border: 1px solid #e2e8f0; padding: 0.5rem; border-radius: 0.375rem; }
        .schedule-row { transition: background-color 0.3s ease; }
        input:disabled { background-color: #e5e7eb; color: #9ca3af; cursor: not-allowed; border-color: #d1d5db; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 font-roboto">

<div class="max-w-6xl mx-auto p-6">
    
    <!-- Header -->
    <div class="mb-8 border-b-2 border-gold pb-4 flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h1 class="text-4xl font-bold mb-2" style="color: #f7e06e; text-shadow: 0 2px 8px #e3c77b88;">Barbers Schedule</h1>
            <p class="text-gray-500">Manage daily availability and shifts for staff.</p>
        </div>
        
        <!-- Date Selector Form -->
        <form action="manage_schedule.php" method="GET" class="mt-4 md:mt-0 flex items-center bg-white p-2 rounded shadow" onsubmit="return false;">
            <label class="mr-3 font-semibold text-gray-700">Select Date:</label>
            <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="updateScheduleDate(this.value)" class="outline-none focus:border-yellow-400 cursor-pointer">
        </form>
    </div>

    <!-- Success Message -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div id="success-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex justify-between">
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            <button onclick="this.parentElement.remove()" class="text-green-700 font-bold">×</button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Schedule Grid -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-12 bg-dark-blue text-white font-bold p-4 hidden md:grid">
            <div class="md:col-span-3">Staff Member</div>
            <div class="md:col-span-3">Status</div>
            <div class="md:col-span-4">Shift Time</div>
            <div class="md:col-span-2 text-center">Action</div>
        </div>

        <div class="divide-y divide-gray-200">
            <?php if (count($staff_members) > 0): ?>
                <?php foreach ($staff_members as $staff): 
                    $uid = $staff['id'];
                    $has_schedule = isset($daily_schedules[$uid]);
                    $current_status = $has_schedule ? $daily_schedules[$uid]['status'] : 'off';
                    $start = $has_schedule ? $daily_schedules[$uid]['start_time'] : '';
                    $end = $has_schedule ? $daily_schedules[$uid]['end_time'] : '';
                    
                    // Logic to determine initial state
                    $row_bg = '';
                    $is_disabled = false;
                    
                    if ($current_status === 'available') {
                        $row_bg = 'bg-green-50 border-l-4 border-green-500';
                    } elseif ($current_status === 'rest') {
                        $row_bg = 'bg-yellow-50 border-l-4 border-yellow-500';
                    } else { // off
                        $row_bg = 'bg-gray-50 border-l-4 border-gray-300';
                        $is_disabled = true;
                    }
                ?>
                
                <!-- Individual Update Form -->
                <form method="POST" action="manage_schedule.php" id="form-<?= $uid ?>" class="schedule-row p-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center <?= $row_bg ?>" onsubmit="return handleScheduleUpdate(event, this)">
                    <input type="hidden" name="update_schedule" value="1">
                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                    <input type="hidden" name="date" value="<?= $selected_date ?>">

                    <!-- Staff Name -->
                    <div class="md:col-span-3 flex items-center">
                        <div class="h-10 w-10 rounded-full bg-gold text-dark-blue flex items-center justify-center font-bold mr-3 shadow-sm">
                            <?= strtoupper(substr($staff['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="font-bold text-lg text-dark-blue"><?= htmlspecialchars($staff['username']) ?></div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold"><?= htmlspecialchars($staff['role']) ?></div>
                        </div>
                    </div>

                    <!-- Status Select -->
                    <div class="md:col-span-3">
                        <label class="block md:hidden text-xs font-bold text-gray-500 mb-1">Status</label>
                        <select name="status" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-yellow-400 font-medium bg-white cursor-pointer" 
                                onchange="handleStatusChange(this, <?= $uid ?>)">
                            <option value="available" <?= $current_status === 'available' ? 'selected' : '' ?>>✅ Available</option>
                            <option value="rest" <?= $current_status === 'rest' ? 'selected' : '' ?>>☕ Rest / Break</option>
                            <option value="off" <?= $current_status === 'off' ? 'selected' : '' ?>>⛔ Off / Unavailable</option>
                        </select>
                    </div>

                    <!-- Time Inputs -->
                    <div class="md:col-span-4 flex items-center space-x-2 transition-opacity duration-300" id="time-inputs-<?= $uid ?>" 
                         style="<?= $is_disabled ? 'opacity: 0.6;' : 'opacity: 1;' ?>">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1 font-semibold">Start</label>
                            <input type="time" name="start_time" value="<?= $start ?>" 
                                   <?= $is_disabled ? 'disabled' : '' ?>
                                   class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-yellow-400">
                        </div>
                        <span class="pt-5 text-gray-400 font-bold">→</span>
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1 font-semibold">End</label>
                            <input type="time" name="end_time" value="<?= $end ?>" 
                                   <?= $is_disabled ? 'disabled' : '' ?>
                                   class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-yellow-400">
                        </div>
                    </div>

                    <!-- Update Button -->
                    <div class="md:col-span-2 text-center mt-2 md:mt-0">
                        <button type="submit" id="btn-<?= $uid ?>" class="bg-dark-blue hover:bg-gray-800 text-white font-bold py-2 px-6 rounded shadow-md transform active:scale-95 transition-all w-full md:w-auto">
                            Update
                        </button>
                    </div>
                </form>
                <?php endforeach; ?>
            
            <?php else: ?>
                <div class="p-8 text-center text-gray-500">
                    <p class="text-xl">No staff members found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function handleStatusChange(selectElem, uid) {
        const timeContainer = document.getElementById('time-inputs-' + uid);
        const formRow = document.getElementById('form-' + uid);
        const inputs = timeContainer.getElementsByTagName('input');
        
        // 1. Highlight row to show it was modified
        formRow.style.backgroundColor = "#fffbeb"; 
        formRow.style.borderColor = "#f59e0b";
        
        // 2. Enable/Disable Inputs immediately using the DOM 'disabled' property
        if (selectElem.value !== 'off') {
            timeContainer.style.opacity = '1';
            inputs[0].disabled = false;
            inputs[1].disabled = false;
            
            // 3. Auto-fill Defaults (2 PM - 12 AM) only if switching to available and empty
            if(selectElem.value === 'available') {
                if(!inputs[0].value) inputs[0].value = '14:00';
                if(!inputs[1].value) inputs[1].value = '00:00';
            }
        } else {
            timeContainer.style.opacity = '0.6';
            inputs[0].disabled = true;
            inputs[1].disabled = true;
        }
    }

    // Preserve scroll position
    document.addEventListener("DOMContentLoaded", function(event) { 
        var scrollpos = sessionStorage.getItem('scrollpos');
        if (scrollpos) window.scrollTo(0, scrollpos);
        
        const alert = document.getElementById('success-alert');
        if(alert) setTimeout(() => { alert.style.display = 'none'; }, 3000);
    });

    window.onbeforeunload = function(e) {
        sessionStorage.setItem('scrollpos', window.scrollY);
    };

    // --- AJAX FUNCTIONS ---
    function updateScheduleDate(date) {
        fetch(`manage_schedule.php?date=${date}`)
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newContent = tempDiv.querySelector('.max-w-6xl'); // Extract main content
                if (newContent) {
                    document.querySelector('.max-w-6xl').innerHTML = newContent.innerHTML;
                    // Re-initialize any scripts if necessary (though simple DOM replacement usually works for this structure)
                }
            })
            .catch(err => console.error('Error updating date:', err));
    }

    function handleScheduleUpdate(event, form) {
        event.preventDefault();
        const formData = new FormData(form);

        fetch('manage_schedule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
             const tempDiv = document.createElement('div');
             tempDiv.innerHTML = html;
             const newContent = tempDiv.querySelector('.max-w-6xl');
             if (newContent) {
                 document.querySelector('.max-w-6xl').innerHTML = newContent.innerHTML;
                 // Re-show success alert logic if needed, but the PHP session flash message is in the HTML
                 const alert = document.getElementById('success-alert');
                 if(alert) setTimeout(() => { alert.style.display = 'none'; }, 3000);
             }
        })
        .catch(err => console.error('Error updating schedule:', err));
        return false;
    }

</script>

</body>
</html>
