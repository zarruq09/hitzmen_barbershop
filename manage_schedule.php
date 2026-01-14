<?php
// 1. Output Buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'includes/calendar_service.php'; // Keep for backend sync if needed later

// 2. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header('Location: login.php');
    exit();
}

// 3. Handle Week Navigation
// Default to current week's Monday
$today = new DateTime();
// If today is Sunday, 'monday this week' might go to next week in some locales/versions, 
// strictly speaking ISO weeks start Monday.
// Let's rely on 'monday this week' if today is not monday, else today.
$current_week_start = (isset($_GET['week_start'])) ? $_GET['week_start'] : date('Y-m-d', strtotime('monday this week'));

// Calculate week range
$start_date_obj = new DateTime($current_week_start);
$end_date_obj = clone $start_date_obj;
$end_date_obj->modify('+6 days');
$end_date = $end_date_obj->format('Y-m-d');

// 4. Handle Schedule Update (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
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

    // Sync Global Status if updating today
    if ($date === date('Y-m-d')) {
        $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $username = $userStmt->fetchColumn();

        if ($username) {
            $globalStatus = ($status === 'available') ? 'Available' : 'Unavailable';
            
            // 1. Try matching by Linked User ID
            $syncStmt = $pdo->prepare("UPDATE barbers SET status = ? WHERE user_id = ? AND status != 'Deleted'");
            $syncStmt->execute([$globalStatus, $user_id]);
            
            // 2. Fallback: If no rows updated (not linked?), try matching by Name
            if ($syncStmt->rowCount() === 0) {
                // Ensure case-insensitive match or exact match depending on DB collation, usually exact for usernames
                $fallbackStmt = $pdo->prepare("UPDATE barbers SET status = ? WHERE name = ? AND status != 'Deleted'");
                $fallbackStmt->execute([$globalStatus, $username]);
            }
            
            // Debugging (Temporary)
            // file_put_contents('debug_sync.txt', date('Y-m-d H:i:s') . " - Sync attempt for UID $user_id / Name $username. ID-Row: " . $syncStmt->rowCount() . " Name-Row: " . ($fallbackStmt->rowCount() ?? 'N/A') . "\n", FILE_APPEND);
        }
    }

    // Return simple JSON or text success
    echo "success";
    exit();
}

// 5. Fetch Data
// Get Staff
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE role = 'staff' AND id != ? ORDER BY username ASC");
$stmt->execute([$_SESSION['user_id']]);
$staff_members = $stmt->fetchAll();

// Get Schedules for the week
$sched_stmt = $pdo->prepare("SELECT * FROM schedules WHERE date BETWEEN ? AND ?");
$sched_stmt->execute([$current_week_start, $end_date]);
$schedules_raw = $sched_stmt->fetchAll();

// Organize Schedules: $roster[user_id][date] = schedule_data
$roster = [];
foreach ($schedules_raw as $s) {
    $roster[$s['user_id']][$s['date']] = $s;
}

// Helper dates array for table header
$period = new DatePeriod(
     new DateTime($current_week_start),
     new DateInterval('P1D'),
     (new DateTime($end_date))->modify('+1 day')
);

