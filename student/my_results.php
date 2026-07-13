<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$db         = getDB();
$student_id = currentUser()['id'];

$results = $db->prepare("
    SELECT r.*,
           q.titre AS quiz_titre, q.passing_score,
           l.titre AS lesson_titre, l.type AS lesson_type,
           c.titre AS course_titre,
           m.titre AS module_titre
    FROM results r
    JOIN quizzes q ON q.id=r.quiz_id
    JOIN lessons l ON l.id=q.lesson_id
    JOIN courses c ON c.id=l.course_id
    JOIN modules m ON m.id=c.module_id
    WHERE r.student_id=?
    ORDER BY r.taken_at DESC
");
$results->execute([$student_id]);
$results = $results->fetchAll();

$total_quizzes = count($results);
$passed_quizzes= count(array_filter($results, fn($r) => $r['passed']));
$avg_score     = $total_quizzes ? round(array_sum(array_column($results, 'pourcentage')) / $total_quizzes, 1) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Mes résultats</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Mes Résultats</span>
    </header>
    <main class="page-body">
      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon indigo"><svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/></svg></div>
          <div><div class="stat-value"><?= $total_quizzes ?></div><div class="stat-label">Quiz passés</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></div>
          <div><div class="stat-value"><?= $passed_quizzes ?></div><div class="stat-label">Validés</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <div><div class="stat-value"><?= $avg_score ?>%</div><div class="stat-label">Score moyen</div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Historique des quiz</h3>
          <input class="form-control" type="search" id="search-res" placeholder="Rechercher…" style="max-width:240px;">
        </div>
        <div class="table-wrap">
          <table id="res-table">
            <thead>
              <tr><th>Module</th><th>Cours</th><th>Leçon</th><th>Quiz</th><th>Score</th><th>Statut</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
              <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted);">Aucun quiz passé.</td></tr>
              <?php else: foreach ($results as $r): ?>
              <tr>
                <td style="font-size:.82rem;font-weight:600;"><?= sanitize($r['module_titre']) ?></td>
                <td style="font-size:.78rem;color:var(--text-muted);"><?= sanitize($r['course_titre']) ?></td>
                <td style="font-size:.78rem;"><?= sanitize($r['lesson_titre']) ?></td>
                <td style="font-size:.82rem;"><?= sanitize($r['quiz_titre']) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress-bar" style="width:50px;">
                      <div class="progress-fill <?= $r['passed']?'green':'red' ?>" data-target="<?= round($r['pourcentage']) ?>"></div>
                    </div>
                    <span style="font-size:.8rem;font-weight:700;"><?= $r['score'] ?>/<?= $r['total'] ?></span>
                  </div>
                </td>
                <td>
                  <span class="badge <?= $r['passed']?'badge-green':'badge-red' ?>"><?= round($r['pourcentage']) ?>%</span>
                </td>
                <td style="font-size:.75rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($r['taken_at'])) ?></td>
                <td>
                  <a href="quizz.php?id=<?= $r['lesson_id'] ?>" class="btn btn-ghost btn-sm">Revoir</a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
<script>tableSearch('search-res','res-table');</script>
</body>
</html>
