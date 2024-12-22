<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Initialize error and success message variables
$error = '';
$success = '';

// Ensure logs directory exists with proper permissions
$log_dir = PROJECT_ROOT . '/logs/';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Pastikan file log dapat ditulis
$log_files = [
    $log_dir . 'email_errors.log',
    $log_dir . 'email_send_failure.log',
    $log_dir . 'email_debug.log'
];

foreach ($log_files as $file) {
    if (!file_exists($file)) {
        touch($file);
        chmod($file, 0666);
    }
}

// Function to generate a secure reset token
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

// Function to send reset email
function sendResetEmail($email, $reset_link) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT nama_lengkap FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        $mail = new PHPMailer(true);
        
        // Extensive logging
        error_log("Starting email send process for: " . $email);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kira1170@gmail.com'; // Ganti dengan email mu
        $mail->Password   = 'avdj xkrf lasf mmqm'; // Ganti dengan App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Debugging
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug ($level): $str");
        };
        
        // Increase timeout
        $mail->Timeout = 60; // 60 seconds
        
        // Relaxed SSL checking
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Email details
        $mail->setFrom('noreply@rentalaccessories.com', 'Rental Aksesoris');
        $mail->addAddress($email, $user['nama_lengkap'] ?? 'Pengguna');

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Akun Rental Aksesoris';
        $mail->Body = "
            <h2>Halo " . htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna') . ",</h2>
            <p>Anda menerima email ini karena ada permintaan reset password untuk akun Anda.</p>
            <p>Klik link berikut untuk mereset password Anda:</p>
            <p><a href='{$reset_link}'>Reset Password</a></p>
            <p>Link ini akan kedaluwarsa dalam 1 jam.</p>
            <p>Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
        ";

        // Attempt to send
        if($mail->send()) {
            error_log("Email sent successfully to: " . $email);
            return true;
        }
        
        // If sending fails
        error_log("Email sending failed. Error: " . $mail->ErrorInfo);
        return false;
    } catch (Exception $e) {
        // Comprehensive error logging
        $error_log_path = PROJECT_ROOT . '/logs/email_errors.log';
        $error_message = date('[Y-m-d H:i:s] ') . 
            "Full Email Error for {$email}: " . $e->getMessage() . 
            "\nTrace: " . $e->getTraceAsString() . PHP_EOL;
        
        file_put_contents($error_log_path, $error_message, FILE_APPEND);
        error_log($error_message);
        
        return false;
    }
}

// Step 1: Request Reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    // Validate email
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error = "Format email tidak valid!";
    } else {
        try {
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
                    // Log additional failure information
                    $failure_log_path = PROJECT_ROOT . '/logs/email_send_failure.log';
                    $failure_message = date('[Y-m-d H:i:s] ') . 
                        "Email send failed for: {$email}\n";
                    
                    file_put_contents($failure_log_path, $failure_message, FILE_APPEND);
                    
                    $error = "Gagal mengirim email. Silakan hubungi administrator dan periksa log.";
                }
            } else {
                $error = "Email tidak terdaftar dalam sistem!";
            }
        } catch (PDOException $e) {
            // Database error logging
            $db_error_log_path = PROJECT_ROOT . '/logs/db_errors.log';
            $db_error_message = date('[Y-m-d H:i:s] ') . 
                "Database error: " . $e->getMessage() . PHP_EOL;
            
            file_put_contents($db_error_log_path, $db_error_message, FILE_APPEND);
            error_log($db_error_message);
            
            $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
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
