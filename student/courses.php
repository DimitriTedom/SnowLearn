<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$db         = getDB();
$student_id = currentUser()['id'];
$module_id  = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
if (!$module_id) { header('Location: modules.php'); exit; }

$module = $db->prepare("SELECT * FROM modules WHERE id=? LIMIT 1");
$module->execute([$module_id]);
$module = $module->fetch();
if (!$module) { header('Location: modules.php'); exit; }

// All courses in module with lessons
$courses = $db->prepare("
    SELECT c.*,
           u.prenom, u.nom
    FROM courses c
    JOIN users u ON u.id=c.teacher_id
    WHERE c.module_id=?
    ORDER BY c.created_at ASC
");
$courses->execute([$module_id]);
$courses = $courses->fetchAll();

foreach ($courses as &$c) {
    $stmt = $db->prepare("
        SELECT l.*,
               q.id AS quiz_id,
               r.passed, r.pourcentage AS quiz_pct,
               sl.completed_at
        FROM lessons l
        LEFT JOIN quizzes q ON q.lesson_id=l.id
        LEFT JOIN results r ON r.student_id=? AND r.quiz_id=q.id
        LEFT JOIN student_lessons sl ON sl.student_id=? AND sl.lesson_id=l.id
        WHERE l.course_id=?
        ORDER BY l.ordre ASC
    ");
    $stmt->execute([$student_id, $student_id, $c['id']]);
    $c['lessons'] = $stmt->fetchAll();
}
unset($c);

// My progress
$prog = $db->prepare("SELECT * FROM progress WHERE student_id=? AND module_id=? LIMIT 1");
$prog->execute([$student_id, $module_id]);
$prog = $prog->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — <?= sanitize($module['titre']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title"><?= sanitize($module['titre']) ?></span>
      <div class="topbar-right">
        <a href="modules.php" class="btn btn-ghost btn-sm">← Modules</a>
      </div>
    </header>
    <main class="page-body">
      <!-- Module header -->
      <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,rgba(99,102,241,.12),rgba(168,85,247,.08));border-color:rgba(99,102,241,.2);">
        <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
          <div style="flex:1;min-width:200px;">
            <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--primary);margin-bottom:6px;">Module</div>
            <h2 style="font-size:1.4rem;margin-bottom:8px;"><?= sanitize($module['titre']) ?></h2>
            <p style="font-size:.875rem;color:var(--text-muted);"><?= sanitize($module['description'] ?? '') ?></p>
          </div>
          <?php if ($prog): ?>
          <div style="text-align:center;padding:0 20px;">
            <div style="font-size:2.2rem;font-weight:800;background:linear-gradient(90deg,var(--primary),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= round($prog['pourcentage']) ?>%</div>
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px;"><?= $prog['lessons_done'] ?>/<?= $prog['lessons_total'] ?> leçons</div>
            <div class="progress-bar" style="width:120px;">
              <div class="progress-fill <?= $prog['pourcentage'] >= $module['passing_threshold'] ? 'green' : '' ?>" data-target="<?= round($prog['pourcentage']) ?>"></div>
            </div>
            <div style="font-size:.7rem;color:var(--text-dim);margin-top:4px;">Seuil : <?= $module['passing_threshold'] ?>%</div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Courses & Lessons -->
      <?php foreach ($courses as $c): ?>
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
          <div>
            <h3 style="font-size:1rem;"><?= sanitize($c['titre']) ?></h3>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">Par <?= sanitize($c['prenom'].' '.$c['nom']) ?> · <?= count($c['lessons']) ?> leçon(s)</div>
          </div>
          <span class="badge badge-indigo"><?= count($c['lessons']) ?> leçon(s)</span>
        </div>
        <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
          <?php if (empty($c['lessons'])): ?>
          <div style="text-align:center;color:var(--text-muted);font-size:.82rem;padding:12px;">Aucune leçon disponible.</div>
          <?php else: foreach ($c['lessons'] as $l):
            $is_done = !empty($l['completed_at']);
          ?>
          <a href="lesson.php?id=<?= $l['id'] ?>" class="lesson-item <?= $is_done ? 'completed' : '' ?>">
            <div class="lesson-num">
              <?php if ($is_done): ?>
              <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <?php else: ?>
              <?= $l['ordre'] ?>
              <?php endif; ?>
            </div>
            <div class="lesson-info">
              <div class="lesson-title"><?= sanitize($l['titre']) ?></div>
              <div class="lesson-meta">
                <span class="badge <?= $l['type']==='video'?'badge-violet':'badge-blue' ?>" style="font-size:.6rem;"><?= $l['type']==='video'?'Vidéo':'PDF' ?></span>
                <?php if ($l['quiz_id']): ?>
                  <?php if ($l['passed'] !== null): ?>
                  <span style="margin-left:6px;font-size:.7rem;color:<?= $l['passed'] ? 'var(--success)' : 'var(--danger)' ?>;">Quiz : <?= round($l['quiz_pct']) ?>%</span>
                  <?php else: ?>
                  <span style="margin-left:6px;font-size:.7rem;color:var(--text-dim);">Quiz disponible</span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="var(--text-dim)" fill="none" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
