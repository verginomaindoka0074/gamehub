<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /gamehub/auth/login.php");
    exit;
}

include('../includes/database.php');
include('../includes/navbar.php');

$error = '';
$success = '';

// Validasi ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID game tidak valid.');
}

// Ambil data game berdasarkan ID
$sql = "SELECT * FROM games WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$game = mysqli_fetch_assoc($result);

if (!$game) {
    die('Game tidak ditemukan.'); // ATUR NANTIIII!!!
}

mysqli_stmt_close($stmt);

// Inisialisasi variabel dari DB
$name = $game['name'];
$developer = $game['developer'];
$publisher = $game['publisher'];
$release_year = $game['release_year'];
$min_spec = $game['min_spec'];
$rec_spec = $game['rec_spec'];
$steam_price = $game['steam_price'];
$epic_price = $game['epic_price'];
$steam_link = $game['steam_link'];
$epic_link = $game['epic_link'];
$screenshot1 = $game['screenshot1'];
$screenshot2 = $game['screenshot2'];
$screenshot3 = $game['screenshot3'];



function handle_screenshot_upload($input_name, $old_file = null) {
    if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES[$input_name]['tmp_name'];
        $name = basename($_FILES[$input_name]['name']);
        $target_dir = '../uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        // Buat nama file unik
        $target_file = $target_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $name);

        if (move_uploaded_file($tmp_name, $target_file)) {
            // Hapus file lama jika ada dan target_file tidak kosong
            if ($old_file) {
                $old_path = $target_dir . $old_file;
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }
            // Return path relatif yang disimpan di DB (bisa disesuaikan)
            return $target_file;
        }
    }
    // Kalau tidak ada upload baru, langsung keluar tanpa sentuh apa-apa
    return null;
}

// Ambil semua genre (opsional, untuk form checkbox)
$genre_list = [];
$genre_query = mysqli_query($conn, "SELECT id, name FROM genres ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($genre_query)) {
    $genre_list[] = $row;
}

