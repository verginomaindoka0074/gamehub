<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Proteksi: hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /gamehub/auth/login.php");
    exit;
}

include('../includes/database.php'); // Koneksi DB
include('../includes/navbar.php');   // Navbar

$error = '';
$success = '';

// Variabel form
$name = $developer = $publisher = $release_year = $min_spec = $rec_spec = '';
$steam_price = $epic_price = $steam_link = $epic_link = '';
$screenshot1 = $screenshot2 = $screenshot3 = null;

// Ambil daftar genre dari DB
$genre_list = [];
$genre_query = mysqli_query($conn, "SELECT id, name FROM genres ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($genre_query)) {
    $genre_list[] = $row;
}

// Fungsi upload screenshot
function uploadScreenshot($input_name) {
    if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES[$input_name]['tmp_name'];
        $name = basename($_FILES[$input_name]['name']);
        $target_dir = '../uploads/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $name);
        return move_uploaded_file($tmp_name, $target_file) ? $target_file : null;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS)) ?: '';
    $developer = trim(filter_input(INPUT_POST, 'developer', FILTER_SANITIZE_SPECIAL_CHARS)) ?: null;
    $publisher = trim(filter_input(INPUT_POST, 'publisher', FILTER_SANITIZE_SPECIAL_CHARS)) ?: null;
    $min_spec = trim(filter_input(INPUT_POST, 'min_spec', FILTER_SANITIZE_SPECIAL_CHARS)) ?: '';
    $rec_spec = trim(filter_input(INPUT_POST, 'rec_spec', FILTER_SANITIZE_SPECIAL_CHARS)) ?: '';
    $steam_link = trim(filter_input(INPUT_POST, 'steam_link', FILTER_SANITIZE_URL)) ?: '';
    $epic_link = trim(filter_input(INPUT_POST, 'epic_link', FILTER_SANITIZE_URL)) ?: '';
    $release_year = filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT);
    $steam_price = filter_input(INPUT_POST, 'steam_price', FILTER_VALIDATE_FLOAT);
    $epic_price = filter_input(INPUT_POST, 'epic_price', FILTER_VALIDATE_FLOAT);

    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Upload screenshot
    $screenshot1 = uploadScreenshot('screenshot1');
    $screenshot2 = uploadScreenshot('screenshot2');
    $screenshot3 = uploadScreenshot('screenshot3');

    // Validasi
    if (!$name || strlen($name) < 3) {
        $error = 'Nama game wajib diisi minimal 3 karakter.';
    } elseif ($release_year !== false && ($release_year < 1970 || $release_year > date('Y'))) {
        $error = 'Tahun rilis tidak valid.';
    } elseif ($steam_price !== false && $steam_price < 0) {
        $error = 'Harga Steam harus 0 atau lebih.';
    } elseif ($epic_price !== false && $epic_price < 0) {
        $error = 'Harga Epic Games harus 0 atau lebih.';
    } elseif ($steam_link && !filter_var($steam_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Steam tidak valid.';
    } elseif ($epic_link && !filter_var($epic_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Epic Games tidak valid.';
    }

    if ($error === '') {
        $sql = "INSERT INTO games 
            (name, developer, publisher, release_year, min_spec, rec_spec, steam_price, epic_price, steam_link, epic_link, screenshot1, screenshot2, screenshot3)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $error = 'Gagal menyiapkan statement.';
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "sssissddsssss",
                $name,
                $developer,
                $publisher,
                $release_year,
                $min_spec,
                $rec_spec,
                $steam_price,
                $epic_price,
                $steam_link,
                $epic_link,
                $screenshot1,
                $screenshot2,
                $screenshot3
            );

            if (mysqli_stmt_execute($stmt)) {
                $game_id = mysqli_insert_id($conn);

                // Simpan relasi genre
                foreach ($selected_genres as $genre_id) {
                    $genre_id = intval($genre_id);
                    mysqli_query($conn, "INSERT INTO game_genres (game_id, genre_id) VALUES ($game_id, $genre_id)");
                }

                $success = 'Game berhasil ditambahkan!';
                $name = $developer = $publisher = $min_spec = $rec_spec = $steam_link = $epic_link = '';
                $release_year = $steam_price = $epic_price = '';
                $screenshot1 = $screenshot2 = $screenshot3 = null;
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Game</title>
    <style>
        /* Gaya disingkat */
        body { font-family: Arial; background: #f6f6f6; }
        #box { background: #fff; padding: 20px 40px; margin: 30px auto; max-width: 600px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        label { font-weight: bold; display: block; margin-top: 12px; }
        input, textarea { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
        textarea { resize: vertical; min-height: 60px; }
        button { background: #4CAF50; color: #fff; padding: 10px 16px; margin-top: 15px; border: none; border-radius: 4px; cursor: pointer; }
        .alert { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .genre-list { margin-top: 8px; }
        .genre-list label { font-weight: normal; display: inline-block; margin-right: 12px; }
        .genre-list {display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 8px;}
        .genre-list label {font-weight: normal; display: flex; align-items: center; gap: 6px; background-color: #f1f1f1; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: background-color 0.2s ease;}
        .genre-list label:hover {background-color: #e0e0e0;}
    </style>
</head>
<body>
    <div id="box">
        <h1>Tambah Game Baru</h1>
        <a href="admin_panel.php">&larr; Kembali ke Panel Admin</a>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label>Nama Game*</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name); ?>" required>

            <label>Developer</label>
            <input type="text" name="developer" value="<?= htmlspecialchars($developer); ?>">

            <label>Publisher</label>
            <input type="text" name="publisher" value="<?= htmlspecialchars($publisher); ?>">
            
            <label>Genre</label>
            <div class="genre-list">
                <?php foreach ($genre_list as $genre): ?>
                    <label>
                        <input type="checkbox" name="genres[]" value="<?= $genre['id']; ?>"> <?= htmlspecialchars($genre['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Tahun Rilis</label>
            <input type="number" name="release_year" value="<?= htmlspecialchars($release_year); ?>">

            <label>Spesifikasi Minimum</label>
            <textarea name="min_spec"><?= htmlspecialchars($min_spec); ?></textarea>

            <label>Spesifikasi Rekomendasi</label>
            <textarea name="rec_spec"><?= htmlspecialchars($rec_spec); ?></textarea>

            <label>Harga Steam</label>
            <input type="number" name="steam_price" step="0.01" value="<?= htmlspecialchars($steam_price); ?>">

            <label>Harga Epic Games</label>
            <input type="number" name="epic_price" step="0.01" value="<?= htmlspecialchars($epic_price); ?>">

            <label>Link Steam</label>
            <input type="url" name="steam_link" value="<?= htmlspecialchars($steam_link); ?>">

            <label>Link Epic Games</label>
            <input type="url" name="epic_link" value="<?= htmlspecialchars($epic_link); ?>">

            <label>Screenshot 1</label>
            <input type="file" name="screenshot1">
            <label>Screenshot 2</label>
            <input type="file" name="screenshot2">
            <label>Screenshot 3</label>
            <input type="file" name="screenshot3">


            <button type="submit">Simpan Game</button>
        </form>
    </div>
</body>
</html>
