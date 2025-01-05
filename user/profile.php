<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Buat folder profiles jika belum ada
$upload_path = PROJECT_ROOT . '/uploads/profiles/';
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// Mengambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission untuk update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $no_telp = $_POST['no_telp'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    $success = [];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            // Nama file baru dengan format: profile_userid_timestamp.extension
            $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $filetype;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path . $new_filename)) {
                // Delete old profile picture if exists
                if ($user['profile_picture'] && file_exists($upload_path . $user['profile_picture'])) {
                    unlink($upload_path . $user['profile_picture']);
                }
                
                // Update database with new profile picture
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$new_filename, $_SESSION['user_id']]);
                $success[] = "Foto profil berhasil diperbarui!";
            } else {
                $errors[] = "Gagal mengupload foto profil.";
            }
        } else {
            $errors[] = "Format file tidak didukung. Gunakan format: jpg, jpeg, png, atau gif.";
        }
    }

    // Update informasi dasar
    if (!empty($nama_lengkap) && !empty($no_telp)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, no_telp = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $no_telp, $_SESSION['user_id']]);
            $success[] = "Informasi profil berhasil diperbarui!";
        } catch (PDOException $e) {
            $errors[] = "Terjadi kesalahan saat memperbarui profil.";
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

                    <!-- Profile Picture Section -->
                    <div class="mb-8 text-center">
                        <div class="mb-4">
                            <?php if ($user['profile_picture'] && file_exists($upload_path . $user['profile_picture'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/profiles/' . $user['profile_picture']; ?>" 
                                     alt="Profile Picture" 
                                     class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full mx-auto bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-user text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div class="flex items-center justify-center">
                                <label for="profile_picture" class="cursor-pointer bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-camera mr-2"></i> Upload Foto
                                </label>
                                <input type="file" id="profile_picture" name="profile_picture" class="hidden" 
                                       accept=".jpg,.jpeg,.png,.gif" onchange="form.submit()">
                            </div>
                        </form>
                    </div>

                    <!-- Profile Form -->
                    <form method="POST" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Dasar</h2>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Username</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 cursor-not-allowed"
                                           disabled>
                                    <p class="mt-1 text-sm text-gray-500">Username tidak dapat diubah</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 cursor-not-allowed"
                                           disabled>
                                    <p class="mt-1 text-sm text-gray-500">Email tidak dapat diubah</p>
                                </div>

                                <div>
                                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" id="nama_lengkap" 
                                           value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="no_telp" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                                    <input type="tel" name="no_telp" id="no_telp" 
                                           value="<?php echo htmlspecialchars($user['no_telp']); ?>" 
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