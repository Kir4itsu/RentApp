<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

// Validate reset token
if (isset($_GET['token'])) {
    $reset_token = $_GET['token'];
    
    // Debug log token yang diterima
    error_log("Received token: " . $reset_token);
    
    try {
        // Check if token is valid and not expired
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$reset_token]);
        $user = $stmt->fetch();
        
        // Debug log hasil query
        error_log("User found: " . ($user ? "Yes" : "No"));
        
        if (!$user) {
            // Query untuk debug tanpa current_time
            $stmt = $pdo->prepare("SELECT reset_token, reset_token_expiry FROM users WHERE reset_token = ?");
            $stmt->execute([$reset_token]);
            $tokenInfo = $stmt->fetch();
            
            if ($tokenInfo) {
                error_log("Token expiry time: " . $tokenInfo['reset_token_expiry']);
                error_log("Current server time: " . date('Y-m-d H:i:s'));
            }
            
            $error = "Token reset password tidak valid atau sudah kedaluwarsa.";
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
            $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password and clear reset token
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);

                $success = "Password berhasil diubah. Silakan login.";
                error_log("Password reset successful for user ID: " . $user['id']);
                
                // Redirect to login page after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error = "Token reset password tidak valid atau sudah kedaluwarsa.";
            }
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "Terjadi kesalahan saat mengubah password. Silakan coba lagi.";
        }
    }
}

// Debug mode
if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    echo "<pre>";
    echo "Token received: " . htmlspecialchars($_GET['token']) . "\n";
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
        $stmt->execute([$_GET['token']]);
        $debug_user = $stmt->fetch();
        
        echo "User found: " . ($debug_user ? "Yes" : "No") . "\n";
        if ($debug_user) {
            echo "Token expiry: " . $debug_user['reset_token_expiry'] . "\n";
            echo "Current time: " . date('Y-m-d H:i:s') . "\n";
        }
    } catch (PDOException $e) {
        echo "Debug query error: " . $e->getMessage() . "\n";
    }
    echo "</pre>";
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

            <?php if (isset($user) && $user): ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                    
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
                    <p class="text-red-500 mb-4">Token reset password tidak valid.</p>
                    <a href="lupa-password.php" class="text-blue-500 hover:text-blue-700">Minta Token Baru</a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" class="text-blue-500 hover:text-blue-700">Kembali ke Login</a>
            </div>
        </div>
    </div>
</body>
</html>
