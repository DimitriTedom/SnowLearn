<?php
// api/mark_lesson_done.php — AJAX endpoint
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$lesson_id  = (int)($input['lesson_id'] ?? 0);
$student_id = (int)$_SESSION['user_id'];

if (!$lesson_id) {
    echo json_encode(['success' => false, 'error' => 'lesson_id manquant']);
    exit;
}

$db = getDB();

// Verify lesson exists
$check = $db->prepare("SELECT id FROM lessons WHERE id=? LIMIT 1");
$check->execute([$lesson_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Leçon introuvable']);
    exit;
}

// Insert or ignore
$db->prepare("INSERT IGNORE INTO student_lessons (student_id, lesson_id) VALUES (?,?)")
   ->execute([$student_id, $lesson_id]);

echo json_encode(['success' => true, 'lesson_id' => $lesson_id]);
