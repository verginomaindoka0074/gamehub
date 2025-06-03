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

// Ambil pesan error dari session jika ada
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Proses form register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    // Validasi input
    if (empty($username)) {
        $_SESSION['error'] = "Username wajib diisi.";
    } elseif (strlen($username) < 3) {
        $_SESSION['error'] = "Username minimal 3 karakter.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $_SESSION['error'] = "Username hanya boleh huruf, angka, dan underscore (3-20 karakter).";
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Password dan konfirmasi tidak cocok.";
    } else {
        // Cek apakah username sudah ada
        $sql_check = "SELECT id FROM users WHERE username = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $_SESSION['error'] = "Username sudah digunakan.";
        } else {
            // Simpan user baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO users (username, password, role) VALUES (?, ?, 'user')";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ss", $username, $hashed_password);

            if (mysqli_stmt_execute($stmt_insert)) {
                mysqli_stmt_close($stmt_insert);
                mysqli_stmt_close($stmt_check);
                mysqli_close($conn);
                header("Location: https://indgamehub.rf.gd/auth/login.php");
                exit;
            } else {
                $_SESSION['error'] = "Gagal registrasi. Coba lagi.";
            }

            mysqli_stmt_close($stmt_insert);
        }

        mysqli_stmt_close($stmt_check);
    }

    mysqli_close($conn);

    // Redirect ulang jika ada error
    if (!empty($_SESSION['error'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GameHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/register_style.css">
</head>
<body>    
    <div class="register-container">
        <div class="register-header">
            <div class="register-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Join GameHub</h2>
            <p class="register-subtitle">Create your gaming account today</p>
        </div>

        <!-- Tampilkan pesan error jika ada -->
        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form Register -->
        <form method="post" action="register.php" id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-container">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" autocomplete="off" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Choose a username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-container">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" autocomplete="off" name="password" placeholder="Create a strong password" required>
                </div>
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <span id="strengthText">Password strength</span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-container">
                    <i class="fas fa-shield-alt input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
            </div>

            <input type="submit" value="Create Account" id="submitBtn">
        </form>

        <div class="login-link">
            <p>Already have an account?</p>
            <a href="https://indgamehub.rf.gd/auth/login.php">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </a>
        </div>
    </div>

    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let text = 'Weak';
            let className = 'strength-weak';

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    text = 'Very Weak';
                    className = 'strength-weak';
                    break;
                case 2:
                    text = 'Weak';
                    className = 'strength-weak';
                    break;
                case 3:
                    text = 'Fair';
                    className = 'strength-fair';
                    break;
                case 4:
                    text = 'Good';
                    className = 'strength-good';
                    break;
                case 5:
                    text = 'Strong';
                    className = 'strength-strong';
                    break;
            }

            return { text, className };
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthFill.className = 'strength-fill';
                strengthText.textContent = 'Password strength';
                return;
            }

            const { text, className } = checkPasswordStrength(password);
            strengthFill.className = `strength-fill ${className}`;
            strengthText.textContent = text;
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '';
            }
        });

        // Add loading state on form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }

            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            
            form.classList.add('loading');
            submitBtn.value = 'Creating Account...';
        });

        // Focus first input on load
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>