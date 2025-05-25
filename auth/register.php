<?php
// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect ke halaman utama
if (isset($_SESSION['username'])) {
    header("Location: /gamehub/index.php");
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
                header("Location: /gamehub/auth/login.php");
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
    <title>Register GameHub</title>
</head>
<body>
    <h2>Register</h2>

    <!-- Tampilkan pesan error jika ada -->
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Form Register -->
    <form method="post" action="register.php">
        Username:<br>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"><br><br>

        Password:<br>
        <input type="password" name="password"><br><br>

        Konfirmasi Password:<br>
        <input type="password" name="confirm_password"><br><br>

        <input type="submit" value="Daftar">
    </form>

    <p><a href="/gamehub/auth/login.php">Sudah punya akun? Login</a></p>
</body>
</html>
