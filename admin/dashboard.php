<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$db   = getDB();
$user = currentUser();

// Stats
$stats = [];
$stats['modules']  = $db->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$stats['courses']  = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$stats['teachers'] = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$stats['students'] = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$stats['certs']    = $db->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$stats['results']  = $db->query("SELECT COUNT(*) FROM results")->fetchColumn();

// Recent modules
$recent_modules = $db->query("
    SELECT m.*, u.prenom, u.nom,
           (SELECT COUNT(*) FROM courses WHERE module_id=m.id) AS nb_courses,
           (SELECT COUNT(*) FROM progress  WHERE module_id=m.id) AS nb_students
    FROM modules m JOIN users u ON u.id=m.created_by
    ORDER BY m.created_at DESC LIMIT 5
")->fetchAll();

// Top students by progress
$top_students = $db->query("
    SELECT u.prenom, u.nom, AVG(p.pourcentage) AS avg_pct, COUNT(p.module_id) AS nb_modules
    FROM progress p JOIN users u ON u.id=p.student_id
    GROUP BY p.student_id
    ORDER BY avg_pct DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Tableau de bord Promoteur</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle" aria-label="Menu">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <span class="topbar-title">Tableau de bord</span>
      <div class="topbar-right">
        <span style="font-size:.82rem;color:var(--text-muted);">Bonjour, <strong style="color:var(--text);"><?= sanitize($user['prenom']) ?></strong> 👋</span>
        <a href="modules.php" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nouveau module
        </a>
      </div>
    </header>

    <main class="page-body">
      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon violet">
            <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['modules'] ?></div>
            <div class="stat-label">Modules</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['courses'] ?></div>
            <div class="stat-label">Cours</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['teachers'] ?></div>
            <div class="stat-label">Enseignants</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['students'] ?></div>
            <div class="stat-label">Étudiants</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['certs'] ?></div>
            <div class="stat-label">Certificats délivrés</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">
            <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['results'] ?></div>
            <div class="stat-label">Quiz passés</div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- Recent modules -->
        <div class="card">
          <div class="card-header">
            <h3>Modules récents</h3>
            <a href="modules.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div style="padding:0;">
            <?php if (empty($recent_modules)): ?>
              <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:.875rem;">Aucun module créé.</div>
            <?php else: foreach ($recent_modules as $m): ?>
            <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);">
              <div style="width:40px;height:40px;background:var(--primary-light);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="var(--primary)" fill="none" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/></svg>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($m['titre']) ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);"><?= $m['nb_courses'] ?> cours · <?= $m['nb_students'] ?> étudiant(s)</div>
              </div>
              <span class="badge badge-indigo"><?= date('d/m/y', strtotime($m['created_at'])) ?></span>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Top students -->
        <div class="card">
          <div class="card-header">
            <h3>Top étudiants</h3>
            <a href="students.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div style="padding:0;">
            <?php if (empty($top_students)): ?>
              <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:.875rem;">Aucune progression enregistrée.</div>
            <?php else: foreach ($top_students as $i => $s): ?>
            <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);">
              <div style="width:28px;height:28px;border-radius:50%;background:var(--surface3);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--text-muted);flex-shrink:0;"><?= $i+1 ?></div>
              <div style="flex:1;">
                <div style="font-weight:600;font-size:.875rem;"><?= sanitize($s['prenom'] . ' ' . $s['nom']) ?></div>
                <div class="progress-bar" style="margin-top:6px;">
                  <div class="progress-fill" data-target="<?= round($s['avg_pct']) ?>" style="width:0%"></div>
                </div>
              </div>
              <span style="font-size:.82rem;font-weight:700;color:var(--primary);"><?= round($s['avg_pct']) ?>%</span>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
