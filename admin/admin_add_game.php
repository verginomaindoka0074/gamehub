<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Proteksi: hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: https://indgamehub.rf.gd/auth/login.php");
    exit;
}

include('../includes/database.php'); // Koneksi DB
include('../includes/navbar.php');   // Navbar

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';
$success = '';

$name = $developer = $publisher = $release_year = '';
$steam_link = $alt_link = '';
$min_spec = $rec_spec = $cover = $screenshot1 = $screenshot2 = $screenshot3 = null;

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

    // Ambil teks mentah dari textarea (tanpa disaring dulu, karena akan diproses sebagai JSON)
    $minSpecText = $_POST['min_spec'];
    $recSpecText = $_POST['rec_spec'];

    $steam_link = trim(filter_input(INPUT_POST, 'steam_link', FILTER_SANITIZE_URL)) ?: '';
    $alt_link = trim(filter_input(INPUT_POST, 'alt_link', FILTER_SANITIZE_URL)) ?: '';
    $release_year = filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT);

    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Upload screenshot
    $screenshot1 = uploadScreenshot('screenshot1');
    $screenshot2 = uploadScreenshot('screenshot2');
    $screenshot3 = uploadScreenshot('screenshot3');
    $cover = uploadScreenshot('cover');

    // Validasi lengkap
    if (!$name || strlen($name) < 3) {
        $error = 'Nama game wajib diisi minimal 3 karakter.';
    } elseif ($release_year === false || $release_year < 1970 || $release_year > date('Y')) {
        $error = 'Tahun rilis wajib diisi dan harus valid.';
    } elseif (!$developer || strlen($developer) < 2) {
        $error = 'Developer wajib diisi.';
    } elseif (!$publisher || strlen($publisher) < 2) {
        $error = 'Publisher wajib diisi.';
    } elseif (empty($selected_genres)) {
        $error = 'Minimal pilih satu genre.';
    } elseif (!is_array(json_decode($minSpecText, true))) {
        $error = 'Format Minimum Spec tidak valid (harus JSON).';
    } elseif (!is_array(json_decode($recSpecText, true))) {
        $error = 'Format Recommended Spec tidak valid (harus JSON).';
    } elseif ($steam_link && !filter_var($steam_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Steam tidak valid.';
    } elseif ($alt_link && !filter_var($alt_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Alternatif tidak valid.';
    } elseif (!$cover || !file_exists($cover)) {
        $error = 'Cover wajib diunggah.';
    }

    if ($error === '') {
        // Encode JSON untuk disimpan
        $min_spec = json_encode(json_decode($minSpecText, true));
        $rec_spec = json_encode(json_decode($recSpecText, true));

        $sql = "INSERT INTO games 
            (name, developer, publisher, release_year, min_spec, rec_spec, steam_link, alt_link, cover, screenshot1, screenshot2, screenshot3)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $error = 'Gagal menyiapkan statement.';
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "sssissssssss",
                $name,
                $developer,
                $publisher,
                $release_year,
                $min_spec,
                $rec_spec,
                $steam_link,
                $alt_link,
                $cover,
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
                $name = $developer = $publisher = $minSpecText = $recSpecText = $steam_link = $alt_link = '';
                $release_year = '';
                $cover = $screenshot1 = $screenshot2 = $screenshot3 = null;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Game - GameHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/admin_add_style.css">
</head>
<body>    
    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h1 class="form-title">Add New Game</h1>
                <p class="form-subtitle">Expand your gaming library</p>
            </div>

            <a href="admin_panel.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Admin Panel
            </a>

            <?php if ($error ?? false): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php elseif ($success ?? false): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="addGameForm">
                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-gamepad"></i>
                                Game Name <span class="required">*</span>
                            </label>
                            <input type="text" name="name" autocomplete="off" value="<?= htmlspecialchars($name ?? ''); ?>" placeholder="Enter game name">
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar"></i>
                                Release Year <span class="required">*</span>
                            </label>
                            <input type="number" name="release_year" autocomplete="off" value="<?= htmlspecialchars($release_year ?? ''); ?>" placeholder="2024" min="1950" max="2030">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-code"></i>
                                Developer <span class="required">*</span>
                            </label>
                            <input type="text" name="developer" autocomplete="off" value="<?= htmlspecialchars($developer ?? ''); ?>" placeholder="Game developer">
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-building"></i>
                                Publisher <span class="required">*</span>
                            </label>
                            <input type="text" name="publisher" autocomplete="off" value="<?= htmlspecialchars($publisher ?? ''); ?>" placeholder="Game publisher">
                        </div>
                    </div>

                    <!-- Genre Selection -->
                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-tags"></i>
                            Genres <span class="required">*</span>
                        </label>
                        <div class="genre-list">
                            <?php 
                            // Sample genres - replace with actual database data
                            $genre_list = $genre_list ?? [
                                ['id' => 1, 'name' => 'Action'],
                                ['id' => 2, 'name' => 'Adventure'],
                                ['id' => 3, 'name' => 'RPG'],
                                ['id' => 4, 'name' => 'Strategy'],
                                ['id' => 5, 'name' => 'Simulation'],
                                ['id' => 6, 'name' => 'Sports'],
                                ['id' => 7, 'name' => 'Racing'],
                                ['id' => 8, 'name' => 'Puzzle']
                            ];
                            
                            foreach ($genre_list as $genre): ?>
                                <div class="genre-item">
                                    <input type="checkbox" name="genres[]" value="<?= $genre['id']; ?>" id="genre_<?= $genre['id']; ?>">
                                    <label for="genre_<?= $genre['id']; ?>"><?= htmlspecialchars($genre['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- System Requirements -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-microchip"></i>
                                Minimum Specifications <span class="required">*</span>
                            </label>
                            <textarea name="min_spec" autocomplete="off" placeholder="Enter minimum system requirements..."><?= htmlspecialchars($min_spec ?? 
                            '{"OS": "Windows 10, Mac OS",
"Processor": "Enter",
"Memory": "Enter GB RAM",
"Graphics": "Enter",
"Storage": "Enter GB available space"}'); ?>
                            </textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-rocket"></i>
                                Recommended Specifications <span class="required">*</span>
                            </label>
                            <textarea name="rec_spec" autocomplete="off" placeholder="Enter recommended system requirements..."><?= htmlspecialchars($rec_spec ?? 
                            '{"OS": "Windows 10, Mac OS",
"Processor": "Enter",
"Memory": "Enter GB RAM",
"Graphics": "Enter",
"Storage": "Enter GB SSD available space"}'); ?>
                            </textarea>                            
                        </div>
                    </div>

                    <!-- Store Links -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fab fa-steam"></i>
                                Steam Link
                            </label>
                            <input type="url" name="steam_link" autocomplete="off" value="<?= htmlspecialchars($steam_link ?? ''); ?>" placeholder="https://store.steampowered.com/app/...">
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-store"></i>
                                Alternative Link
                            </label>
                            <input type="url" name="alt_link" autocomplete="off" value="<?= htmlspecialchars($alt_link ?? ''); ?>" placeholder="https://store.epicgames.com/...">
                        </div>
                    </div>

                    <!-- Media Files -->
                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-image"></i>
                            Game Cover (Min. 700<sub>x</sub>700 / Max. 1200<sub>x</sub>1200)<span class="required">*</span>
                        </label>
                        <input type="file" name="cover" accept="image/*">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-camera"></i>
                                Screenshot 1
                            </label>
                            <input type="file" name="screenshot1" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-camera"></i>
                                Screenshot 2
                            </label>
                            <input type="file" name="screenshot2" accept="image/*">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-camera"></i>
                            Screenshot 3
                        </label>
                        <input type="file" name="screenshot3" accept="image/*">
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Save Game
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('addGameForm').addEventListener('submit', function() {
            const form = this;
            const submitBtn = form.querySelector('.submit-btn');
            
            form.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Game...';
        });

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    console.log(`Selected file: ${this.files[0].name}`);
                }
            });
        });
    </script>
</body>
</html>