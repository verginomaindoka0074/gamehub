<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /gamehub/auth/login.php");
    exit;
}

include('../includes/database.php'); // koneksi DB
include('../includes/navbar.php');

// Ambil semua genre (untuk dropdown checkbox)
$genreSql = "SELECT id, name FROM genres ORDER BY name ASC";
$genreResult = mysqli_query($conn, $genreSql);

// Parsing filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedGenres = isset($_GET['genres']) && is_array($_GET['genres']) ? array_map('intval', $_GET['genres']) : [];
$priceFilter = isset($_GET['price_filter']) ? $_GET['price_filter'] : '';

// Bangun WHERE clause dinamis
$whereParts = [];

if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $whereParts[] = "(
        name LIKE '%$searchEsc%' OR 
        developer LIKE '%$searchEsc%' OR 
        publisher LIKE '%$searchEsc%' OR 
        release_year LIKE '%$searchEsc%' OR 
        min_spec LIKE '%$searchEsc%' OR 
        rec_spec LIKE '%$searchEsc%'
    )";
}



if (count($selectedGenres) > 0) {
    $genreIdsStr = implode(',', $selectedGenres);
    $countGenres = count($selectedGenres);

    $whereParts[] = "id IN (
        SELECT game_id FROM game_genres 
        WHERE genre_id IN ($genreIdsStr)
        GROUP BY game_id
        HAVING COUNT(DISTINCT genre_id) = $countGenres
    )";
}

if ($priceFilter !== '') {
    switch ($priceFilter) {
        case 'lt100000':
            $whereParts[] = "(epic_price < 100000 OR steam_price < 100000)";
            break;
        case 'gt100000':
            $whereParts[] = "(epic_price > 100000 OR steam_price > 100000)";
            break;
        case 'gt250000':
            $whereParts[] = "(epic_price > 250000 OR steam_price > 250000)";
            break;
        case 'gt500000':
            $whereParts[] = "(epic_price > 500000 OR steam_price > 500000)";
            break;
    }
}

$whereClause = '';
if (count($whereParts) > 0) {
    $whereClause = "WHERE " . implode(' AND ', $whereParts);
}

$sql = "SELECT * FROM games $whereClause ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Admin Panel - Manajemen Game</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f6f8;
    }
    .container {
        max-width: 1000px;
        margin: 20px auto;
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h1 {
        text-align: center;
        color: #333;
    }
    .filter-group {
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }
    input[type=text] {
        padding: 8px;
        width: 250px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    select {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    button, a.reset-btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 9px 15px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
    }
    button:hover, a.reset-btn:hover {
        background-color: #0056b3;
    }

    /* Dropdown Checkbox Styles */
    .dropdown-checkbox {
        position: relative;
        display: inline-block;
        user-select: none;
    }
    .dropdown-checkbox .dropbtn {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: white;
        cursor: pointer;
        min-width: 150px;
        text-align: left;
    }
    .dropdown-checkbox .dropbtn:after {
        content: ' ▼';
        font-size: 10px;
    }
    .dropdown-checkbox-content {
        display: none;
        position: absolute;
        background-color: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        max-height: 180px;
        overflow-y: auto;
        width: 220px;
        z-index: 1000;
        padding: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .dropdown-checkbox-content label {
        display: block;
        margin-bottom: 6px;
        cursor: pointer;
        font-size: 14px;
    }
    .dropdown-checkbox-content label:hover {
        background-color: #f1f1f1;
    }
    .dropdown-checkbox.open .dropdown-checkbox-content {
        display: block;
    }
</style>
</head>
<body>
<div class="container">
    <h1>Admin Panel - Manajemen Game</h1>

    <form method="GET" action="" id="filterForm">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Cari nama, developer, publisher" value="<?= htmlspecialchars($search) ?>" />

            <!-- Dropdown checkbox genre -->
            <div class="dropdown-checkbox" id="genreDropdown">
                <div class="dropbtn">Pilih Genre</div>
                <div class="dropdown-checkbox-content">
                    <?php
                    // Reset pointer genreResult sebelum loop
                    mysqli_data_seek($genreResult, 0);
                    while ($genre = mysqli_fetch_assoc($genreResult)): ?>
                        <label>
                            <input type="checkbox" name="genres[]" value="<?= $genre['id'] ?>" <?= in_array($genre['id'], $selectedGenres) ? 'checked' : '' ?> />
                            <?= htmlspecialchars($genre['name']) ?>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Dropdown harga -->
            <select name="price_filter">
                <option value="">Filter Harga</option>
                <option value="lt100000" <?= $priceFilter == 'lt100000' ? 'selected' : '' ?>>Harga < Rp.100,000</option>
                <option value="gt100000" <?= $priceFilter == 'gt100000' ? 'selected' : '' ?>>Harga > Rp.100,000</option>
                <option value="gt250000" <?= $priceFilter == 'gt250000' ? 'selected' : '' ?>>Harga > Rp.250,000</option>
                <option value="gt500000" <?= $priceFilter == 'gt500000' ? 'selected' : '' ?>>Harga > Rp.500,000</option>
            </select>

            <button type="submit">Filter</button>
            <a href="admin_panel.php" class="reset-btn">Reset</a>
        </div>
    </form>

    <a class="add-button" href="admin_add_game.php" style="background-color:#28a745; color:#fff; padding:10px 15px; border-radius:4px; text-decoration:none;">+ Tambah Game Baru</a>

    <table style="width:100%; border-collapse:collapse; margin-top:15px;">
        <thead>
            <tr style="background:#007bff; color:#fff;">
                <th style="padding:12px; text-align:left;">ID</th>
                <th style="padding:12px; text-align:left;">Nama Game</th>
                <th style="padding:12px; text-align:left;">Developer</th>
                <th style="padding:12px; text-align:left;">Publisher</th>
                <th style="padding:12px; text-align:left;">Tahun Rilis</th>
                <th style="padding:12px; text-align:left;">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($game = mysqli_fetch_assoc($result)): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:12px;"><?= $game['id'] ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($game['name']) ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($game['developer']) ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($game['publisher']) ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($game['release_year']) ?></td>
                    <td style="padding:12px;">
                        <a href="admin_edit_game.php?id=<?= $game['id'] ?>">Edit</a> | 
                        <a href="admin_delete_game.php?id=<?= $game['id'] ?>" onclick="return confirm('Yakin ingin hapus game ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="padding:15px; text-align:center;">Tidak ada data game ditemukan.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Toggle dropdown checkbox open/close
    document.getElementById('genreDropdown').querySelector('.dropbtn').addEventListener('click', function(e){
        e.stopPropagation();
        this.parentElement.classList.toggle('open');
    });

    // Agar klik checkbox tidak menutup dropdown
    document.querySelectorAll('#genreDropdown input[type=checkbox]').forEach(checkbox => {
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Agar klik label teks juga tidak menutup dropdown
    document.querySelectorAll('#genreDropdown label').forEach(label => {
        label.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Tutup dropdown jika klik di luar
    window.addEventListener('click', function(){
        document.getElementById('genreDropdown').classList.remove('open');
    });
</script>
</body>
</html>
