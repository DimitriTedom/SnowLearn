<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$db  = getDB();
$msg = $err = '';

// Délivrer un certificat manuellement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deliver') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $module_id  = (int)($_POST['module_id'] ?? 0);
    if ($student_id && $module_id) {
        $db->prepare("INSERT IGNORE INTO certificates (student_id, module_id) VALUES (?,?)")
           ->execute([$student_id, $module_id]);
        $msg = 'Certificat délivré avec succès.';
    }
}

// Révoquer un certificat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke') {
    $id = (int)($_POST['cert_id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM certificates WHERE id=?")->execute([$id]);
        $msg = 'Certificat révoqué.';
    }
}

// Certificates list
$certs = $db->query("
    SELECT c.*, 
           u.prenom, u.nom, u.email,
           m.titre AS module_titre, m.passing_threshold,
           p.pourcentage AS student_pct
    FROM certificates c
    JOIN users u ON u.id = c.student_id
    JOIN modules m ON m.id = c.module_id
    LEFT JOIN progress p ON p.student_id = c.student_id AND p.module_id = c.module_id
    ORDER BY c.delivered_at DESC
")->fetchAll();

// Eligible students (completed module but no cert yet)
$eligible = $db->query("
    SELECT p.student_id, p.module_id, p.pourcentage,
           u.prenom, u.nom, u.email,
           m.titre AS module_titre, m.passing_threshold
    FROM progress p
    JOIN users u ON u.id = p.student_id
    JOIN modules m ON m.id = p.module_id
    WHERE p.pourcentage >= m.passing_threshold
      AND NOT EXISTS (SELECT 1 FROM certificates c WHERE c.student_id=p.student_id AND c.module_id=p.module_id)
    ORDER BY p.pourcentage DESC
")->fetchAll();

// All students + modules for manual
$all_students = $db->query("SELECT id, prenom, nom FROM users WHERE role='student' ORDER BY nom")->fetchAll();
$all_modules  = $db->query("SELECT id, titre FROM modules ORDER BY titre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Certificats</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Gestion des Certificats</span>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-deliver')">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Délivrer un certificat
        </button>
      </div>
    </header>
    <main class="page-body">
      <?php if ($msg): ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= sanitize($err) ?></div><?php endif; ?>

      <!-- Eligible students -->
      <?php if (!empty($eligible)): ?>
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
          <h3>Étudiants éligibles (<?= count($eligible) ?>)</h3>
          <span class="badge badge-amber">En attente de certification</span>
        </div>
        <div style="padding:0;">
          <?php foreach ($eligible as $e): ?>
          <div style="display:flex;align-items:center;gap:16px;padding:14px 20px;border-bottom:1px solid var(--border);">
            <div class="user-avatar" style="width:36px;height:36px;font-size:.8rem;"><?= strtoupper(substr($e['prenom'],0,1).substr($e['nom'],0,1)) ?></div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:.875rem;"><?= sanitize($e['prenom'].' '.$e['nom']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted);">Module : <?= sanitize($e['module_titre']) ?> · Score : <?= round($e['pourcentage']) ?>% (seuil <?= $e['passing_threshold'] ?>%)</div>
            </div>
            <form method="POST">
              <input type="hidden" name="action" value="deliver">
              <input type="hidden" name="student_id" value="<?= $e['student_id'] ?>">
              <input type="hidden" name="module_id" value="<?= $e['module_id'] ?>">
              <button class="btn btn-success btn-sm">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                Délivrer
              </button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Certificates table -->
      <div class="card">
        <div class="card-header">
          <h3>Certificats délivrés (<?= count($certs) ?>)</h3>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Étudiant</th><th>Module</th><th>Score</th><th>Délivré le</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if (empty($certs)): ?>
              <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);">Aucun certificat délivré.</td></tr>
              <?php else: foreach ($certs as $c): ?>
              <tr>
                <td>
                  <div style="font-weight:600;font-size:.875rem;"><?= sanitize($c['prenom'].' '.$c['nom']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted);"><?= sanitize($c['email']) ?></div>
                </td>
                <td style="font-size:.875rem;"><?= sanitize($c['module_titre']) ?></td>
                <td>
                  <?php if ($c['student_pct']): ?>
                  <span class="badge badge-green"><?= round($c['student_pct']) ?>%</span>
                  <?php else: ?><span class="badge badge-gray">—</span><?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d/m/Y H:i', strtotime($c['delivered_at'])) ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Révoquer ce certificat ?')">
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="cert_id" value="<?= $c['id'] ?>">
                    <button class="btn btn-danger btn-sm">Révoquer</button>
                  </form>
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

<!-- Modal Deliver Manual -->
<div class="modal-overlay" id="modal-deliver">
  <div class="modal">
    <div class="modal-header">
      <h3>Délivrer un certificat</h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="deliver">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Étudiant *</label>
          <select class="form-control" name="student_id" required>
            <option value="">— Choisir un étudiant —</option>
            <?php foreach ($all_students as $s): ?>
            <option value="<?= $s['id'] ?>"><?= sanitize($s['prenom'].' '.$s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Module *</label>
          <select class="form-control" name="module_id" required>
            <option value="">— Choisir un module —</option>
            <?php foreach ($all_modules as $m): ?>
            <option value="<?= $m['id'] ?>"><?= sanitize($m['titre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Délivrer</button>
      </div>
    </form>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
