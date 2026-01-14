<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'db.php';
require_once 'includes/csrf_token.php';

// --- FETCH USERS ---
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-heading font-bold bg-clip-text text-transparent bg-gradient-to-r from-gold to-white">
            👥 Manage Users
        </h2>
        <button onclick="openUserModal()" class="btn-gold px-4 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Add User
        </button>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="bg-green-900/30 border border-green-500/50 text-green-400 p-4 rounded mb-6 flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="bg-red-900/30 border border-red-500/50 text-red-400 p-4 rounded mb-6 flex items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="bg-dark-card border border-dark-border rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#181818] border-b border-dark-border">
                    <tr>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Username</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Email</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider">Role</th>
                        <th class="py-3 px-6 font-bold text-gold uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-dark-hover transition-colors group">
                        <td class="py-3 px-6 font-medium text-white"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="py-3 px-6 text-gray-400"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="py-3 px-6">
                            <?php 
                                $roleClass = match($user['role']) {
                                    'admin' => 'bg-purple-900/30 text-purple-400 border-purple-900/50',
                                    'staff' => 'bg-blue-900/30 text-blue-400 border-blue-900/50',
                                    default => 'bg-gray-800 text-gray-400 border-gray-700',
                                };
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?= $roleClass ?>">
                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                            </span>
                        </td>
                        <td class="py-3 px-6 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button 
                                    onclick="openUserModal(this)"
                                    data-id="<?= $user['id'] ?>"
                                    data-username="<?= htmlspecialchars($user['username']) ?>"
                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                    data-role="<?= $user['role'] ?>"
                                    class="text-gray-400 hover:text-gold transition-colors" 
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <form action="actions/delete_user.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- USER MODAL (Dark Theme) -->
<div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-6 w-full max-w-md relative transform transition-all">
        <button onclick="closeUserModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <h2 id="modalTitle" class="text-2xl font-heading font-bold text-white mb-6 border-b border-dark-border pb-2">Add User</h2>
        
        <form method="POST" action="actions/save_user.php" class="space-y-4">
            <?php csrfField(); ?>
            <input type="hidden" name="save_user" value="1">
            <input type="hidden" name="user_id" id="userId">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                <input type="text" name="username" id="username" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                <input type="email" name="email" id="email" required class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Role</label>
                <div class="relative">
                    <select name="role" id="role" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors appearance-none">
                        <option value="customer">Customer</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gold">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">
                    <span id="passwordLabel">Password</span>
                </label>
                <input type="password" name="password" id="password" class="w-full bg-dark border border-dark-border rounded-lg px-4 py-2 text-white focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition-colors">
                <p id="passwordHint" class="text-xs text-gray-500 mt-1 hidden">Leave blank to keep current password</p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-dark-border mt-6">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-dark-hover transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-gold px-6 py-2 rounded-lg shadow-lg hover:shadow-gold/20 transition font-bold text-dark">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
// Attach functions to window to ensure global availability (especially for AJAX loaded content)
window.openUserModal = function(triggerElement = null) {
    const modal = document.getElementById('userModal');
    const title = document.getElementById('modalTitle');
    const userIdInput = document.getElementById('userId');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const passwordInput = document.getElementById('password');
    const passwordLabel = document.getElementById('passwordLabel');
    const passwordHint = document.getElementById('passwordHint');

    if (triggerElement && triggerElement.dataset && triggerElement.dataset.id) {
        // Edit Mode
        const data = triggerElement.dataset;
        userIdInput.value = data.id;
        usernameInput.value = data.username;
        emailInput.value = data.email;
        roleSelect.value = data.role;

        title.textContent = 'Edit User';
        passwordInput.required = false; 
        passwordLabel.textContent = 'New Password (Optional)';
        passwordHint.classList.remove('hidden');
    } else {
        // Add Mode
        userIdInput.value = '';
        usernameInput.value = '';
        emailInput.value = '';
        roleSelect.value = 'customer'; // Default

        title.textContent = 'Add User';
        passwordInput.required = true;
        passwordLabel.textContent = 'Password';
        passwordHint.classList.add('hidden');
    }

    modal.classList.remove('hidden');
};

window.closeUserModal = function() {
    document.getElementById('userModal').classList.add('hidden');
};

// Immediate execution for alert dismissal (AJAX compatible)
(function() {
    const alerts = document.querySelectorAll('[role="alert"]');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 1000); // 1 second delay
    }
})();

// Close modal when clicking outside (Attach event strictly to this modal instance)
// Using optional chaining/check in case this runs multiple times or element is missing transiently
const userModal = document.getElementById('userModal');
if (userModal) {
    userModal.onclick = function(e) {
        if (e.target === this) {
            window.closeUserModal();
        }
    };
}
</script>
</div>

