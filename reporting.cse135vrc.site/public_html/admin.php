<?php
require_once 'auth.php';
requireRole('super_admin');

require_once 'db.php';

$message = '';

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $uname    = trim($_POST['username'] ?? '');
        $pass     = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'viewer';
        $sections = trim($_POST['allowed_sections'] ?? '');
        $email    = trim($_POST['email'] ?? '');

        if ($uname && $pass) {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $sectionsVal = $sections ? '{' . $sections . '}' : null;
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, allowed_sections, email) VALUES (:u, :h, :r, :s, :e)");
            $stmt->execute(['u' => $uname, 'h' => $hash, 'r' => $role, 's' => $sectionsVal, 'e' => $email ?: null]);
            $message = "User '$uname' created.";
        } else {
            $message = "Username and password are required.";
        }

    } elseif ($action === 'edit') {
        $id       = (int)$_POST['id'];
        $role     = $_POST['role'] ?? 'viewer';
        $sections = trim($_POST['allowed_sections'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $sectionsVal = $sections ? '{' . $sections . '}' : null;

        $stmt = $pdo->prepare("UPDATE users SET role = :r, allowed_sections = :s, email = :e WHERE id = :id");
        $stmt->execute(['r' => $role, 's' => $sectionsVal, 'e' => $email ?: null, 'id' => $id]);

        if ($_POST['password'] ?? '') {
            $hash = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id")->execute(['h' => $hash, 'id' => $id]);
        }
        $message = "User updated.";

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== $currentUserId) { // prevent self-delete
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $id]);
            $message = "User deleted.";
        } else {
            $message = "You cannot delete your own account.";
        }
    }
}

$users = $pdo->query("SELECT id, username, role, allowed_sections, email, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <button class="nav-left" id="hamburger">&#9776;</button>
        <span class="nav-title">User Management</span>
        <a href="/logout.php" class="nav-right">Logout</a>
    </nav>
    <div class="sidebar" id="sidebar">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/admin.php">Manage Accounts</a>
        <a href="/report.php">View Reports</a>
    </div>
    <div class="overlay" id="overlay"></div>

    <div class="content">
    <h1>User Management</h1>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>All Users</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Username</th><th>Role</th><th>Allowed Sections</th><th>Email</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= $u['role'] ?></td>
                <td><?= htmlspecialchars($u['allowed_sections'] ?? '—') ?></td>
                <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button onclick="return confirm('Delete <?= htmlspecialchars($u['username']) ?>?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <fieldset>
        <legend>Add New User</legend>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <label>Username: <input type="text" name="username" required></label>
            <label>Password: <input type="password" name="password" required></label>
            <label>Role:
                <select name="role">
                    <option value="viewer">Viewer</option>
                    <option value="analyst">Analyst</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </label>
            <label>Allowed Sections (analysts only, comma-separated e.g. <code>performance,behavioral</code>):
                <input type="text" name="allowed_sections" placeholder="performance,behavioral">
            </label>
            <label>Email (optional): <input type="email" name="email"></label>
            <button type="submit">Add User</button>
        </form>
    </fieldset>

    <fieldset>
        <legend>Edit User</legend>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <label>User ID: <input type="number" name="id" required></label>
            <label>New Role:
                <select name="role">
                    <option value="viewer">Viewer</option>
                    <option value="analyst">Analyst</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </label>
            <label>Allowed Sections: <input type="text" name="allowed_sections" placeholder="performance,behavioral"></label>
            <label>Email: <input type="email" name="email"></label>
            <label>New Password (leave blank to keep current): <input type="password" name="password"></label>
            <button type="submit">Save Changes</button>
        </form>
    </fieldset>
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