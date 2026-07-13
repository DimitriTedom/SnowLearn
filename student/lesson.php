<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$lesson_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$lesson_id) { header('Location: modules.php'); exit; }

$db         = getDB();
$student_id = currentUser()['id'];

$stmt = $db->prepare("
    SELECT l.*, c.id AS course_id, c.module_id, c.titre AS course_titre, m.titre AS module_titre
    FROM lessons l
    JOIN courses c ON c.id=l.course_id
    JOIN modules m ON m.id=c.module_id
    WHERE l.id=?
    LIMIT 1
");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();
if (!$lesson) { header('Location: modules.php'); exit; }

$module_id = $lesson['module_id'];
$course_id = $lesson['course_id'];

$done_check = $db->prepare("SELECT 1 FROM student_lessons WHERE student_id=? AND lesson_id=? LIMIT 1");
$done_check->execute([$student_id, $lesson_id]);
$is_completed = (bool)$done_check->fetch();

$quiz = $db->prepare("SELECT * FROM quizzes WHERE lesson_id=? LIMIT 1");
$quiz->execute([$lesson_id]);
$quiz = $quiz->fetch();

$toc = $db->prepare("
    SELECT l.id, l.titre, l.ordre, l.type, sl.completed_at
    FROM lessons l
    LEFT JOIN student_lessons sl ON sl.lesson_id=l.id AND sl.student_id=?
    WHERE l.course_id=?
    ORDER BY l.ordre ASC
");
$toc->execute([$student_id, $course_id]);
$toc = $toc->fetchAll();

$prev = $next = null;
foreach ($toc as $idx => $t) {
    if ($t['id'] == $lesson_id) {
        $prev = $toc[$idx - 1] ?? null;
        $next = $toc[$idx + 1] ?? null;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — <?= sanitize($lesson['titre']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/lesson.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title"><?= sanitize($lesson['titre']) ?></span>
      <div class="topbar-right">
        <?php if ($prev): ?><a href="lesson.php?id=<?= $prev['id'] ?>" class="btn btn-ghost btn-sm">← Préc.</a><?php endif; ?>
        <?php if ($next): ?><a href="lesson.php?id=<?= $next['id'] ?>" class="btn btn-ghost btn-sm">Suiv. →</a><?php endif; ?>
        <a href="courses.php?module_id=<?= $module_id ?>" class="btn btn-ghost btn-sm">↩ Cours</a>
      </div>
    </header>
    <main class="page-body">
      <div class="back-nav">
        <a href="modules.php">
          <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/></svg>
          <?= sanitize($lesson['module_titre']) ?>
        </a>
        <span style="color:var(--border);">/</span>
        <a href="courses.php?module_id=<?= $module_id ?>"><?= sanitize($lesson['course_titre']) ?></a>
        <span style="color:var(--border);">/</span>
        <span style="color:var(--text);"><?= sanitize($lesson['titre']) ?></span>
      </div>

      <div class="lesson-viewer-wrap">
        <div>
          <div class="lesson-gate <?= $is_completed ? 'state-done' : 'state-locked' ?>" id="lesson-gate">
            <div class="gate-icon">
              <?php if ($is_completed): ?>
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <?php elseif ($lesson['type'] === 'video'): ?>
              <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              <?php else: ?>
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <?php endif; ?>
            </div>
            <div class="gate-text">
              <?php if ($is_completed): ?>
                <div class="gate-title">Leçon terminée !</div>
                <div class="gate-sub">Vous avez complété cette leçon.</div>
              <?php elseif ($lesson['type'] === 'video'): ?>
                <div class="gate-title" id="gate-title">Regardez la vidéo jusqu'à la fin</div>
                <div class="gate-sub" id="gate-sub">La progression se met à jour automatiquement à 90%.</div>
              <?php else: ?>
                <div class="gate-title" id="gate-title">Lisez le PDF et cliquez sur le bouton ci-dessous</div>
                <div class="gate-sub" id="gate-sub">Le bouton apparaît après 30 secondes de lecture.</div>
              <?php endif; ?>
            </div>
            <?php if ($is_completed && $quiz): ?>
            <a href="quizz.php?id=<?= $lesson_id ?>" class="btn btn-primary btn-sm">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
              Passer le quiz
            </a>
            <?php elseif (!$is_completed && $lesson['type'] === 'pdf'): ?>
            <button id="btn-mark-pdf-done" class="btn btn-primary btn-sm" style="display:none;">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              J'ai lu le PDF
            </button>
            <?php endif; ?>
          </div>

          <?php if ($lesson['type'] === 'video' && !$is_completed): ?>
          <div class="video-track-wrap">
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="var(--primary)" fill="none" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            <div class="video-track-bar"><div class="video-track-fill" id="vid-track"></div></div>
            <span id="vid-pct">0%</span>
          </div>
          <?php endif; ?>

          <div class="lesson-viewer" style="margin-top:16px;">
            <?php if ($lesson['type'] === 'video'): ?>
            <video id="lesson-video" controls style="width:100%;max-height:520px;background:#000;display:block;">
              <source src="../uploads/videos/<?= sanitize($lesson['fichier']) ?>" type="video/mp4">
              Votre navigateur ne supporte pas la lecture vidéo.
            </video>
            <?php else: ?>
            <iframe id="lesson-pdf" class="pdf-frame"
              src="../uploads/pdfs/<?= sanitize($lesson['fichier']) ?>#toolbar=1&navpanes=1">
            </iframe>
            <div class="pdf-hint">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span>Lisez le document, puis cliquez sur « J'ai lu le PDF » pour valider la leçon et accéder au quiz.</span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="lesson-sidebar-panel">
          <div class="lesson-toc">
            <div class="lesson-toc-header">Sommaire du cours</div>
            <?php foreach ($toc as $t): ?>
            <a href="lesson.php?id=<?= $t['id'] ?>"
               class="toc-item <?= $t['id']==$lesson_id?'current':'' ?> <?= $t['completed_at']?'done-toc':'' ?>">
              <div class="toc-status">
                <?php if ($t['completed_at']): ?>✓<?php else: ?><?= $t['ordre'] ?><?php endif; ?>
              </div>
              <span style="flex:1;font-size:.78rem;"><?= sanitize($t['titre']) ?></span>
              <span class="badge <?= $t['type']==='video'?'badge-violet':'badge-blue' ?>" style="font-size:.58rem;"><?= $t['type']==='video'?'Vid':'PDF' ?></span>
            </a>
            <?php endforeach; ?>
          </div>

          <div style="display:flex;gap:8px;">
            <?php if ($prev): ?>
            <a href="lesson.php?id=<?= $prev['id'] ?>" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center;">← Préc.</a>
            <?php endif; ?>
            <?php if ($next): ?>
            <a href="lesson.php?id=<?= $next['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;justify-content:center;">Suiv. →</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<div id="toast-container" class="toast-container"></div>

<script>
window.LESSON_ID       = <?= $lesson_id ?>;
window.LESSON_TYPE     = '<?= $lesson['type'] ?>';
window.HAS_QUIZ        = <?= $quiz ? 'true' : 'false' ?>;
window.QUIZ_URL        = 'quizz.php?id=<?= $lesson_id ?>';
window.IS_ALREADY_DONE = <?= $is_completed ? 'true' : 'false' ?>;
</script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/tracking/core.js"></script>
<?php if ($lesson['type'] === 'video'): ?>
<script src="../assets/js/tracking/video.js"></script>
<?php else: ?>
<script src="../assets/js/tracking/pdf.js"></script>
<?php endif; ?>
</body>
</html>
