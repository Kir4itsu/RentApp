<?php
// database.php

// Database configuration
$host = 'localhost';
$dbname = 'rental_aksesoris';
$username = 'root';
$password = '';

// Define project root path
define('PROJECT_ROOT', dirname(__DIR__));  // Akan mengarah ke folder root project

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host);

// Define upload paths
define('UPLOAD_PATH', PROJECT_ROOT . '/uploads/');  // Physical path di server
define('UPLOAD_URL', BASE_URL . '/uploads/');  // URL untuk akses via web

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
    // Buat .htaccess untuk keamanan
    $htaccess = "Options -Indexes\nAllowOverride All";
    file_put_contents(UPLOAD_PATH . '.htaccess', $htaccess);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
}

// Helper function untuk validasi path
function validateUploadPath($path) {
    return str_replace('\\', '/', $path);  // Convert backslashes to forward slashes
}

// Helper function untuk get URL gambar lengkap
function getImageUrl($filename) {
    if (!$filename) return null;
    return UPLOAD_URL . basename($filename);
}