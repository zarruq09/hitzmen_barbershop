<?php
/**
 * Calendar Sync AJAX Handler
 * Handles manual sync and bulk sync operations
 */

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/calendar_service.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only admins can trigger syncs
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'single_sync':
        syncSingleSchedule();
        break;
    
    case 'bulk_sync':
        bulkSyncSchedules();
        break;
    
    case 'enable_calendar':
        enableUserCalendar();
        break;
    
    case 'disable_calendar':
        disableUserCalendar();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Sync a single schedule to Google Calendar
 */
function syncSingleSchedule() {
    global $pdo;
    
    $scheduleId = $_POST['schedule_id'] ?? null;
    
    if (!$scheduleId) {
        echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
        return;
    }
    
    $result = syncScheduleToCalendar($scheduleId);
    echo json_encode($result);
}

/**
 * Sync all schedules for a specific date
 */
function bulkSyncSchedules() {
    global $pdo;
    
    $date = $_POST['date'] ?? date('Y-m-d');
    
    // Get all schedules for this date
    $stmt = $pdo->prepare("SELECT id FROM schedules WHERE date = ?");
    $stmt->execute([$date]);
    $schedules = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [
        'success' => true,
        'total' => count($schedules),
        'synced' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    foreach ($schedules as $scheduleId) {
        $result = syncScheduleToCalendar($scheduleId);
        
        if ($result['success']) {
            $results['synced']++;
        } else {
            if (strpos($result['message'], 'not enabled') !== false) {
                $results['skipped']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Schedule #$scheduleId: " . $result['message'];
            }
        }
    }
    
    $results['message'] = "Synced {$results['synced']} schedules, {$results['failed']} failed, {$results['skipped']} skipped";
    
    echo json_encode($results);
}

/**
 * Enable calendar sync for a user
 */
function enableUserCalendar() {
    global $pdo;
    
    $userId = $_POST['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET calendar_sync_enabled = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Calendar sync enabled']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Disable calendar sync for a user
 */
function disableUserCalendar() {
    global $pdo;
    
    $userId = $_POST['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET calendar_sync_enabled = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Calendar sync disabled']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
