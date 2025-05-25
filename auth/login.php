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
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f8; /* warna netral sama seperti navbar */
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 30px 40px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    width: 320px;
}

h2 {
    margin-bottom: 25px;
    color: #333;
    text-align: center;
    font-weight: 600;
}

.error-msg {
    color: #c0392b;
    background-color: #f8d7da;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 14px;
    border: 1px solid #f5c6cb;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #555;
    font-size: 14px;
}

input[type="text"],
input[type="password"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
    margin-bottom: 20px;
    transition: border-color 0.2s ease-in-out;
}

input[type="text"]:focus,
input[type="password"]:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.5);
}

input[type="submit"] {
    background-color: #007bff;
    border: none;
    padding: 12px;
    color: white;
    font-weight: 600;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

input[type="submit"]:hover {
    background-color: #0056b3;
}

p {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #555;
}

p a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

p a:hover {
    text-decoration: underline;
    color: #0056b3;
}
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>

        <!-- Tampilkan pesan error jika ada -->
        <?php if (!empty($error)): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="post">
            <label for="username">Username:</label>
            <input id="username" type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>

            <label for="password">Password:</label>
            <input id="password" type="password" name="password" required>

            <input type="submit" value="Login">
        </form>

        <p><a href="/gamehub/auth/register.php">Belum punya akun? Daftar</a></p>
    </div>
</body>
</html>

