<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('includes/navbar.php');

include('includes/database.php'); // pastikan koneksi ada


$selectedGenre = isset($_GET['genre']) && $_GET['genre'] !== 'all' ? strtolower($_GET['genre']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$searchClause = '';
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $searchClause = "g.name LIKE '%$searchEsc%' OR 
        g.developer LIKE '%$searchEsc%' OR 
        g.publisher LIKE '%$searchEsc%' OR 
        g.release_year LIKE '%$searchEsc%' OR 
        g.min_spec LIKE '%$searchEsc%' OR 
        g.rec_spec LIKE '%$searchEsc%'";
}



// Pagination
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 
    ? (int)$_GET['page'] : 1;

// Hitung total data dengan filter yang sama
$totalItemsSql = "SELECT COUNT(*) as total FROM games g";
$totalItemsResult = mysqli_query($conn, $totalItemsSql);
$totalItems = mysqli_fetch_assoc($totalItemsResult)['total'] ?? 0;

// Hitung total halaman
$totalPages = ceil($totalItems / $itemsPerPage);

// Hitung offset untuk LIMIT
$offset = ($currentPage - 1) * $itemsPerPage;

if ($selectedGenre && $selectedGenre !== 'all') {
    // Ambil semua ID game sesuai genre yang dipilih
    $idsQuery = "
        SELECT DISTINCT g.id
        FROM games g
        JOIN game_genres gg ON g.id = gg.game_id
        JOIN genres gen ON gg.genre_id = gen.id
        WHERE LOWER(gen.name) = ?
    ";
    $stmtIds = mysqli_prepare($conn, $idsQuery);
    mysqli_stmt_bind_param($stmtIds, "s", $selectedGenre);
    mysqli_stmt_execute($stmtIds);
    $resultIds = mysqli_stmt_get_result($stmtIds);

    $filteredIds = [];
    while ($row = mysqli_fetch_assoc($resultIds)) {
        $filteredIds[] = $row['id'];
    }

    if (count($filteredIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($filteredIds), '?'));
        $types = str_repeat('i', count($filteredIds)); // id = integer

        // Jika ada search
        $searchSQL = '';
        if ($searchClause !== '') {
            $searchSQL = "AND ($searchClause)";
        }

        $query = "
            SELECT 
                g.id, g.name, g.developer, g.publisher, g.release_year, g.cover,
                GROUP_CONCAT(DISTINCT gen.name) AS genres
            FROM games g
            JOIN game_genres gg ON g.id = gg.game_id
            JOIN genres gen ON gg.genre_id = gen.id
            WHERE g.id IN ($placeholders)
            $searchSQL
            GROUP BY g.id
            ORDER BY g.id DESC
            LIMIT $offset, $itemsPerPage
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$filteredIds);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $games = []; // Tidak ada hasil
    }

} else {
    // Hanya search filter atau tidak ada filter sama sekali
    $where = '';
    if ($searchClause !== '') {
        $where = "WHERE $searchClause";
    }

    $query = "
        SELECT 
            g.id, g.name, g.developer, g.publisher, g.release_year, g.cover,
            GROUP_CONCAT(gen.name) AS genres
        FROM games g
        LEFT JOIN game_genres gg ON g.id = gg.game_id
        LEFT JOIN genres gen ON gg.genre_id = gen.id
        $where
        GROUP BY g.id
        ORDER BY g.id DESC
        LIMIT $offset, $itemsPerPage
    ";

    $result = mysqli_query($conn, $query);
}


$games = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row['genre'] = array_filter(array_map('trim', explode(',', $row['genres'])));
    unset($row['genres']);
    $games[] = $row;
}

$genreIcons = [
    'action'      => 'fas fa-fist-raised',
    'adventure'   => 'fas fa-hiking',
    'rpg'         => 'fas fa-dragon',
    'strategy'    => 'fas fa-chess',
    'shooter'     => 'fas fa-crosshairs',
    'puzzle'      => 'fas fa-puzzle-piece',
    'platformer'  => 'fas fa-running',
    'simulation'  => 'fas fa-cogs',
    'horror'      => 'fas fa-ghost',
    'racing'      => 'fas fa-car',
    'sports'      => 'fas fa-football-ball',
    'fighting'    => 'fas fa-hand-rock',
    'survival'    => 'fas fa-heartbeat',
    'stealth'     => 'fas fa-user-secret',
    'open-world'  => 'fas fa-globe',
    'metroidvania'=> 'fas fa-bolt',
    'sandbox'     => 'fas fa-cube',
    'indie'       => 'fas fa-star',
    'multiplayer' => 'fas fa-users',
    'casual'      => 'fas fa-smile'
];


$genresResult = mysqli_query($conn, "SELECT name FROM genres ORDER BY name ASC");
$uniqueGenres = [];

