<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Filter date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');   // Last day of current month

// Get rental statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_rental,
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'Selesai' THEN total_harga ELSE 0 END) as total_pendapatan
    FROM rental
    WHERE tanggal_mulai BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats = $stmt->fetch();

// Get popular items
$stmt = $pdo->prepare("
    SELECT 
        a.nama_aksesoris,
        COUNT(*) as total_rental,
        SUM(r.total_harga) as total_pendapatan
    FROM rental r
    JOIN aksesoris a ON r.aksesoris_id = a.id
    WHERE r.tanggal_mulai BETWEEN ? AND ?
    GROUP BY a.id, a.nama_aksesoris
    ORDER BY total_rental DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$popular_items = $stmt->fetchAll();

// Get monthly revenue
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(tanggal_mulai, '%Y-%m') as bulan,
        COUNT(*) as total_rental,
        SUM(total_harga) as pendapatan
    FROM rental
    WHERE status = 'Selesai'
    GROUP BY DATE_FORMAT(tanggal_mulai, '%Y-%m')
    ORDER BY bulan DESC
    LIMIT 12
");
$stmt->execute();
$monthly_revenue = $stmt->fetchAll();

// Generate data for charts
$monthly_labels = array_column(array_reverse($monthly_revenue), 'bulan');
$monthly_data = array_column(array_reverse($monthly_revenue), 'pendapatan');
$popular_labels = array_column($popular_items, 'nama_aksesoris');
$popular_data = array_column($popular_items, 'total_rental');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Rental Aksesoris Komputer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
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
                <a href="laporan.php" class="flex items-center py-2 px-8 bg-gray-200 text-gray-700 border-r-4 border-gray-700">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Laporan
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 flex-1 p-8">
            <h1 class="text-2xl font-semibold mb-6">Laporan</h1>

            <!-- Date Filter -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form class="flex gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                               class="px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                               class="px-4 py-2 border rounded-lg">
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="export_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </a>
                    <a href="export_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </a>
                </form>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- Total Rental -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-clipboard-list text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total Rental</h2>
                            <p class="text-2xl font-semibold"><?php echo number_format($stats['total_rental']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Pendapatan -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-money-bill text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total Pendapatan</h2>
                            <p class="text-2xl font-semibold">Rp <?php echo number_format($stats['total_pendapatan']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-chart-pie text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Distribusi Status</h2>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-yellow-600">Menunggu</span>
                            <span class="font-semibold"><?php echo $stats['menunggu']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-blue-600">Disetujui</span>
                            <span class="font-semibold"><?php echo $stats['disetujui']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600">Ditolak</span>
                            <span class="font-semibold"><?php echo $stats['ditolak']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-600">Selesai</span>
                            <span class="font-semibold"><?php echo $stats['selesai']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Revenue Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Pendapatan Bulanan</h2>
                    <canvas id="revenueChart"></canvas>
                </div>

                <!-- Popular Items Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Aksesoris Terpopuler</h2>
                    <canvas id="popularItemsChart"></canvas>
                </div>
            </div>

            <!-- Popular Items Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Detail Aksesoris Terpopuler</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Aksesoris</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Rental</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_items as $item): ?>
                            <tr class="border-b">
                                <td class="px-6 py-4"><?php echo htmlspecialchars($item['nama_aksesoris']); ?></td>
                                <td class="px-6 py-4"><?php echo number_format($item['total_rental']); ?></td>
                                <td class="px-6 py-4">Rp <?php echo number_format($item['total_pendapatan']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthly_labels); ?>,
                datasets: [{
                    label: 'Pendapatan Bulanan',
                    data: <?php echo json_encode($monthly_data); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Popular Items Chart
        new Chart(document.getElementById('popularItemsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($popular_labels); ?>,
                datasets: [{
                    label: 'Total Rental',
                    data: <?php echo json_encode($popular_data); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>