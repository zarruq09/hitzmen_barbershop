<?php
/**
 * Database Migration Helper
 * Run this file once to add Google Calendar fields to the database
 * Access: http://localhost/hitzmen_barbershop/setup_calendar_db.php
 */

session_start();
require_once 'db.php';

// Security: Only allow this to run once or for admins
$migration_completed_file = __DIR__ . '/.calendar_migration_done';

if (file_exists($migration_completed_file)) {
    die("Migration already completed. Delete the file '.calendar_migration_done' to run again.");
}

echo "<h1>Google Calendar Database Migration</h1>";
echo "<pre>";

try {
    // Add columns to users table
    echo "Adding columns to users table...\n";
    
    $pdo->exec("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS google_access_token TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS google_refresh_token TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS google_calendar_id VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS calendar_sync_enabled TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS token_expires_at DATETIME DEFAULT NULL");
    
    echo "✓ Users table updated\n";
    
    // Add columns to schedules table
    echo "\nAdding columns to schedules table...\n";
    
    $pdo->exec("ALTER TABLE schedules 
        ADD COLUMN IF NOT EXISTS google_event_id VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS last_synced TIMESTAMP NULL DEFAULT NULL");
    
    echo "✓ Schedules table updated\n";
    
    // Add indexes
    echo "\nAdding indexes...\n";
    
    try {
        $pdo->exec("ALTER TABLE schedules ADD INDEX idx_google_event_id (google_event_id)");
        echo "✓ Index on schedules.google_event_id created\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000' && strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠ Index on schedules.google_event_id already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_calendar_sync (calendar_sync_enabled)");
        echo "✓ Index on users.calendar_sync_enabled created\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000' && strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠ Index on users.calendar_sync_enabled already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Mark migration as complete
    file_put_contents($migration_completed_file, date('Y-m-d H:i:s'));
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo str_repeat("=", 50) . "\n";
    echo "\nNext steps:\n";
    echo "1. Enable Google Calendar API in Google Cloud Console\n";
    echo "2. Log out and log in again with Google OAuth to get calendar permissions\n";
    echo "3. Enable calendar sync for staff members\n";
    echo "\nYou can now delete this file (setup_calendar_db.php) for security.\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check your database connection and try again.\n";
}

echo "</pre>";
?>
