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
            header("Location: /gamehub/index.php");
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
    <title>Login GameHub</title>
</head>
<body>
    <h2>Login</h2>

    <!-- Tampilkan pesan error jika ada -->
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Form Login -->
    <form method="post">
        Username:<br>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>"><br><br>

        Password:<br>
        <input type="password" name="password"><br><br>

        <input type="submit" value="Login">
    </form>

    <p><a href="/gamehub/auth/register.php">Belum punya akun? Daftar</a></p>
</body>
</html>
