<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect ke halaman utama
if (isset($_SESSION['username'])) {
    header("Location: https://indgamehub.rf.gd/index.php");
    exit;
}

// Sertakan koneksi database dan navbar
include('../includes/database.php'); /** @var mysqli $conn */
include('../includes/navbar.php');

// Inisialisasi variabel
$error = '';
$username = '';

// Ambil pesan error dari session setelah redirect, lalu hapus
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Ambil username yang diketik sebelumnya jika ada
if (isset($_SESSION['old_username'])) {
    $username = $_SESSION['old_username'];
    unset($_SESSION['old_username']);
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST["password"] ?? '';

    // Validasi input kosong
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username dan password wajib diisi.";
        $_SESSION['old_username'] = $username;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Cek akun di database
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Jika user ditemukan
    if ($row = mysqli_fetch_assoc($result)) {
        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Simpan data user di session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: https://indgamehub.rf.gd/index.php");
            exit;
        } else {
            $_SESSION['error'] = "Password salah.";
        }
    } else {
        $_SESSION['error'] = "Akun tidak ditemukan.";
    }

    // Simpan username untuk prefilling dan redirect
    $_SESSION['old_username'] = $username;
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GameHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/login_style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-gamepad"></i>
            </div>
            <h2>Welcome Back</h2>
            <p class="login-subtitle">Sign in to your GameHub account</p>
        </div>

        <!-- Tampilkan pesan error jika ada -->
        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="post" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-container">
                    <i class="fas fa-user input-icon"></i>
                    <input id="username" type="text" autocomplete="off" name="username" value="<?= htmlspecialchars($username ?? '') ?>" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-container">
                    <i class="fas fa-lock input-icon"></i>
                    <input id="password" type="password" autocomplete="off" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <input type="submit" value="Sign In" id="submitBtn">
        </form>

        <div class="register-link">
            <p>Don't have an account?</p>
            <a href="https://indgamehub.rf.gd/auth/register.php">
                <i class="fas fa-user-plus"></i>
                Create Account
            </a>
        </div>
    </div>

    <script>
        // Add loading state on form submission
        document.getElementById('loginForm').addEventListener('submit', function() {
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            
            form.classList.add('loading');
            submitBtn.value = 'Signing In...';
        });

        // Add enter key support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Focus first input on load
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>