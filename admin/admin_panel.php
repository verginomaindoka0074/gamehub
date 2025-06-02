<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: https://indgamehub.rf.gd/auth/login.php");
    exit;
}

include('../includes/database.php'); // koneksi DB
include('../includes/navbar.php');

// Ambil semua genre (untuk dropdown checkbox)
$genreSql = "SELECT id, name FROM genres ORDER BY name ASC";
$genreResult = mysqli_query($conn, $genreSql);

// Parsing filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereClause = '';

if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $whereClause = "WHERE (
        name LIKE '%$searchEsc%' OR 
        developer LIKE '%$searchEsc%' OR 
        publisher LIKE '%$searchEsc%' OR 
        release_year LIKE '%$searchEsc%'
    )";
}

$sql = "SELECT * FROM games $whereClause ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// Ambil statistik
$totalGames = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM games"));
$totalDevelopers = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT developer FROM games"));
$totalPublishers = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT publisher FROM games"));
$latestYearRes = mysqli_query($conn, "SELECT MAX(release_year) AS latest_year FROM games");
$latestYear = mysqli_fetch_assoc($latestYearRes)['latest_year'] ?? 0;




// Pagination
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 
    ? (int)$_GET['page'] : 1;

// Hitung total data dengan filter yang sama
$totalItemsSql = "SELECT COUNT(*) as total FROM games $whereClause";
$totalItemsResult = mysqli_query($conn, $totalItemsSql);
$totalItems = mysqli_fetch_assoc($totalItemsResult)['total'] ?? 0;

// Hitung total halaman
$totalPages = ceil($totalItems / $itemsPerPage);

// Hitung offset untuk LIMIT
$offset = ($currentPage - 1) * $itemsPerPage;

// Query data dengan LIMIT dan OFFSET
$sql = "SELECT * FROM games $whereClause ORDER BY id DESC LIMIT $offset, $itemsPerPage";
$result = mysqli_query($conn, $sql);

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - GameHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/admin_panel_style.css">
</head>
<body>

    
    <div class="main-content">
        <div class="admin-header">
            <h1 class="admin-title">
                <a href="<?= $_SERVER['PHP_SELF'] ?>" text-decoration: none;>
                <i class="fas fa-cog"></i>
                Admin Panel</a>
            </h1>
            <p class="admin-subtitle">Manage your game library</p>
        </div>

        <div class="admin-panel">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalGames"><?= $totalGames ?></div>
                    <div class="stat-label">Total Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalDevelopers"><?= $totalDevelopers ?></div>
                    <div class="stat-label">Developers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalPublishers"><?= $totalPublishers ?></div>
                    <div class="stat-label">Publishers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="latestYear"><?= $latestYear ?></div>
                    <div class="stat-label">Latest Release</div>
                </div>
            </div>

            <!-- Controls -->
            <div class="admin-controls">
                <a href="https://indgamehub.rf.gd/admin/admin_add_game.php" class="add-game-btn">
                    <i class="fas fa-plus"></i>
                    Add New Game
                </a>
                <form method="get">
                <div class="search-container">
                    <input type="text" class="search-input" name="search" placeholder="Search games..." id="searchInput" autocomplete="off" value="<?= htmlspecialchars($search) ?>" />
                    <i class="fas fa-search search-icon"></i>
                </div>
                </form>
            </div>

            <!-- Games Table -->
            <div class="table-container">
                <table class="games-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cover</th>
                            <th>Game Title</th>
                            <th>Developer</th>
                            <th>Publisher</th>
                            <th>Release Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gamesTableBody">
                        <?php if (mysqli_num_rows($result) === 0): ?>
                            <tr>
                                <td colspan="7" class="empty-state"><i class="fas fa-gamepad"></i> No games found</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($game = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $game['id'] ?></td>
                                    <td>
                                        <?php if (!empty($game['cover'])): ?>
                                            <a href="https://indgamehub.rf.gd/gamepage.php?id=<?= $game['id'] ?>">
                                                <img src="<?= htmlspecialchars($game['cover']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="game-cover">
                                            </a>
                                        <?php else: ?>
                                            <div class="cover-placeholder"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="game-title"><?= htmlspecialchars($game['name']) ?></div></td>
                                    <td><div class="game-meta"><?= htmlspecialchars($game['developer']) ?></div></td>
                                    <td><div class="game-meta"><?= htmlspecialchars($game['publisher']) ?></div></td>
                                    <td><div class="game-meta"><?= htmlspecialchars($game['release_year']) ?></div></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="https://indgamehub.rf.gd/admin/admin_edit_game.php?id=<?= $game['id'] ?>" class="btn btn-edit">
                                                <i class="fas fa-edit"></i>
                                                Edit
                                            </a>
                                            <a href="https://indgamehub.rf.gd/admin/admin_delete_game.php?id=<?= $game['id'] ?>" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus game ini?');">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination">
                <?php if ($totalPages > 1): ?>
                    <!-- Previous Page -->
                    <?php if ($currentPage > 1): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $currentPage - 1 ?>" class="pagination-btn active">&laquo; Prev</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">&laquo; Prev</span>
                    <?php endif; ?>

                    <!-- Pages Numbers -->
                    <?php
                    // Batas range halaman yang ditampilkan
                    $range = 3; // contoh, 3 halaman sebelum dan sesudah

                    for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="pagination-btn"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="pagination-btn <?= ($i == $currentPage) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next Page -->
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $currentPage + 1 ?>" class="pagination-btn active">Next &raquo;</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">Next &raquo;</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>
<?php ob_end_flush(); ?>