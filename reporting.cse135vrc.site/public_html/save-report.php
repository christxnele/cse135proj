<?php
require_once 'auth.php';
require_once 'db.php';
requireRole('analyst', 'super_admin');

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$name     = trim($body['name'] ?? '');
$category = trim($body['category'] ?? '');
$data     = $body['report_data'] ?? [];

if (!$name || !$category) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing name or category']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO saved_reports (name, category, created_by, report_data)
    VALUES (:name, :category, :created_by, :data)
    RETURNING id
");
$stmt->execute([
    'name'       => $name,
    'category'   => $category,
    'created_by' => $currentUserId,
    'data'       => json_encode($data)
]);

$id = $stmt->fetchColumn();
echo json_encode(['status' => 'saved', 'id' => $id]);
