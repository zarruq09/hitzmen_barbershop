<?php
session_start();
require_once __DIR__ . '/includes/auth_functions.php';
redirectIfLoggedIn();

// Redirect to the new consolidated login page with the register action
header("Location: index.php?action=register");
exit();
?>