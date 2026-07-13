<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('teacher');

$db         = getDB();
$user       = currentUser();
$teacher_id = $user['id'];

// Stats
$nb_courses = $db->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id=?");
$nb_courses->execute([$teacher_id]);
$nb_courses = $nb_courses->fetchColumn();

$nb_lessons = $db->prepare("SELECT COUNT(*) FROM lessons l JOIN courses c ON c.id=l.course_id WHERE c.teacher_id=?");
$nb_lessons->execute([$teacher_id]);
$nb_lessons = $nb_lessons->fetchColumn();

$nb_results = $db->prepare("
    SELECT COUNT(*) FROM results r
    JOIN quizzes q ON q.id=r.quiz_id
    JOIN lessons l ON l.id=q.lesson_id
    JOIN courses c ON c.id=l.course_id
    WHERE c.teacher_id=?
");
$nb_results->execute([$teacher_id]);
$nb_results = $nb_results->fetchColumn();

// Recent results
$recent_results = $db->prepare("
    SELECT r.*, u.prenom, u.nom, q.titre AS quiz_titre, l.titre AS lesson_titre
    FROM results r
    JOIN users u ON u.id = r.student_id
    JOIN quizzes q ON q.id = r.quiz_id
    JOIN lessons l ON l.id = q.lesson_id
    JOIN courses c ON c.id = l.course_id
    WHERE c.teacher_id = ?
    ORDER BY r.taken_at DESC
    LIMIT 6
");
$recent_results->execute([$teacher_id]);
$recent_results = $recent_results->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Tableau de bord Enseignant</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Tableau de bord</span>
      <div class="topbar-right">
        <span style="font-size:.82rem;color:var(--text-muted);">Bonjour, <strong style="color:var(--text);"><?= sanitize($user['prenom']) ?></strong> 👋</span>
        <a href="create_course.php" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nouveau cours
        </a>
      </div>
    </header>
    <main class="page-body">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div><div class="stat-value"><?= $nb_courses ?></div><div class="stat-label">Cours créés</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon violet">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div><div class="stat-value"><?= $nb_lessons ?></div><div class="stat-label">Leçons publiées</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <div><div class="stat-value"><?= $nb_results ?></div><div class="stat-label">Quiz soumis</div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Derniers résultats</h3>
          <a href="results.php" class="btn btn-ghost btn-sm">Voir tout</a>
        </div>
        <div style="padding:0;">
          <?php if (empty($recent_results)): ?>
          <div style="padding:32px;text-align:center;color:var(--text-muted);">Aucun résultat pour l'instant.</div>
          <?php else: foreach ($recent_results as $r): ?>
          <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);">
            <div class="user-avatar" style="width:36px;height:36px;font-size:.78rem;"><?= strtoupper(substr($r['prenom'],0,1).substr($r['nom'],0,1)) ?></div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:.875rem;"><?= sanitize($r['prenom'].' '.$r['nom']) ?></div>
              <div style="font-size:.75rem;color:var(--text-muted);"><?= sanitize($r['lesson_titre']) ?> — <?= sanitize($r['quiz_titre']) ?></div>
            </div>
            <div style="text-align:right;">
              <span class="badge <?= $r['passed'] ? 'badge-green' : 'badge-red' ?>"><?= round($r['pourcentage']) ?>%</span>
              <div style="font-size:.72rem;color:var(--text-dim);margin-top:3px;"><?= date('d/m/Y', strtotime($r['taken_at'])) ?></div>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
