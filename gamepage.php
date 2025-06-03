<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('includes/navbar.php');
include('includes/database.php'); // Koneksi MySQL tersedia sebagai $conn

// Ambil ID game dari URL 
$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($gameId <= 0) {
    die("Invalid game ID.");
}

// Ambil data game utama
$sql = "SELECT * FROM games WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $gameId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$selectedGame = null;
if (mysqli_num_rows($result) > 0) {
    $selectedGame = mysqli_fetch_assoc($result);

    // Ambil genre game
    $sqlGenres = "
        SELECT g.name 
        FROM genres g
        JOIN game_genres gg ON g.id = gg.genre_id
        WHERE gg.game_id = ?
    ";
    $stmtGenres = mysqli_prepare($conn, $sqlGenres);
    mysqli_stmt_bind_param($stmtGenres, "i", $gameId);
    mysqli_stmt_execute($stmtGenres);
    $resultGenres = mysqli_stmt_get_result($stmtGenres);

    $genres = [];
    while ($row = mysqli_fetch_assoc($resultGenres)) {
        $genres[] = $row['name'];
    }
    $selectedGame['genre'] = $genres;

    // Decode spesifikasi sistem (dalam format JSON)
    $selectedGame['minimum_spec']     = json_decode($selectedGame['min_spec'], true) ?: [];
    $selectedGame['recommended_spec'] = json_decode($selectedGame['rec_spec'], true) ?: [];

    // Ambil screenshot
    $screenshots = [];
    if (!empty($selectedGame['screenshot1'])) $screenshots[] = $selectedGame['screenshot1'];
    if (!empty($selectedGame['screenshot2'])) $screenshots[] = $selectedGame['screenshot2'];
    if (!empty($selectedGame['screenshot3'])) $screenshots[] = $selectedGame['screenshot3'];
    $selectedGame['screenshots'] = $screenshots;

    // Rapikan path cover
    $selectedGame['cover'] = strpos($selectedGame['cover'], '../') === 0
        ? substr($selectedGame['cover'], 3)
        : $selectedGame['cover'];
}

