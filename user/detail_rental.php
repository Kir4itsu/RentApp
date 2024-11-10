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

// Mengambil detail rental
$stmt = $pdo->prepare("
    SELECT r.*, a.nama_aksesoris, a.harga_sewa, a.gambar,
           u.username as nama_user
    FROM rental r 
    JOIN aksesoris a ON r.aksesoris_id = a.id
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$rental_id, $_SESSION['user_id']]);
$rental = $stmt->fetch();

// Jika rental tidak ditemukan atau bukan milik user ini
if (!$rental) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Rental - Rental Aksesoris Komputer</title>
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
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="katalog.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Katalog
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="../auth/logout.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-900">Detail Rental</h2>
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informasi Aksesoris -->
                    <div>
                        <img src="<?php echo getImageUrl($rental['gambar']); ?>" 
                             alt="<?php echo htmlspecialchars($rental['nama_aksesoris']); ?>"
                             class="w-full h-64 object-cover rounded-lg mb-4">
                        <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($rental['nama_aksesoris']); ?></h3>
                        <p class="text-gray-600">Harga Sewa: Rp <?php echo number_format($rental['harga_sewa'], 0, ',', '.'); ?>/hari</p>
                    </div>

                    <!-- Detail Rental -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h4 class="font-semibold mb-4">Informasi Rental</h4>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php
                                        switch($rental['status']) {
                                            case 'Menunggu':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Disetujui':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Ditolak':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'Selesai':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                        }
                                        ?>">
                                        <?php echo $rental['status']; ?>
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tanggal Mulai</dt>
                                <dd class="mt-1"><?php echo date('d/m/Y', strtotime($rental['tanggal_mulai'])); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tanggal Selesai</dt>
                                <dd class="mt-1"><?php echo date('d/m/Y', strtotime($rental['tanggal_selesai'])); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Durasi</dt>
                                <dd class="mt-1"><?php echo $rental['durasi']; ?> hari</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Harga</dt>
                                <dd class="mt-1 text-lg font-semibold">
                                    Rp <?php echo number_format($rental['total_harga'], 0, ',', '.'); ?>
                                </dd>
                            </div>
                            <?php if ($rental['catatan']): ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                                    <dd class="mt-1"><?php echo nl2br(htmlspecialchars($rental['catatan'])); ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>

                        <?php if ($rental['status'] == 'Menunggu'): ?>
                            <div class="mt-6">
                                <a href="batalkan_rental.php?id=<?php echo $rental['id']; ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                   onclick="return confirm('Apakah Anda yakin ingin membatalkan rental ini?')">
                                    <i class="fas fa-times mr-2"></i> Batalkan Rental
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>