<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $new_password = $_POST['new_password'];
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $id]);

    header('Location: ../admin_dashboard.php?page=manage_users');
    exit();
}
?>
<form method="POST" action="actions/edit_user.php">
    <input type="hidden" name="id" id="editUserId">
    <input type="text" name="username" id="editUsername" required>
    <select name="role" id="editRole">
        <option value="admin">Admin</option>
        <option value="barber">Barber</option>
        <option value="customer">Customer</option>
    </select>
    <button type="submit" class="btn-primary">Save</button>
</form>
<form method="POST" action="actions/change_password.php">
    <input type="hidden" name="id" id="passwordUserId">
    <input type="password" name="new_password" required>
    <button type="submit" class="btn-primary">Change</button>
</form>

<script>
function openEditUserModal(id, username, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editRole').value = role;
    document.getElementById('editUserModal').classList.remove('hidden');
    document.getElementById('modalOverlay').classList.remove('hidden');
}
</script>

<button onclick="openEditUserModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>')" class="btn-secondary text-sm">Edit</button>
<button onclick="openPasswordModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" class="btn-primary text-sm">Change Password</button>