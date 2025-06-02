<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: https://indgamehub.rf.gd/auth/login.php");
    exit;
}

include('../includes/database.php');
include('../includes/navbar.php');


$error = '';
$success = '';

// Validasi ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: https://indgamehub.rf.gd/index.php");
    exit;
}

// Ambil data game berdasarkan ID
$sql = "SELECT * FROM games WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$game = mysqli_fetch_assoc($result);

if (!$game) {
    header("Location: https://indgamehub.rf.gd/index.php");
    exit;
}

mysqli_stmt_close($stmt);

// Inisialisasi variabel dari DB
$name = $game['name'];
$developer = $game['developer'];
$publisher = $game['publisher'];
$release_year = $game['release_year'];
$min_spec = $game['min_spec'];
$rec_spec = $game['rec_spec'];
$steam_link = $game['steam_link'];
$alt_link = $game['alt_link'];
$cover = $game['cover'];
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
    
    $minSpecText = $_POST['min_spec'];
    $recSpecText = $_POST['rec_spec'];

    $steam_link = trim(filter_input(INPUT_POST, 'steam_link', FILTER_SANITIZE_URL)) ?: '';
    $alt_link = trim(filter_input(INPUT_POST, 'alt_link', FILTER_SANITIZE_URL)) ?: '';

    $release_year = filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT);

    $selected_genres = isset($_POST['genres']) ? array_map('intval', $_POST['genres']) : [];

    // Bandingkan: apakah input sama dengan genre saat ini?
    $genres_changed = count($selected_genres) !== count($current_genre_ids) || array_diff($selected_genres, $current_genre_ids) || array_diff($current_genre_ids, $selected_genres);


    // Proses upload screenshot (jika ada)
    // Misal, sebelum upload, variabel lama sudah terisi dari DB
    $old_screenshot1 = $screenshot1;
    $old_screenshot2 = $screenshot2;
    $old_screenshot3 = $screenshot3;
    $old_cover = $cover;

    // Jalankan fungsi upload, hasilnya bisa null jika tidak ada upload baru
    $new_screenshot1 = handle_screenshot_upload('screenshot1', $old_screenshot1);
    $new_screenshot2 = handle_screenshot_upload('screenshot2', $old_screenshot2);
    $new_screenshot3 = handle_screenshot_upload('screenshot3', $old_screenshot3);
    $new_cover = handle_screenshot_upload('cover', $old_cover);

    // Jika tidak ada file baru, gunakan nilai lama
    $screenshot1 = $new_screenshot1 !== null ? $new_screenshot1 : $old_screenshot1;
    $screenshot2 = $new_screenshot2 !== null ? $new_screenshot2 : $old_screenshot2;
    $screenshot3 = $new_screenshot3 !== null ? $new_screenshot3 : $old_screenshot3;
    $cover = $new_cover !== null ? $new_cover : $old_cover;

    // Validasi logika
    if (!$name || strlen($name) < 3) {
        $error = 'Nama game wajib diisi minimal 3 karakter.';
    } elseif (!$release_year || $release_year < 1970 || $release_year > date('Y')) {
        $error = 'Tahun rilis tidak valid.';
    } elseif (!$developer || strlen($developer) < 2) {
        $error = 'Developer wajib diisi.';
    } elseif (!$publisher || strlen($publisher) < 2) {
        $error = 'Publisher wajib diisi.';
    } elseif (!is_array($selected_genres) || count($selected_genres) === 0) {
        $error = 'Minimal pilih 1 genre.';
    } elseif (!is_array(json_decode($minSpecText, true))) {
        $error = 'Format Minimum Spec tidak valid (harus JSON).';
    } elseif (!is_array(json_decode($recSpecText, true))) {
        $error = 'Format Recommended Spec tidak valid (harus JSON).';
    } elseif ($steam_link && !filter_var($steam_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Steam tidak valid.';
    } elseif ($alt_link && !filter_var($alt_link, FILTER_VALIDATE_URL)) {
        $error = 'Link Epic Games tidak valid.';
    } elseif (!isset($cover)){
        $error = 'Cover harus berupa file gambar yang valid.';
    }


    // Jika valid dan tidak ada error upload
    if ($error === '') {
        $min_spec = json_encode(json_decode($minSpecText, true));
        $rec_spec = json_encode(json_decode($recSpecText, true));
        $sql_update = "UPDATE games SET 
            name = ?, developer = ?, publisher = ?, release_year = ?, 
            min_spec = ?, rec_spec = ?, steam_link = ?, alt_link = ?, cover = ?,
            screenshot1 = ?, screenshot2 = ?, screenshot3 = ?
            WHERE id = ?";

        $stmt_update = mysqli_prepare($conn, $sql_update);

        mysqli_stmt_bind_param(
            $stmt_update,
            "sssissssssssi",
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
            // header("Location: https://indgamehub.rf.gd/admin/admin_panel.php");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Game <?php echo htmlspecialchars($name ?? 'Game'); ?> - GameHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/admin_edit_style.css">
</head>
<body>
    
    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h1 class="form-title">Edit Game</h1>
                <p class="form-subtitle">Update <a class="game-name" href="https://indgamehub.rf.gd/gamepage.php?id=<?= htmlspecialchars($game['id']) ?>"><?php echo htmlspecialchars($name ?? 'Game'); ?></a> information</p>
            </div>

            <a href="https://indgamehub.rf.gd/admin/admin_panel.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Admin Panel
            </a>

            <?php if ($error ?? false): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php elseif ($success ?? false): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" id="editGameForm">
                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-gamepad"></i>
                                Game Name <span class="required">*</span>
                            </label>
                            <input type="text" name="name" autocomplete="off" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Enter game name" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar"></i>
                                Release Year <span class="required">*</span>
                            </label>
                            <input type="number" name="release_year" autocomplete="off" value="<?php echo htmlspecialchars($release_year ?? ''); ?>" placeholder="2024" min="1970" max="2030">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-code"></i>
                                Developer <span class="required">*</span>
                            </label>
                            <input type="text" name="developer" autocomplete="off" value="<?php echo htmlspecialchars($developer ?? ''); ?>" placeholder="Game developer">
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-building"></i>
                                Publisher <span class="required">*</span>
                            </label>
                            <input type="text" name="publisher" autocomplete="off" value="<?php echo htmlspecialchars($publisher ?? ''); ?>" placeholder="Game publisher">
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
                            
                            $current_genre_ids = $current_genre_ids ?? [];
                            
                            foreach ($genre_list as $genre): ?>
                                <div class="genre-item">
                                    <input type="checkbox" name="genres[]" value="<?= $genre['id']; ?>" id="genre_<?= $genre['id']; ?>"
                                        <?= in_array($genre['id'], $current_genre_ids) ? 'checked' : '' ?>>
                                    <label for="genre_<?= $genre['id']; ?>"><?= htmlspecialchars($genre['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                     <!-- Store Links -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fab fa-steam"></i>
                                Steam Link <span class="required">*</span>
                            </label>
                            <input type="url" name="steam_link" autocomplete="off" value="<?php echo htmlspecialchars($steam_link ?? ''); ?>" placeholder="https://store.steampowered.com/app/...">
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-store"></i>
                                Alternative Link <span class="required">*</span>
                            </label>
                            <input type="url" name="alt_link" autocomplete="off" value="<?= htmlspecialchars($alt_link ?? ''); ?>" placeholder="https://store.epicgames.com/...">
                        </div>
                    </div>

                    <!-- System Requirements -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-microchip"></i>
                                Minimum Specifications <span class="required">*</span>
                            </label>
                            <textarea name="min_spec" autocomplete="off" placeholder="Enter minimum system requirements..."><?php echo htmlspecialchars($min_spec ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-rocket"></i>
                                Recommended Specifications <span class="required">*</span>
                            </label>
                            <textarea name="rec_spec" autocomplete="off" placeholder="Enter recommended system requirements..."><?php echo htmlspecialchars($rec_spec ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Cover Image -->
                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-image"></i>
                            Game Cover (Min. 700<sub>x</sub>700 / Max. 1200<sub>x</sub>1200)<span class="required">*</span>
                        </label>
                        <div class="cover-section">
                            <div class="cover-preview">
                                <?php if (!empty($cover)): ?>
                                    <img src="<?php echo htmlspecialchars($cover); ?>" alt="Current Cover">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                        <span style="margin-left: 0.5rem;">No cover image</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="cover" accept="image/*">
                        </div>
                    </div>

                    <!-- Screenshots -->
                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-camera"></i>
                            Screenshots
                        </label>
                        <div class="screenshot-grid">
                            <!-- Screenshot 1 -->
                            <div class="screenshot-col">
                                <div class="screenshot-preview">
                                    <?php if (!empty($screenshot1)): ?>
                                        <img src="<?php echo htmlspecialchars($screenshot1); ?>" alt="Screenshot 1">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-camera"></i>
                                            <span style="margin-left: 0.5rem;">No screenshot</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="screenshot1" accept="image/*">
                            </div>

                            <!-- Screenshot 2 -->
                            <div class="screenshot-col">
                                <div class="screenshot-preview">
                                    <?php if (!empty($screenshot2)): ?>
                                        <img src="<?php echo htmlspecialchars($screenshot2); ?>" alt="Screenshot 2">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-camera"></i>
                                            <span style="margin-left: 0.5rem;">No screenshot</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="screenshot2" accept="image/*">
                            </div>

                            <!-- Screenshot 3 -->
                            <div class="screenshot-col">
                                <div class="screenshot-preview">
                                    <?php if (!empty($screenshot3)): ?>
                                        <img src="<?php echo htmlspecialchars($screenshot3); ?>" alt="Screenshot 3">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-camera"></i>
                                            <span style="margin-left: 0.5rem;">No screenshot</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="screenshot3" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Update Game
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('editGameForm').addEventListener('submit', function() {
            const form = this;
            const submitBtn = form.querySelector('.submit-btn');
            
            form.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Game...';
        });

        // File input preview enhancement
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Find the preview container
                        const previewContainer = input.parentElement.querySelector('.cover-preview, .screenshot-preview');
                        if (previewContainer) {
                            previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 0.5rem; border: 2px solid rgba(245, 158, 11, 0.3);">`;
                        }
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>