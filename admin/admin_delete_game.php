<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi: hanya admin boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /gamehub/auth/login.php");
    exit;
}

include('../includes/database.php');

// Validasi input ID game dari parameter GET
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID game tidak valid.');
}

// Ambil data game, khususnya nama file screenshot untuk dihapus
$sql = "SELECT screenshot1, screenshot2, screenshot3 FROM games WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$game = mysqli_fetch_assoc($result);

if (!$game) {
    die('Game tidak ditemukan.');
}

mysqli_stmt_close($stmt);

// Fungsi hapus file jika ada
function delete_file_if_exists($filepath) {
    if ($filepath && file_exists($filepath)) {
        unlink($filepath);
    }
}

// Hapus file screenshot yang terkait
delete_file_if_exists($game['screenshot1']);
delete_file_if_exists($game['screenshot2']);
delete_file_if_exists($game['screenshot3']);

// Hapus data game dari database
$sql_delete = "DELETE FROM games WHERE id = ?";
$stmt_delete = mysqli_prepare($conn, $sql_delete);
mysqli_stmt_bind_param($stmt_delete, "i", $id);

if (mysqli_stmt_execute($stmt_delete)) {
    mysqli_stmt_close($stmt_delete);
    mysqli_close($conn);
    // Redirect ke halaman admin setelah sukses hapus
    header("Location: /gamehub/admin/admin_panel.php?msg=delete_success");
    exit;
} else {
    mysqli_stmt_close($stmt_delete);
    mysqli_close($conn);
    die("Gagal menghapus data game: " . mysqli_error($conn));
}
?>
