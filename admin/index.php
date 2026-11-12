<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$pages = get_site_pages();
$backups = list_backups();
$portfolio_file = ROOT_DIR . '/assets/portfolio/portfolio_items.json';
$portfolio_count = 0;
if (file_exists($portfolio_file)) {
    $p_data = json_decode(file_get_contents($portfolio_file), true);
    if (is_array($p_data)) {
        $portfolio_count = count($p_data);
    }
}
$banners_dir = ROOT_DIR . '/assets/banners';
$banner_count = 0;
if (is_dir($banners_dir)) {
    $banner_count = count(glob($banners_dir . '/*.svg') ?: []);
}

$inquiries_file = __DIR__ . '/data/inquiries.json';
$inquiry_count = 0;
$unread_inquiries = 0;
if (file_exists($inquiries_file)) {
    $all_inqs = json_decode(file_get_contents($inquiries_file), true) ?: [];
    $inquiry_count = count($all_inqs);
    $unread_inquiries = count(array_filter($all_inqs, fn($i) => empty($i['read'])));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Overview — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
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
      <a href="index.php" class="nav-item active">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="inquiries.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Inquiries &amp; Bookings</span>
        <?php if ($unread_inquiries > 0): ?>
          <span style="background: var(--red2); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: 6px;"><?= $unread_inquiries ?></span>
        <?php endif; ?>
      </a>
      <a href="content-manager.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span>Website Content Editor</span>
      </a>
      <a href="pages.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>HTML File Editor</span>
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
          <span>Dashboard Overview</span>
        </div>
      </div>

      <div class="topbar-right">
        <a href="../index.html" target="_blank" class="btn-view-site">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          <span>View Live Website</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <!-- Welcome Banner -->
      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Welcome back, <?= htmlspecialchars($config['username'] ?? 'Ezekiel') ?>! 👋</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your website pages, upload images, update portfolio items, and configure credentials effortlessly.</p>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card" style="border-color: <?= $unread_inquiries > 0 ? 'var(--red2)' : 'var(--border-subtle)' ?>;">
          <div class="stat-info">
            <h3 style="color: <?= $unread_inquiries > 0 ? 'var(--red2)' : '#fff' ?>;"><?= $inquiry_count ?> <?= $unread_inquiries > 0 ? "<small style='font-size:0.85rem;color:var(--gold);'>({$unread_inquiries} New)</small>" : "" ?></h3>
            <p>Customer Inquiries</p>
          </div>
          <div class="stat-icon" style="background: rgba(232, 37, 53, 0.15); color: var(--red2);">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3><?= count($pages) ?></h3>
            <p>Active Pages</p>
          </div>
          <div class="stat-icon">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3><?= $portfolio_count ?></h3>
            <p>Portfolio Items</p>
          </div>
          <div class="stat-icon">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3><?= count($backups) ?></h3>
            <p>Saved Backups</p>
          </div>
          <div class="stat-icon">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div class="admin-card">
        <div class="card-header">
          <div>
            <h2 class="card-title">⚡ Quick Management Actions</h2>
            <p class="card-subtitle">One-click access to most frequent website updates</p>
          </div>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <a href="content-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem; border-color: var(--border-gold); background: rgba(232, 185, 35, 0.05);">
              <span style="font-size: 1.5rem; margin-right: 8px;">✏️</span>
              <div style="text-align: left;">
                <strong style="display: block; color: var(--gold);">Website Content Editor</strong>
                <small style="color: var(--text-muted);">Edit prices, contact info, hero &amp; about (No HTML)</small>
              </div>
            </a>

            <a href="inquiries.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem; <?= $unread_inquiries > 0 ? 'border-color: var(--red2);' : '' ?>">
              <span style="font-size: 1.5rem; margin-right: 8px;">📬</span>
              <div style="text-align: left;">
                <strong style="display: block; color: #fff;">Inquiries &amp; Bookings</strong>
                <small style="color: var(--text-muted);"><?= $unread_inquiries > 0 ? "<strong>{$unread_inquiries} new inquiries waiting!</strong>" : "View customer notes &amp; schedules" ?></small>
              </div>
            </a>

            <a href="portfolio-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🎨</span>
              <div style="text-align: left;">
                <strong style="display: block; color: #fff;">Manage Portfolio</strong>
                <small style="color: var(--text-muted);">Add, edit or reorder designs</small>
              </div>
            </a>

            <a href="media.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">📁</span>
              <div style="text-align: left;">
                <strong style="display: block; color: #fff;">Upload Images & Banners</strong>
                <small style="color: var(--text-muted);">Upload new media assets</small>
              </div>
            </a>

            <a href="settings.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🔑</span>
              <div style="text-align: left;">
                <strong style="display: block; color: #fff;">Change Credentials</strong>
                <small style="color: var(--text-muted);">Update username & password</small>
              </div>
            </a>
          </div>
        </div>
      </div>

      <!-- Website Pages Table -->
      <div class="admin-card">
        <div class="card-header">
          <div>
            <h2 class="card-title">📄 Website Pages (6 Active)</h2>
            <p class="card-subtitle">Click 'Edit Page' to modify headlines, descriptions, and content</p>
          </div>
          <a href="pages.php" class="btn btn-sm btn-outline">View Page Directory</a>
        </div>
        <div class="card-body" style="padding: 0;">
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Page Name</th>
                  <th>Filename</th>
                  <th>Last Modified</th>
                  <th>File Size</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pages as $key => $p): 
                  $exists = file_exists($p['file']);
                  $mtime = $exists ? date('M j, Y - H:i', filemtime($p['file'])) : 'N/A';
                  $size = $exists ? round(filesize($p['file']) / 1024, 1) . ' KB' : 'N/A';
                ?>
                  <tr>
                    <td>
                      <strong style="color: #fff; display: block;"><?= htmlspecialchars($p['name']) ?></strong>
                      <small style="color: var(--text-muted);"><?= htmlspecialchars($p['desc']) ?></small>
                    </td>
                    <td><span class="badge badge-wine"><?= htmlspecialchars($key) ?></span></td>
                    <td><?= $mtime ?></td>
                    <td><?= $size ?></td>
                    <td style="text-align: right;">
                      <a href="edit-page.php?page=<?= urlencode($key) ?>" class="btn btn-sm btn-gold">
                        Edit Page
                      </a>
                      <a href="../<?= htmlspecialchars($key) ?>" target="_blank" class="btn btn-sm btn-outline" style="margin-left: 6px;">
                        Preview
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- System Environment & Hosting Note -->
      <div class="admin-card">
        <div class="card-header">
          <h2 class="card-title">🌐 Hosting & cPanel Deployment Status</h2>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
            <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); text-transform: uppercase;">cPanel Compatibility</span>
              <p style="color: #fff; font-weight: 600; margin-top: 4px;">100% Native PHP Ready</p>
              <small style="color: var(--text-muted);">Runs in public_html with zero extra server setup</small>
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); text-transform: uppercase;">Inquiry Target Email</span>
              <p style="color: #fff; font-weight: 600; margin-top: 4px;"><?= htmlspecialchars($config['admin_email'] ?? 'glorifixmediaconcept@gmail.com') ?></p>
              <small style="color: var(--text-muted);">Configured for all booking & consultation forms</small>
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); text-transform: uppercase;">Auto-Backup System</span>
              <p style="color: #6ee7b7; font-weight: 600; margin-top: 4px;">Active (<?= count($backups) ?> Backups)</p>
              <small style="color: var(--text-muted);">Timestamped snapshots created on every edit</small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script src="js/admin.js"></script>
</body>
</html>
