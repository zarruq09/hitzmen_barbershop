<?php
session_start();
require_once '../db.php'; // Assumes db.php is one level up from 'actions' folder

// 1. Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login if not admin
    header('Location: ../login.php'); // Adjust path if login.php is elsewhere
    exit();
}

// 2. Check if the request is POST and if an ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $service_id = $_POST['id'];

    try {
        // --- Optional: Get the image filename BEFORE deleting from DB ---
        $stmt_img = $pdo->prepare("SELECT image FROM services WHERE id = ?");
        $stmt_img->execute([$service_id]);
        $service = $stmt_img->fetch();
        $image_filename = $service ? $service['image'] : null;
        // --- End Optional ---

        // 3. Prepare and execute the DELETE statement
        // Hard delete: Remove record from database (Services table has no status column)
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        // $stmt = $pdo->prepare("UPDATE services SET status = 'Deleted' WHERE id = ?");
        $stmt->execute([$service_id]);

        // --- Optional: Delete the image file from the server ---
        if ($image_filename) {
            $image_path = '../uploads/' . $image_filename; // Adjust path to your uploads folder
            if (file_exists($image_path)) {
                @unlink($image_path); // Use @ to suppress errors if file not found
            }
        }
        // --- End Optional ---

        // 4. Redirect back to the dashboard, showing the services tab
        header('Location: ../admin_dashboard.php?tab=services&status=deleted'); // Adjust path if needed
        exit();

    } catch (PDOException $e) {
        // 5. Handle potential database errors
        // You should log this error instead of just redirecting in a real application
        // error_log("Error deleting service: " . $e->getMessage());
        header('Location: ../admin_dashboard.php?tab=services&status=error'); // Adjust path if needed
        exit();
    }

} else {
    // 6. If not a POST request or no ID, just redirect back
    header('Location: ../admin_dashboard.php?tab=services'); // Adjust path if needed
    exit();
}
?>