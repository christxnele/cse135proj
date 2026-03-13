<?php
require_once 'auth.php';
require_once 'db.php';

requireRole('analyst', 'super_admin');

$id = $_POST['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM saved_reports WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

header('Location: /report.php');
exit;
