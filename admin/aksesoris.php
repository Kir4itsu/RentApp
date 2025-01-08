<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Helper function untuk upload gambar
function uploadImage($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $filesize = $file['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validasi
    if (!in_array($ext, $allowed)) {
        return null;
    }
    
    // Validasi ukuran (max 5MB)
    if ($filesize > 5 * 1024 * 1024) {
        return null;
    }
    
    // Buat direktori uploads jika belum ada
    $upload_dir = PROJECT_ROOT . '/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $newname = 'product_' . uniqid() . '_' . time() . '.' . $ext;
    $destination = $upload_dir . $newname;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $newname; // Return relative path untuk database
    }
    
    return null;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $gambar_path = null;
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $gambar_path = uploadImage($_FILES['gambar']);
                }
                
                $stmt = $pdo->prepare("INSERT INTO aksesoris (nama_aksesoris, kategori, harga_sewa, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nama_aksesoris'],
                    $_POST['kategori'],
                    $_POST['harga_sewa'],
                    $_POST['stok'],
                    $_POST['deskripsi'],
                    $gambar_path
                ]);
                break;
                
            case 'update':
                $gambar_path = null;
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    // Delete old image if exists
                    $stmt = $pdo->prepare("SELECT gambar FROM aksesoris WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $old_image = $stmt->fetchColumn();
                    
                    if ($old_image && file_exists(PROJECT_ROOT . '/' . $old_image)) {
                        unlink(PROJECT_ROOT . '/' . $old_image);
                    }
                    
                    $gambar_path = uploadImage($_FILES['gambar']);
                }
                
                if ($gambar_path) {
                    $stmt = $pdo->prepare("UPDATE aksesoris SET nama_aksesoris = ?, kategori = ?, harga_sewa = ?, stok = ?, deskripsi = ?, gambar = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nama_aksesoris'],
                        $_POST['kategori'],
                        $_POST['harga_sewa'],
                        $_POST['stok'],
                        $_POST['deskripsi'],
                        $gambar_path,
                        $_POST['id']
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE aksesoris SET nama_aksesoris = ?, kategori = ?, harga_sewa = ?, stok = ?, deskripsi = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nama_aksesoris'],
                        $_POST['kategori'],
                        $_POST['harga_sewa'],
                        $_POST['stok'],
                        $_POST['deskripsi'],
                        $_POST['id']
                    ]);
                }
                break;
                
            case 'delete':
                // Delete image file if exists
                $stmt = $pdo->prepare("SELECT gambar FROM aksesoris WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $image = $stmt->fetchColumn();
                
                if ($image && file_exists(PROJECT_ROOT . '/' . $image)) {
                    unlink(PROJECT_ROOT . '/' . $image);
                }
                
                $stmt = $pdo->prepare("DELETE FROM aksesoris WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header('Location: aksesoris.php');
        exit();
    }
}

// Get all accessories
$stmt = $pdo->query("SELECT * FROM aksesoris ORDER BY created_at DESC");
$aksesoris = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Aksesoris - Rental Aksesoris Komputer</title>
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
                <a href="dashboard.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-home mr-3"></i>
                    Dashboard
                </a>
                <a href="aksesoris.php" class="flex items-center py-2 px-8 bg-gray-200 text-gray-700 border-r-4 border-gray-700">
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
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Manajemen Aksesoris</h1>
                <button onclick="openModal('createModal')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Tambah Aksesoris
                </button>
            </div>

            <!-- Accessories Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Aksesoris</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Sewa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($aksesoris as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($item['nama_aksesoris']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['kategori']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($item['harga_sewa'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['stok']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $item['status'] == 'Tersedia' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteAksesoris(<?php echo $item['id']; ?>)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto">
        <div class="relative my-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Tambah Aksesoris</h3>
                <button onclick="closeModal('createModal')" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="max-h-[80vh] overflow-y-auto px-2">
                <input type="hidden" name="action" value="create">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Aksesoris</label>
                    <input type="text" name="nama_aksesoris" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                    <select name="kategori" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="Mouse">Mouse</option>
                        <option value="Keyboard">Keyboard</option>
                        <option value="Headset">Headset</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Webcam">Webcam</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Harga Sewa</label>
                    <input type="number" name="harga_sewa" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Stok</label>
                    <input type="number" name="stok" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi</label>
                    <textarea name="deskripsi" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Gambar</label>
                    <input type="file" name="gambar" accept="image/*" 
                        onchange="previewImage(this, document.getElementById('create_image_preview'))" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <div id="create_image_preview" class="mt-2"></div>
                </div>
                <div class="sticky bottom-0 bg-white pt-4 pb-2">
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto">
        <div class="relative my-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Edit Aksesoris</h3>
                <button onclick="closeModal('editModal')" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="max-h-[80vh] overflow-y-auto px-2">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Aksesoris</label>
                    <input type="text" name="nama_aksesoris" id="edit_nama_aksesoris" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                    <select name="kategori" id="edit_kategori" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="Mouse">Mouse</option>
                        <option value="Keyboard">Keyboard</option>
                        <option value="Headset">Headset</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Webcam">Webcam</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Harga Sewa</label>
                    <input type="number" name="harga_sewa" id="edit_harga_sewa" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Stok</label>
                    <input type="number" name="stok" id="edit_stok" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Gambar</label>
                    <input type="file" name="gambar" accept="image/*" 
                        onchange="previewImage(this, document.getElementById('edit_image_preview'))" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <div id="current_image"></div>
                    <div id="edit_image_preview"></div>
                </div>
                <div class="sticky bottom-0 bg-white pt-4 pb-2">
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Konfirmasi Hapus</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus aksesoris ini? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="flex justify-center gap-4 mt-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="button" onclick="closeModal('deleteModal')" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                            Batal
                        </button>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Function to open modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Function to open edit modal and populate data
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_nama_aksesoris').value = item.nama_aksesoris;
            document.getElementById('edit_kategori').value = item.kategori;
            document.getElementById('edit_harga_sewa').value = item.harga_sewa;
            document.getElementById('edit_stok').value = item.stok;
            document.getElementById('edit_deskripsi').value = item.deskripsi;

            // Display current image if exists
            const currentImageDiv = document.getElementById('current_image');
            if (item.gambar) {
                const imgSrc = item.gambar + '?t=' + new Date().getTime();
                currentImageDiv.innerHTML = `
                    <div class="mt-2">
                        <p class="text-sm text-gray-600 mb-2">Gambar Saat Ini:</p>
                        <img src="${imgSrc}" 
                            alt="Current Image" 
                            class="w-full max-w-xs h-48 object-cover rounded-lg shadow-sm"
                            onerror="this.src='path/to/placeholder.jpg';">
                    </div>
                `;
            } else {
                currentImageDiv.innerHTML = `
                    <div class="mt-2">
                        <div class="w-full max-w-xs h-48 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text- 4xl"></i>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">Belum ada gambar</p>
                    </div>
                `;
            }
            
            openModal('editModal');
        }

        function previewImage(input, previewElement) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewElement.innerHTML = `
                        <div class="mt-2">
                            <p class="text-sm text-gray-600 mb-2">Preview:</p>
                            <img src="${e.target.result}" 
                                alt="Image Preview" 
                                class="w-full max-w-xs h-48 object-cover rounded-lg shadow-sm">
                        </div>
                    `;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Function to handle delete confirmation
        function deleteAksesoris(id) {
            document.getElementById('delete_id').value = id;
            openModal('deleteModal');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['createModal', 'editModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    closeModal(modalId);
                }
            });
        }
    </script>
</body>
</html>