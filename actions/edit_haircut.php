<?php
session_start();
require_once '../db.php'; // Adjust path if needed

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); // Adjust path if needed
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['haircut_id'];
    $style_name = $_POST['style_name'];
    $description = $_POST['description'];

    // Duplicate Check (exclude current ID)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM haircuts WHERE style_name = ? AND id != ?");
    $stmtCheck->execute([$style_name, $id]);
    if ($stmtCheck->fetchColumn() > 0) {
         header('Location: ../admin_dashboard.php?tab=haircuts&error=duplicate_style');
         exit();
    }

    // Handle multiple selection for face_shape and hair_type
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

    $image_path = $_POST['current_image'];
    $new_image_uploaded = false;

    // --- UPDATED IMAGE LOGIC ---
    // Check if a new file was uploaded successfully
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
        $target_dir = "../uploads/"; // Adjust path if needed
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowedTypes)) {
            $newFileName = uniqid('haircut_', true) . '.' . $imageFileType;
            $target_file = $target_dir . $newFileName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                 // Optionally delete the OLD image file 
                 if ($image_path && file_exists($target_dir . $image_path)) {
                    @unlink($target_dir . $image_path); // Use @ to suppress errors if file doesn't exist
                 }
                $image_path = $newFileName; // Use the NEW filename
                $new_image_uploaded = true; 
            } else {
                 header('Location: ../admin_dashboard.php?tab=haircuts&status=upload_error');
                 exit();
            }
        } else {
             header('Location: ../admin_dashboard.php?tab=haircuts&status=invalid_type');
             exit();
        }
    } 
    // If no new image uploaded, $image_path retains the value from $_POST['current_image']
    // --- END UPDATED IMAGE LOGIC ---

    try {
        $sql = "UPDATE haircuts 
                SET style_name = ?, description = ?, face_shape = ?, hair_type = ?, image = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $style_name,
            $description,
            $face_shape,
            $hair_type,
            $image_path,
            $id
        ]);

        header('Location: ../admin_dashboard.php?tab=haircuts&status=updated'); 
        exit();

    } catch (PDOException $e) {
        header('Location: ../admin_dashboard.php?tab=haircuts&status=db_error'); 
        exit();
    }

} else {
    header('Location: ../admin_dashboard.php?tab=haircuts'); 
    exit();
}
?>