-- Google Calendar Integration - Database Migration
-- Run this SQL in your hitzmen_barbershop database

-- Add Google Calendar fields to users table
ALTER TABLE users 
ADD COLUMN google_access_token TEXT DEFAULT NULL AFTER google_id,
ADD COLUMN google_refresh_token TEXT DEFAULT NULL AFTER google_access_token,
ADD COLUMN google_calendar_id VARCHAR(255) DEFAULT NULL AFTER google_refresh_token,
ADD COLUMN calendar_sync_enabled TINYINT(1) DEFAULT 0 AFTER google_calendar_id,
ADD COLUMN token_expires_at DATETIME DEFAULT NULL AFTER calendar_sync_enabled;

-- Add Google Event ID to schedules table for tracking synced events
ALTER TABLE schedules 
ADD COLUMN google_event_id VARCHAR(255) DEFAULT NULL AFTER end_time,
ADD COLUMN last_synced TIMESTAMP NULL DEFAULT NULL AFTER google_event_id;

-- Add index for faster lookups
ALTER TABLE schedules ADD INDEX idx_google_event_id (google_event_id);
ALTER TABLE users ADD INDEX idx_calendar_sync (calendar_sync_enabled);
