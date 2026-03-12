<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// if user is not logged in, redirect to login page
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$currentRole = $_SESSION['role'];
$currentUser = $_SESSION['user'];
$currentUserId = $_SESSION['user_id'];


// check allowed sections from pgres array string
function getAllowedSections(): array {
    $raw = $_SESSION['allowed_sections'] ?? null;
    if (!$raw) return [];
    $raw = trim($raw, '{}');
    if ($raw === '') return [];
    return explode(',', $raw);
}

// check if curr user and their role can access a specific secction
function canAccessSection(string $section): bool {
    $role = $_SESSION['role'];
    if ($role === 'super_admin') return true;
    if ($role === 'analyst') {
        $sections = getAllowedSections();
        return in_array($section, $sections);
    }
    return false;   // curr user + their role cannot acces sections
}

// requiring a specific role/higher role than curr role
function requireRole(string ...$allowedRoles): void {
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        echo "<h1>403 forbidden</h1><p>You dont have permission to view this page</p>";
        exit;
    }
}
?>