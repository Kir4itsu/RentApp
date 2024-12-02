<?php
// Autoload Composer dependencies
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback error handling if Composer autoload is missing
    die('Composer autoload file not found. Please run "composer install".');
}

// Optional: Additional custom autoloading or configuration
// For example, setting up error reporting, custom error handlers, etc.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// You can add custom autoload functions here if needed
spl_autoload_register(function($class) {
    // Example of custom class autoloading
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Optional: Set up session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Optional: CSRF protection token generation function
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Optional: CSRF token validation function
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}