// Ambil game terkait
$relatedGames = [];
if ($selectedGame && !empty($selectedGame['genre'])) {
    $genrePlaceholders = implode(',', array_fill(0, count($selectedGame['genre']), '?'));
    $params = $selectedGame['genre'];
    $params[] = $selectedGame['developer'];
    $params[] = $selectedGame['publisher'];
    $params[] = $selectedGame['id'];

    $sqlRelated = "
        SELECT DISTINCT g.*
        FROM games g
        LEFT JOIN game_genres gg ON g.id = gg.game_id
        LEFT JOIN genres ge ON gg.genre_id = ge.id
        WHERE (ge.name IN ($genrePlaceholders) OR g.developer = ? OR g.publisher = ?)
        AND g.id != ?
        ORDER BY RAND()
        LIMIT 4
    ";

    $stmtRelated = mysqli_prepare($conn, $sqlRelated);
    $types = str_repeat('s', count($selectedGame['genre'])) . 'ssi';
    mysqli_stmt_bind_param($stmtRelated, $types, ...$params);
    mysqli_stmt_execute($stmtRelated);
    $resultRelated = mysqli_stmt_get_result($stmtRelated);

    while ($row = mysqli_fetch_assoc($resultRelated)) {
        $row['cover'] = strpos($row['cover'], '../') === 0
            ? substr($row['cover'], 3)
            : $row['cover'];
        $relatedGames[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $selectedGame ? htmlspecialchars($selectedGame['name']) . ' - GameHub' : 'Game Not Found - GameHub' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/gamepage_style.css">
</head>
<body>
    <?php if ($selectedGame): ?>
        <section class="hero">
            <div class="hero-bg" style="background-image: url('<?= htmlspecialchars($selectedGame['cover']) ?>')"></div>
            <div class="hero-overlay"></div>
            
            <div class="hero-content">
                <div class="hero-grid">
                    <div class="game-cover-container">
                        <img src="<?= htmlspecialchars($selectedGame['cover']) ?>"
                             alt="Cover for <?= htmlspecialchars($selectedGame['name']) ?>" 
                             class="game-cover">
                    </div>

                    <div class="game-info">
                        <h1 class="game-title">
                            <?= htmlspecialchars($selectedGame['name']) ?>
                        </h1>

                        <div class="genre-tags">
                            <?php
                            $genres = isset($selectedGame['genre']) && is_array($selectedGame['genre']) 
                                ? $selectedGame['genre'] 
                                : (isset($selectedGame['genre']) ? [$selectedGame['genre']] : []);
                            foreach ($genres as $genre):
                            ?>
                                <span class="genre-tag">
                                    <?= htmlspecialchars($genre) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-header">
                                    <div class="info-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <h3 class="info-title">Developer</h3>
                                </div>
                                <p class="info-content"><?= htmlspecialchars($selectedGame['developer']) ?></p>
                            </div>

                            <div class="info-card">
                                <div class="info-header">
                                    <div class="info-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <h3 class="info-title">Publisher</h3>
                                </div>
                                <p class="info-content"><?= htmlspecialchars($selectedGame['publisher']) ?></p>
                            </div>

                            <div class="info-card">
                                <div class="info-header">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <h3 class="info-title">Release Year</h3>
                                </div>
                                <p class="info-content"><?= htmlspecialchars($selectedGame['release_year']) ?></p>
                            </div>

                            <div class="info-card">
                                <div class="info-header">
                                    <div class="info-icon">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <h3 class="info-title">Platform</h3>
                                </div>
                                <div class="info-content">
                                    <i class="fab fa-windows" style="color: #0078d4; margin-right: 0.5rem;"></i>
                                    Windows PC
                                </div>
                            </div>
                        </div>

                       <div class="action-buttons">
    <h3 class="action-title">Buy the game if you can afford it.. otherwise download here</h3>

    <!-- Download button -->
    <button class="btn btn-download">
        <i class="fas fa-download"></i>
        <span>Download</span>
    </button>

    <!-- Steam button -->
    <?php if (!empty($selectedGame['steam_link'])): ?>
        <button class="btn btn-link btn-small" onclick="window.open('<?= htmlspecialchars($selectedGame['steam_link']) ?>', '_blank')">
            <i class="fab fa-steam"></i>
            <span>Steam</span>
        </button>
    <?php else: ?>
        <button class="btn btn-disabled btn-small" disabled>
            <i class="fab fa-steam"></i>
            <span>Not on Steam</span>
        </button>
    <?php endif; ?>

    <!-- Alternative Link button -->
    <?php if (!empty($selectedGame['alt_link'])): ?>
        <button 
            class="btn btn-link btn-small" 
            onclick="window.open('<?= htmlspecialchars($selectedGame['alt_link']) ?>', '_blank')" 
            data-url="<?= htmlspecialchars($selectedGame['alt_link']) ?>">
            <i class="fas fa-store"></i>
            <span class="platform-label">Loading...</span>
        </button>
    <?php else: ?>
        <button class="btn btn-disabled btn-small" disabled>
            <i class="fas fa-store"></i>
            <span>Not available</span>
        </button>
    <?php endif; ?>
</div>



                    </div>
                </div>
            </div>
        </section>

        <section class="requirements-section">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title">
                        System Requirements
                    </h2>
                    <p class="section-subtitle">Make sure your system can handle this game</p>
                </div>

                <div class="requirements-grid">
                    <div class="requirement-card">
                        <div class="requirement-header">
                            <div class="requirement-icon minimum-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="requirement-title">Minimum Requirements</h3>
                        </div>
                        <div class="requirement-list">
                            <?php foreach ($selectedGame['minimum_spec'] as $key => $value): ?>
                                <div class="requirement-item minimum-item">
                                    <div class="requirement-row">
                                        <span class="requirement-label minimum-label"><?= htmlspecialchars($key) ?>:</span>
                                        <span class="requirement-value"><?= htmlspecialchars($value) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="requirement-card">
                        <div class="requirement-header">
                            <div class="requirement-icon recommended-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="requirement-title">Recommended Requirements</h3>
                        </div>
                        <div class="requirement-list">
                            <?php foreach ($selectedGame['recommended_spec'] as $key => $value): ?>
                                <div class="requirement-item recommended-item">
                                    <div class="requirement-row">
                                        <span class="requirement-label recommended-label"><?= htmlspecialchars($key) ?>:</span>
                                        <span class="requirement-value"><?= htmlspecialchars($value) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="screenshots-section">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title">
                        Screenshots & Media
                    </h2>
                    <p class="section-subtitle">Get a closer look at the game</p>
                </div>

                <?php
                // Ambil path screenshot dari database, koreksi jika pakai "../"
                $screenshotsRaw = [
                    $selectedGame['screenshot1'] ?? null,
                    $selectedGame['screenshot2'] ?? null,
                    $selectedGame['screenshot3'] ?? null
                ];

                $screenshots = [];
                foreach ($screenshotsRaw as $shot) {
                    if ($shot) {
                        $cleaned = strpos($shot, '../') === 0 ? substr($shot, 3) : $shot;
                        $screenshots[] = $cleaned;
                    }
                }
                ?>

                <?php if (count($screenshots) > 0): ?>
                    <div class="screenshots-grid">
                        <?php foreach ($screenshots as $index => $screenshotUrl): ?>
                            <div class="screenshot-container"
                                style="animation-delay: <?= $index * 0.1 ?>s;"
                                data-src="<?= htmlspecialchars($screenshotUrl) ?>"> <img src="<?= htmlspecialchars($screenshotUrl) ?>" 
                                     alt="Screenshot <?= $index + 1 ?>" 
                                     class="screenshot">
                                <div class="screenshot-overlay">
                                    <i class="fas fa-expand screenshot-icon"></i>
                                    <p class="screenshot-text">View Full Size</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-screenshots">
                        <i class="fas fa-image no-screenshots-icon"></i>
                        <h3 class="no-screenshots-title">No Screenshots Available</h3>
                        <p class="no-screenshots-text">Screenshots for this game will be added soon.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php else: ?>
        <section class="not-found">
            <div class="not-found-content">
                <i class="fas fa-exclamation-triangle not-found-icon"></i>
                <h1 class="not-found-title">Game Not Found</h1>
                <p class="not-found-text">The game you're looking for doesn't exist or has been removed.</p>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Game Library
                </a>
            </div>
        </section>
    <?php endif; ?>

    <!-- Related Games Section -->
    <section class="related-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">You Might Also Like</h2>
                <p class="section-subtitle">Similar games based on genre and developer</p>
            </div>

            <div class="related-grid">
                <?php foreach ($relatedGames as $index => $relatedGame): ?>
                    <div class="related-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                        <div class="related-image-container">
                            <img src="<?= htmlspecialchars($relatedGame['cover']) ?>" 
                                alt="<?= htmlspecialchars($relatedGame['name']) ?>" 
                                class="related-image">
                            <div class="related-overlay"></div>
                        </div>
                        <div class="related-content">
                            <h3 class="related-title"><?= htmlspecialchars($relatedGame['name']) ?></h3>
                            <p class="related-developer"><?= htmlspecialchars($relatedGame['developer']) ?></p>
                            <a href="gamepage.php?id=<?= $relatedGame['id'] ?>" class="related-button">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($relatedGames)): ?>
                    <p>No related games found.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>


    <div id="lightbox" class="lightbox">
        <div class="lightbox-content">
            <button onclick="closeLightbox()" class="lightbox-close">
                <i class="fas fa-times"></i>
            </button>
            <img id="lightbox-image" src="" alt="Screenshot" class="lightbox-image">
        </div>
    </div>

<script>
        // --- Fungsi Lightbox Global ---
        // Fungsi ini harus berada di scope global agar atribut onclick di HTML dapat memanggilnya.
        function openLightbox(imageSrc) {
            document.getElementById('lightbox-image').src = imageSrc;
            document.getElementById('lightbox').classList.add('show');
            document.body.style.overflow = 'hidden'; // Mencegah scroll pada body
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('show');
            document.body.style.overflow = 'auto'; // Mengembalikan scroll pada body
        }
        // -----------------------------

        // --- Event Listener Setelah DOM Siap ---
        // Kode di dalam DOMContentLoaded akan dieksekusi setelah seluruh HTML selesai dimuat.
        document.addEventListener('DOMContentLoaded', function() {
            // Menangani klik pada setiap screenshot container untuk membuka lightbox
            const screenshotContainers = document.querySelectorAll('.screenshot-container');
            screenshotContainers.forEach(container => {
                container.addEventListener('click', function() {
                    const imageUrl = this.getAttribute('data-src'); // Ambil URL dari data-src
                    openLightbox(imageUrl);
                });
            });

            // Menutup lightbox saat tombol escape ditekan
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeLightbox();
                }
            });

            // Efek Parallax untuk latar belakang hero
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const parallax = document.querySelector('.hero-bg');
                if (parallax) {
                    const speed = scrolled * 0.5;
                    parallax.style.transform = 'translateY(${speed}px)';
                }
            });

            // Menutup lightbox jika mengklik di luar gambar (area overlay lightbox)
            const lightbox = document.getElementById('lightbox');
            lightbox.addEventListener('click', function(event) {
                // Pastikan yang diklik adalah lightbox itu sendiri, bukan konten di dalamnya
                if (event.target === lightbox) {
                    closeLightbox();
                }
            });
        });

        document.querySelectorAll('.btn-link').forEach(button => {
            const url = button.dataset.url;
            if (!url) return;

            try {
                const domain = new URL(url).hostname;
                const parts = domain.replace(/^www\./, '').split('.');
                const base = parts.length >= 2 ? parts[parts.length - 2] : parts[0];

                const domainMap = {
                    epicgames: 'Epic Games',
                    humblebundle: 'Humble Bundle',
                    ubisoft: 'Ubisoft',
                    minecraft: 'Minecraft',
                    itch: 'itch.io'
                };

                const label = domainMap[base] || base.charAt(0).toUpperCase() + base.slice(1);
                const labelSpan = button.querySelector('.platform-label');
                if (labelSpan) {
                    labelSpan.textContent = label;
                }
            } catch (err) {
                const labelSpan = button.querySelector('.platform-label');
                if (labelSpan) {
                    labelSpan.textContent = "Visit";
                }
            }
        });
    </script>

</body>
</html>
<?php ob_end_flush(); ?>