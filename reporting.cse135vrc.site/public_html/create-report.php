<?php
require_once 'auth.php';
require_once 'db.php';

// only analysts and super_admin can create reports
requireRole('analyst', 'super_admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $comment = trim($_POST['analyst_comment'] ?? '');

    if (!$name || !$category) {
        $error = 'Name and category are required.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO saved_reports (name, category, created_by, analyst_comment)
            VALUES (:name, :category, :created_by, :comment)
        ");
        $stmt->execute([
            'name'       => $name,
            'category'   => $category,
            'created_by' => $currentUserId,
            'comment'    => $comment ?: null,
        ]);
        header('Location: /report.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Report</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
            <button class="nav-left" id="hamburger">&#9776;</button>
            <a href="/reports.php" class="nav-title">Analytics Dashboard</a>
            <a href="/logout.php" class="nav-right">Logout</a>
        </nav>
        <div class="sidebar" id="sidebar">
            <a href="/dashboard.php">Dashboard</a>
            <?php if ($currentRole === 'super_admin'): ?>
                <a href="/admin.php">Manage Accounts</a>
            <?php endif; ?>
            <a href="/report.php">View Reports</a>
        </div>
        <div class="overlay" id="overlay"></div>

    <div class="content">
        <h2>Create Report</h2>
        <?php if ($error): ?><p><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <label>Name: <input type="text" name="name"></label>
            <label>Category: <input type="text" name="category"></label>
            <label>Comment: <textarea name="analyst_comment"></textarea></label>
            <button type="submit">Save Report</button>
        </form>
    </div>
    
    <script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const hamburger = document.getElementById('hamburger');

    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });
</script>

</body>
</html>
