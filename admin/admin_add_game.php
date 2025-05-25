<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: hanya admin yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /gamehub/auth/login.php");
    exit;
}

include('../includes/database.php'); // Koneksi DB
include('../includes/navbar.php');   // Navbar

$error = '';
$success = '';

$name = '';
$developer = '';
$publisher = '';
$release_year = '';
$min_spec = '';
$rec_spec = '';
$steam_price = '';
$epic_price = '';
$steam_link = '';
$epic_link = '';

$screenshot1 = null;
$screenshot2 = null;
$screenshot3 = null;

// Fungsi upload file screenshot
function uploadScreenshot($input_name) {
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
            // Return path relatif yang disimpan di DB (bisa disesuaikan)
            return $target_file;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $error = 'Harga Steam harus ada atau Free (0.0).';
    } elseif ($epic_price !== false && $epic_price < 0) {
        $error = 'Harga Epic Games harus ada atau Free (0.0).';
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
            $error = 'Gagal menyiapkan statement: ' . mysqli_error($conn);
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
                $success = 'Game berhasil ditambahkan!';
                // Reset nilai form
                $name = $developer = $publisher = $min_spec = $rec_spec = $steam_link = $epic_link = '';
                $release_year = $steam_price = $epic_price = '';
                $screenshot1 = $screenshot2 = $screenshot3 = null;
            } else {
                $error = 'Gagal menyimpan data ke database: ' . mysqli_stmt_error($stmt);
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
    <title>Tambah Game Baru</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f6f6;
            
        }

        
        h1 {
            color: #333;
        }

        #box {
            background-color: #fff;
            padding-right: 30%;
            padding-left: 5%;
            padding-top: 2%;
            padding-bottom: 5%;
            border: 1px solid #ccc;
            max-width: 600px;
            margin-top: 20px;
            margin-bottom: 20px;
            margin-left: 75px;
            margin-right: 75px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        textarea {
            resize: vertical;
            min-height: 60px;
        }

        input[type="file"] {
            margin-top: 5px;
        }

        button {
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #4CAF50;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #45a049;
        }

        #back{
            display: inline-block;
            padding: 8px 16px;
            margin-top: 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            line-height: 1;
            user-select: none;
        }

        .alert {
            padding: 10px;
            margin-top: 15px;
            border-radius: 4px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        a {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #007BFF;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    
    <div id="box">
        
    <h1>Tambah Game Baru</h1>
        <a href="admin_panel.php" id="back">&larr; Kembali ke Panel Admin</a>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                <?php endif; ?>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Nama Game:
            <input type="text" name="name" value="<?= htmlspecialchars($name); ?>" required>
        </label>

        <label>Developer:
            <input type="text" name="developer" value="<?= htmlspecialchars($developer); ?>">
        </label>

        <label>Publisher:
            <input type="text" name="publisher" value="<?= htmlspecialchars($publisher); ?>">
        </label>

        <label>Tahun Rilis:
            <input type="number" name="release_year" value="<?= htmlspecialchars($release_year); ?>">
        </label>

        <label>Spesifikasi Minimum:
            <textarea name="min_spec"><?= htmlspecialchars($min_spec); ?></textarea>
        </label>

        <label>Spesifikasi Rekomendasi:
            <textarea name="rec_spec"><?= htmlspecialchars($rec_spec); ?></textarea>
        </label>

        <label>Harga Steam (IDR):
            <input type="number" step="0.01" name="steam_price" value="<?= htmlspecialchars($steam_price); ?>">
        </label>

        <label>Harga Epic Games (IDR):
            <input type="number" step="0.01" name="epic_price" value="<?= htmlspecialchars($epic_price); ?>">
        </label>

        <label>Link Steam:
            <input type="url" name="steam_link" value="<?= htmlspecialchars($steam_link); ?>">
        </label>

        <label>Link Epic Games:
            <input type="url" name="epic_link" value="<?= htmlspecialchars($epic_link); ?>">
        </label>

        <label>Screenshot 1:
            <input type="file" name="screenshot1" accept="image/*">
        </label>

        <label>Screenshot 2:
            <input type="file" name="screenshot2" accept="image/*">
        </label>

        <label>Screenshot 3:
            <input type="file" name="screenshot3" accept="image/*">
        </label>

        <button type="submit">Tambah Game</button>
    
    </form>
    </div>
</body>
</html>
