<?php
/**
 * Calendar Settings Management
 * Allows admins to enable/disable calendar sync for staff members
 */

session_start();
require_once 'db.php';
require_once 'config/google_config.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle enable/disable actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'];
    
    if ($userId) {
        if ($action === 'enable') {
            $stmt = $pdo->prepare("UPDATE users SET calendar_sync_enabled = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['success'] = "Calendar sync enabled for user.";
        } elseif ($action === 'disable') {
            $stmt = $pdo->prepare("UPDATE users SET calendar_sync_enabled = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['success'] = "Calendar sync disabled for user.";
        }
    }
    
    header('Location: calendar_settings.php');
    exit();
}

// Fetch all staff members with their calendar status
$stmt = $pdo->prepare("SELECT id, username, full_name, email, calendar_sync_enabled, google_access_token, google_refresh_token FROM users WHERE role = 'staff' ORDER BY username ASC");
$stmt->execute();
$staff_members = $stmt->fetchAll();

// Generate Google OAuth URL for calendar authorization
$authUrl = $googleClient->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Calendar Settings | Hitzmen Barbershop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        h1, h2 { font-family: 'Playfair Display', serif; letter-spacing: 1px; }
        .text-gold { color: #e3c77b; }
        .bg-gold { background-color: #e3c77b; }
        .border-gold { border-color: #e3c77b; }
        .bg-dark-blue { background-color: #232946; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 font-roboto">

<div class="max-w-6xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-8 border-b-2 border-gold pb-4">
        <h1 class="text-4xl font-bold mb-2" style="color: #f7e06e; text-shadow: 0 2px 8px #e3c77b88;">Google Calendar Settings</h1>
        <p class="text-gray-500">Manage calendar synchronization for staff members</p>
        <div class="mt-4">
            <a href="admin_dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
            <a href="manage_schedule.php" class="ml-4 text-blue-600 hover:underline">→ Manage Schedules</a>
        </div>
    </div>

    <!-- Success Message -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <h3 class="font-bold text-blue-900 mb-2">ℹ️ How Calendar Sync Works</h3>
        <ul class="text-blue-800 text-sm space-y-1">
            <li>• Staff members must first log in with Google OAuth to connect their calendar</li>
            <li>• Once connected, enable "Calendar Sync" for automatic synchronization</li>
            <li>• Schedules will automatically sync to their Google Calendar</li>
            <li>• Events show as "Hitzmen Barbershop - Available/Rest/Off" in their calendar</li>
        </ul>
    </div>

    <!-- Staff Calendar Status Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <table class="min-w-full">
            <thead class="bg-dark-blue text-white">
                <tr>
                    <th class="px-6 py-3 text-left">Staff Member</th>
                    <th class="px-6 py-3 text-left">Email</th>
                    <th class="px-6 py-3 text-center">Google Connected</th>
                    <th class="px-6 py-3 text-center">Calendar Sync</th>
                    <th class="px-6 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($staff_members as $staff): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gold text-dark-blue flex items-center justify-center font-bold mr-3">
                                    <?= strtoupper(substr($staff['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($staff['username']) ?></div>
                                    <?php if ($staff['full_name']): ?>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($staff['full_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <?= htmlspecialchars($staff['email'] ?? 'No email') ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($staff['google_access_token']): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✓ Connected</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">Not Connected</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($staff['calendar_sync_enabled']): ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">📅 Enabled</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($staff['google_access_token']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?= $staff['id'] ?>">
                                    <?php if ($staff['calendar_sync_enabled']): ?>
                                        <input type="hidden" name="action" value="disable">
                                        <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                                            Disable Sync
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="enable">
                                        <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded text-sm">
                                            Enable Sync
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">Connect Google first</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($staff_members)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No staff members found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Setup Instructions -->
    <div class="mt-8 bg-gray-100 rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-900">Setup Instructions for Staff</h2>
        <ol class="space-y-3 text-gray-700">
            <li><span class="font-bold">1.</span> Staff member must <strong>log out</strong> of the barbershop system</li>
            <li><span class="font-bold">2.</span> Staff member logs in again using <strong>"Sign in with Google"</strong></li>
            <li><span class="font-bold">3.</span> Grant calendar permissions when prompted</li>
            <li><span class="font-bold">4.</span> Admin enables "Calendar Sync" for that staff member (on this page)</li>
            <li><span class="font-bold">5.</span> Future schedule updates will automatically sync to Google Calendar!</li>
        </ol>
    </div>
</div>

</body>
</html>
