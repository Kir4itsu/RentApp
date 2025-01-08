<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';
$token_valid = false;

// Debug log untuk melihat token yang diterima
error_log("Received token from URL: " . ($_GET['token'] ?? 'No token'));

// Validate reset token
if (isset($_GET['token'])) {
    $reset_token = trim($_GET['token']);
    
    try {
        // Tambahkan log untuk SQL query
        $sql = "SELECT id, email, reset_token, reset_token_expiry FROM users WHERE reset_token = ?";
        error_log("Executing SQL: " . $sql);
        error_log("With token: " . $reset_token);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reset_token]);
        $user = $stmt->fetch();
        
        // Log hasil query
        error_log("Query result: " . print_r($user, true));
        
        if ($user) {
            // Periksa apakah token sudah expired
            if (strtotime($user['reset_token_expiry']) > time()) {
                $token_valid = true;
                error_log("Token is valid and not expired");
            } else {
                $error = "Token reset password sudah kedaluwarsa.";
                error_log("Token is expired");
            }
        } else {
            $error = "Token reset password tidak valid.";
            error_log("Token not found in database");
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Semua field harus diisi!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password harus minimal 6 karakter!";
    } else {
        try {
            // Verify token again
            $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password and clear reset token
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user['id']])) {
                    $success = "Password berhasil diubah. Anda akan dialihkan ke halaman login dalam 3 detik.";
                    header("refresh:3;url=login.php");
                } else {
                    $error = "Gagal mengupdate password.";
                }
            } else {
                $error = "Token reset password tidak valid.";
            }
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "Terjadi kesalahan saat mengubah password. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Rental Aksesoris Komputer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Reset Password</h1>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Password Baru</label>
                        <input type="password" name="new_password" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               minlength="6">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               minlength="6">
                    </div>

                    <button type="submit" name="reset_password"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                        Reset Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <a href="lupa-password.php" class="text-blue-500 hover:text-blue-700">Minta Token Baru</a>
                    <div class="mt-4">
                        <a href="login.php" class="text-blue-500 hover:text-blue-700">Kembali ke Login</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>