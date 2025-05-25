<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav>
    <a href="/gamehub/index.php">Home</a>

    <?php if (isset($_SESSION['username'])): ?>
        | <a href="/gamehub/includes/logout.php">Logout</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            | <a href="/gamehub/admin_panel.php">Admin Panel</a>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($currentPage !== 'login.php'): ?>
            | <a href="/gamehub/auth/login.php">Login</a>
        <?php endif; ?>
        <?php if ($currentPage !== 'register.php'): ?>
            | <a href="/gamehub/auth/register.php">Register</a>
        <?php endif; ?>
    <?php endif; ?>
</nav>
