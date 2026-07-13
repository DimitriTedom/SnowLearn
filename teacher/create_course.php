<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('teacher');
$db = getDB();
$user = currentUser();
$teacher_id = $user['id'];

$modules = $db->query("SELECT id, titre FROM modules ORDER BY titre")->fetchAll();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre     = trim($_POST['titre'] ?? '');
    $module_id = (int)($_POST['module_id'] ?? 0);
    $desc      = trim($_POST['description'] ?? '');

    if (!$titre || !$module_id) {
        $err = 'Le titre et le module sont obligatoires.';
    } else {
        $db->prepare("INSERT INTO courses (module_id, teacher_id, titre, description) VALUES (?,?,?,?)")
           ->execute([$module_id, $teacher_id, $titre, $desc]);
        header('Location: ' . BASE_URL . '/teacher/courses.php?created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Créer un cours</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Créer un cours</span>
      <div class="topbar-right">
        <a href="courses.php" class="btn btn-ghost btn-sm">← Retour</a>
      </div>
    </header>
    <main class="page-body">
      <?php if ($err): ?><div class="alert alert-danger"><?= sanitize($err) ?></div><?php endif; ?>

      <div class="card" style="max-width:600px;">
        <div class="card-header"><h3>Nouveau cours</h3></div>
        <form method="POST">
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Titre du cours *</label>
              <input class="form-control" name="titre" required placeholder="Ex : Les bases du JavaScript" value="<?= sanitize($_POST['titre'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Module *</label>
              <select class="form-control" name="module_id" required>
                <option value="">— Choisir un module —</option>
                <?php foreach ($modules as $m): ?>
                <option value="<?= $m['id'] ?>" <?= (($_POST['module_id'] ?? 0) == $m['id']) ? 'selected' : '' ?>><?= sanitize($m['titre']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (empty($modules)): ?>
              <span class="form-hint" style="color:var(--warning);">Aucun module disponible. Demandez au promoteur d'en créer un.</span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="4" placeholder="Décrivez ce que l'étudiant va apprendre…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
            </div>
          </div>
          <div class="modal-footer" style="padding:16px 24px;">
            <a href="courses.php" class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              Créer le cours
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
