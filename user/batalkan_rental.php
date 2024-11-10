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

try {
    // Mulai transaction
    $pdo->beginTransaction();

    // Cek status rental dan kepemilikan
    $stmt = $pdo->prepare("
        SELECT status 
        FROM rental 
        WHERE id = ? AND user_id = ? AND status = 'Menunggu'
    ");
    $stmt->execute([$rental_id, $_SESSION['user_id']]);
    $rental = $stmt->fetch();

    if (!$rental) {
        throw new Exception("Rental tidak ditemukan atau tidak dapat dibatalkan");
    }

    // Update status rental menjadi Dibatalkan
    $stmt = $pdo->prepare("
        UPDATE rental 
        SET status = 'Ditolak', 
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$rental_id]);

    // Commit transaction
    $pdo->commit();

    // Set flash message
    $_SESSION['flash_message'] = "Rental berhasil dibatalkan";
    $_SESSION['flash_type'] = "success";

} catch (Exception $e) {
    // Rollback transaction jika terjadi error
    $pdo->rollBack();

    // Set flash message
    $_SESSION['flash_message'] = "Gagal membatalkan rental: " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
}

// Redirect kembali ke halaman dashboard
header('Location: dashboard.php');
exit();