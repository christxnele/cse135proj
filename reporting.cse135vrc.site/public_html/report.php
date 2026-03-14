<?php
    require_once 'auth.php';
    require_once 'db.php';

    $sections = getAllowedSections();

    if ($currentRole === 'super_admin') {
        $stmt = $pdo->query("SELECT * FROM saved_reports ORDER BY created_at DESC");
    } elseif ($currentRole === 'analyst' && !empty($sections)) {
        $in = implode(',', array_fill(0, count($sections), '?'));
        $stmt = $pdo->prepare("SELECT * FROM saved_reports WHERE category IN ($in) ORDER BY created_at DESC");
        $stmt->execute($sections);
    } else {
        $stmt = $pdo->query("SELECT * FROM saved_reports ORDER BY created_at DESC");
    }
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
        <nav class="navbar">
            <button class="nav-left" id="hamburger">&#9776;</button>
            <a href="/reports.php" class="nav-title">Analytics Dashboard</a>
            <a href="/logout.php" class="nav-right">Logout</a>
        </nav>
        <div class="sidebar" id="sidebar">
            <a href="/reports.php">Analytics Dashboard</a>
            <a href="/dashboard.php">Dashboard (HW4 Checkpoint)</a>
            <?php if ($currentRole === 'super_admin'): ?>
                <a href="/admin.php">Manage Accounts</a>
            <?php endif; ?>
            <a href="/report.php">View Reports</a>
        </div>
        <div class="overlay" id="overlay"></div>


    <div class="content">
        <h2>Saved Reports</h2>

        <?php if ($currentRole === 'analyst' || $currentRole === 'super_admin'): ?>
            <a href="/create-report.php">+ Create Report</a>
        <?php endif; ?>

        <?php if (empty($reports)): ?>
            <p>No reports found.</p>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
                <div class="report-item">
                    <strong><?= htmlspecialchars($r['name']) ?></strong>
                    <span><?= htmlspecialchars($r['category']) ?></span>
                    <span><?= $r['created_at'] ?></span>
                    <?php if ($currentRole !== 'viewer'): ?>
                    <form method="POST" action="/delete-report.php">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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