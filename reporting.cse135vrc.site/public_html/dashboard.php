<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Dashboard</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <h1>Analytics Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?></p>

        <nav>
            <a href="/logout.php">Logout</a>
        </nav>

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
        </script>
    </body>
</html>
