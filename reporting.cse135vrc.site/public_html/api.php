<?php
require_once 'auth.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://reporting.cse135vrc.site");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Parse URI: strip query string and leading slash, then split on /
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');
$parts = explode('/', $uri);

// Expected: api/{resource}[/{id}]
// $parts[0] = "api", $parts[1] = resource, $parts[2] = id (optional)
if (count($parts) < 2 || $parts[0] !== 'api') {
    http_response_code(404);
    echo json_encode(["error" => "Not found"]);
    exit();
}

$resource = $parts[1];
$id = isset($parts[2]) && $parts[2] !== '' ? $parts[2] : null;
$method = $_SERVER['REQUEST_METHOD'];

require_once 'db.php';

if ($resource === 'events') {
    handleEvents($pdo, $method, $id);
} elseif ($resource === 'sessions') {
    handleSessions($pdo, $method, $id);
} elseif ($resource === 'event-summary') {
    handleEventSummary($pdo, $method);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Unknown resource"]);
}

function handleEvents(PDO $pdo, string $method, ?string $id): void {
    switch ($method) {
        case 'GET':
            if ($id === null) {
                $stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
                $stmt->execute(["id" => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(["error" => "Event not found"]);
                } else {
                    echo json_encode($row);
                }
            }
            break;

        case 'POST':
            if ($id !== null) {
                http_response_code(400);
                echo json_encode(["error" => "Do not include an ID when creating an event"]);
                break;
            }
            $body = json_decode(file_get_contents("php://input"), true);
            if (!$body || !isset($body['session_id'], $body['event_type'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required fields: session_id, event_type"]);
                break;
            }
            $stmt = $pdo->prepare("
                INSERT INTO events (session_id, event_type, url, payload)
                VALUES (:sid, :type, :url, CAST(:payload AS jsonb))
                RETURNING id
            ");
            $stmt->execute([
                "sid"     => $body['session_id'],
                "type"    => $body['event_type'],
                "url"     => $body['url'] ?? null,
                "payload" => json_encode($body['payload'] ?? $body),
            ]);
            $newId = $stmt->fetchColumn();
            http_response_code(201);
            echo json_encode(["status" => "created", "id" => $newId]);
            break;

        case 'PUT':
            if ($id === null) {
                http_response_code(400);
                echo json_encode(["error" => "ID required for PUT"]);
                break;
            }
            $body = json_decode(file_get_contents("php://input"), true);
            if (!$body) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid JSON body"]);
                break;
            }
            $fields = [];
            $params = ["id" => $id];

            if (isset($body['event_type'])) {
                $fields[] = "event_type = :event_type";
                $params['event_type'] = $body['event_type'];
            }
            if (isset($body['url'])) {
                $fields[] = "url = :url";
                $params['url'] = $body['url'];
            }
            if (isset($body['payload'])) {
                $fields[] = "payload = CAST(:payload AS jsonb)";
                $params['payload'] = json_encode($body['payload']);
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(["error" => "No updatable fields provided (event_type, url, payload)"]);
                break;
            }

            $stmt = $pdo->prepare("UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id");
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Event not found"]);
            } else {
                echo json_encode(["status" => "updated"]);
            }
            break;

        case 'DELETE':
            if ($id === null) {
                http_response_code(400);
                echo json_encode(["error" => "ID required for DELETE"]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $stmt->execute(["id" => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Event not found"]);
            } else {
                echo json_encode(["status" => "deleted"]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
    }
}

function handleSessions(PDO $pdo, string $method, ?string $id): void {
    switch ($method) {
        case 'GET':
            if ($id === null) {
                $stmt = $pdo->query("SELECT * FROM sessions ORDER BY last_seen DESC");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_id = :id");
                $stmt->execute(["id" => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(["error" => "Session not found"]);
                } else {
                    echo json_encode($row);
                }
            }
            break;

        case 'POST':
            if ($id !== null) {
                http_response_code(400);
                echo json_encode(["error" => "Do not include an ID when creating a session"]);
                break;
            }
            $body = json_decode(file_get_contents("php://input"), true);
            if (!$body || !isset($body['session_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required field: session_id"]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO sessions (session_id) VALUES (:sid)");
            $stmt->execute(["sid" => $body['session_id']]);
            http_response_code(201);
            echo json_encode(["status" => "created", "session_id" => $body['session_id']]);
            break;

        case 'PUT':
            if ($id === null) {
                http_response_code(400);
                echo json_encode(["error" => "ID required for PUT"]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE sessions SET last_seen = NOW() WHERE session_id = :id");
            $stmt->execute(["id" => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Session not found"]);
            } else {
                echo json_encode(["status" => "updated"]);
            }
            break;

        case 'DELETE':
            if ($id === null) {
                http_response_code(400);
                echo json_encode(["error" => "ID required for DELETE"]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = :id");
            $stmt->execute(["id" => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Session not found"]);
            } else {
                echo json_encode(["status" => "deleted"]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
    }
}

function handleEventSummary(PDO $pdo, string $method): void {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }

    $stmt = $pdo->query("
        SELECT event_type, COUNT(*) AS total
        FROM events
        GROUP BY event_type
        ORDER BY total DESC
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
