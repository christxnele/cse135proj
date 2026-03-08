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
    </body>
</html>