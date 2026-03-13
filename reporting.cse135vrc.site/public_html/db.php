<?php
try {
    $pdo = new PDO(
        "pgsql:host=127.0.0.1;port=5432;dbname=analytics",
        "analytics_user",
        "analytics-cse135!"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo "Database connection failed";
    exit;
}
?>