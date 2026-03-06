<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}
?>

<!DOCTYPE html>
    <head>
        <title>Dashboard</title>
    </head>
    <body>
        <h1>Analytics Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?></p>
        
        <nav>
            <a href="/reports.php">View Reports</a><br>
            <a href="/reports.php">Logout</a>
        </nav>
    </body>
</html>