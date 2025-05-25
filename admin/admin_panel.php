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

// Ambil semua data game
$sql = "SELECT * FROM games ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Manajemen Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;

        }

        h1 {
            color: #333;
            text-align: center;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .add-button {
            display: inline-block;
            margin-bottom: 15px;
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .add-button:hover {
            background-color: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .action-links a {
            color: #007bff;
            text-decoration: none;
            margin-right: 8px;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .no-data {
            text-align: center;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel - Manajemen Game</h1>
        <a class="add-button" href="admin_add_game.php">+ Tambah Game Baru</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Game</th>
                    <th>Developer</th>
                    <th>Publisher</th>
                    <th>Tahun Rilis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($game = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $game['id'] ?></td>
                        <td><?= htmlspecialchars($game['name']) ?></td>
                        <td><?= htmlspecialchars($game['developer']) ?></td>
                        <td><?= htmlspecialchars($game['publisher']) ?></td>
                        <td><?= $game['release_year'] ?></td>
                        <td class="action-links">
                            <a href="admin_edit_game.php?id=<?= $game['id'] ?>">Edit</a>
                            <a href="admin_delete_game.php?id=<?= $game['id'] ?>" onclick="return confirm('Hapus game ini?');">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="no-data">Belum ada data game.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
