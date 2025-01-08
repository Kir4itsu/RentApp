<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $_POST['username'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $_POST['role'];
                $nama_lengkap = $_POST['nama_lengkap'];
                $email = $_POST['email'];
                $no_telp = $_POST['no_telp'];

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap, email, no_telp) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password, $role, $nama_lengkap, $email, $no_telp]);
                    $_SESSION['success'] = "User berhasil ditambahkan";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                break;

            case 'edit':
                $id = $_POST['id'];
                $nama_lengkap = $_POST['nama_lengkap'];
                $email = $_POST['email'];
                $no_telp = $_POST['no_telp'];
                $role = $_POST['role'];

                try {
                    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, no_telp = ?, role = ? WHERE id = ?");
                    $stmt->execute([$nama_lengkap, $email, $no_telp, $role, $id]);
                    $_SESSION['success'] = "User berhasil diupdate";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                break;

            case 'delete':
                $id = $_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = "User berhasil dihapus";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                break;
        }
        header('Location: users.php');
        exit();
    }
}

// Fetch users with search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = '';
$params = [];
if ($search) {
    $where = " WHERE username LIKE ? OR nama_lengkap LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Count total records
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users" . $where);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Fetch records for current page - Fixed the LIMIT and OFFSET syntax
$query = "SELECT * FROM users" . $where . " ORDER BY created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Rental Aksesoris Komputer</title>
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
                <a href="aksesoris.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-desktop mr-3"></i>
                    Aksesoris
                </a>
                <a href="rental.php" class="flex items-center py-2 px-8 text-gray-600 hover:bg-gray-200 hover:text-gray-700 border-r-4 border-transparent hover:border-gray-700">
                    <i class="fas fa-clipboard-list mr-3"></i>
                    Rental
                </a>
                <a href="users.php" class="flex items-center py-2 px-8 bg-gray-200 text-gray-700 border-r-4 border-gray-700">
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
                <h1 class="text-2xl font-semibold">Manajemen User</h1>
                <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Tambah User
                </button>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form class="flex gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Cari username, nama, atau email..." 
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Lengkap</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Telp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($user['no_telp']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $user['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_records); ?> 
                            of <?php echo $total_records; ?> entries
                        </div>
                        <div class="flex gap-2">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-xl font-semibold mb-4">Tambah User</h2>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input type="text" name="username" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_lengkap">
                        Nama Lengkap
                    </label>
                    <input type="text" name="nama_lengkap" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input type="email" name="email" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="no_telp">
                        No. Telp
                    </label>
                    <input type="text" name="no_telp"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                        Role
                    </label>
                    <select name="role" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeAddModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        Batal
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-xl font-semibold mb-4">Edit User</h2>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_nama_lengkap">
                        Nama Lengkap
                    </label>
                    <input type="text" name="nama_lengkap" id="edit_nama_lengkap" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_email">
                        Email
                    </label>
                    <input type="email" name="email" id="edit_email" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_no_telp">
                        No. Telp
                    </label>
                    <input type="text" name="no_telp" id="edit_no_telp"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_role">
                        Role
                    </label>
                    <select name="role" id="edit_role" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeEditModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        Batal
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Form (Hidden) -->
    <form id="deleteForm" action="users.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div id="successAlert" class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div id="errorAlert" class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('flex');
            document.getElementById('addModal').classList.add('hidden');
        }

        function openEditModal(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_no_telp').value = user.no_telp;
            document.getElementById('edit_role').value = user.role;

            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('flex');
            document.getElementById('editModal').classList.add('hidden');
        }

        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Auto-hide alerts after 3 seconds
        setTimeout(() => {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (successAlert) {
                successAlert.style.display = 'none';
            }
            if (errorAlert) {
                errorAlert.style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>