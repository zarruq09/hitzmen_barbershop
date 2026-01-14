<?php
/**
 * Google Calendar Service Helper
 * Handles all Google Calendar API operations for schedule synchronization
 */

require_once __DIR__ . '/../config/google_config.php';
require_once __DIR__ . '/../db.php';

/**
 * Get authenticated Google Calendar service for a user
 * @param int $userId User ID from database
 * @return Google_Service_Calendar|null Calendar service or null if not authenticated
 */
function getUserCalendarService($userId) {
    global $pdo, $googleClient;
    
    // Fetch user's tokens from database
    $stmt = $pdo->prepare("SELECT google_access_token, google_refresh_token, token_expires_at, calendar_sync_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['calendar_sync_enabled'] || !$user['google_access_token']) {
        return null;
    }
    
    try {
        // Set access token
        $googleClient->setAccessToken($user['google_access_token']);
        
        // Check if token is expired and refresh if needed
        if ($googleClient->isAccessTokenExpired() && $user['google_refresh_token']) {
            $newToken = $googleClient->fetchAccessTokenWithRefreshToken($user['google_refresh_token']);
            
            if (!isset($newToken['error'])) {
                // Update token in database
                $expiresAt = date('Y-m-d H:i:s', time() + ($newToken['expires_in'] ?? 3600));
                $stmt = $pdo->prepare("UPDATE users SET google_access_token = ?, token_expires_at = ? WHERE id = ?");
                $stmt->execute([json_encode($newToken), $expiresAt, $userId]);
                
                $googleClient->setAccessToken($newToken);
            } else {
                error_log("Token refresh failed for user $userId: " . print_r($newToken, true));
                return null;
            }
        }
        
        return new Google_Service_Calendar($googleClient);
    } catch (Exception $e) {
        error_log("Error getting calendar service for user $userId: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a calendar event for a schedule
 * @param int $userId Staff user ID
 * @param array $scheduleData Schedule data (date, status, start_time, end_time)
 * @return string|null Google Event ID or null on failure
 */
function createScheduleEvent($userId, $scheduleData) {
    $calendarService = getUserCalendarService($userId);
    
    if (!$calendarService) {
        return null;
    }
    
    try {
        $event = buildCalendarEvent($scheduleData);
        
        // Get user's calendar ID (use 'primary' by default)
        global $pdo;
        $stmt = $pdo->prepare("SELECT google_calendar_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $calendarId = $user['google_calendar_id'] ?? 'primary';
        
        $createdEvent = $calendarService->events->insert($calendarId, $event);
        
        return $createdEvent->getId();
    } catch (Exception $e) {
        error_log("Error creating calendar event for user $userId: " . $e->getMessage());
        return null;
    }
}

/**
 * Update an existing calendar event
 * @param int $userId Staff user ID
 * @param string $eventId Google Event ID
 * @param array $scheduleData Updated schedule data
 * @return bool Success status
 */
function updateScheduleEvent($userId, $eventId, $scheduleData) {
    $calendarService = getUserCalendarService($userId);
    
    if (!$calendarService || !$eventId) {
        return false;
    }
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT google_calendar_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $calendarId = $user['google_calendar_id'] ?? 'primary';
        
        $event = buildCalendarEvent($scheduleData);
        
        $calendarService->events->update($calendarId, $eventId, $event);
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating calendar event $eventId for user $userId: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a calendar event
 * @param int $userId Staff user ID
 * @param string $eventId Google Event ID
 * @return bool Success status
 */
function deleteScheduleEvent($userId, $eventId) {
    $calendarService = getUserCalendarService($userId);
    
    if (!$calendarService || !$eventId) {
        return false;
    }
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT google_calendar_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $calendarId = $user['google_calendar_id'] ?? 'primary';
        
        $calendarService->events->delete($calendarId, $eventId);
        
        return true;
    } catch (Exception $e) {
        error_log("Error deleting calendar event $eventId for user $userId: " . $e->getMessage());
        return false;
    }
}

/**
 * Build Google Calendar Event object from schedule data
 * @param array $scheduleData Schedule information
 * @return Google_Service_Calendar_Event
 */
function buildCalendarEvent($scheduleData) {
    $event = new Google_Service_Calendar_Event();
    
    // Set event title based on status
    $statusMap = [
        'available' => '✅ Available for Work',
        'rest' => '☕ Rest / Break',
        'off' => '⛔ Off / Unavailable'
    ];
    
    $status = $scheduleData['status'] ?? 'off';
    $summary = 'Hitzmen Barbershop - ' . ($statusMap[$status] ?? 'Schedule');
    $event->setSummary($summary);
    
    // Set description
    $description = "Schedule Status: " . ucfirst($status) . "\n";
    if ($status !== 'off' && !empty($scheduleData['start_time']) && !empty($scheduleData['end_time'])) {
        $description .= "Shift: " . date('g:i A', strtotime($scheduleData['start_time'])) . " - " . 
                       date('g:i A', strtotime($scheduleData['end_time'])) . "\n";
    }
    $description .= "Location: Hitzmen Barbershop";
    $event->setDescription($description);
    
    // Set event times
    $date = $scheduleData['date'];
    
    if ($status !== 'off' && !empty($scheduleData['start_time']) && !empty($scheduleData['end_time'])) {
        // Regular shift with specific times
        $start = new Google_Service_Calendar_EventDateTime();
        $start->setDateTime($date . 'T' . $scheduleData['start_time'] . ':00');
        $start->setTimeZone('Asia/Manila'); // Adjust timezone as needed
        $event->setStart($start);
        
        $end = new Google_Service_Calendar_EventDateTime();
        $endDateTime = $date . 'T' . $scheduleData['end_time'] . ':00';
        
        // Handle midnight crossover (e.g., shift ends at 00:00 next day)
        if ($scheduleData['end_time'] < $scheduleData['start_time']) {
            $endDateTime = date('Y-m-d', strtotime($date . ' +1 day')) . 'T' . $scheduleData['end_time'] . ':00';
        }
        
        $end->setDateTime($endDateTime);
        $end->setTimeZone('Asia/Manila');
        $event->setEnd($end);
    } else {
        // All-day event for 'off' status
        $start = new Google_Service_Calendar_EventDateTime();
        $start->setDate($date);
        $event->setStart($start);
        
        $end = new Google_Service_Calendar_EventDateTime();
        $end->setDate(date('Y-m-d', strtotime($date . ' +1 day')));
        $event->setEnd($end);
    }
    
    // Set color based on status
    $colorMap = [
        'available' => '10', // Green
        'rest' => '5',       // Yellow
        'off' => '11'        // Red
    ];
    $event->setColorId($colorMap[$status] ?? '8'); // Default gray
    
    return $event;
}

/**
 * Main sync function - syncs a schedule to Google Calendar
 * @param int $scheduleId Schedule ID from database
 * @return array ['success' => bool, 'message' => string, 'event_id' => string|null]
 */
function syncScheduleToCalendar($scheduleId) {
    global $pdo;
    
    // Get schedule details
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        return ['success' => false, 'message' => 'Schedule not found', 'event_id' => null];
    }
    
    $userId = $schedule['user_id'];
    
    // Check if user has calendar sync enabled
    $stmt = $pdo->prepare("SELECT calendar_sync_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['calendar_sync_enabled']) {
        return ['success' => false, 'message' => 'Calendar sync not enabled for this user', 'event_id' => null];
    }
    
    $eventId = $schedule['google_event_id'];
    
    try {
        if ($eventId) {
            // Update existing event
            $success = updateScheduleEvent($userId, $eventId, $schedule);
            
            if ($success) {
                // Update last synced time
                $stmt = $pdo->prepare("UPDATE schedules SET last_synced = NOW() WHERE id = ?");
                $stmt->execute([$scheduleId]);
                
                return ['success' => true, 'message' => 'Event updated successfully', 'event_id' => $eventId];
            } else {
                return ['success' => false, 'message' => 'Failed to update calendar event', 'event_id' => null];
            }
        } else {
            // Create new event
            $newEventId = createScheduleEvent($userId, $schedule);
            
            if ($newEventId) {
                // Store event ID and update last synced time
                $stmt = $pdo->prepare("UPDATE schedules SET google_event_id = ?, last_synced = NOW() WHERE id = ?");
                $stmt->execute([$newEventId, $scheduleId]);
                
                return ['success' => true, 'message' => 'Event created successfully', 'event_id' => $newEventId];
            } else {
                return ['success' => false, 'message' => 'Failed to create calendar event', 'event_id' => null];
            }
        }
    } catch (Exception $e) {
        error_log("Sync error for schedule $scheduleId: " . $e->getMessage());
        return ['success' => false, 'message' => 'Sync error: ' . $e->getMessage(), 'event_id' => null];
    }
}