// Ambil genre game saat ini (ID saja)
$current_genre_ids = [];
$genre_query = mysqli_query($conn, "SELECT genre_id FROM game_genres WHERE game_id = $id ORDER BY genre_id ASC");
while ($row = mysqli_fetch_assoc($genre_query)) {
    $current_genre_ids[] = (int)$row['genre_id'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input dan sanitasi
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS)) ?: '';
    $developer = trim(filter_input(INPUT_POST, 'developer', FILTER_SANITIZE_SPECIAL_CHARS));
    $developer = $developer === '' ? null : $developer;
    $publisher = trim(filter_input(INPUT_POST, 'publisher', FILTER_SANITIZE_SPECIAL_CHARS));
    $publisher = $publisher === '' ? null : $publisher;
    $min_spec = trim(filter_input(INPUT_POST, 'min_spec', FILTER_SANITIZE_SPECIAL_CHARS)) ?: '';
    $rec_spec = trim(filter_input(INPUT_POST, 'rec_spec', FILTER_SANITIZE_SPECIAL_CHARS)) ?: '';
    $steam_link = trim(filter_input(INPUT_POST, 'steam_link', FILTER_SANITIZE_URL)) ?: '';
    $epic_link = trim(filter_input(INPUT_POST, 'epic_link', FILTER_SANITIZE_URL)) ?: '';

    $release_year = filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT);
    $steam_price = filter_input(INPUT_POST, 'steam_price', FILTER_VALIDATE_FLOAT);
    $epic_price = filter_input(INPUT_POST, 'epic_price', FILTER_VALIDATE_FLOAT);

    $selected_genres = isset($_POST['genres']) ? array_map('intval', $_POST['genres']) : [];

    // Bandingkan: apakah input sama dengan genre saat ini?
    $genres_changed = count($selected_genres) !== count($current_genre_ids) || array_diff($selected_genres, $current_genre_ids) || array_diff($current_genre_ids, $selected_genres);


    // Proses upload screenshot (jika ada)
    // Misal, sebelum upload, variabel lama sudah terisi dari DB
    $old_screenshot1 = $screenshot1;
    $old_screenshot2 = $screenshot2;
    $old_screenshot3 = $screenshot3;

    // Jalankan fungsi upload, hasilnya bisa null jika tidak ada upload baru
    $new_screenshot1 = handle_screenshot_upload('screenshot1', $old_screenshot1);
    $new_screenshot2 = handle_screenshot_upload('screenshot2', $old_screenshot2);
    $new_screenshot3 = handle_screenshot_upload('screenshot3', $old_screenshot3);

    // Jika tidak ada file baru, gunakan nilai lama
    $screenshot1 = $new_screenshot1 !== null ? $new_screenshot1 : $old_screenshot1;
    $screenshot2 = $new_screenshot2 !== null ? $new_screenshot2 : $old_screenshot2;
    $screenshot3 = $new_screenshot3 !== null ? $new_screenshot3 : $old_screenshot3;

    // Validasi logika
    if (!$name || strlen($name) < 3) {
        $error = 'Nama game wajib diisi minimal 3 karakter.';
    } elseif ($release_year !== false && ($release_year < 1970 || $release_year > date('Y'))) {
        $error = 'Tahun rilis tidak valid.';
    } elseif ($steam_price !== false && $steam_price < 0) {
        $error = 'Harga Steam harus ada atau Free (0.0).';
    } elseif ($epic_price !== false && $epic_price < 0) {
        $error = 'Harga Epic Games harus ada atau Free (0.0).';
    } elseif ($steam_link && !filter_var($steam_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Steam tidak valid.';
    } elseif ($epic_link && !filter_var($epic_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Epic Games tidak valid.';
    }

    // Jika valid dan tidak ada error upload
    if ($error === '') {
        $sql_update = "UPDATE games SET 
            name = ?, developer = ?, publisher = ?, release_year = ?, 
            min_spec = ?, rec_spec = ?, steam_price = ?, epic_price = ?, 
            steam_link = ?, epic_link = ?, screenshot1 = ?, screenshot2 = ?, screenshot3 = ?
            WHERE id = ?";

        $stmt_update = mysqli_prepare($conn, $sql_update);

        mysqli_stmt_bind_param(
            $stmt_update,
            "sssissddsssssi",
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
            $screenshot3,
            $id
        );

        if (mysqli_stmt_execute($stmt_update)) {
            if ($genres_changed) {
                // Hapus semua genre lama
                mysqli_query($conn, "DELETE FROM game_genres WHERE game_id = $id");

                // Insert genre baru
                foreach ($selected_genres as $genre_id) {
                    $genre_id = (int)$genre_id;
                    mysqli_query($conn, "INSERT INTO game_genres (game_id, genre_id) VALUES ($id, $genre_id)");
                }}
            $success = "Berhasil mengupdate data";
            // mysqli_stmt_close($stmt_update);
            // mysqli_close($conn);
            // header("Location: /gamehub/admin/admin_panel.php");
            // exit;
        } else {
            $error = "Gagal memperbarui data: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt_update);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Edit Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        h1 {
            font-size: 24px;
            margin-bottom: 25px;
            color: #333;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="number"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 60px;
        }

        input[type="file"] {
            margin-top: 10px;
        }

        #back{
            display: inline-block;
            padding: 15px 16px;
            margin-top: 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            line-height: 1;
            user-select: none;
        }

        button[type="submit"] {
            margin-top: 25px;
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        /* Flexbox for screenshot preview */
        .screenshot-grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .screenshot-col {
            flex: 1;
            text-align: center;
        }

        .screenshot-col img {
            max-width: 100%;
            max-height: 120px;
            display: block;
            margin: 0 auto 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .genre-list { margin-top: 8px; }
        .genre-list label { font-weight: normal; display: inline-block; margin-right: 12px; }
        .genre-list {display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 8px;}
        .genre-list label {font-weight: normal; display: flex; align-items: center; gap: 6px; background-color: #f1f1f1; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: background-color 0.2s ease;}
        .genre-list label:hover {background-color: #e0e0e0;}
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Game: <?php echo htmlspecialchars($name); ?></h1>

    <?php if ($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php elseif ($success): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label>Nama Game: 
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </label>

        <label>Developer: 
            <input type="text" name="developer" value="<?php echo htmlspecialchars($developer); ?>">
        </label>

        <label>Publisher: 
            <input type="text" name="publisher" value="<?php echo htmlspecialchars($publisher); ?>">
        </label>

        <div class="genre-list">
                <?php foreach ($genre_list as $genre): ?>
                    <label>
                        <input type="checkbox" name="genres[]" value="<?= $genre['id']; ?>"
                            <?= in_array($genre['id'], $current_genre_ids) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($genre['name']); ?>
                    </label><br>
                <?php endforeach; ?>
        </div>

        <label>Tahun Rilis: 
            <input type="number" name="release_year" value="<?php echo htmlspecialchars($release_year); ?>">
        </label>

        <label>Minimum Spec: 
            <textarea name="min_spec"><?php echo htmlspecialchars($min_spec); ?></textarea>
        </label>

        <label>Recommended Spec: 
            <textarea name="rec_spec"><?php echo htmlspecialchars($rec_spec); ?></textarea>
        </label>

        <label>Harga Steam: 
            <input type="number" step="0.01" name="steam_price" value="<?php echo htmlspecialchars($steam_price); ?>">
        </label>

        <label>Harga Epic Games: 
            <input type="number" step="0.01" name="epic_price" value="<?php echo htmlspecialchars($epic_price); ?>">
        </label>

        <label>Link Steam: 
            <input type="url" name="steam_link" value="<?php echo htmlspecialchars($steam_link); ?>">
        </label>

        <label>Link Epic Games: 
            <input type="url" name="epic_link" value="<?php echo htmlspecialchars($epic_link); ?>">
        </label>

        <label>Screenshots:</label>
        <div class="screenshot-grid">
            <!-- Screenshot 1 -->
            <div class="screenshot-col">
                <?php if (!empty($screenshot1)): ?>
                    <img src="<?php echo htmlspecialchars($screenshot1); ?>" alt="Screenshot 1">
                <?php endif; ?>
                <input type="file" name="screenshot1" accept="image/*">
            </div>

            <!-- Screenshot 2 -->
            <div class="screenshot-col">
                <?php if (!empty($screenshot2)): ?>
                    <img src="<?php echo htmlspecialchars($screenshot2); ?>" alt="Screenshot 2">
                <?php endif; ?>
                <input type="file" name="screenshot2" accept="image/*">
            </div>

            <!-- Screenshot 3 -->
            <div class="screenshot-col">
                <?php if (!empty($screenshot3)): ?>
                    <img src="<?php echo htmlspecialchars($screenshot3); ?>" alt="Screenshot 3">
                <?php endif; ?>
                <input type="file" name="screenshot3" accept="image/*">
            </div>
        </div>

        <button type="submit">Update Game</button>
        <div style="margin-top: 10px; display: flex; gap: 10px;">
        <a id="back" href="/gamehub/admin/admin_panel.php">Kembali ke Panel</a>

    </form>
</div>

</body>
</html>
