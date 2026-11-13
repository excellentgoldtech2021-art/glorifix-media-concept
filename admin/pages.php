<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$pages = get_site_pages();
$message = '';
$message_type = 'success';

// Handle restore action if requested
if (isset($_GET['action']) && $_GET['action'] === 'restore' && !empty($_GET['backup'])) {
    $backup_file = basename($_GET['backup']);
    $backup_path = BACKUP_DIR . '/' . $backup_file;
    $target_page = isset($_GET['page']) ? basename($_GET['page']) : '';

    if (file_exists($backup_path) && isset($pages[$target_page])) {
        // Create a safety backup of current state first
        create_page_backup($target_page);
        if (copy($backup_path, $pages[$target_page]['file'])) {
            $message = "Successfully restored {$target_page} from backup {$backup_file}!";
        } else {
            $message = "Failed to restore backup.";
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Pages — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css?v=<?= filemtime(__DIR__ . '/css/admin.css') ?>">
</head>
<body>

<div class="admin-wrapper">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="brand-badge">G</div>
      <div class="brand-info">
        <span class="brand-title">Glorifix Admin</span>
        <span class="brand-sub">Management Suite</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-title">Navigation</span>
      <a href="index.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="pages.php" class="nav-item active">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Edit Pages</span>
      </a>
      <a href="portfolio-manager.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Portfolio Gallery</span>
      </a>
      <a href="media.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        <span>Media & Uploads</span>
      </a>

      <span class="nav-section-title">Configuration</span>
      <a href="settings.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Account & Settings</span>
      </a>
      <a href="logout.php" class="nav-item" style="color: #f87171;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span>Log Out</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($config['username'] ?? 'E', 0, 1)) ?></div>
        <div class="user-meta">
          <span class="user-name"><?= htmlspecialchars($config['username'] ?? 'Ezekiel') ?></span>
          <span class="user-role">Administrator</span>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Area -->
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="page-breadcrumb">
          <a href="index.php" style="color: var(--text-muted);">Dashboard</a>
          <span>/</span>
          <span>Website Pages</span>
        </div>
      </div>

      <div class="topbar-right">
        <a href="../index.html" target="_blank" class="btn-view-site">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          <span>View Site</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Manage Website Pages</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Select any page below to edit its headlines, body copy, images, and structure. Automatic backups are saved on every update.</p>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-auto-dismiss">
          <span><?= $message_type === 'success' ? '✅' : '⚠️' ?></span>
          <span><?= htmlspecialchars($message) ?></span>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem;">
        <?php foreach ($pages as $key => $p): 
          $exists = file_exists($p['file']);
          $mtime = $exists ? date('M j, Y - H:i', filemtime($p['file'])) : 'N/A';
          $size = $exists ? round(filesize($p['file']) / 1024, 1) . ' KB' : 'N/A';
          $page_backups = list_backups($key);
        ?>
          <div class="admin-card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div class="card-header">
                <div>
                  <h3 class="card-title"><?= htmlspecialchars($p['name']) ?></h3>
                  <span class="badge badge-wine" style="margin-top: 4px;"><?= htmlspecialchars($key) ?></span>
                </div>
                <span class="badge badge-gold"><?= $size ?></span>
              </div>
              <div class="card-body">
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                  <?= htmlspecialchars($p['desc']) ?>
                </p>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                  <strong>Last Modified:</strong> <?= $mtime ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                  <strong>Available Backups:</strong> <?= count($page_backups) ?> saved versions
                </div>
              </div>
            </div>

            <div style="padding: 1.25rem 1.75rem; border-top: 1px solid var(--border-subtle); display: flex; gap: 8px;">
              <a href="edit-page.php?page=<?= urlencode($key) ?>" class="btn btn-primary" style="flex: 1;">
                ✏️ Edit Content
              </a>
              <a href="../<?= htmlspecialchars($key) ?>" target="_blank" class="btn btn-outline">
                👁️ Preview
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </main>

</div>

<script src="js/admin.js?v=<?= filemtime(__DIR__ . '/js/admin.js') ?>"></script>
</body>
</html>
