<?php
// Konfigurasi database
$db_host = 'sql101.infinityfree.com';
$db_user = 'if0_39104727';
$db_pass = 'SkN631cBue';
$db_name = 'if0_39104727_gamehub_db';

// Buat dan cek koneksi
try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    // echo"YOU HAVE BEEN CONNECTED!!";
} catch (mysqli_sql_exception $e) {
    echo "Could not connect! Error: " . "<b>" . $e->getMessage() . "</b>";
}
?>
