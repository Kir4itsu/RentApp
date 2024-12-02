<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// Function to generate a secure reset token
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

// Function to send reset email
function sendResetEmail($email, $reset_link) {
    global $pdo;
    
    // Fetch user details to personalize email
    $stmt = $pdo->prepare("SELECT nama_lengkap FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    $mail = new PHPMailer(true);
    try {
        // Server settings (using localhost SMTP for development)
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = false;
        $mail->Port       = 1025; // Recommended to use Mailhog or Mailtrap for testing

        // Recipients
        $mail->setFrom('noreply@rentalaccessories.com', 'Rental Aksesoris');
        $mail->addAddress($email, $user['nama_lengkap'] ?? 'Pengguna');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Akun Rental Aksesoris';
        $mail->Body    = "
            <h2>Halo " . htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna') . ",</h2>
            <p>Anda menerima email ini karena ada permintaan reset password untuk akun Anda.</p>
            <p>Klik link berikut untuk mereset password Anda:</p>
            <p><a href='{$reset_link}'>Reset Password</a></p>
            <p>Link ini akan kedaluwarsa dalam 1 jam.</p>
            <p>Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Email send error: " . $mail->ErrorInfo);
        return false;
    }
}

// Step 1: Request Reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error = "Format email tidak valid!";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate reset token
            $reset_token = generateResetToken();
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            $stmt->execute([$reset_token, $token_expiry, $email]);

            // Create reset link
            $reset_link = BASE_URL . '/auth/reset-password.php?token=' . $reset_token;

            // Send reset email
            if (sendResetEmail($email, $reset_link)) {
                $success = "Email reset password telah dikirim. Silakan periksa email Anda.";
            } else {
                $error = "Gagal mengirim email. Silakan hubungi administrator.";
            }
        } else {
            $error = "Email tidak terdaftar dalam sistem!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Rental Aksesoris Komputer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Lupa Password</h1>
            
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

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Masukkan email terdaftar">
                </div>

                <button type="submit" name="request_reset"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Kirim Link Reset
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="login.php" class="text-blue-500 hover:text-blue-700">Kembali ke Login</a>
            </div>
        </div>
    </div>
</body>
</html>