<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$db  = getDB();
$msg = $err = '';

// --- Teachers list for select ---
$teachers = $db->query("SELECT id, prenom, nom FROM users WHERE role='teacher' ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $titre     = trim($_POST['titre'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $threshold = (int) ($_POST['passing_threshold'] ?? 70);
        if ($titre) {
            $db->prepare("INSERT INTO modules (titre, description, passing_threshold, created_by) VALUES (?,?,?,?)")
               ->execute([$titre, $desc, $threshold, $_SESSION['user_id']]);
            $msg = 'Module créé avec succès.';
        } else {
            $err = 'Le titre est requis.';
        }
    }

    elseif ($action === 'edit') {
        $id        = (int) ($_POST['id'] ?? 0);
        $titre     = trim($_POST['titre'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $threshold = (int) ($_POST['passing_threshold'] ?? 70);
        if ($id && $titre) {
            $db->prepare("UPDATE modules SET titre=?, description=?, passing_threshold=? WHERE id=?")
               ->execute([$titre, $desc, $threshold, $id]);
            $msg = 'Module modifié avec succès.';
        }
    }

    elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM modules WHERE id=?")->execute([$id]);
            $msg = 'Module supprimé.';
        }
    }
}

$modules = $db->query("
    SELECT m.*, u.prenom, u.nom,
           (SELECT COUNT(*) FROM courses WHERE module_id=m.id) AS nb_courses,
           (SELECT COUNT(*) FROM progress WHERE module_id=m.id) AS nb_students
    FROM modules m JOIN users u ON u.id=m.created_by
    ORDER BY m.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Modules</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Gestion des Modules</span>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-create')">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nouveau Module
        </button>
      </div>
    </header>
    <main class="page-body">
      <?php if ($msg): ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= sanitize($err) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3>Modules (<?= count($modules) ?>)</h3>
          <input class="form-control" type="search" id="search-mod" placeholder="Rechercher…" style="max-width:260px;">
        </div>
        <div class="table-wrap">
          <table id="mod-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Titre</th>
                <th>Description</th>
                <th>Seuil</th>
                <th>Cours</th>
                <th>Étudiants</th>
                <th>Créé par</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($modules as $m): ?>
              <tr>
                <td style="color:var(--text-muted);font-size:.8rem;">#<?= $m['id'] ?></td>
                <td><strong><?= sanitize($m['titre']) ?></strong></td>
                <td style="font-size:.82rem;color:var(--text-muted);max-width:200px;"><?= sanitize(mb_substr($m['description'] ?? '', 0, 70)) ?>…</td>
                <td><span class="badge badge-amber"><?= $m['passing_threshold'] ?>%</span></td>
                <td><span class="badge badge-indigo"><?= $m['nb_courses'] ?></span></td>
                <td><span class="badge badge-green"><?= $m['nb_students'] ?></span></td>
                <td style="font-size:.82rem;"><?= sanitize($m['prenom'] . ' ' . $m['nom']) ?></td>
                <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <button class="btn btn-ghost btn-sm btn-icon"
                      onclick="editModule(<?= $m['id'] ?>, '<?= addslashes(sanitize($m['titre'])) ?>', '<?= addslashes(sanitize($m['description'] ?? '')) ?>', <?= $m['passing_threshold'] ?>)"
                      title="Modifier">
                      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce module et tous ses cours ?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $m['id'] ?>">
                      <button class="btn btn-danger btn-sm btn-icon" title="Supprimer">
                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Modal Create -->
<div class="modal-overlay" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <h3>Nouveau module</h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre *</label>
          <input class="form-control" name="titre" required placeholder="Ex : Développement Web">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Description du module…"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Seuil de validation (%) *</label>
          <input class="form-control" type="number" name="passing_threshold" value="70" min="0" max="100" required>
          <span class="form-hint">Pourcentage minimum pour valider le module et obtenir un certificat.</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer le module</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>Modifier le module</h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre *</label>
          <input class="form-control" name="titre" id="edit-titre" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" id="edit-desc" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Seuil de validation (%)</label>
          <input class="form-control" type="number" name="passing_threshold" id="edit-threshold" value="70" min="0" max="100">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function editModule(id, titre, desc, threshold) {
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-titre').value = titre;
  document.getElementById('edit-desc').value = desc;
  document.getElementById('edit-threshold').value = threshold;
  openModal('modal-edit');
}
tableSearch('search-mod', 'mod-table');
</script>
</body>
</html>
