<?php
// Event collection endpoint
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit();
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit();
}

$session = isset($data["session"]) ? $data["session"] : null;
$type = isset($data["type"]) ? $data["type"] : null;
$url = isset($data["url"]) ? $data["url"] : null;

if (!$session || !$type) {
    http_response_code(400);
    echo json_encode(["error" => "Missing session or type"]);
    exit();
}

try {
    $pdo = new PDO(
        "pgsql:host=127.0.0.1;port=5432;dbname=analytics",
        "analytics_user",
        "analytics-cse135!"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        INSERT INTO sessions (session_id)
        VALUES (:sid)
        ON CONFLICT (session_id)
        DO UPDATE SET last_seen = NOW()
    ");
    $stmt->execute(["sid" => $session]);

    // Insert event
    $stmt2 = $pdo->prepare("
        INSERT INTO events (session_id, event_type, url, payload)
        VALUES (:sid, :type, :url, CAST(:payload AS jsonb))
    ");

    $stmt2->execute([
        "sid" => $session,
        "type" => $type,
        "url" => $url,
        "payload" => json_encode($data)
    ]);

    echo json_encode(["status" => "ok"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}