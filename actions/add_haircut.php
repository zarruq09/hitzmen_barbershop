<?php
session_start(); // Start the session for user authentication

// Include the database connection file. Adjust path if necessary.
// If add_haircut.php is in 'actions/' and db.php is in the root, '../db.php' is correct.
require '../db.php';

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect unauthorized users to the login page. Adjust path if necessary.
    header('Location: ../login.php');
    exit();
}

// Check if the request method is POST (meaning the form was submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input from the POST request
    // Using htmlspecialchars to prevent XSS if these values are echoed later
    $style_name = htmlspecialchars($_POST['style_name'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');

    // Duplicate Check
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM haircuts WHERE style_name = ?");
    $stmtCheck->execute([$style_name]);
    if ($stmtCheck->fetchColumn() > 0) {
        header('Location: ../admin_dashboard.php?error=duplicate_haircut&tab=haircuts');
        exit();
    }

    // Handle face_shape (string or array)
    $raw_face_shape = $_POST['face_shape'] ?? '';
    if (is_array($raw_face_shape)) {
        $face_shape = implode(',', array_map('htmlspecialchars', $raw_face_shape));
    } else {
        $face_shape = htmlspecialchars($raw_face_shape);
    }
    // Handle hair_type (string or array)
    $raw_hair_type = $_POST['hair_type'] ?? '';
    if (is_array($raw_hair_type)) {
        $hair_type = implode(',', array_map('htmlspecialchars', $raw_hair_type));
    } else {
        $hair_type = htmlspecialchars($raw_hair_type);
    }

    $image = ''; // Initialize image filename

    // Handle image upload
    // Check if a file was uploaded without errors
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/'; // Define the directory where images will be stored. Adjust path if necessary.

        // Create the upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create directory with read/write/execute permissions for all
        }

        // Generate a unique filename to prevent overwriting and ensure safety
        $imageFileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . $imageFileName;

        // Move the uploaded file from its temporary location to the target directory
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $image = $imageFileName; // Store the new unique filename
        } else {
            // Handle file upload failure
            error_log("Failed to move uploaded file: " . $_FILES['image']['tmp_name'] . " to " . $targetFilePath);
            // Redirect with an error message and stay on the haircuts tab
            header('Location: ../admin_dashboard.php?error=image_upload_failed&tab=haircuts');
            exit();
        }
    }

    try {
        // Prepare the SQL statement to insert data into the 'haircuts' table
        $stmt = $pdo->prepare("INSERT INTO haircuts (style_name, description, image, face_shape, hair_type) VALUES (?, ?, ?, ?, ?)");
        // Execute the prepared statement with the sanitized data
        $success = $stmt->execute([$style_name, $description, $image, $face_shape, $hair_type]);

        if ($success) {
            // Redirect back to the admin dashboard, specifically to the haircuts tab on success
            header('Location: ../admin_dashboard.php?tab=haircuts');
            exit(); // Always call exit() after a header redirect
        } else {
            // If execution failed (e.g., due to database constraint or error not caught by PDOException)
            error_log("Failed to insert haircut into database. Info: " . implode(" | ", $stmt->errorInfo()));
            header('Location: ../admin_dashboard.php?error=add_haircut_failed&tab=haircuts');
            exit();
        }
    } catch (PDOException $e) {
        // Catch any PDO database-related errors
        error_log("Database Error adding haircut: " . $e->getMessage());
        header('Location: ../admin_dashboard.php?error=db_error&tab=haircuts');
        exit();
    }
} else {
    // If the request method is not POST, redirect or show an error
    header('Location: ../admin_dashboard.php?error=invalid_request_method&tab=haircuts');
    exit();
}
?>