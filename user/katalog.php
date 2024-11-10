<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Mengambil daftar aksesoris yang tersedia
$stmt = $pdo->query("SELECT * FROM aksesoris WHERE status = 'Tersedia' ORDER BY nama_aksesoris");
$aksesoris = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Aksesoris - Rental Aksesoris Komputer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navbar (sama seperti dashboard.php) -->
    <nav class="bg-white shadow-lg">
        <!-- ... (kode navbar) ... -->
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-semibold mb-6">Katalog Aksesoris</h1>

            <!-- Filter dan Search -->
            <div class="mb-6 flex justify-between items-center">
                <div class="flex space-x-4">
                    <select id="kategori" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Semua Kategori</option>
                        <option value="Mouse">Mouse</option>
                        <option value="Keyboard">Keyboard</option>
                        <option value="Headset">Headset</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Webcam">Webcam</option>
                    </select>
                </div>
                <div class="relative">
                    <input type="text" id="search" placeholder="Cari aksesoris..." 
                           class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
            </div>

            <!-- Grid Aksesoris -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($aksesoris as $item): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <?php if ($item['gambar']): ?>
                            <img src="<?php echo getImageUrl($item['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['nama_aksesoris']); ?>"
                                 class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-desktop text-4xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($item['nama_aksesoris']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-2">
                                <?php echo htmlspecialchars($item['kategori']); ?>
                            </p>
                            <p class="text-lg font-bold text-blue-600 mb-3">
                                Rp <?php echo number_format($item['harga_sewa'], 0, ',', '.'); ?>/hari
                            </p>
                            <p class="text-sm text-gray-600 mb-4">
                                <?php echo htmlspecialchars($item['deskripsi']); ?>
                            </p>
                            <a href="form_rental.php?id=<?php echo $item['id']; ?>" 
                               class="block w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center">
                                Rental Sekarang
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Implementasi filter dan search
        const kategoriSelect = document.getElementById('kategori');
        const searchInput = document.getElementById('search');
        const aksesorisList = document.querySelectorAll('.grid > div');

        function filterAksesoris() {
            const kategori = kategoriSelect.value.toLowerCase();
            const searchTerm = searchInput.value.toLowerCase();

            aksesorisList.forEach(item => {
                const itemKategori = item.querySelector('p').textContent.toLowerCase();
                const itemNama = item.querySelector('h3').textContent.toLowerCase();
                
                const matchKategori = kategori === '' || itemKategori.includes(kategori);
                const matchSearch = itemNama.includes(searchTerm);

                if (matchKategori && matchSearch) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        kategoriSelect.addEventListener('change', filterAksesoris);
        searchInput.addEventListener('input', filterAksesoris);
    </script>
</body>
</html>