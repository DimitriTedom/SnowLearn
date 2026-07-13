<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');
$db = getDB();
$teachers = $db->query("
    SELECT u.*,
           COUNT(DISTINCT c.id) AS nb_courses,
           COUNT(DISTINCT l.id) AS nb_lessons
    FROM users u
    LEFT JOIN courses c ON c.teacher_id = u.id
    LEFT JOIN lessons l ON l.course_id = c.id
    WHERE u.role = 'teacher'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Enseignants</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Gestion des Enseignants</span>
    </header>
    <main class="page-body">
      <div class="card">
        <div class="card-header">
          <h3>Enseignants (<?= count($teachers) ?>)</h3>
          <input class="form-control" type="search" id="search-te" placeholder="Rechercher…" style="max-width:260px;">
        </div>
        <div class="table-wrap">
          <table id="te-table">
            <thead>
              <tr><th>Enseignant</th><th>Email</th><th>Cours</th><th>Leçons</th><th>Inscrit le</th></tr>
            </thead>
            <tbody>
              <?php foreach ($teachers as $t): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div class="user-avatar" style="width:32px;height:32px;font-size:.72rem;background:linear-gradient(135deg,var(--info),var(--primary));"><?= strtoupper(substr($t['prenom'],0,1).substr($t['nom'],0,1)) ?></div>
                    <span style="font-weight:600;font-size:.875rem;"><?= sanitize($t['prenom'].' '.$t['nom']) ?></span>
                  </div>
                </td>
                <td style="font-size:.82rem;color:var(--text-muted);"><?= sanitize($t['email']) ?></td>
                <td><span class="badge badge-indigo"><?= $t['nb_courses'] ?></span></td>
                <td><span class="badge badge-violet"><?= $t['nb_lessons'] ?></span></td>
                <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
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
<script>tableSearch('search-te','te-table');</script>
</body>
</html>