ob_end_flush(); 
?>
<div class="p-6">
    
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h1 class="text-3xl font-heading font-bold bg-clip-text text-transparent bg-gradient-to-r from-gold to-white mb-2">Weekly Roster</h1>
            <p class="text-gray-400 text-sm">Manage staff shifts for the week.</p>
        </div>
        
        <!-- Navigation -->
        <div class="flex items-center gap-4 mt-4 md:mt-0 bg-dark-card p-2 rounded-lg border border-dark-border">
            <a href="javascript:void(0)" 
               onclick="fetchAndLoadTab('manage_schedule', '?week_start=<?= date('Y-m-d', strtotime($current_week_start . ' -7 days')) ?>')"
               class="text-gold hover:text-white transition-colors p-2" title="Previous Week">
                <i class="fas fa-chevron-left"></i>
            </a>
            
            <div class="text-center min-w-[200px]">
                <span class="block text-white font-bold text-sm"><?= date('M d', strtotime($current_week_start)) ?> - <?= date('M d', strtotime($end_date)) ?></span>
                <span class="block text-xs text-gray-500 uppercase tracking-wider"><?= date('Y', strtotime($current_week_start)) ?></span>
            </div>

            <a href="javascript:void(0)" 
               onclick="fetchAndLoadTab('manage_schedule', '?week_start=<?= date('Y-m-d', strtotime($current_week_start . ' +7 days')) ?>')"
               class="text-gold hover:text-white transition-colors p-2" title="Next Week">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Success Toast (Hidden by default) -->
    <div id="toast" class="fixed bottom-5 right-5 bg-green-900 border border-green-500 text-white px-6 py-3 rounded-lg shadow-xl translate-y-20 opacity-0 transition-all duration-300 z-50 flex items-center gap-2">
        <i class="fas fa-check-circle"></i> <span id="toast-msg">Saved</span>
    </div>

    <!-- Roster Table Container -->
    <div class="bg-dark-card rounded-xl shadow-lg border border-dark-border overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="bg-[#181818] text-xs uppercase border-b border-dark-border">
                    <th class="px-6 py-4 font-bold text-gold sticky left-0 z-10 bg-[#181818] border-r border-dark-border min-w-[150px]">
                        Staff Member
                    </th>
                    <?php foreach ($period as $dt): 
                        $is_today = ($dt->format('Y-m-d') === date('Y-m-d'));
                        $is_wednesday = ($dt->format('D') === 'Wed');
                        $header_class = $is_today ? 'text-gold bg-gold/10' : ($is_wednesday ? 'text-red-500' : 'text-gray-400');
                    ?>
                    <th class="px-4 py-3 min-w-[120px] text-center border-r border-[#333] last:border-0 <?= $header_class ?>">
                        <div class="font-bold text-lg leading-none"><?= $dt->format('d') ?></div>
                        <div class="font-normal opacity-70"><?= $dt->format('D') ?></div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-dark-border">
                <?php foreach ($staff_members as $staff): ?>
                <tr class="hover:bg-dark-hover/50 transition-colors">
                    <td class="px-6 py-4 font-medium text-white sticky left-0 z-10 bg-dark-card border-r border-dark-border group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-[#2a2a2a] flex items-center justify-center text-xs text-gold border border-[#444]">
                                <?= strtoupper(substr($staff['username'], 0, 1)) ?>
                            </div>
                            <span class="group-hover:text-gold transition-colors"><?= htmlspecialchars($staff['username']) ?></span>
                        </div>
                    </td>
                    
                    <?php foreach ($period as $dt): 
                         $d_str = $dt->format('Y-m-d');
                         $day_of_week = $dt->format('D');
                         $data = isset($roster[$staff['id']][$d_str]) ? $roster[$staff['id']][$d_str] : null;
                         
                         // Default Logic: 
                         // If data exists, use it.
                         // If NO data: Wednesday = 'off', Others = 'available' (Work)
                         if ($data) {
                             $status = $data['status'];
                         } else {
                             $status = ($day_of_week === 'Wed') ? 'off' : 'available';
                         }
                         
                         // Visuals
                         $cell_bg = '';
                         $content = '<span class="text-gray-600 font-bold text-xs tracking-wider">OFF</span>';
                         
                         // Time formatting for inputs
                         $raw_start = $data && !empty($data['start_time']) ? date('H:i', strtotime($data['start_time'])) : '';
                         $raw_end = $data && !empty($data['end_time']) ? date('H:i', strtotime($data['end_time'])) : '';

                         if ($status === 'available') {
                             $content = '<span class="text-green-400 font-bold text-xs"><i class="fas fa-check mr-1"></i>WORK</span>';
                             $cell_bg = 'bg-green-900/10 hover:bg-green-900/20';
                         } elseif ($status === 'rest') {
                             $content = '<span class="text-yellow-500 font-bold text-xs"><i class="fas fa-mug-hot mr-1"></i>REST</span>';
                             $cell_bg = 'bg-yellow-900/10 hover:bg-yellow-900/20';
                         } else {
                             // OFF styling
                             if ($dt->format('D') === 'Wed') {
                                 $content = '<span class="text-red-500 font-bold text-xs"><i class="fas fa-store-slash mr-1"></i>CLOSED</span>';
                                 $cell_bg = 'bg-red-900/10 hover:bg-red-900/20';
                             } else {
                                 $cell_bg = 'hover:bg-[#252525]';
                             }
                         }
                    ?>
                    <td class="p-0 border-r border-[#333] last:border-0 relative h-16 cursor-pointer transition-colors <?= $cell_bg ?>"
                        data-userid="<?= $staff['id'] ?>"
                        data-date="<?= $d_str ?>"
                        data-status="<?= $status ?>"
                        data-start="<?= $raw_start ?>"
                        data-end="<?= $raw_end ?>"
                        data-username="<?= htmlspecialchars($staff['username'], ENT_QUOTES) ?>"
                        onclick="openScheduleModalSafe(this)">
                        <div class="w-full h-full flex items-center justify-center text-center p-2">
                            <?= $content ?>
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div id="shiftEditModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-[#181818] border border-gold/30 rounded-xl p-6 w-full max-w-sm transform scale-95 transition-transform duration-300 shadow-2xl relative">
            
            <button onclick="closeShiftModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>

            <h3 class="text-xl font-bold text-white mb-1">Edit Shift</h3>
            <p id="modal-subtitle" class="text-sm text-gold mb-4"></p>

            <form id="scheduleForm" onsubmit="saveSchedule(event)">
                <input type="hidden" name="update_schedule" value="1">
                <!-- User ID selected via dropdown -->
                <input type="hidden" id="modal_date" name="date">
                <!-- Default Auto-Time -->
                <input type="hidden" name="start_time" value="11:00">
                <input type="hidden" name="end_time" value="23:00">

                <div class="space-y-4">
                    <!-- Barber Selection (Hidden) -->
                    <input type="hidden" name="user_id" id="modal_user_id">

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Status</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="available" class="peer sr-only">
                                <div class="text-center py-2 rounded border border-[#333] bg-[#000] peer-checked:bg-green-900/30 peer-checked:border-green-500 peer-checked:text-green-400 transition-all text-gray-400 text-xs font-bold">
                                    <i class="fas fa-check block mb-1"></i> WORK
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="off" class="peer sr-only">
                                <div class="text-center py-2 rounded border border-[#333] bg-[#000] peer-checked:bg-red-900/30 peer-checked:border-red-500 peer-checked:text-red-400 transition-all text-gray-400 text-xs font-bold">
                                    <i class="fas fa-ban block mb-1"></i> OFF
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">* Work status automatically sets time to shop hours (Standard: 11am-11pm, Fri: 3pm-11pm)</p>
                    </div>

                    <button type="submit" class="w-full bg-gold hover:bg-white text-black font-bold py-3 rounded-lg shadow-lg hover:shadow-gold/50 transition-all mt-4">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>



<script>
    // Define functions globally but safe for re-execution
    window.openScheduleModalSafe = function(element) {
        console.log('Opening Shift Modal', element);
        // alert('Opening Modal'); 
        
        const shiftModal = document.getElementById('shiftEditModal');
        const shiftModalContent = shiftModal.querySelector('div');
        
        // Read from data attributes
        const dataset = element.dataset;
        const userId = dataset.userid;
        const date = dataset.date;
        const status = dataset.status;
        const start = dataset.start;
        const end = dataset.end;
        const userName = dataset.username;

        // Set values
        document.getElementById('modal_user_id').value = userId;
        document.getElementById('modal_date').value = date;
        
        // Form subtitle
        const dateObj = new Date(date);
        const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'short' });
        document.getElementById('modal-subtitle').textContent = `${userName} • ${dateStr}`;

        // Set Radio Status
        const radios = document.getElementsByName('status');
        for(let r of radios) {
            if(r.value === status) r.checked = true;
        }

        // Set Default Times based on Day
        const dayOfWeek = dateObj.getDay(); // 0 = Sun, 1 = Mon, ..., 5 = Fri
        const startTimeInput = document.querySelector('input[name="start_time"]');
        const endTimeInput = document.querySelector('input[name="end_time"]');
        
        if (startTimeInput && endTimeInput) {
            // Friday (5): 15:00 - 23:00
            if (dayOfWeek === 5) {
                startTimeInput.value = "15:00"; 
                endTimeInput.value = "23:00";
            } 
            // Others: 11:00 - 23:00
            else {
                startTimeInput.value = "11:00";
                endTimeInput.value = "23:00";
            }
        }

        // Show Modal
        if(shiftModal) {
            shiftModal.classList.remove('hidden');
            // Small timeout for transition
            setTimeout(() => {
                shiftModal.classList.remove('opacity-0');
                if(shiftModalContent) {
                    shiftModalContent.classList.remove('scale-95');
                    shiftModalContent.classList.add('scale-100');
                }
            }, 10);
        }
    };

    window.closeShiftModal = function() {
        const shiftModal = document.getElementById('shiftEditModal');
        const shiftModalContent = shiftModal.querySelector('div');

        if(shiftModal) {
            shiftModal.classList.add('opacity-0');
            if(shiftModalContent) {
                shiftModalContent.classList.remove('scale-100');
                shiftModalContent.classList.add('scale-95');
            }
            setTimeout(() => {
                shiftModal.classList.add('hidden');
            }, 300);
        }
    };

    window.saveSchedule = function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const dateVal = document.getElementById('modal_date').value;

        fetch('manage_schedule.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === 'success') {
                showToast('Schedule Update Saved');
                window.closeShiftModal();
                setTimeout(() => {
                    if(dateVal && typeof fetchAndLoadTab === 'function') {
                        const d = new Date(dateVal);
                        const day = d.getDay();
                        const diff = d.getDate() - day + (day == 0 ? -6 : 1);
                        const monday = new Date(d.setDate(diff));
                        const weekStart = monday.toISOString().split('T')[0];
                        
                        fetchAndLoadTab('manage_schedule', '?week_start=' + weekStart);
                    } else {
                        window.location.reload();
                    }
                }, 800);
            } else {
                console.error('Save Error:', response);
                alert('Gagal simpan jadual. Error:\n' + response);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Gagal connect ke server.');
        });
    };
    
    function showToast(msg) {
        const t = document.getElementById('toast');
        const msgEl = document.getElementById('toast-msg');
        if(t && msgEl) {
            msgEl.textContent = msg;
            t.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                t.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }
    }

    // Close modal on click outside
    // We need to attach this safely to avoid multiple listeners if possible, 
    // or just rely on the onclick defined in global scope if any.
    // In this file, we don't have a distinct onclick for the backend overlay.
    // Let's add it via JS but check if it exists? 
    // Actually, simply re-adding it is fine as the element is destroyed and recreated on AJAX load.
    
    setTimeout(() => {
        const shiftModal = document.getElementById('shiftEditModal');
        if(shiftModal) {
            shiftModal.onclick = function(e) {
                 if(e.target === shiftModal) window.closeShiftModal();
            }
        }
    }, 100);

</script>
</div>
</html>
