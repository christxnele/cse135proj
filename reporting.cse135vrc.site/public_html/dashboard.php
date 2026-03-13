<?php 
require_once 'auth.php'; 
if ($currentRole === 'viewer') {
    header('Location: /report.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Dashboard</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <nav class="navbar">
            <button class="nav-left" id="hamburger">&#9776;</button>
            <a href="/dashboard.php" class="nav-title">Analytics Dashboard</a>
            <a href="/logout.php" class="nav-right">Logout</a>
        </nav>

        <div class="sidebar" id="sidebar">
            <?php if ($currentRole === 'super_admin'): ?>
                <a href="/admin.php">Manage Accounts</a>
            <?php endif; ?>
            <a href="/report.php">View Reports</a>
        </div>
        <div class="overlay" id="overlay"></div>

        <div class="content">
            <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?></p>
            <h2>Event Counts</h2>
            <canvas id="eventsChart" width="800" height="400"></canvas>
            
            <script>
            async function loadChart() {
                const response = await fetch("/api/event-summary");
                const data = await response.json();

                const labels = data.map(row => row.event_type);
                const values = data.map(row => Number(row.total));

                const ctx = document.getElementById("eventsChart").getContext("2d");

                new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: "Event Count",
                            data: values
                        }]
                    }
                });
            }

            loadChart();
            </script>

            <h2>Recent Events</h2>
            <table id="eventsTable" border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Session ID</th>
                        <th>Event Type</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <script>
        async function loadTable() {
            const response = await fetch("/api/events");
            const data = await response.json();
            const tbody = document.querySelector("#eventsTable tbody");
            data.slice(0, 100).forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td>${row.session_id}</td>
                    <td>${row.event_type}</td>
                    <td>${row.url ?? ''}</td>
                `;
                tbody.appendChild(tr);
            });
        }
        loadTable();

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
