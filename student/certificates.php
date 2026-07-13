<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$db         = getDB();
$user       = currentUser();
$student_id = $user['id'];

$certs = $db->prepare("
    SELECT c.*,
           m.titre AS module_titre,
           m.description AS module_desc,
           m.passing_threshold,
           p.pourcentage, p.score_moyen
    FROM certificates c
    JOIN modules m ON m.id = c.module_id
    LEFT JOIN progress p ON p.student_id=c.student_id AND p.module_id=c.module_id
    WHERE c.student_id=?
    ORDER BY c.delivered_at DESC
");
$certs->execute([$student_id]);
$certs = $certs->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Mes Certificats</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Mes Certificats</span>
    </header>
    <main class="page-body">
      <div class="page-header">
        <div>
          <h2>Mes Certificats</h2>
          <p class="page-subtitle"><?= count($certs) ?> certificat(s) obtenu(s)</p>
        </div>
      </div>

      <?php if (empty($certs)): ?>
      <div class="card" style="text-align:center;">
        <div class="card-body" style="padding:60px;">
          <div style="width:72px;height:72px;border-radius:50%;background:var(--surface3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg viewBox="0 0 24 24" width="32" height="32" stroke="var(--text-dim)" fill="none" stroke-width="1.5"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <h3 style="font-size:1.1rem;margin-bottom:8px;">Aucun certificat encore</h3>
          <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:20px;">Complétez un module pour obtenir votre certificat.</p>
          <a href="modules.php" class="btn btn-primary">Voir les modules</a>
        </div>
      </div>

      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;">
        <?php foreach ($certs as $c): ?>
        <div>
          <!-- Certificate preview card -->
          <div class="cert-preview" style="margin-bottom:16px;">
            <div class="cert-badge">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
            </div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--primary);margin-bottom:10px;">Certificat de Validation</div>
            <div style="font-family:'Outfit',sans-serif;font-size:1rem;font-weight:800;margin-bottom:6px;"><?= sanitize($c['module_titre']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:16px;">
              Décerné à <strong style="color:var(--text);"><?= sanitize($user['prenom'] . ' ' . $user['nom']) ?></strong>
            </div>
            <?php if ($c['score_moyen']): ?>
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:4px;">Score obtenu</div>
            <div style="font-family:'Outfit',sans-serif;font-size:1.8rem;font-weight:800;background:linear-gradient(90deg,var(--primary),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= round($c['score_moyen']) ?>%</div>
            <?php endif; ?>
            <div style="margin-top:12px;font-size:.72rem;color:var(--text-dim);">
              Délivré le <?= date('d/m/Y', strtotime($c['delivered_at'])) ?>
            </div>
          </div>
          <!-- Actions -->
          <div style="display:flex;gap:8px;">
            <button onclick="printCertificate(<?= $c['id'] ?>)" class="btn btn-primary btn-sm" style="flex:1;justify-content:center;">
              <svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              Imprimer / PDF
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- Print certificate modal -->
<?php foreach ($certs as $c): ?>
<div id="cert-print-<?= $c['id'] ?>" style="display:none;">
  <div style="font-family:'Outfit', sans-serif; padding: 60px; text-align: center; background: #ffffff; color: #0f172a; max-width: 800px; margin: 0 auto; border: 12px double #4f46e5; position: relative;">
    <div style="position: absolute; inset: 10px; border: 1.5px dashed #9333ea; opacity: 0.3; pointer-events: none;"></div>
    
    <div style="display: flex; justify-content: center; margin-bottom: 24px;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #9333ea); display: flex; align-items: center; justify-content: center;">
        <svg viewBox="0 0 24 24" width="32" height="32" stroke="#ffffff" fill="none" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
      </div>
    </div>

    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .25em; color: #4f46e5; margin-bottom: 12px;">SnowLearn — Certificat de Réussite</div>
    <div style="font-size: 32px; font-weight: 800; color: #1e1b4b; margin-bottom: 16px; line-height: 1.2;"><?= sanitize($c['module_titre']) ?></div>
    
    <div style="font-size: 15px; color: #475569; margin-bottom: 24px; line-height: 1.6;">
      Ce document atteste que<br>
      <strong style="font-size: 20px; color: #0f172a; display: block; margin: 8px 0; font-family: 'Outfit', sans-serif;"><?= sanitize($user['prenom'] . ' ' . $user['nom']) ?></strong>
      a validé l'ensemble des leçons et quiz de ce module.
    </div>

    <?php if ($c['score_moyen']): ?>
    <div style="display: inline-block; background: #f1f5f9; padding: 12px 24px; border-radius: 8px; margin: 8px 0 24px;">
      <span style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">Score Moyen Obtenu</span>
      <strong style="font-size: 36px; font-weight: 900; color: #4f46e5;"><?= round($c['score_moyen']) ?>%</strong>
    </div>
    <?php endif; ?>

    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
      Délivré le <?= date('d F Y', strtotime($c['delivered_at'])) ?> · Plateforme SnowLearn LMS
    </div>
  </div>
</div>
<?php endforeach; ?>

<script src="../assets/js/app.js"></script>
<script>
function printCertificate(id) {
  const content = document.getElementById('cert-print-' + id).innerHTML;
  const win = window.open('', '_blank');
  win.document.write(`<!DOCTYPE html><html><head><title>Certificat</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>body{margin:0;padding:20px;background:#f8fafc;} @media print{body{background:#fff;}}</style>
    </head><body>${content}</body></html>`);
  win.document.close();
  setTimeout(() => win.print(), 800);
}
</script>
</body>
</html>
