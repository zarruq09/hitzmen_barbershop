<?php
session_start(); // Start the session for user authentication

// Include the database connection file. Adjust path if necessary.
require '../db.php';

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect unauthorized users to the login page. Adjust path if necessary.
    header('Location: ../login.php');
    exit();
}

// Check if the request method is POST (meaning the form was submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate the service ID from the POST request
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    // If ID is invalid or missing, redirect to dashboard with an error
    if ($id === false || $id === null) {
        header('Location: ../admin_dashboard.php?error=invalid_service_id&tab=services');
        exit();
    }

    // Sanitize and get other input from the POST request
    $service_name = htmlspecialchars($_POST['service_name'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT); // Validate price as a float
    $description = htmlspecialchars($_POST['description'] ?? '');

    // Ensure price is a valid number, default to 0.0 if not
    if ($price === false) {
        $price = 0.0;
        error_log("Invalid price received for service ID: " . $id);
    }

    $imageToSave = ''; // Initialize the image filename to be saved

    // --- Step 1: Fetch the current image filename from the database ---
    try {
        $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

        if ($current) {
            $imageToSave = $current['image']; // Keep the existing image by default
        } else {
            // Service not found, redirect with an error
            header('Location: ../admin_dashboard.php?error=service_not_found_for_edit&tab=services');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database Error fetching current service image for edit: " . $e->getMessage());
        header('Location: ../admin_dashboard.php?error=db_error_fetch_image&tab=services');
        exit();
    }

    // --- Step 2: Handle new image upload ---
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate a unique filename for the new image
        $newImageFileName = uniqid() . '_' . basename($_FILES['service_image']['name']);
        $targetFilePath = $uploadDir . $newImageFileName;

        if (move_uploaded_file($_FILES['service_image']['tmp_name'], $targetFilePath)) {
            $imageToSave = $newImageFileName; // Set the new image filename

            // Delete the old image file if it exists and is not a default placeholder
            if (!empty($current['image']) && file_exists($uploadDir . $current['image']) && $current['image'] !== 'default_service.png' /* Add other default image names here */) {
                if (!unlink($uploadDir . $current['image'])) {
                    error_log("Failed to delete old service image: " . $uploadDir . $current['image']);
                    // Decide if you want to redirect with an error here or just log
                }
            }
        } else {
            // Handle file upload error
            error_log("Failed to move uploaded file for service ID " . $id);
            header('Location: ../admin_dashboard.php?error=image_upload_failed&tab=services');
            exit();
        }
    }

    // --- Step 3: Update the service record in the database ---
    try {
        $updateStmt = $pdo->prepare("UPDATE services SET service_name = ?, price = ?, description = ?, image = ? WHERE id = ?");
        $updated = $updateStmt->execute([$service_name, $price, $description, $imageToSave, $id]);

        if ($updated) {
            // Redirect back to admin dashboard, specifically to the services tab on success
            header('Location: ../admin_dashboard.php?tab=services');
            exit(); // Always call exit() after a header redirect
        } else {
            // Handle case where update execution returns false
            error_log("Failed to update service ID: " . $id . ". Info: " . implode(" | ", $updateStmt->errorInfo()));
            header('Location: ../admin_dashboard.php?error=update_service_failed&tab=services');
            exit();
        }
    } catch (PDOException $e) {
        // Catch any PDO database-related errors during update
        error_log("Database Error updating service: " . $e->getMessage());
        header('Location: ../admin_dashboard.php?error=db_error_update&tab=services');
        exit();
    }
} else {
    // If the request method is not POST, redirect (shouldn't be accessed directly via GET)
    header('Location: ../admin_dashboard.php?error=invalid_request_method&tab=services');
    exit();
}
?>