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
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item <?= $current==='dashboard.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Tableau de bord
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-title">Gestion</div>
      <a href="<?= BASE_URL ?>/admin/modules.php" class="nav-item <?= $current==='modules.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Modules
      </a>
      <a href="<?= BASE_URL ?>/admin/teachers.php" class="nav-item <?= $current==='teachers.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Enseignants
      </a>
      <a href="<?= BASE_URL ?>/admin/students.php" class="nav-item <?= $current==='students.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Étudiants
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-title">Certifications</div>
      <a href="<?= BASE_URL ?>/admin/certificates.php" class="nav-item <?= $current==='certificates.php'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
        Certificats
      </a>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= sanitize(getUserFullName()) ?></div>
        <div class="user-role">Promoteur</div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="margin-top:4px;color:var(--danger);">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Déconnexion
    </a>
  </div>
</aside>
