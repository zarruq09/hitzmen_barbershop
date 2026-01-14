<?php
session_start();
require '../db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $specialty = trim($_POST['specialty']);
    $userId = !empty($_POST['user_id']) ? $_POST['user_id'] : null;

    if (!$id || empty($name)) {
        header('Location: ../admin_dashboard.php?error=invalid_input&tab=barbers');
        exit();
    }

    try {
        // Status is managed strictly by schedule
        // $status = $_POST['status'] ?? 'Available';

        // Handle image upload if provided
        $imageName = null;
        if (!empty($_FILES['new_image']['name'])) {
            $uploadDir = '../uploads/';
            $imageName = uniqid() . '_' . basename($_FILES['new_image']['name']);
            $targetPath = $uploadDir . $imageName;
            
            // Validate and move uploaded file
            if (move_uploaded_file($_FILES['new_image']['tmp_name'], $targetPath)) {
                // Delete old image if it exists
                $stmt = $pdo->prepare("SELECT image FROM barbers WHERE id = ?");
                $stmt->execute([$id]);
                $oldImage = $stmt->fetchColumn();
                
                if ($oldImage && file_exists($uploadDir . $oldImage)) {
                    unlink($uploadDir . $oldImage);
                }
            } else {
                throw new Exception("Image upload failed");
            }
        }

        // Update barber record (excluding status)
        if ($imageName) {
            $stmt = $pdo->prepare("UPDATE barbers SET name = ?, specialty = ?, image = ?, user_id = ? WHERE id = ?");
            $stmt->execute([$name, $specialty, $imageName, $userId, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE barbers SET name = ?, specialty = ?, user_id = ? WHERE id = ?");
            $stmt->execute([$name, $specialty, $userId, $id]);
        }

        header('Location: ../admin_dashboard.php?success=barber_updated&tab=barbers');
        exit();
    } catch (Exception $e) {
        error_log("Update Error: " . $e->getMessage());
        header('Location: ../admin_dashboard.php?error=update_failed&tab=barbers');
        exit();
    }
} else {
    header('Location: ../admin_dashboard.php?error=invalid_request&tab=barbers');
    exit();
}