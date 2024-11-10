<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Mengambil statistik
$stmt = $pdo->query("SELECT COUNT(*) as total_aksesoris FROM aksesoris");
$total_aksesoris = $stmt->fetch()['total_aksesoris'];

$stmt = $pdo->query("SELECT COUNT(*) as total_rental FROM rental");
$total_rental = $stmt->fetch()['total_rental'];

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['total_users'];

// Mengambil daftar rental terbaru
$stmt = $pdo->query("
    SELECT r.*, u.username, a.nama_aksesoris 
    FROM rental r 
    JOIN users u ON r.user_id = u.id 
    JOIN aksesoris a ON r.aksesoris_id = a.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$rental_terbaru = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rental Aksesoris Komputer</title>
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
                        <span class="text-xl font-bold">Admin Panel</span>
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

    <!-- Sidebar & Content -->
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg h-screen fixed">
            <nav class="mt-10">
                <a href="dashboard.php" class="flex items-center py-2 px-8 bg-gray-200 text-gray-700 border-r-4 border-gray-700">
                    <i class="fas fa-home mr-3"></i>
                    Dashboard
                </a>
                <a href="aksesoris.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-desktop mr-3"></i>
                    Aksesoris
                </a>
                <a href="rental.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-clipboard-list mr-3"></i>
                    Rental
                </a>
                <a href="users.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
                <a href="laporan.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Laporan
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 flex-1 p-8">
            <h1 class="text-2xl font-semibold mb-6">Dashboard</h1>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Aksesoris -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-desktop text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total Aksesoris</h2>
                            <p class="text-2xl font-semibold"><?php echo $total_aksesoris; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Rental -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-clipboard-list text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total Rental</h2>
                            <p class="text-2xl font-semibold"><?php echo $total_rental; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total Users</h2>
                            <p class="text-2xl font-semibold"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Pendapatan -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                            <i class="fas fa-money-bill text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total Pendapatan</h2>
                            <p class="text-2xl font-semibold">
                                <?php
                                $stmt = $pdo->query("SELECT SUM(total_harga) as total FROM rental WHERE status = 'Selesai'");
                                $total_pendapatan = $stmt->fetch()['total'];
                                echo 'Rp ' . number_format($total_pendapatan, 0, ',', '.');
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Rentals Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Rental Terbaru</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksesoris</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($rental_terbaru as $rental): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($rental['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($rental['nama_aksesoris']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($rental['tanggal_mulai'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($rental['tanggal_selesai'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($rental['total_harga'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html