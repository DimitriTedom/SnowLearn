<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('teacher');

$db = getDB();
$user = currentUser();
$teacher_id = $user['id'];

$results = $db->prepare("
    SELECT r.*, 
           u.prenom, u.nom, u.email,
           q.titre AS quiz_titre, q.passing_score,
           l.titre AS lesson_titre, l.type AS lesson_type,
           c.titre AS course_titre
    FROM results r
    JOIN users u ON u.id = r.student_id
    JOIN quizzes q ON q.id = r.quiz_id
    JOIN lessons l ON l.id = q.lesson_id
    JOIN courses c ON c.id = l.course_id
    WHERE c.teacher_id = ?
    ORDER BY r.taken_at DESC
");
$results->execute([$teacher_id]);
$results = $results->fetchAll();

// Average stats
$avg_score = count($results) ? round(array_sum(array_column($results, 'pourcentage')) / count($results), 1) : 0;
$nb_passed = count(array_filter($results, fn($r) => $r['passed']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Résultats étudiants</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Résultats des étudiants</span>
    </header>
    <main class="page-body">
      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-icon indigo"><svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
          <div><div class="stat-value"><?= count($results) ?></div><div class="stat-label">Quiz soumis</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></div>
          <div><div class="stat-value"><?= $nb_passed ?></div><div class="stat-label">Quiz validés</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <div><div class="stat-value"><?= $avg_score ?>%</div><div class="stat-label">Score moyen</div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Tous les résultats</h3>
          <input class="form-control" type="search" id="search-res" placeholder="Rechercher…" style="max-width:260px;">
        </div>
        <div class="table-wrap">
          <table id="res-table">
            <thead>
              <tr><th>Étudiant</th><th>Cours</th><th>Leçon</th><th>Quiz</th><th>Score</th><th>Statut</th><th>Date</th></tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
              <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted);">Aucun résultat.</td></tr>
              <?php else: foreach ($results as $r): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div class="user-avatar" style="width:28px;height:28px;font-size:.65rem;"><?= strtoupper(substr($r['prenom'],0,1).substr($r['nom'],0,1)) ?></div>
                    <div>
                      <div style="font-size:.82rem;font-weight:600;"><?= sanitize($r['prenom'].' '.$r['nom']) ?></div>
                      <div style="font-size:.72rem;color:var(--text-muted);"><?= sanitize($r['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="font-size:.82rem;"><?= sanitize($r['course_titre']) ?></td>
                <td style="font-size:.82rem;"><?= sanitize($r['lesson_titre']) ?></td>
                <td style="font-size:.82rem;"><?= sanitize($r['quiz_titre']) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress-bar" style="width:60px;">
                      <div class="progress-fill <?= $r['passed']?'green':'red' ?>" data-target="<?= round($r['pourcentage']) ?>"></div>
                    </div>
                    <span style="font-size:.82rem;font-weight:700;"><?= $r['score'] ?>/<?= $r['total'] ?> (<?= round($r['pourcentage']) ?>%)</span>
                  </div>
                </td>
                <td>
                  <?php if ($r['passed']): ?>
                    <span class="badge badge-green">Validé</span>
                  <?php else: ?>
                    <span class="badge badge-red">Échoué</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:.75rem;color:var(--text-muted);"><?= date('d/m/Y H:i', strtotime($r['taken_at'])) ?></td>
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
