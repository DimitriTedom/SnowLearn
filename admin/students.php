<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');
$db = getDB();
$students = $db->query("
    SELECT u.*, 
           COUNT(DISTINCT sl.lesson_id) AS lessons_done,
           COUNT(DISTINCT r.id) AS quizzes_done,
           COALESCE(AVG(r.pourcentage), 0) AS avg_score
    FROM users u
    LEFT JOIN student_lessons sl ON sl.student_id = u.id
    LEFT JOIN results r ON r.student_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Étudiants</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Gestion des Étudiants</span>
    </header>
    <main class="page-body">
      <div class="card">
        <div class="card-header">
          <h3>Étudiants (<?= count($students) ?>)</h3>
          <input class="form-control" type="search" id="search-st" placeholder="Rechercher…" style="max-width:260px;">
        </div>
        <div class="table-wrap">
          <table id="st-table">
            <thead>
              <tr>
                <th>Étudiant</th><th>Email</th><th>Leçons</th><th>Quiz</th><th>Score moyen</th><th>Inscrit le</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div class="user-avatar" style="width:32px;height:32px;font-size:.72rem;"><?= strtoupper(substr($s['prenom'],0,1).substr($s['nom'],0,1)) ?></div>
                    <span style="font-weight:600;font-size:.875rem;"><?= sanitize($s['prenom'].' '.$s['nom']) ?></span>
                  </div>
                </td>
                <td style="font-size:.82rem;color:var(--text-muted);"><?= sanitize($s['email']) ?></td>
                <td><span class="badge badge-indigo"><?= $s['lessons_done'] ?></span></td>
                <td><span class="badge badge-violet"><?= $s['quizzes_done'] ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress-bar" style="width:80px;">
                      <div class="progress-fill" data-target="<?= round($s['avg_score']) ?>"></div>
                    </div>
                    <span style="font-size:.82rem;font-weight:700;"><?= round($s['avg_score']) ?>%</span>
                  </div>
                </td>
                <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
<script>tableSearch('search-st','st-table');</script>
</body>
</html>
