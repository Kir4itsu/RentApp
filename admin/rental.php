<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle rental status updates
// Handle rental status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $rental_id = filter_var($_POST['rental_id'], FILTER_SANITIZE_NUMBER_INT);
        $new_status = filter_var($_POST['new_status'], FILTER_SANITIZE_STRING);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get the aksesoris_id for this rental
        $stmt = $pdo->prepare("SELECT aksesoris_id FROM rental WHERE id = ?");
        $stmt->execute([$rental_id]);
        $aksesoris_id = $stmt->fetchColumn();
        
        // Update rental status
        $stmt = $pdo->prepare("UPDATE rental SET status = ? WHERE id = ?");
        $success = $stmt->execute([$new_status, $rental_id]);
        
        // Update aksesoris status based on rental status
        if ($success) {
            $aksesoris_status = '';
            switch ($new_status) {
                case 'Disetujui':
                    $aksesoris_status = 'Disewa';
                    break;
                case 'Ditolak':
                case 'Selesai':
                case 'Dibatalkan':
                    $aksesoris_status = 'Tersedia';
                    break;
            }
            
            if ($aksesoris_status) {
                $stmt = $pdo->prepare("UPDATE aksesoris SET status = ? WHERE id = ?");
                $stmt->execute([$aksesoris_status, $aksesoris_id]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return JSON response for AJAX
        if ($success) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Status berhasil diperbarui',
                'new_status' => $new_status
            ]);
            exit;
        } else {
            throw new Exception('Gagal mengupdate status');
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Fetch rentals with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR a.nama_aksesoris LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Modified query without using LIMIT/OFFSET parameters
$query = "
    SELECT r.*, u.username, a.nama_aksesoris 
    FROM rental r 
    JOIN users u ON r.user_id = u.id 
    JOIN aksesoris a ON r.aksesoris_id = a.id 
    {$where_clause}
    ORDER BY r.created_at DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

// Get total pages
$count_query = "
    SELECT COUNT(*) as total 
    FROM rental r 
    JOIN users u ON r.user_id = u.id 
    JOIN aksesoris a ON r.aksesoris_id = a.id 
    {$where_clause}
";

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Management - Computer Accessories Rental</title>
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
                <a href="rental.php" class="flex items-center py-2 px-8 bg-gray-200 text-gray-700 border-r-4 border-gray-700">
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
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Rental Management</h1>
                <div class="flex space-x-4">
                    <form class="flex space-x-4">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search rentals..." 
                               class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                               <select name="status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Status</option>
                                    <option value="Menunggu" <?php echo $status_filter == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="Disetujui" <?php echo $status_filter == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="Ditolak" <?php echo $status_filter == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                    <option value="Selesai" <?php echo $status_filter == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="Dibatalkan" <?php echo $status_filter == 'Dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </form>
                </div>
            </div>

            <!-- Rentals Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksesoris</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($rentals as $rental): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($rental['username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($rental['nama_aksesoris']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($rental['tanggal_mulai'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($rental['tanggal_selesai'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($rental['total_harga'], 0, ',', '.'); ?></td>
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($rental['status'] == 'Menunggu'): ?>
                                    <button 
                                        onclick="updateStatus(<?php echo $rental['id']; ?>, 'Disetujui')"
                                        class="text-green-600 hover:text-green-900 mr-3 approve-btn">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button 
                                        onclick="updateStatus(<?php echo $rental['id']; ?>, 'Ditolak')"
                                        class="text-red-600 hover:text-red-900 reject-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($rental['status'] == 'Disetujui'): ?>
                                    <button 
                                        onclick="updateStatus(<?php echo $rental['id']; ?>, 'Selesai')"
                                        class="text-blue-600 hover:text-blue-900 complete-btn">
                                        <i class="fas fa-check-circle"></i> Complete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page == $i ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <!-- Sweet Alert untuk notifikasi -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
            function updateStatus(rentalId, newStatus) {
                let message = '';
                switch(newStatus) {
                    case 'Disetujui':
                        message = 'Apakah anda yakin ingin menyetujui rental ini?';
                        break;
                    case 'Ditolak':
                        message = 'Apakah anda yakin ingin menolak rental ini?';
                        break;
                    case 'Selesai':
                        message = 'Apakah anda yakin rental ini telah selesai?';
                        break;
                }

                Swal.fire({
                    title: 'Konfirmasi',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create form data
                        const formData = new FormData();
                        formData.append('rental_id', rentalId);
                        formData.append('new_status', newStatus);
                        formData.append('update_status', '1');

                        // Send AJAX request
                        fetch('rental.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: 'Status rental berhasil diperbarui',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Reload page to show updated data
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'Terjadi kesalahan');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.message
                            });
                        });
                    }
                });
            }

        // Tampilkan alert jika ada parameter status di URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            
            if (status && message) {
                Swal.fire({
                    icon: status === 'success' ? 'success' : 'error',
                    title: status === 'success' ? 'Berhasil' : 'Gagal',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
    </script>
</body>
</html>