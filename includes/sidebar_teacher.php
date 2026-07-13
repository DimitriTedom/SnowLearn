<?php
$current = basename($_SERVER['PHP_SELF']);
$user = currentUser();
$initials = getUserInitials();
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">SL</div>
    <span class="logo-text">SnowLearn</span>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">
      <div class="sidebar-section-title">Vue d'ensemble</div>
      <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="nav-item <?= $current==='dashboard.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Tableau de bord
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-title">Contenu</div>
      <a href="<?= BASE_URL ?>/teacher/courses.php" class="nav-item <?= in_array($current,['courses.php','create_course.php'])?'active':'' ?>">
        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Mes Cours
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-title">Résultats</div>
      <a href="<?= BASE_URL ?>/teacher/results.php" class="nav-item <?= $current==='results.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Résultats étudiants
      </a>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= sanitize(getUserFullName()) ?></div>
        <div class="user-role">Enseignant</div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="margin-top:4px;color:var(--danger);">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Déconnexion
    </a>
  </div>
</aside>
