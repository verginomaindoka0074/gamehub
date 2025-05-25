<?php
// Konfigurasi database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'gamehub_db';

// Buat dan cek koneksi
try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    // echo"YOU HAVE BEEN CONNECTED!!";
} catch (mysqli_sql_exception $e) {
    echo "Could not connect! Error: " . "<b>" . $e->getMessage() . "</b>";
}
?>
