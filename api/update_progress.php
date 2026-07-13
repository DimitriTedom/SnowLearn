<?php
// api/update_progress.php — AJAX progress updater
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$input      = json_decode(file_get_contents('php://input'), true);
$lesson_id  = (int)($input['lesson_id'] ?? 0);
$student_id = (int)$_SESSION['user_id'];

if (!$lesson_id) {
    echo json_encode(['success' => false, 'error' => 'lesson_id manquant']);
    exit;
}

$db = getDB();

// Get module_id from lesson
$stmt = $db->prepare("SELECT c.module_id FROM lessons l JOIN courses c ON c.id=l.course_id WHERE l.id=? LIMIT 1");
$stmt->execute([$lesson_id]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Leçon introuvable']);
    exit;
}

updateModuleProgress($db, $student_id, $row['module_id']);

// Return current progress
$prog = $db->prepare("SELECT * FROM progress WHERE student_id=? AND module_id=? LIMIT 1");
$prog->execute([$student_id, $row['module_id']]);
$prog = $prog->fetch();

echo json_encode([
    'success'    => true,
    'pourcentage' => $prog ? round($prog['pourcentage'], 1) : 0,
    'lessons_done'  => $prog['lessons_done'] ?? 0,
    'lessons_total' => $prog['lessons_total'] ?? 0,
]);
