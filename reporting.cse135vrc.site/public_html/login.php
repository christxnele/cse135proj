<?php
session_start();

// if logged in, skip to dashboard
if (isset($_SESSION['user'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // grader user and pw
    if ($username === 'grader' && $password === 'ilovecse135') {
        $_SESSION['user'] = $username;
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
    </head>
    <body>
        <h1>Login</h1>

        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label>Username: <input type="text" name="username"></label><br>
            <label>Password: <input type="text" name="password"></label><br>
            <button type="submit">Login</button>
        </form>
</html>