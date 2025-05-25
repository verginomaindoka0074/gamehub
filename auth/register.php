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
    <style>
        /* style.css */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f8;
    height: 100vh;
    position: relative;
}

h2 {
    margin-bottom: 25px;
    color: #333;
    text-align: center;
    font-weight: 600;
}

/* Container untuk form, posisikan di tengah layar */
.register-container {
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

/* Pesan error */
.error-msg {
    color: #c0392b;
    background-color: #f8d7da;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 14px;
    border: 1px solid #f5c6cb;
}

/* Form styling */
form {
    display: flex;
    flex-direction: column;
}

label, /* Kalau mau pakai label, bisa ditambahkan */
p {
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

p {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #555;
}

    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>

        <!-- Tampilkan pesan error jika ada -->
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Register -->
        <form method="post" action="register.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password">

            <label for="confirm_password">Konfirmasi Password:</label>
            <input type="password" id="confirm_password" name="confirm_password">

            <input type="submit" value="Daftar">
        </form>

        <p><a href="/gamehub/auth/login.php">Sudah punya akun? Login</a></p>
    </div>
</body>
</html>
