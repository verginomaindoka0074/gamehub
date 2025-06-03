
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset(); // Hapus semua variabel session
session_destroy(); // Hapus session dari server
header("Location: https://indgamehub.rf.gd/index.php");
exit;