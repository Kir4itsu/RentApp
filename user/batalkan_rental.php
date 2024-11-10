<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan memiliki role user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Cek apakah ada ID rental
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$rental_id = $_GET['id'];

// Mengambil detail rental untuk memastikan bahwa rental ini milik user
$stmt = $pdo->prepare("SELECT * FROM rental WHERE id = ? AND user_id = ?");
$stmt->execute([$rental_id, $_SESSION['user_id']]);
$rental = $stmt->fetch();

// Jika rental tidak ditemukan atau bukan milik user ini
if (!$rental) {
    header('Location: dashboard.php');
    exit();
}

// Proses pembatalan rental
try {
    $stmt = $pdo->prepare("DELETE FROM rental WHERE id = ?");
    $stmt->execute([$rental_id]);

    // Redirect ke dashboard dengan pesan sukses
    $_SESSION['message'] = 'Rental berhasil dibatalkan.';
    header('Location: dashboard.php');
    exit();
} catch (PDOException $e) {
    // Jika terjadi kesalahan, redirect ke dashboard dengan pesan error
    $_SESSION['error'] = 'Gagal membatalkan rental: ' . $e->getMessage();
    header('Location: dashboard.php');
    exit();
}
?>