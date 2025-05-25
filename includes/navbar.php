<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$excludedPages = ['admin_panel.php', 'admin_add_game.php', 'admin_edit_game.php'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
       body{
        margin: 0;
       }
        nav {
    background-color: #333;
    padding: 10px 20px;
    border-radius: 0 0 5px 5px;
    font-family: Arial, sans-serif;
    text-align: center;
}

nav a {
    color: #eee;
    text-decoration: none;
    margin: 0 8px;
    font-weight: 600;
    transition: color 0.3s ease;
}

nav a:hover {
    color: #1abc9c; /* hijau tosca */
    text-decoration: underline;
}

nav a + a::before {
    content: "|";
    color: #666;
    margin-right: 8px;
}
</style>

</head>
<body>
    
    <nav>
        <a href="/gamehub/index.php">Home</a>
        
    <?php if (isset($_SESSION['username'])): ?>
        <a href="/gamehub/includes/logout.php">Logout</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <?php if (!in_array($currentPage, $excludedPages)): ?>
             <a href="/gamehub/admin/admin_panel.php">Admin Panel</a>
        <?php endif; ?>
    <?php endif; ?>
    <?php else: ?>
        <?php if ($currentPage !== 'login.php'): ?>
             <a href="/gamehub/auth/login.php">Login</a>
        <?php endif; ?>
        <?php if ($currentPage !== 'register.php'): ?>
             <a href="/gamehub/auth/register.php">Register</a>
        <?php endif; ?>
    <?php endif; ?>
    </nav><br>

</body>
</html>