<?php
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

try {
    $pdo = new PDO(
        "pgsql:host=127.0.0.1;port=5432;dbname=analytics",
        "analytics_user",
        "analytics-cse135!"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

if ($resource === 'events') {
    handleEvents($pdo, $method, $id);
} elseif ($resource === 'sessions') {
    handleSessions($pdo, $method, $id);
} elseif ($resource === 'event-summary') {
    handleEventSummary($pdo, $method);
} elseif ($resource === 'reports') {
    // $id holds the sub-resource name: traffic | performance | errors
    if ($id === 'traffic') {
        handleReportsTraffic($pdo, $method);
    } elseif ($id === 'performance') {
        handleReportsPerformance($pdo, $method);
    } elseif ($id === 'errors') {
        handleReportsErrors($pdo, $method);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Unknown report type"]);
    }
} elseif ($resource === 'comments') {
    handleComments($pdo, $method, $id);
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

function handleReportsTraffic(PDO $pdo, string $method): void {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }

    try {
        // Subquery combining direct pageview events with page-enter sub-events in activity batches.
        // jsonb_typeof guard prevents errors if payload->events is not an array.
        // Timestamp lives in payload->>'timestamp', not a dedicated column.
        $pvsql = "
            (
                SELECT url, session_id,
                       (payload->>'timestamp')::timestamptz AS ts
                FROM events
                WHERE event_type = 'pageview'
              UNION ALL
                SELECT COALESCE(sub->>'url', e.url), e.session_id,
                       (sub->>'timestamp')::timestamptz AS ts
                FROM events e,
                     jsonb_array_elements(e.payload->'events') AS sub
                WHERE e.event_type = 'activity'
                  AND jsonb_typeof(e.payload->'events') = 'array'
                  AND sub->>'type' = 'page-enter'
            ) AS pv
        ";

        $kpi = $pdo->query("
            SELECT COUNT(*) AS total_pageviews,
                   COUNT(DISTINCT session_id) AS unique_sessions
            FROM $pvsql
        ")->fetch(PDO::FETCH_ASSOC);

        $daily = $pdo->query("
            SELECT DATE(ts) AS day, COUNT(*) AS views
            FROM $pvsql
            GROUP BY day ORDER BY day
        ")->fetchAll(PDO::FETCH_ASSOC);

        $topPages = $pdo->query("
            SELECT url,
                   COUNT(*) AS views,
                   COUNT(DISTINCT session_id) AS sessions,
                   MIN(ts) AS first_seen,
                   MAX(ts) AS last_seen
            FROM $pvsql
            WHERE url IS NOT NULL
            GROUP BY url ORDER BY views DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "kpi"               => $kpi,
            "pageviews_per_day" => $daily,
            "top_pages"         => $topPages,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function handleReportsPerformance(PDO $pdo, string $method): void {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }

    $dist = $pdo->query("
        SELECT 'lcp' AS metric,
               COALESCE(payload->'vitals'->'lcp'->>'score', 'unknown') AS score,
               COUNT(*) AS count
        FROM events WHERE event_type = 'vitals'
          AND payload->'vitals'->'lcp' IS NOT NULL
        GROUP BY score
        UNION ALL
        SELECT 'cls' AS metric,
               COALESCE(payload->'vitals'->'cls'->>'score', 'unknown') AS score,
               COUNT(*) AS count
        FROM events WHERE event_type = 'vitals'
          AND payload->'vitals'->'cls' IS NOT NULL
        GROUP BY score
        UNION ALL
        SELECT 'inp' AS metric,
               COALESCE(payload->'vitals'->'inp'->>'score', 'unknown') AS score,
               COUNT(*) AS count
        FROM events WHERE event_type = 'vitals'
          AND payload->'vitals'->'inp' IS NOT NULL
        GROUP BY score
    ")->fetchAll(PDO::FETCH_ASSOC);

    $pages = $pdo->query("
        SELECT url,
               ROUND(AVG((payload->'vitals'->'lcp'->>'value')::numeric))::int AS avg_lcp,
               ROUND(AVG((payload->'vitals'->'cls'->>'value')::numeric * 1000)::numeric / 1000, 3) AS avg_cls,
               ROUND(AVG((payload->'vitals'->'inp'->>'value')::numeric))::int AS avg_inp,
               COUNT(*) AS samples
        FROM events WHERE event_type = 'vitals'
        GROUP BY url ORDER BY avg_lcp DESC NULLS LAST LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "distributions" => $dist,
        "pages"         => $pages,
    ]);
}

function handleReportsErrors(PDO $pdo, string $method): void {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }

    // Errors are buffered into 'activity' batches — query sub-events via jsonb_array_elements
    $errorsCTE = "
        WITH errors AS (
            SELECT sub->>'type' AS error_type,
                   COALESCE(sub->>'message', sub->>'src', '(unknown)') AS message,
                   e.url,
                   e.session_id
            FROM events e, jsonb_array_elements(e.payload->'events') sub
            WHERE e.event_type = 'activity'
              AND sub->>'type' IN ('js-error', 'promise-rejection', 'resource-error')
        )
    ";

    $byType = $pdo->query("
        $errorsCTE
        SELECT error_type AS event_type, COUNT(*) AS count
        FROM errors GROUP BY error_type ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $topMessages = $pdo->query("
        $errorsCTE
        SELECT error_type AS event_type, message, url,
               COUNT(*) AS occurrences
        FROM errors
        GROUP BY error_type, message, url
        ORDER BY occurrences DESC LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);

    $affected = $pdo->query("
        $errorsCTE
        SELECT COUNT(DISTINCT session_id) AS affected_sessions FROM errors
    ")->fetch(PDO::FETCH_ASSOC);

    $kpi = ['js_errors' => 0, 'promise_rejections' => 0, 'resource_errors' => 0];
    foreach ($byType as $row) {
        if ($row['event_type'] === 'js-error')           $kpi['js_errors']           = (int)$row['count'];
        if ($row['event_type'] === 'promise-rejection')  $kpi['promise_rejections']  = (int)$row['count'];
        if ($row['event_type'] === 'resource-error')     $kpi['resource_errors']     = (int)$row['count'];
    }
    $kpi['affected_sessions'] = (int)$affected['affected_sessions'];

    echo json_encode([
        "kpi"          => $kpi,
        "by_type"      => $byType,
        "top_messages" => $topMessages,
    ]);
}

function handleComments(PDO $pdo, string $method, ?string $id): void {
    if ($method === 'GET') {
        $reportType = $_GET['report'] ?? null;
        if ($reportType) {
            $stmt = $pdo->prepare("
                SELECT id, report_type, comment_text, author, created_at
                FROM report_comments
                WHERE report_type = :rt
                ORDER BY created_at DESC
            ");
            $stmt->execute(['rt' => $reportType]);
        } else {
            $stmt = $pdo->query("
                SELECT id, report_type, comment_text, author, created_at
                FROM report_comments ORDER BY created_at DESC
            ");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        return;
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents("php://input"), true);
        if (!$body || !isset($body['report_type'], $body['comment_text'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing report_type or comment_text"]);
            return;
        }
        $stmt = $pdo->prepare("
            INSERT INTO report_comments (report_type, comment_text, author)
            VALUES (:rt, :ct, :author)
            RETURNING id, report_type, comment_text, author, created_at
        ");
        $stmt->execute([
            'rt'     => $body['report_type'],
            'ct'     => $body['comment_text'],
            'author' => $_SESSION['user'],
        ]);
        http_response_code(201);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        return;
    }

    if ($method === 'DELETE') {
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['analyst', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden"]);
            return;
        }
        if ($id === null) {
            http_response_code(400);
            echo json_encode(["error" => "ID required"]);
            return;
        }
        $stmt = $pdo->prepare("DELETE FROM report_comments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Comment not found"]);
        } else {
            echo json_encode(["status" => "deleted"]);
        }
        return;
    }

    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
