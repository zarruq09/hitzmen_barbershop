document.addEventListener('DOMContentLoaded', function () {
    // Register form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const password = document.getElementById('reg_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }

            return true;
        });
    }

    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }

            return true;
        });
    }

    // Phone number formatting
    // Phone number formatting - Removed as per user request to avoid ()
    /* 
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // Simplify: just allow numbers and maybe dashes/spaces but no auto-formatting with parenthesis
             e.target.value = e.target.value.replace(/[^0-9+\s-]/g, '');
        });
    }
    */
});