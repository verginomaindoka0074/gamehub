<?php
include('database.php'); // Pastikan file koneksi sudah benar

$admin_username = "admin";
$admin_password = "indie256"; // Ganti jika mau
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Cek apakah admin sudah ada
$check_query = "SELECT * FROM users WHERE username = '$admin_username'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) > 0) {
    echo "Admin already exists.";
} else {
    $insert_query = "INSERT INTO users (username, password, role)
                     VALUES ('$admin_username', '$hashed_password', 'admin')";
    if (mysqli_query($conn, $insert_query)) {
        echo "Admin successfully inserted.";
    } else {
        echo "Error inserting admin: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
