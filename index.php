<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('includes/navbar.php');
?>

<?php if (isset($_SESSION['username'])): ?>
    <br><strong>Hello <?= htmlspecialchars($_SESSION['username']) ?></strong>
<?php endif; ?>