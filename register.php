<?php
session_start();
include('database.php'); /** @var mysqli $conn */

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan bersihkan input user
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validasi input
    if (empty($username)) {
        $error = "Username wajib diisi.";
    } elseif (empty($password)) {
        $error = "Password wajib diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama.";
    } else {
        // Cek apakah username sudah dipakai
        $sql_check = "SELECT id FROM users WHERE username = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "Username sudah digunakan, coba yang lain.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru ke database, role default 'user'
            $sql_insert = "INSERT INTO users (username, password, role) VALUES (?, ?, 'user')";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ss", $username, $hashed_password);

            if (mysqli_stmt_execute($stmt_insert)) {
                header("Location: index.php");
            } else {
                $error = "Terjadi kesalahan saat registrasi.";
            }
        }
        
        mysqli_stmt_close($stmt_check);
        if (isset($stmt_insert) && $stmt_insert !== null) {
            mysqli_stmt_close($stmt_insert);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Register User GameHub</title>
</head>
<body>
    <h2>Register User</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        Username:<br>
        <input type="text" name="username"><br><br>

        Password:<br>
        <input type="password" name="password"><br><br>

        Konfirmasi Password:<br>
        <input type="password" name="confirm_password"><br><br>

        <input type="submit" value="Register">
    </form>
    <p><a href="login.php">Sudah punya akun? Login di sini</a></p>
</body>
</html>
