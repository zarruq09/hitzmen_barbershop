<?php
/******************************************************************************
 * actions/edit_barber.php
 * ---------------------------------------------------------------------------
 * Edit a barber record (admin-only)
 ******************************************************************************/

/* ---------- 1.  Session / auth guard ------------------------------------- */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

/* ---------- 2.  Database (PDO) ------------------------------------------ */
/* Adjust the path below so it points to the file that creates $pdo.        */
require_once '../config/config.php';   // <-- your PDO connection

/* helper: quick redirect back to barbers tab */
function back($extra = '') {
    header("Location: ../admin_dashboard.php?tab=barbers{$extra}");
    exit();
}

/* ---------- 3.  Load barber by GET id (for GET or failed POST) ---------- */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) back('&error=invalid_id');

$stmt = $pdo->prepare('SELECT * FROM barbers WHERE id = ?');
$stmt->execute([$id]);
$barber = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$barber) back('&error=barber_not_found');

/* ---------- 4.  Handle POST (update) ------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Sanitize input */
    $name      = trim($_POST['barber_name'] ?? '');
    $specialty = trim($_POST['specialty']    ?? '');

    if ($name === '')           back('&error=name_required');
    if ($specialty === '')      back('&error=specialty_required');

    /* keep current image unless replaced */
    $imageSave = $barber['image'];

    /* New image upload? */
    if (!empty($_FILES['barber_image']['name']) &&
        $_FILES['barber_image']['error'] === UPLOAD_ERR_OK) {

        $upDir = '../uploads/';
        if (!is_dir($upDir)) mkdir($upDir, 0777, true);

        $newFile = uniqid() . '_' . basename($_FILES['barber_image']['name']);
        $target  = $upDir . $newFile;

        if (!move_uploaded_file($_FILES['barber_image']['tmp_name'], $target)) {
            back('&error=image_upload_failed');
        }

        /* Delete old image (unless placeholder) */
        if ($imageSave &&
            $imageSave !== 'default_barber.png' &&
            file_exists($upDir . $imageSave)) {
            @unlink($upDir . $imageSave);
        }
        $imageSave = $newFile;
    }

    /* Update record */
    $u = $pdo->prepare('UPDATE barbers SET name = ?, specialty = ?, image = ? WHERE id = ?');
    if ($u->execute([$name, $specialty, $imageSave, $id])) {
        back('&success=updated');
    }
    back('&error=update_failed');
}

/* ---------- 5.  Show edit form (GET) ------------------------------------ */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Barber</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6">Edit Barber</h2>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="barber_id" value="<?= $barber['id'] ?>">

            <!-- Name -->
            <div>
                <label class="block font-semibold mb-1">Name</label>
                <input name="barber_name" required
                       value="<?= htmlspecialchars($barber['name']) ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <!-- Specialty -->
            <div>
                <label class="block font-semibold mb-1">Specialty</label>
                <input name="specialty"
                       value="<?= htmlspecialchars($barber['specialty']) ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <!-- Image -->
            <div>
                <label class="block font-semibold mb-1">Image</label>
                <?php if ($barber['image']): ?>
                    <img src="../uploads/<?= htmlspecialchars($barber['image']) ?>"
                         alt="Current image"
                         class="h-24 w-24 object-cover rounded mb-2">
                <?php endif; ?>
                <input type="file" name="barber_image" class="w-full border rounded px-3 py-2">
                <p class="text-sm text-gray-500">Leave blank to keep current image.</p>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between">
                <a href="../admin_dashboard.php?tab=barbers"
                   class="px-4 py-2 bg-gray-300 rounded">Cancel</a>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded">Update</button>
            </div>
        </form>
    </div>
</body>
</html>
