<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
$currentPage = basename($_SERVER['PHP_SELF']);
$excludedPages = ['admin_panel.php', 'admin_add_game.php', 'admin_edit_game.php'];
include('includes/database.php'); /** @var mysqli $conn */


?>
<head>
    <link rel="stylesheet" href="https://indgamehub.rf.gd/css/navbar_style.css">
    <link rel="icon" type="image/png" href="https://indgamehub.rf.gd/img/gamepad-solid.png">
</head>
<nav class="universal-nav">
    <div class="universal-nav-container">
        <!-- Logo Section (Left) -->
        <div class="nav-logo">
            <div class="nav-logo-icon">
                <i class="fas fa-gamepad"></i>
            </div>
            <a href="https://indgamehub.rf.gd/index.php" class="nav-logo-text">GameHub</a>
            <?php if (isset($_SESSION['username'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <?php if (!in_array($currentPage, $excludedPages)): ?>
            <!-- Admin Panel Button (Right of title) -->
            <a href="https://indgamehub.rf.gd/admin/admin_panel.php" class="admin-panel-btn">
                <i class="fas fa-cog"></i>
                <span>Admin Panel</span>
            </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Middle Section (Empty Space) -->
        <div class="nav-middle"></div>

        <!-- Right Section (Stats + Search + Hamburger) -->
        <div class="nav-right">
            <!-- Stats Card -->
            <div class="nav-stats-card">
                <span>Total Games:</span>
                <span class="nav-stats-number" id="nav-total-games">
                    <?php
                        $sql = "SELECT COUNT(id) AS total FROM games";
                        $result = mysqli_query($conn, $sql);
                        if ($result) {
                            $row = mysqli_fetch_assoc($result);
                            echo $row['total'];
                        } else {
                            echo "0"; // atau error handling lain
                        }
                    ?>
                </span>
            </div>

            <!-- Search Bar -->
            <div class="nav-search-container">
                <form action="https://indgamehub.rf.gd/index.php" method="get">
                    <input type="text" name="search" id="nav-search-input" placeholder="Search games, developers, genres..." class="nav-search-input">
                    <div class="nav-search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                </form>
            </div>

            <!-- Hamburger Menu -->
            <div class="hamburger-menu">
                <button class="hamburger-button" id="hamburger-btn">
                    <i class="fas fa-bars hamburger-icon"></i>
                </button>

                <!-- Dropdown Overlay -->
                <div class="dropdown-overlay" id="dropdown-overlay"></div>

                <!-- Dropdown Menu -->
                <div class="dropdown-menu" id="dropdown-menu">
                    <?php if (isset($_SESSION['username'])): ?>
                        <a href="https://indgamehub.rf.gd/includes/logout.php" class="dropdown-item logout-link">
                            <i class="fas fa-sign-out-alt item-icon"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <?php if ($currentPage !== 'login.php'): ?>
                            <a href="https://indgamehub.rf.gd/auth/login.php" class="dropdown-item auth-link">
                                <i class="fas fa-sign-in-alt item-icon"></i>
                                <span>Login</span>
                            </a>
                        <?php endif; ?>
                        <?php if ($currentPage !== 'register.php'): ?>
                            <a href="https://indgamehub.rf.gd/auth/register.php" class="dropdown-item auth-link">
                                <i class="fas fa-user-plus item-icon"></i>
                                <span>Register</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="https://indgamehub.rf.gd/about.php" class="dropdown-item about-link">
                        <i class="fas fa-info-circle item-icon"></i>
                        <span>About</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // Hamburger menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const dropdownMenu = document.getElementById('dropdown-menu');
        const dropdownOverlay = document.getElementById('dropdown-overlay');

        function toggleDropdown() {
            const isOpen = dropdownMenu.classList.contains('show');
            
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        }

        function openDropdown() {
            dropdownMenu.classList.add('show');
            dropdownOverlay.classList.add('show');
            hamburgerBtn.classList.add('active');
        }

        function closeDropdown() {
            dropdownMenu.classList.remove('show');
            dropdownOverlay.classList.remove('show');
            hamburgerBtn.classList.remove('active');
        }

        // Toggle dropdown when hamburger button is clicked
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });

        // Close dropdown when clicking on overlay
        dropdownOverlay.addEventListener('click', closeDropdown);

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!hamburgerBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                closeDropdown();
            }
        });

        // Close dropdown when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Prevent dropdown from closing when clicking inside it
        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>