while ($row = mysqli_fetch_assoc($genresResult)) {
    $uniqueGenres[] = strtolower($row['name']);
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub - Game Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/index_style.css">
    <link rel="icon" type="image/png" href="https://indgamehub.rf.gd/img/gamepad-solid.png">


</head>
<body>
    <div class="main-content">
        <!-- Header Section -->
        <div class="header-section">
             <a href="<?= $_SERVER['PHP_SELF'] ?>"><h1 class="main-title">
                <i class="fas fa-gamepad"></i>
                GameHub
            </h1></a>
            <p class="main-subtitle">Discover your next gaming adventure.</p>
            <p class="main-subtitle">All games listed are for testing purposes only — part of an educational college project.</p>
        </div>

        <!-- Genre Filter Section -->
        <div class="genre-filter">
            <h2 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter by Genre
            </h2>
            <div id="genreButtons" class="genre-buttons">
                <!-- Tombol All Games -->
                <a class="genre-btn <?= $selectedGenre === null ? 'active' : '' ?>" href="?genre=all">
                    <i class="fas fa-th"></i>
                    All Games
                </a>


                <!-- Tombol genre -->
                <?php foreach ($uniqueGenres as $genre): 
                    // Ambil icon berdasarkan genre, default ke 'fas fa-gamepad'
                    $icon = $genreIcons[$genre] ?? 'fas fa-gamepad';

                    // Buat nama genre tampilannya, huruf pertama kapital
                    $displayName = ucfirst($genre);
                ?>
                    <a class="genre-btn <?= $selectedGenre === $genre ? 'active' : '' ?>" href="?genre=<?= urlencode($genre) ?>">
                        <i class="<?= htmlspecialchars($icon) ?>"></i>
                        <?= htmlspecialchars($displayName) ?>
                    </a>

                <?php endforeach; ?>
            </div>
            
        </div>

        
        <!-- Games Container -->
        <div class="games-container">
            <?php if ($search !== ''): ?>
                <div class="search-info">
                    <h2 class="search-title">
                        <i class="fas fa-search"></i>
                        Search: <strong>"<?= htmlspecialchars(trim($search)) ?>"</strong>
                    </h2>
                </div>
            <?php endif; ?>
            
            <div id='loadingState' class="loading-state">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i>
                    <h3>Loading games...</h3>
            </div>
            <div class="games-grid" id="gamesGrid">
                <?php if (count($games) === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-gamepad"></i>
                        <h3>No games found</h3>
                        <p>Try selecting a different genre or check back later for new games.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($games as $index => $game): ?>
                        <div class="game-card" style="animation-delay: <?= $index * 0.1 ?>s" onclick="window.location.href='https://indgamehub.rf.gd/gamepage.php?id=<?= htmlspecialchars($game['id']) ?>'">
                            <div class="game-image-container">
                                <?php if (!empty($game['cover'])): ?>
                                    <img src="<?= htmlspecialchars(strpos($game['cover'], '../') === 0 ? substr($game['cover'], 3) : $game['cover']) ?>"
                                        alt="<?= htmlspecialchars($game['name']) ?>"
                                        class="game-image"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        onload="this.nextElementSibling.style.display='none';">
                                    <div class="image-placeholder" style="display: none;">
                                        <i class="fas fa-image"></i>
                                        <span>No Image Available</span>
                                    </div>
                                <?php else: ?>
                                    <div class="image-placeholder" style="display: flex;">
                                        <i class="fas fa-image"></i>
                                        <span>No Image Available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="game-info">
                                <h3 class="game-title"><?= htmlspecialchars($game['name']) ?></h3>
                                <div class="game-meta">
                                    <span class="game-developer"><?= htmlspecialchars($game['developer']) ?></span>
                                    <span class="game-year"><?= htmlspecialchars($game['release_year']) ?></span>
                                </div>
                                <div class="game-genres">
                                    <?php
                                        if (!empty($game['genre'])) {
                                            foreach ($game['genre'] as $genre) {
                                                echo '<span class="genre-tag">' . strtoupper(htmlspecialchars($genre)) . '</span>';
                                            }
                                        }
                                    ?>
                                </div>
                                <button class="view-details-btn" onclick="window.location.href='https://indgamehub.rf.gd/gamepage.php?id=<?= htmlspecialchars($game['id']) ?>'">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
                    <span class="pagination-btn active"><?= $i ?></span>
                <?php else: ?>
                    <a 
                        href="?search=<?= urlencode($search) ?>&genre=<?= urlencode($selectedGenre ?? 'all') ?>&page=<?= $i ?>" 
                        class="pagination-btn"><?= $i ?></a>
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

    <script>
        function showLoadingState() {
            const gamesGrid = document.getElementById('gamesGrid');
            const loadingState = document.getElementById('loadingState');

            gamesGrid.style.display = 'none';
            loadingState.style.display = 'block';
        }

        function hideLoadingState() {
            const gamesGrid = document.getElementById('gamesGrid');
            const loadingState = document.getElementById('loadingState');

            gamesGrid.style.display = 'grid';
            loadingState.style.display = 'none';
        }

        function showErrorState() {
            const gamesGrid = document.getElementById('gamesGrid');
            const loadingState = document.getElementById('loadingState');

            loadingState.style.display = 'none'; // Sembunyikan loading saat error
            gamesGrid.style.display = 'block';
            gamesGrid.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #ef4444;"></i>
                    <h3>Failed to load games</h3>
                    <p>Please check your connection and try again.</p>
                    <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
                        Retry
                    </button>
                </div>
            `;
        }

        document.addEventListener('DOMContentLoaded', function () {
            showLoadingState();

            // Simulasi load data 1.5 detik, lalu tampilkan grid
            setTimeout(() => {
                hideLoadingState();

                // Jika ingin test error, ganti hideLoadingState() dengan showErrorState();
                // showErrorState();
            }, 750);
        });
    </script>


</body>
</html>


<?php ob_end_flush(); ?>