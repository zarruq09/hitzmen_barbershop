<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/csrf_token.php';
require_once __DIR__ . '/config/google_config.php';
redirectIfLoggedIn();

$errors = [];
$success_msg = '';

// Check for Google OAuth errors
if (isset($_SESSION['error'])) {
    $errors[] = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN LOGIC
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "Invalid security token. Please refresh and try again.";
        } else {
            $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'customer'; 

        $allowed_roles = ['customer', 'staff', 'admin'];
        if (!in_array($role, $allowed_roles)) {
            $errors[] = "Invalid role selected.";
        }

        if (empty($errors)) {
            $user = loginUser($username, $password, $role); 

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        break;
                    case 'staff':
                        header('Location: staff_dashboard.php');
                        break;
                    case 'customer':
                    default:
                        header('Location: dashboard.php');
                        break;
                }
                exit();
            } else {
                $errors[] = "Invalid username, password, or role combination.";
            }
        }
      } // End else (CSRF valid)
    }
    // REGISTER LOGIC
    elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "Invalid security token. Please refresh and try again.";
        }
        else {
            $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        
        // Validation
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($password)) $errors[] = "Password is required";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match";
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($phone)) $errors[] = "Phone number is required";
        
        if (empty($errors)) {
            if (registerUser($username, $email, $password, $full_name, $phone)) {
                $success_msg = "Registration successful! Please login.";
            } else {
                $errors[] = "Username or email already exists";
            }
        }
      } // End else (CSRF valid)
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register | Hitzmen Barbershop</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/Logo.png">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#C5A059',
                        dark: '#121212',
                        card: '#1E1E1E',
                        cardBorder: '#333333'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif'],
                        serif: ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .fade-enter {
            opacity: 0;
            transform: translateY(10px);
        }
        .fade-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms, transform 300ms;
        }
        .hidden-form {
            display: none;
        }
        /* Hide browser default password reveal button */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
    </style>
