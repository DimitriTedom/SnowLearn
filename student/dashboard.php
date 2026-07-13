<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$db         = getDB();
$user       = currentUser();
$student_id = $user['id'];

// Stats
$lessons_done  = $db->prepare("SELECT COUNT(*) FROM student_lessons WHERE student_id=?");
$lessons_done->execute([$student_id]);
$lessons_done  = $lessons_done->fetchColumn();

$quizzes_done  = $db->prepare("SELECT COUNT(*) FROM results WHERE student_id=?");
$quizzes_done->execute([$student_id]);
$quizzes_done  = $quizzes_done->fetchColumn();

$avg_score = $db->prepare("SELECT AVG(pourcentage) FROM results WHERE student_id=?");
$avg_score->execute([$student_id]);
$avg_score = round($avg_score->fetchColumn() ?? 0, 1);

$certs_count = $db->prepare("SELECT COUNT(*) FROM certificates WHERE student_id=?");
$certs_count->execute([$student_id]);
$certs_count = $certs_count->fetchColumn();

// Module progress
$my_progress = $db->prepare("
    SELECT p.*, m.titre AS module_titre, m.passing_threshold,
           c.id AS cert_id
    FROM progress p
    JOIN modules m ON m.id = p.module_id
    LEFT JOIN certificates c ON c.student_id=p.student_id AND c.module_id=p.module_id
    WHERE p.student_id = ?
    ORDER BY p.updated_at DESC
    LIMIT 4
");
$my_progress->execute([$student_id]);
$my_progress = $my_progress->fetchAll();

// Recent results
$recent = $db->prepare("
    SELECT r.*, q.titre AS quiz_titre, l.titre AS lesson_titre
    FROM results r
    JOIN quizzes q ON q.id=r.quiz_id
    JOIN lessons l ON l.id=q.lesson_id
    WHERE r.student_id=?
    ORDER BY r.taken_at DESC
    LIMIT 5
");
$recent->execute([$student_id]);
$recent = $recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Mon tableau de bord</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Mon Espace</span>
      <div class="topbar-right">
        <a href="modules.php" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          Découvrir les cours
        </a>
      </div>
    </header>
    <main class="page-body">
      <!-- Welcome -->
      <div style="margin-bottom:24px;">
        <h2 style="font-size:1.6rem;">Bonjour, <span style="background:linear-gradient(90deg,var(--primary),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= sanitize($user['prenom']) ?></span> 🎓</h2>
        <p style="color:var(--text-muted);margin-top:4px;">Continuez votre parcours d'apprentissage.</p>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <div><div class="stat-value"><?= $lessons_done ?></div><div class="stat-label">Leçons terminées</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon violet">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          </div>
          <div><div class="stat-value"><?= $quizzes_done ?></div><div class="stat-label">Quiz passés</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <div><div class="stat-value"><?= $avg_score ?>%</div><div class="stat-label">Score moyen</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <div><div class="stat-value"><?= $certs_count ?></div><div class="stat-label">Certificats obtenus</div></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <!-- My progress -->
        <div class="card">
          <div class="card-header">
            <h3>Ma progression</h3>
            <a href="modules.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div style="padding:16px;display:flex;flex-direction:column;gap:14px;">
            <?php if (empty($my_progress)): ?>
            <div style="text-align:center;color:var(--text-muted);font-size:.875rem;padding:20px;">
              Commencez un module pour voir votre progression.
            </div>
            <?php else: foreach ($my_progress as $p): ?>
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:.82rem;font-weight:600;"><?= sanitize($p['module_titre']) ?></span>
                <div style="display:flex;align-items:center;gap:8px;">
                  <?php if ($p['cert_id']): ?>
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="var(--warning)" fill="none" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                  <?php endif; ?>
                  <span style="font-size:.8rem;font-weight:700;color:var(--primary);"><?= round($p['pourcentage']) ?>%</span>
                </div>
              </div>
              <div class="progress-bar">
                <div class="progress-fill <?= $p['pourcentage'] >= $p['passing_threshold'] ? 'green' : '' ?>" data-target="<?= round($p['pourcentage']) ?>"></div>
              </div>
              <div style="font-size:.7rem;color:var(--text-dim);margin-top:3px;"><?= $p['lessons_done'] ?>/<?= $p['lessons_total'] ?> leçons · seuil <?= $p['passing_threshold'] ?>%</div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Recent results -->
        <div class="card">
          <div class="card-header">
            <h3>Résultats récents</h3>
            <a href="my_results.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div style="padding:0;">
            <?php if (empty($recent)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:.875rem;">Aucun quiz passé.</div>
            <?php else: foreach ($recent as $r): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);">
              <div style="width:36px;height:36px;border-radius:50%;background:<?= $r['passed'] ? 'var(--success-light)' : 'var(--danger-light)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php if ($r['passed']): ?>
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="var(--success)" fill="none" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?>
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="var(--danger)" fill="none" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <?php endif; ?>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($r['lesson_titre']) ?></div>
                <div style="font-size:.72rem;color:var(--text-muted);"><?= sanitize($r['quiz_titre']) ?></div>
              </div>
              <span style="font-size:.82rem;font-weight:700;color:<?= $r['passed'] ? 'var(--success)' : 'var(--danger)' ?>;"><?= round($r['pourcentage']) ?>%</span>
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
