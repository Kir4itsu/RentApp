<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan memiliki role user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Mengambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission untuk update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    $success = [];

    // Update informasi dasar
    if (!empty($nama_lengkap) && !empty($email) && !empty($no_telp)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, no_telp = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $email, $no_telp, $_SESSION['user_id']]);
            $success[] = "Informasi profil berhasil diperbarui!";
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $errors[] = "Email tersebut sudah digunakan!";
            } else {
                $errors[] = "Terjadi kesalahan saat memperbarui profil.";
            }
        }
    }

    // Update password jika diisi
    if (!empty($current_password) && !empty($new_password)) {
        // Verifikasi password lama
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();

        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $success[] = "Password berhasil diperbarui!";
            } else {
                $errors[] = "Password baru dan konfirmasi password tidak cocok!";
            }
        } else {
            $errors[] = "Password saat ini tidak sesuai!";
        }
    }

    // Refresh user data setelah update
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Rental Aksesoris Komputer</title>
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
                        <a href="profile.php" class="border-b-2 border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 text-sm font-medium">
                            Profile
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
        <!-- Profile Section -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="max-w-3xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Profile Settings</h1>

                    <!-- Alert Messages -->
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline"><?php echo $error; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <?php foreach ($success as $message): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline"><?php echo $message; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Profile Form -->
                    <form method="POST" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Dasar</h2>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           disabled>
                                </div>

                                <div>
                                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" id="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="no_telp" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                                    <input type="tel" name="no_telp" id="no_telp" value="<?php echo htmlspecialchars($user['no_telp']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Ubah Password</h2>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                                    <input type="password" name="current_password" id="current_password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                    <input type="password" name="new_password" id="new_password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" id="confirm_password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>

                    <!-- Account Info -->
                    <div class="mt-8 bg-gray-50 p-6 rounded-lg shadow-sm">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Akun</h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Tanggal Bergabung</p>
                                <p class="font-medium"><?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Status Akun</p>
                                <p class="font-medium">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>