</head>
<body class="bg-[#121212] text-gray-100 font-sans min-h-[100dvh] flex flex-col justify-center items-center relative overflow-hidden py-10">
    
    <!-- Background element -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-[#121212] opacity-90 z-10"></div>
        <img src="assets/images/background.jpg" alt="Background" class="w-full h-full object-cover">
    </div>

    <div class="z-10 w-full max-w-sm p-4">
        
        <div class="bg-[#1E1E1E] border border-[#333] rounded-xl shadow-2xl p-6 transform transition-all duration-300 hover:border-[#C5A059]/50 relative">
            
            <div class="text-center mb-4">
                <img src="assets/images/Logo.png" alt="Hitzmen Barbershop Logo" class="mx-auto mb-2 w-14 h-14 object-contain drop-shadow-lg" />
                <h1 class="text-2xl font-bold font-heading text-white mb-1">Hitzmen Barbershop</h1>
                <p id="pageSubtitle" class="text-[#C5A059] text-xs italic font-serif tracking-wider">HAIR IS A MAN'S CROWN</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/30 border-l-4 border-red-500 text-red-200 px-3 py-2 rounded mb-4 text-xs">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="bg-green-900/30 border-l-4 border-green-500 text-green-200 px-3 py-2 rounded mb-4 text-xs">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <!-- LOGIN FORM -->
            <div id="loginSection" class="<?php echo (isset($_POST['action']) && $_POST['action'] === 'register' && empty($success_msg)) ? 'hidden-form' : ''; ?>">
                <div class="mb-4">
                     <a href="<?php echo $googleClient->createAuthUrl(); ?>"
                       class="flex items-center justify-center w-full bg-[#e94235] hover:bg-[#d63325] text-white font-bold py-2.5 px-4 rounded-lg shadow transition transform hover:-translate-y-0.5 duration-200 text-sm">
                        <i class="fab fa-google mr-2 text-base"></i>
                        Login with Google
                    </a>
                </div>

                <div class="flex items-center my-4">
                    <div class="flex-grow border-t border-[#333]"></div>
                    <span class="mx-2 text-gray-500 text-[10px] uppercase tracking-wider">OR</span>
                    <div class="flex-grow border-t border-[#333]"></div>
                </div>

                <form id="loginForm" action="index.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="login">
                    <?php csrfField(); ?>
                    <div>
                        <input type="text" id="username" name="username" required placeholder="Username or Email"
                               class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                    </div>
                    <div>
                        <div class="relative">
                            <input type="password" id="password" name="password" required placeholder="Password"
                                   class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 pr-10 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-[#C5A059]" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </div>
                        </div>
                        <div class="text-right mt-1">
                            <a href="forgot_password.php" class="text-xs text-gray-400 hover:text-[#C5A059] transition-colors">Forgot Password?</a>
                        </div>
                    </div>
                    <div>
                        <div class="relative">
                            <select id="role" name="role" required
                                    class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none appearance-none">
                                <option value="customer">Customer</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button type="submit"
                            class="w-full bg-[#C5A059] hover:bg-[#D4AF37] text-[#121212] py-2.5 rounded-lg font-bold tracking-wide shadow-lg text-base transition-all duration-300 transform hover:-translate-y-0.5">
                            LOGIN
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center border-t border-[#333] pt-4">
                    <p class="text-gray-400 text-xs">Don't have an account?
                        <button onclick="toggleAuth('register')" class="text-[#C5A059] hover:text-white font-bold ml-1 transition-colors focus:outline-none">Register here</button>
                    </p>
                </div>
            </div>

            <!-- REGISTER FORM -->
            <div id="registerSection" class="<?php echo (isset($_POST['action']) && $_POST['action'] === 'register' && empty($success_msg)) ? '' : 'hidden-form'; ?>">
                
                <div class="mb-4">
                     <a href="<?php echo $googleClient->createAuthUrl(); ?>"
                       class="flex items-center justify-center w-full bg-[#e94235] hover:bg-[#d63325] text-white font-bold py-2.5 px-4 rounded-lg shadow transition transform hover:-translate-y-0.5 duration-200 text-sm">
                        <i class="fab fa-google mr-2 text-base"></i>
                        Register with Google
                    </a>
                </div>

                <div class="flex items-center my-4">
                    <div class="flex-grow border-t border-[#333]"></div>
                    <span class="mx-2 text-gray-500 text-[10px] uppercase tracking-wider">OR</span>
                    <div class="flex-grow border-t border-[#333]"></div>
                </div>

                <form id="registerForm" action="index.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="register">
                    <?php csrfField(); ?>
                    <div>
                        <input type="text" id="reg_username" name="username" required placeholder="Username"
                               class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                    </div>
                    <div>
                        <input type="email" id="reg_email" name="email" required placeholder="Email"
                               class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="relative">
                                <input type="password" id="reg_password" name="password" required placeholder="Password"
                                       class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 pr-10 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-[#C5A059]" onclick="togglePassword('reg_password', this)">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm"
                                       class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 pr-10 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400 hover:text-[#C5A059]" onclick="togglePassword('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <input type="text" id="full_name" name="full_name" required placeholder="Full Name"
                               class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                    </div>
                    <div>
                        <input type="tel" id="phone" name="phone" required placeholder="Phone Number"
                               class="w-full bg-[#121212] border border-[#333] text-white text-sm rounded-lg p-2.5 focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] outline-none placeholder-gray-600">
                    </div>
                    
                    <div class="pt-1">
                        <button type="submit"
                            class="w-full bg-[#C5A059] hover:bg-[#D4AF37] text-[#121212] py-2.5 rounded-lg font-bold tracking-wide shadow-lg text-base transition-all duration-300 transform hover:-translate-y-0.5">
                            REGISTER
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center border-t border-[#333] pt-4">
                    <p class="text-gray-400 text-xs">Already have an account?
                        <button onclick="toggleAuth('login')" class="text-[#C5A059] hover:text-white font-bold ml-1 transition-colors focus:outline-none">Login here</button>
                    </p>
                </div>
            </div>

        </div>
        
        <div class="text-center mt-4 text-gray-600 text-[10px]">
             &copy; <?php echo date('Y'); ?> Hitzmen Barbershop
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, iconDiv) {
            const input = document.getElementById(inputId);
            const icon = iconDiv.querySelector('i');
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function toggleAuth(mode) {
            const loginSection = document.getElementById('loginSection');
            const registerSection = document.getElementById('registerSection');
            const subtitle = document.getElementById('pageSubtitle');

            if (mode === 'register') {
                loginSection.classList.add('hidden-form');
                registerSection.classList.remove('hidden-form');
                registerSection.classList.add('fade-enter-active');
                subtitle.textContent = "Create your account";
                // Update URL without reloading
                const url = new URL(window.location);
                url.searchParams.set('action', 'register');
                window.history.pushState({}, '', url);
            } else {
                registerSection.classList.add('hidden-form');
                loginSection.classList.remove('hidden-form');
                loginSection.classList.add('fade-enter-active');
                subtitle.textContent = "HAIR IS A MAN'S CROWN";
                // Update URL without reloading
                const url = new URL(window.location);
                url.searchParams.delete('action');
                window.history.pushState({}, '', url);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'register') {
                toggleAuth('register');
            }
        });
    </script>
    <script src="assets/js/auth.js?v=<?php echo time(); ?>"></script>
</body>
</html>