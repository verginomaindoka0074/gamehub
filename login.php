<?php
session_start();
include('database.php'); /** @var mysqli $conn */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        $error = "Username and password required!";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Redirect by role
                if ($row['role'] === 'admin') {
                    // header("Location: admin_dashboard.php");
                    header("Location: index.php");
                    // echo'You are Admin';
                } else {
                    header("Location: index.php");
                    // echo'You are User';
                }
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login GameHub</title>
</head>
<body>
    <h2>Login</h2>
    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        Username:<br>
        <input type="text" name="username"><br>
        Password:<br>
        <input type="password" name="password"><br><br>
        <input type="submit" value="Login">
    </form>
    <p><a href="register.php">Belum punya akun? Buat di sini</a></p> <br>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
