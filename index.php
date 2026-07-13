<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin')        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    elseif ($role === 'teacher')  header('Location: ' . BASE_URL . '/teacher/dashboard.php');
    else                          header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['avatar']  = $user['avatar'];

            if ($user['role'] === 'admin')        header('Location: ' . BASE_URL . '/admin/dashboard.php');
            elseif ($user['role'] === 'teacher')  header('Location: ' . BASE_URL . '/teacher/dashboard.php');
            else                                  header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Plateforme d'Apprentissage</title>
  <meta name="description" content="SnowLearn : la plateforme e-learning qui transforme vos connaissances en compétences certifiées. Cours PDF, vidéo, quiz et certificats.">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="landing-page">
  <nav class="landing-nav">
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="logo-mark">SL</div>
      <span class="logo-text">SnowLearn</span>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="register.php" class="btn btn-ghost btn-sm">Créer un compte</a>
    </div>
  </nav>

  <div class="landing-hero">
    <div class="hero-left">
      <div class="hero-pill">
        <span></span>
        Plateforme d'apprentissage
      </div>

      <h1 class="hero-title">
        Apprenez.<br>
        Progressez.<br>
        <span class="gradient">Certifiez-vous.</span>
      </h1>

      <p class="hero-subtitle">
        SnowLearn est une plateforme e-learning moderne où enseignants créent des cours enrichis (PDF & vidéo), étudiants progressent leçon par leçon, et promoteurs délivrent des certificats reconnus.
      </p>

      <div class="hero-cta">
        <a href="register.php" class="btn btn-primary btn-lg">
          <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          Commencer gratuitement
        </a>
        <a href="#login-section" class="btn btn-ghost btn-lg">Se connecter</a>
      </div>

      <div class="hero-features">
        <div class="feature-chip">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Cours PDF & Vidéo
        </div>
        <div class="feature-chip">
          <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Quiz interactifs
        </div>
        <div class="feature-chip">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          Certificats officiels
        </div>
        <div class="feature-chip">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Suivi de progression
        </div>
      </div>
    </div>

    <div id="login-section">
      <div class="login-box">
        <h2>Connexion</h2>
        <p>Entrez vos identifiants pour accéder à votre espace.</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'access'): ?>
        <div class="alert alert-danger">Accès non autorisé. Veuillez vous reconnecter.</div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
          Compte créé avec succès ! Connectez-vous.
        </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group">
            <label class="form-label" for="email">Adresse e-mail</label>
            <input class="form-control" type="email" id="email" name="email"
              placeholder="vous@exemple.com" required autocomplete="email"
              value="<?= sanitize($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="password">Mot de passe</label>
            <input class="form-control" type="password" id="password" name="password"
              placeholder="••••••••" required autocomplete="current-password">
          </div>
          <button type="submit" id="btn-login" class="btn btn-primary w-full" style="justify-content:center;padding:12px;font-size:0.95rem;">
            <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Se connecter
          </button>
        </form>

        <div class="divider"></div>

        <p class="text-center" style="font-size:.82rem;color:var(--text-muted);">
          Pas encore de compte ?
          <a href="register.php" style="color:var(--primary);font-weight:600;">S'inscrire</a>
        </p>

        <div style="margin-top:20px;padding:14px;background:var(--surface2);border-radius:var(--radius);border:1px solid var(--border);">
          <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);margin-bottom:10px;">Comptes de démo</p>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <button onclick="fillDemo('admin@snowlearn.com','Admin123!')" class="demo-btn">
              <span class="badge badge-violet" style="font-size:.65rem;">Promoteur</span>
              admin@snowlearn.com
            </button>
            <button onclick="fillDemo('prof@snowlearn.com','Teacher123!')" class="demo-btn">
              <span class="badge badge-blue" style="font-size:.65rem;">Enseignant</span>
              prof@snowlearn.com
            </button>
            <button onclick="fillDemo('etudiant@snowlearn.com','Student123!')" class="demo-btn">
              <span class="badge badge-green" style="font-size:.65rem;">Étudiant</span>
              etudiant@snowlearn.com
            </button>
          </div>
          <p style="font-size:.7rem;color:var(--text-dim);margin-top:8px;">Mot de passe partagé : <strong style="color:var(--text-muted);">voir le compte</strong></p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.demo-btn {
  display:flex;align-items:center;gap:8px;width:100%;
  background:var(--glass);border:1px solid var(--border);
  border-radius:var(--radius-sm);padding:8px 12px;
  font-size:.78rem;color:var(--text-muted);cursor:pointer;
  transition:var(--t);text-align:left;
}
.demo-btn:hover { background:var(--glass-hover);color:var(--text);border-color:var(--border-hover); }
</style>

<script>
function fillDemo(email, pwd) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = pwd;
  document.getElementById('btn-login').focus();
}
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
