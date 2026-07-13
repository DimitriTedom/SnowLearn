<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$db         = getDB();
$student_id = currentUser()['id'];

// Tous les modules avec progression étudiant
$modules = $db->prepare("
    SELECT m.*,
           u.prenom, u.nom AS teacher_nom,
           (SELECT COUNT(*) FROM courses WHERE module_id=m.id) AS nb_courses,
           (SELECT COUNT(*) FROM lessons l JOIN courses c ON c.id=l.course_id WHERE c.module_id=m.id) AS nb_lessons,
           COALESCE(p.pourcentage, 0) AS my_pct,
           COALESCE(p.lessons_done, 0) AS my_done,
           c2.id AS cert_id
    FROM modules m
    JOIN users u ON u.id = m.created_by
    LEFT JOIN progress p ON p.student_id=? AND p.module_id=m.id
    LEFT JOIN certificates c2 ON c2.student_id=? AND c2.module_id=m.id
    ORDER BY m.created_at DESC
");
$modules->execute([$student_id, $student_id]);
$modules = $modules->fetchAll();

// Gradient presets per module
$gradients = [
    ['#6366f1','#a855f7'],
    ['#3b82f6','#6366f1'],
    ['#10b981','#3b82f6'],
    ['#f59e0b','#ef4444'],
    ['#a855f7','#ec4899'],
    ['#14b8a6','#6366f1'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Modules & Cours</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Modules & Cours</span>
      <div class="topbar-right">
        <input class="form-control" type="search" id="search-mod" placeholder="Rechercher un module…" style="width:240px;">
      </div>
    </header>
    <main class="page-body">
      <div class="page-header">
        <div>
          <h2>Catalogue de formation</h2>
          <p class="page-subtitle"><?= count($modules) ?> module(s) disponible(s)</p>
        </div>
      </div>

      <?php if (empty($modules)): ?>
      <div class="card"><div style="padding:60px;text-align:center;color:var(--text-muted);">Aucun module disponible pour le moment.</div></div>
      <?php else: ?>
      <div class="module-grid" id="module-grid">
        <?php foreach ($modules as $i => $m):
          [$c1, $c2] = $gradients[$i % count($gradients)];
          $pct = round($m['my_pct']);
        ?>
        <div class="module-card mod-item" data-title="<?= strtolower(sanitize($m['titre'])) ?>" onclick="location.href='courses.php?module_id=<?= $m['id'] ?>'">
          <div class="module-card-cover" style="background:linear-gradient(135deg,<?= $c1 ?>,<?= $c2 ?>);">
            <?php if ($m['cert_id']): ?>
            <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.3);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);border-radius:99px;padding:4px 10px;font-size:.65rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:5px;">
              <svg viewBox="0 0 24 24" width="10" height="10" stroke="#fff" fill="none" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
              Certifié
            </div>
            <?php endif; ?>
            <div class="module-card-icon">
              <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
          </div>
          <div class="module-card-body">
            <div class="module-card-title"><?= sanitize($m['titre']) ?></div>
            <div class="module-card-desc"><?= sanitize($m['description'] ?? '') ?></div>
          </div>
          <div class="module-card-footer">
            <div>
              <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:5px;"><?= $m['nb_lessons'] ?> leçon(s) · seuil <?= $m['passing_threshold'] ?>%</div>
              <div class="progress-bar" style="width:140px;">
                <div class="progress-fill <?= $pct >= $m['passing_threshold'] ? 'green' : '' ?>" data-target="<?= $pct ?>"></div>
              </div>
            </div>
            <span style="font-size:.85rem;font-weight:700;color:<?= $pct >= $m['passing_threshold'] ? 'var(--success)' : 'var(--primary)' ?>;"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
<script>
document.getElementById('search-mod').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.mod-item').forEach(el => {
    el.style.display = el.dataset.title.includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>
