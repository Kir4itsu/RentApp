<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Mengambil data aksesoris yang akan dirental
if (!isset($_GET['id'])) {
    header('Location: katalog.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM aksesoris WHERE id = ? AND status = 'Tersedia'");
$stmt->execute([$_GET['id']]);
$aksesoris = $stmt->fetch();

if (!$aksesoris) {
    header('Location: katalog.php');
    exit();
}

// Proses form rental
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    
    // Hitung jumlah hari
    $date1 = new DateTime($tanggal_mulai);
    $date2 = new DateTime($tanggal_selesai);
    $interval = $date1->diff($date2);
    $jumlah_hari = $interval->days + 1;
    
    // Hitung total harga
    $total_harga = $aksesoris['harga_sewa'] * $jumlah_hari;
    
    try {
        $pdo->beginTransaction();
        
        // Insert data rental
        $stmt = $pdo->prepare("INSERT INTO rental (user_id, aksesoris_id, tanggal_mulai, tanggal_selesai, total_harga, status) VALUES (?, ?, ?, ?, ?, 'Menunggu')");
        $stmt->execute([$_SESSION['user_id'], $aksesoris['id'], $tanggal_mulai, $tanggal_selesai, $total_harga]);
        
        // Update status aksesoris
        $stmt = $pdo->prepare("UPDATE aksesoris SET status = 'Disewa' WHERE id = ?");
        $stmt->execute([$aksesoris['id']]);
        
        $pdo->commit();
        
        header('Location: dashboard.php?success=1');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan saat memproses rental. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Rental - Rental Aksesoris Komputer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold">Rental Aksesoris</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Form Rental Aksesoris
                    </h3>

                    <?php if (isset($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold mb-2">Detail Aksesoris:</h4>
                        <p>Nama: <?php echo htmlspecialchars($aksesoris['nama_aksesoris']); ?></p>
                        <p>Kategori: <?php echo htmlspecialchars($aksesoris['kategori']); ?></p>
                        <p>Harga Sewa: Rp <?php echo number_format($aksesoris['harga_sewa'], 0, ',', '.'); ?>/hari</p>
                    </div>

                    <form action="" method="POST">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Tanggal Mulai
                                </label>
                                <input type="date" name="tanggal_mulai" required
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Tanggal Selesai
                                </label>
                                <input type="date" name="tanggal_selesai" required
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div class="flex items-center justify-end">
                                <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                    Proses Rental
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validasi tanggal
        const tanggalMulai = document.querySelector('input[name="tanggal_mulai"]');
        const tanggalSelesai = document.querySelector('input[name="tanggal_selesai"]');

        tanggalMulai.addEventListener('change', function() {
            tanggalSelesai.min = this.value;
            if (tanggalSelesai.value && tanggalSelesai.value < this.value) {
                tanggalSelesai.value = this.value;
            }
        });
    </script>
</body>
</html>