<?php
// ============================================================
//  SnowLearn LMS — Configuration
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'snowlearn_db');
define('BASE_URL', 'http://localhost/SnowLearn');

define('UPLOAD_PDF',   __DIR__ . '/../uploads/pdfs/');
define('UPLOAD_VIDEO', __DIR__ . '/../uploads/videos/');
define('UPLOAD_CERT',  __DIR__ . '/../uploads/certificates/');

define('APP_NAME', 'SnowLearn');
define('APP_VERSION', '1.0.0');

// Créer les dossiers d'upload si nécessaire
foreach ([UPLOAD_PDF, UPLOAD_VIDEO, UPLOAD_CERT] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion impossible : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Met à jour la progression globale d'un étudiant dans un module
function updateModuleProgress(PDO $db, int $student_id, int $module_id): void {
    // Nombre total de leçons dans le module
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM lessons l
        JOIN courses c ON c.id = l.course_id
        WHERE c.module_id = ?
    ");
    $stmt->execute([$module_id]);
    $total = (int)($stmt->fetch()['total'] ?? 0);

    if ($total === 0) return;

    // Leçons complétées
    $stmt2 = $db->prepare("
        SELECT COUNT(*) AS done
        FROM student_lessons sl
        JOIN lessons l ON l.id = sl.lesson_id
        JOIN courses c ON c.id = l.course_id
        WHERE sl.student_id = ? AND c.module_id = ?
    ");
    $stmt2->execute([$student_id, $module_id]);
    $done = (int)($stmt2->fetch()['done'] ?? 0);

    // Score moyen sur les quiz du module passés
    $stmt3 = $db->prepare("
        SELECT AVG(r.pourcentage) AS avg_score
        FROM results r
        JOIN quizzes q ON q.id = r.quiz_id
        JOIN lessons l ON l.id = q.lesson_id
        JOIN courses c ON c.id = l.course_id
        WHERE r.student_id = ? AND c.module_id = ?
    ");
    $stmt3->execute([$student_id, $module_id]);
    $avg = round((float)($stmt3->fetch()['avg_score'] ?? 0), 2);

    $pct = round(($done / $total) * 100, 2);

    $db->prepare("
        INSERT INTO progress (student_id, module_id, lessons_done, lessons_total, score_moyen, pourcentage)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            lessons_done  = VALUES(lessons_done),
            lessons_total = VALUES(lessons_total),
            score_moyen   = VALUES(score_moyen),
            pourcentage   = VALUES(pourcentage)
    ")->execute([$student_id, $module_id, $done, $total, $avg, $pct]);
}
