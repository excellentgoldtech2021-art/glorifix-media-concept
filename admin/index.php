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

// Load Editable Website Content
$content_file = __DIR__ . '/data/site_content.json';
$site_content = file_exists($content_file) ? (json_decode(file_get_contents($content_file), true) ?: []) : [];
$comp = $site_content['company'] ?? [];
$hero = $site_content['hero'] ?? [];
$pri = $site_content['pricing'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Overview — Glorifix Admin</title>
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
          <span class="user-role">Lead Administrator</span>
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
        <!-- Light / Dark Theme Switcher Button -->
        <button type="button" class="theme-toggle-btn" aria-label="Toggle Light and Dark Mode" title="Toggle Theme">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <span class="theme-icon icon-moon">🌙</span>
              <span class="theme-icon icon-sun">☀️</span>
            </span>
          </span>
          <span class="theme-toggle-label">Dark</span>
        </button>

        <a href="../index.html" target="_blank" class="btn-view-site">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          <span>View Live Site</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <!-- Welcome Banner -->
      <div style="margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
          <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Welcome back, <?= htmlspecialchars($config['username'] ?? 'Ezekiel') ?>! 👋</h1>
          <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your website content, customer inquiries, and studio operations with instant live synchronization.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;">🟢 Live &amp; Connected</span>
          <span style="background: rgba(212, 175, 55, 0.15); color: var(--gold-primary); border: 1px solid rgba(212, 175, 55, 0.3); font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;">📱 Mobile Optimized</span>
        </div>
      </div>

      <!-- Stats Grid (2x2 on Mobile Screens) -->
      <div class="stats-grid">
        <a href="inquiries.php" class="stat-card" style="border-color: <?= $unread_inquiries > 0 ? 'var(--red2)' : 'var(--border-subtle)' ?>; text-decoration: none;">
          <div class="stat-info">
            <h3 style="color: <?= $unread_inquiries > 0 ? 'var(--red2)' : '#fff' ?>;"><?= $inquiry_count ?> <?= $unread_inquiries > 0 ? "<small style='font-size:0.85rem;color:var(--gold);'>({$unread_inquiries} New)</small>" : "" ?></h3>
            <p>Customer Inquiries</p>
          </div>
          <div class="stat-icon" style="background: rgba(232, 37, 53, 0.15); color: var(--red2);">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
        </a>

        <a href="pages.php" class="stat-card" style="text-decoration: none;">
          <div class="stat-info">
            <h3><?= count($pages) ?></h3>
            <p>Active Pages</p>
          </div>
          <div class="stat-icon">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
        </a>

        <a href="portfolio-manager.php" class="stat-card" style="text-decoration: none;">
          <div class="stat-info">
            <h3><?= $portfolio_count ?></h3>
            <p>Portfolio Items</p>
          </div>
          <div class="stat-icon">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
        </a>

        <a href="pages.php" class="stat-card" style="text-decoration: none;">
          <div class="stat-info">
            <h3><?= count($backups) ?></h3>
            <p>Auto-Backups</p>
          </div>
          <div class="stat-icon">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
        </a>
      </div>

      <!-- Quick Visual Content Editor Card (Visually Editable Live Site) -->
      <div class="v-editor-card">
        <div class="v-editor-header">
          <div>
            <h2 class="card-title" style="font-size: 1.35rem; margin-bottom: 2px;">✏️ Quick Visual Content Editor</h2>
            <p class="card-subtitle" style="font-size: 0.88rem;">Instantly edit live text, studio contacts, and pricing without writing HTML</p>
          </div>
          <a href="content-manager.php" class="btn btn-sm btn-outline" style="border-color: var(--gold-primary); color: var(--gold-primary);">
            <span>Open Advanced Editor &rarr;</span>
          </a>
        </div>

        <div class="v-editor-tabs">
          <button type="button" class="v-tab-btn active" data-panel="v-panel-company">📍 Company &amp; Studios</button>
          <button type="button" class="v-tab-btn" data-panel="v-panel-hero">🎯 Hero &amp; Metrics</button>
          <button type="button" class="v-tab-btn" data-panel="v-panel-pricing">💰 Core Pricing</button>
        </div>

        <!-- Panel 1: Company Details & Studios -->
        <div class="v-editor-panel active" id="v-panel-company">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="company">
            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>Primary Phone Line</label>
                <input type="text" name="phone_primary" value="<?= htmlspecialchars($comp['phone_primary'] ?? '09050278999') ?>" required>
              </div>
              <div class="v-form-group">
                <label>WhatsApp Hotline</label>
                <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($comp['whatsapp_number'] ?? '2349050278999') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Official Email</label>
                <input type="email" name="email_address" value="<?= htmlspecialchars($comp['email_address'] ?? 'glorifixmediaconcept@gmail.com') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Studio Hours</label>
                <input type="text" name="working_hours" value="<?= htmlspecialchars($comp['working_hours'] ?? 'Monday - Saturday: 8:00 AM - 8:00 PM') ?>" required>
              </div>
            </div>

            <div class="v-editor-grid">
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Ikare-Akoko Ondo State Headquarters Address</label>
                <input type="text" name="office_address" value="<?= htmlspecialchars($comp['office_address'] ?? 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State') ?>" required>
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Abuja Operations Hub Address</label>
                <input type="text" name="support_hub" value="<?= htmlspecialchars($comp['support_hub'] ?? 'Phase 2, Shop 7, Opp. UniAbuja Mini-Campus, Gwagwalada, Abuja FCT') ?>" required>
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Changes automatically update `index.html`, `contact.html`, and `about.html`.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Live Website</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Panel 2: Hero & Metrics -->
        <div class="v-editor-panel" id="v-panel-hero">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="hero">
            <div class="v-editor-grid">
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Hero Announcement Badge</label>
                <input type="text" name="badge" value="<?= htmlspecialchars($hero['badge'] ?? '🚀 Premium Digital Agency & CAC Legal Partners') ?>" required>
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Main Headline</label>
                <input type="text" name="headline" value="<?= htmlspecialchars($hero['headline'] ?? 'Transforming Brands, Elevating Businesses with Exceptional Digital & Legal Solutions') ?>" required>
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Hero Subtitle &amp; Pitch</label>
                <textarea name="subtitle" rows="3" required><?= htmlspecialchars($hero['subtitle'] ?? 'Glorifix Media Concept Limited is your trusted partner for high-performance website design, Meta advertising, CAC business registration, and comprehensive brand transformation.') ?></textarea>
              </div>
            </div>

            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>Completed Projects Stat</label>
                <input type="text" name="stat_projects" value="<?= htmlspecialchars($hero['stat_projects'] ?? '500+') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Happy Clients Stat</label>
                <input type="text" name="stat_clients" value="<?= htmlspecialchars($hero['stat_clients'] ?? '350+') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Client Satisfaction</label>
                <input type="text" name="stat_satisfaction" value="<?= htmlspecialchars($hero['stat_satisfaction'] ?? '98%') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Revenue Generated</label>
                <input type="text" name="stat_revenue" value="<?= htmlspecialchars($hero['stat_revenue'] ?? '₦50M+') ?>" required>
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Updates homepage banners, statistics counters, and brand messaging.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Hero Content</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Panel 3: Core Pricing Fast Adjuster -->
        <div class="v-editor-panel" id="v-panel-pricing">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="pricing">
            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>CAC Business Name (₦)</label>
                <input type="text" name="cac_business_name_price" value="<?= htmlspecialchars($pri['cac_business_name_price'] ?? '35,000') ?>" required>
              </div>
              <div class="v-form-group">
                <label>CAC Limited Company LLC (₦)</label>
                <input type="text" name="cac_llc_price" value="<?= htmlspecialchars($pri['cac_llc_price'] ?? '75,000') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Basic Website Design (₦)</label>
                <input type="text" name="web_basic_price" value="<?= htmlspecialchars($pri['web_basic_price'] ?? '100,000') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Business Website Design (₦)</label>
                <input type="text" name="web_business_price" value="<?= htmlspecialchars($pri['web_business_price'] ?? '150,000') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Starter Social Media (₦)</label>
                <input type="text" name="social_starter_price" value="<?= htmlspecialchars($pri['social_starter_price'] ?? '50,000') ?>" required>
              </div>
              <div class="v-form-group">
                <label>Basic 7-Day Meta Ads (₦)</label>
                <input type="text" name="ads_basic_price" value="<?= htmlspecialchars($pri['ads_basic_price'] ?? '60,000') ?>" required>
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Instantly synchronizes prices on `pricing.html` and booking estimators.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Pricing Plans</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div class="admin-card">
        <div class="card-header">
          <div>
            <h2 class="card-title">⚡ Quick Management Actions</h2>
            <p class="card-subtitle">One-click access to most frequent website operations</p>
          </div>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <a href="content-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem; border-color: var(--border-gold); background: rgba(232, 185, 35, 0.05);">
              <span style="font-size: 1.5rem; margin-right: 8px;">✏️</span>
              <div style="text-align: left;">
                <strong style="display: block; color: var(--gold-primary);">Website Content Editor</strong>
                <small style="color: var(--text-muted);">Full editor for prices, about, hero &amp; footer</small>
              </div>
            </a>

            <a href="inquiries.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem; <?= $unread_inquiries > 0 ? 'border-color: var(--red2);' : '' ?>">
              <span style="font-size: 1.5rem; margin-right: 8px;">📬</span>
              <div style="text-align: left;">
                <strong style="display: block;">Inquiries &amp; Bookings</strong>
                <small style="color: var(--text-muted);"><?= $unread_inquiries > 0 ? "<strong>{$unread_inquiries} new inquiries waiting!</strong>" : "View customer notes &amp; schedules" ?></small>
              </div>
            </a>

            <a href="portfolio-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🎨</span>
              <div style="text-align: left;">
                <strong style="display: block;">Manage Portfolio</strong>
                <small style="color: var(--text-muted);">Add, edit or reorder designs</small>
              </div>
            </a>

            <a href="media.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">📁</span>
              <div style="text-align: left;">
                <strong style="display: block;">Upload Images &amp; Banners</strong>
                <small style="color: var(--text-muted);">Upload new media assets</small>
              </div>
            </a>

            <a href="pages.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">📄</span>
              <div style="text-align: left;">
                <strong style="display: block;">HTML Page Editor</strong>
                <small style="color: var(--text-muted);">Edit raw page files with backup protection</small>
              </div>
            </a>

            <a href="settings.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🔑</span>
              <div style="text-align: left;">
                <strong style="display: block;">Admin Credentials</strong>
                <small style="color: var(--text-muted);">Update password &amp; admin notification email</small>
              </div>
            </a>
          </div>
        </div>
      </div>

      <!-- Website Pages (Desktop Table + Mobile Touch Cards) -->
      <div class="admin-card">
        <div class="card-header">
          <div>
            <h2 class="card-title">📄 Website Pages (6 Active)</h2>
            <p class="card-subtitle">Manage, edit content, or preview each public page</p>
          </div>
          <a href="pages.php" class="btn btn-sm btn-outline">View Page Directory</a>
        </div>
        <div class="card-body" style="padding: 0;">
          
          <!-- Desktop Table (Visible on Desktop, Hidden on Mobile) -->
          <div class="table-responsive desktop-only-table">
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
                      <strong style="display: block;"><?= htmlspecialchars($p['name']) ?></strong>
                      <small style="color: var(--text-muted);"><?= htmlspecialchars($p['desc']) ?></small>
                    </td>
                    <td><span class="badge badge-wine"><?= htmlspecialchars($key) ?></span></td>
                    <td><?= $mtime ?></td>
                    <td><?= $size ?></td>
                    <td style="text-align: right;">
                      <a href="edit-page.php?page=<?= urlencode($key) ?>" class="btn btn-sm btn-gold">
                        Edit Content
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

          <!-- Mobile Optimized Touch Cards (Hidden on Desktop, Visible on Mobile) -->
          <div class="admin-page-cards-mobile">
            <?php foreach ($pages as $key => $p): 
              $exists = file_exists($p['file']);
              $mtime = $exists ? date('M j, Y - H:i', filemtime($p['file'])) : 'N/A';
              $size = $exists ? round(filesize($p['file']) / 1024, 1) . ' KB' : 'N/A';
            ?>
              <div class="page-card-item">
                <div class="page-card-top">
                  <div>
                    <h3 class="page-card-title"><?= htmlspecialchars($p['name']) ?></h3>
                    <p class="page-card-desc"><?= htmlspecialchars($p['desc']) ?></p>
                  </div>
                  <span class="badge badge-wine"><?= htmlspecialchars($key) ?></span>
                </div>
                <div class="page-card-meta">
                  <span>📅 <?= $mtime ?></span>
                  <span>📦 <?= $size ?></span>
                </div>
                <div class="page-card-actions">
                  <a href="edit-page.php?page=<?= urlencode($key) ?>" class="btn btn-primary">
                    ✏️ Edit Page
                  </a>
                  <a href="../<?= htmlspecialchars($key) ?>" target="_blank" class="btn btn-outline">
                    👁️ Preview
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      </div>

      <!-- System Environment & Hosting Note -->
      <div class="admin-card">
        <div class="card-header">
          <h2 class="card-title">🌐 Hosting &amp; cPanel Deployment Status</h2>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
            <div style="background: rgba(0,0,0,0.15); padding: 1.1rem; border-radius: 10px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 700; text-transform: uppercase;">cPanel Compatibility</span>
              <p style="font-weight: 700; margin-top: 4px;">100% Native PHP Ready</p>
              <small style="color: var(--text-muted);">Runs directly in public_html with zero extra server setup</small>
            </div>

            <div style="background: rgba(0,0,0,0.15); padding: 1.1rem; border-radius: 10px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 700; text-transform: uppercase;">Inquiry Target Email</span>
              <p style="font-weight: 700; margin-top: 4px;"><?= htmlspecialchars($config['admin_email'] ?? 'glorifixmediaconcept@gmail.com') ?></p>
              <small style="color: var(--text-muted);">Configured for all booking &amp; consultation forms</small>
            </div>

            <div style="background: rgba(0,0,0,0.15); padding: 1.1rem; border-radius: 10px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 700; text-transform: uppercase;">Auto-Backup System</span>
              <p style="color: #10b981; font-weight: 700; margin-top: 4px;">Active (<?= count($backups) ?> Backups Saved)</p>
              <small style="color: var(--text-muted);">Timestamped snapshots created on every edit</small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script src="js/admin.js?v=<?= filemtime(__DIR__ . '/js/admin.js') ?>"></script>
</body>
</html>
