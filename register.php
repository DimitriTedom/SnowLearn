<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom']     ?? '');
    $prenom  = trim($_POST['prenom']  ?? '');
    $email   = trim($_POST['email']   ?? '');
    $password= trim($_POST['password']?? '');
    $confirm = trim($_POST['confirm'] ?? '');
    $role    = $_POST['role'] ?? 'student';

    if (!in_array($role, ['student', 'teacher'])) $role = 'student';

    if (!$nom || !$prenom || !$email || !$password || !$confirm) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $db = getDB();
        $check = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('INSERT INTO users (nom, prenom, email, password, role) VALUES (?,?,?,?,?)')
               ->execute([$nom, $prenom, $email, $hash, $role]);
            header('Location: ' . BASE_URL . '/index.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Créer un compte</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="landing-page" style="align-items:center;justify-content:center;padding:40px 20px;">
  <div class="landing-bg-blob blob1"></div>
  <div class="landing-bg-blob blob2"></div>

  <div style="width:100%;max-width:480px;position:relative;z-index:2;">
    <div style="text-align:center;margin-bottom:28px;">
      <a href="index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
        <div class="logo-mark">SL</div>
        <span class="logo-text" style="font-size:1.4rem;">SnowLearn</span>
      </a>
      <p style="font-size:.875rem;color:var(--text-muted);margin-top:8px;">Créez votre compte gratuitement</p>
    </div>

    <div class="login-box">
      <h2 style="margin-bottom:6px;">Inscription</h2>
      <p>Remplissez le formulaire pour rejoindre la plateforme.</p>

      <?php if ($error): ?>
      <div class="alert alert-danger"><?= sanitize($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="prenom">Prénom *</label>
            <input class="form-control" type="text" id="prenom" name="prenom" required
              placeholder="Jean" value="<?= sanitize($_POST['prenom'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="nom">Nom *</label>
            <input class="form-control" type="text" id="nom" name="nom" required
              placeholder="Dupont" value="<?= sanitize($_POST['nom'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Adresse e-mail *</label>
          <input class="form-control" type="email" id="email" name="email" required
            placeholder="vous@exemple.com" value="<?= sanitize($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Je m'inscris en tant que *</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px;">
            <label style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:var(--t);" id="role-student-label">
              <input type="radio" name="role" value="student" id="role-student" style="accent-color:var(--primary);"
                <?= (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : '' ?>>
              <div>
                <div style="font-size:.82rem;font-weight:700;">Étudiant</div>
                <div style="font-size:.7rem;color:var(--text-muted);">Suivre des cours</div>
              </div>
            </label>
            <label style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:var(--t);" id="role-teacher-label">
              <input type="radio" name="role" value="teacher" id="role-teacher" style="accent-color:var(--primary);"
                <?= (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'checked' : '' ?>>
              <div>
                <div style="font-size:.82rem;font-weight:700;">Enseignant</div>
                <div style="font-size:.7rem;color:var(--text-muted);">Créer des cours</div>
              </div>
            </label>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="password">Mot de passe *</label>
            <input class="form-control" type="password" id="password" name="password"
              placeholder="Min. 8 caractères" required minlength="8">
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm">Confirmation *</label>
            <input class="form-control" type="password" id="confirm" name="confirm"
              placeholder="Répéter" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px;font-size:.95rem;">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          Créer mon compte
        </button>
      </form>

      <div class="divider"></div>
      <p class="text-center" style="font-size:.82rem;color:var(--text-muted);">
        Déjà un compte ?
        <a href="index.php" style="color:var(--primary);font-weight:600;">Se connecter</a>
      </p>
    </div>
  </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
