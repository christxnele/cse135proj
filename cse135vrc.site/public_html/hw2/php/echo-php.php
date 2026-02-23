<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawBody = file_get_contents('php://input');

$data = [];

if ($method === 'GET') {
    $data = $_GET;
} else {
    if (strpos($contentType, 'application/json') !== false) {
        $decoded = json_decode($rawBody, true);
        $data = $decoded !== null ? $decoded : ["raw" => $rawBody];
    } else {
        $data = $_POST;
    }
}

$response = [
    "language" => "PHP",
    "method" => $method,
    "time" => date('c'),
    "ip" => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? '',
    "data_received" => $data
];

echo json_encode($response, JSON_PRETTY_PRINT);

