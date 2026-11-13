<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$content_file = __DIR__ . '/data/site_content.json';
$site_content = file_exists($content_file) ? (json_decode(file_get_contents($content_file), true) ?: []) : [];

$message = '';
$message_type = 'success';

// Synchronization helper function
function sync_content_to_html($content) {
    $root = ROOT_DIR;
    
    // 1. Sync Contact & Company Details in all HTML files
    $html_files = ['index.html', 'about.html', 'services.html', 'pricing.html', 'gallery.html', 'contact.html'];
    
    $phone_primary = $content['company']['phone_primary'] ?? '09050278999';
    $phone_display = $content['company']['phone_display'] ?? '0905 027 8999';
    $email_address = $content['company']['email_address'] ?? 'glorifixmediaconcept@gmail.com';
    $office_address = $content['company']['office_address'] ?? 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko';
    
    foreach ($html_files as $hf) {
        $file_path = $root . '/' . $hf;
        if (!file_exists($file_path)) continue;
        create_page_backup($hf);
        $html = file_get_contents($file_path);
        
        // Update Email & Phones if needed
        $html = str_replace('glorifixmediaconcept@gmail.com', $email_address, $html);
        file_put_contents($file_path, $html);
    }
    
    // 2. Sync Pricing.html
    $pricing_path = $root . '/pricing.html';
    if (file_exists($pricing_path)) {
        create_page_backup('pricing.html');
        $p_html = file_get_contents($pricing_path);
        
        $cac_bn = $content['pricing']['cac_business_name_price'] ?? '35,000';
        $cac_llc = $content['pricing']['cac_llc_price'] ?? '75,000';
        $cac_trust = $content['pricing']['cac_trustee_price'] ?? '150,000';
        
        $soc_start = $content['pricing']['social_starter_price'] ?? '50,000';
        $soc_growth = $content['pricing']['social_growth_price'] ?? '120,000';
        $soc_prem = $content['pricing']['social_premium_price'] ?? '240,000';
        $soc_prem_plat = $content['pricing']['social_premium_platforms'] ?? '4 platforms managed';
        
        // CAC updates
        $p_html = preg_replace('/(<div class="pricing-tier">Business Name<\/div>.*?<div class="pricing-amt"><span>₦<\/span>)(.*?)(<\/div>)/is', '$1' . $cac_bn . '$3', $p_html);
        $p_html = preg_replace('/(<div class="pricing-tier">Company \(LLC\)<\/div>.*?<div class="pricing-amt"><span>₦<\/span>)(.*?)(<\/div>)/is', '$1' . $cac_llc . '$3', $p_html);
        $p_html = preg_replace('/(<div class="pricing-tier">Trustee \/ NGO<\/div>.*?<div class="pricing-amt"><span>₦<\/span>)(.*?)(<\/div>)/is', '$1' . $cac_trust . '$3', $p_html);
        
        // Social Media updates
        $p_html = preg_replace('/(<div class="pricing-tier">Starter<\/div>.*?<div class="pricing-amt"><span>₦<\/span>)(.*?)(<\/div>)/is', '$1' . $soc_start . '$3', $p_html);
        $p_html = preg_replace('/(<div class="pricing-tier">Growth<\/div>.*?<div class="pricing-amt"><span>₦<\/span>)(.*?)(<\/div>)/is', '$1' . $soc_growth . '$3', $p_html);
        $p_html = preg_replace('/(<div class="pricing-tier">Premium<\/div>.*?<div class="pricing-amt"><span>₦<\/span>)(.*?)(<\/div>)/is', '$1' . $soc_prem . '$3', $p_html);
        $p_html = preg_replace('/(<li><span class="pricing-check">✓<\/span> )([0-9]+ platforms managed)(<\/li>)/is', '$1' . $soc_prem_plat . '$3', $p_html);
        
        file_put_contents($pricing_path, $p_html);
    }
    
    // 3. Sync Voice Agent KB in js/voice-agent.js
    $va_path = $root . '/js/voice-agent.js';
    if (file_exists($va_path)) {
        $va_js = file_get_contents($va_path);
        if (!empty($content['voice_agent']['cac_response'])) {
            $va_js = preg_replace('/(keywords: \[\'cac\'.*?answer: ")(.*?)(")/is', '$1' . addslashes($content['voice_agent']['cac_response']) . '$3', $va_js);
        }
        if (!empty($content['voice_agent']['social_response'])) {
            $va_js = preg_replace('/(keywords: \[\'social media\'.*?answer: ")(.*?)(")/is', '$1' . addslashes($content['voice_agent']['social_response']) . '$3', $va_js);
        }
        file_put_contents($va_path, $va_js);
    }
}

// Handle Form Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'company';
    
    // Read and merge updated values
    if ($tab === 'company') {
        $site_content['company']['name'] = trim($_POST['company_name'] ?? '');
        $site_content['company']['tagline'] = trim($_POST['tagline'] ?? '');
        $site_content['company']['phone_primary'] = trim($_POST['phone_primary'] ?? '');
        $site_content['company']['phone_display'] = trim($_POST['phone_display'] ?? '');
        $site_content['company']['whatsapp_number'] = trim($_POST['whatsapp_number'] ?? '');
        $site_content['company']['email_address'] = trim($_POST['email_address'] ?? '');
        $site_content['company']['office_address'] = trim($_POST['office_address'] ?? '');
        $site_content['company']['support_hub'] = trim($_POST['support_hub'] ?? '');
        $site_content['company']['working_hours'] = trim($_POST['working_hours'] ?? '');
    } elseif ($tab === 'hero') {
        $site_content['hero']['badge'] = trim($_POST['hero_badge'] ?? '');
        $site_content['hero']['headline'] = trim($_POST['hero_headline'] ?? '');
        $site_content['hero']['subtitle'] = trim($_POST['hero_subtitle'] ?? '');
        $site_content['hero']['stat_projects'] = trim($_POST['stat_projects'] ?? '');
        $site_content['hero']['stat_clients'] = trim($_POST['stat_clients'] ?? '');
        $site_content['hero']['stat_satisfaction'] = trim($_POST['stat_satisfaction'] ?? '');
        $site_content['hero']['stat_revenue'] = trim($_POST['stat_revenue'] ?? '');
    } elseif ($tab === 'pricing') {
        $site_content['pricing']['cac_business_name_price'] = trim($_POST['cac_business_name_price'] ?? '');
        $site_content['pricing']['cac_business_name_sub'] = trim($_POST['cac_business_name_sub'] ?? '');
        $site_content['pricing']['cac_llc_price'] = trim($_POST['cac_llc_price'] ?? '');
        $site_content['pricing']['cac_llc_sub'] = trim($_POST['cac_llc_sub'] ?? '');
        $site_content['pricing']['cac_trustee_price'] = trim($_POST['cac_trustee_price'] ?? '');
        $site_content['pricing']['cac_trustee_sub'] = trim($_POST['cac_trustee_sub'] ?? '');
        
        $site_content['pricing']['social_starter_price'] = trim($_POST['social_starter_price'] ?? '');
        $site_content['pricing']['social_growth_price'] = trim($_POST['social_growth_price'] ?? '');
        $site_content['pricing']['social_premium_price'] = trim($_POST['social_premium_price'] ?? '');
        $site_content['pricing']['social_premium_platforms'] = trim($_POST['social_premium_platforms'] ?? '');
        
        $site_content['pricing']['ads_basic_price'] = trim($_POST['ads_basic_price'] ?? '');
        $site_content['pricing']['ads_growth_price'] = trim($_POST['ads_growth_price'] ?? '');
        $site_content['pricing']['ads_premium_price'] = trim($_POST['ads_premium_price'] ?? '');
        
        $site_content['pricing']['web_basic_price'] = trim($_POST['web_basic_price'] ?? '');
        $site_content['pricing']['web_business_price'] = trim($_POST['web_business_price'] ?? '');
        $site_content['pricing']['web_premium_price'] = trim($_POST['web_premium_price'] ?? '');
        
        $site_content['pricing']['app_web_price'] = trim($_POST['app_web_price'] ?? '');
        $site_content['pricing']['app_mobile_price'] = trim($_POST['app_mobile_price'] ?? '');
        $site_content['pricing']['app_enterprise_price'] = trim($_POST['app_enterprise_price'] ?? '');
    } elseif ($tab === 'about') {
        $site_content['about']['founder_name'] = trim($_POST['founder_name'] ?? '');
        $site_content['about']['founder_role'] = trim($_POST['founder_role'] ?? '');
        $site_content['about']['founder_bio'] = trim($_POST['founder_bio'] ?? '');
        $site_content['about']['mission'] = trim($_POST['mission'] ?? '');
        $site_content['about']['vision'] = trim($_POST['vision'] ?? '');
    } elseif ($tab === 'voice') {
        $site_content['voice_agent']['cac_response'] = trim($_POST['va_cac_response'] ?? '');
        $site_content['voice_agent']['social_response'] = trim($_POST['va_social_response'] ?? '');
        $site_content['voice_agent']['web_response'] = trim($_POST['va_web_response'] ?? '');
    }
    
    // Save to JSON
    file_put_contents($content_file, json_encode($site_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    
    // Sync into HTML pages
    sync_content_to_html($site_content);
    
    $message = "Website content saved and synchronized across all pages successfully!";
}

$active_tab = $_POST['tab'] ?? ($_GET['tab'] ?? 'company');
$inquiries_file = __DIR__ . '/data/inquiries.json';
$unread_count = 0;
if (file_exists($inquiries_file)) {
    $all_inqs = json_decode(file_get_contents($inquiries_file), true) ?: [];
    $unread_count = count(array_filter($all_inqs, fn($i) => empty($i['read'])));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Content Manager — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css?v=<?= filemtime(__DIR__ . '/css/admin.css') ?>">
  <style>
    .content-nav-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 0.5rem;
      overflow-x: auto;
    }
    .c-tab-btn {
      background: transparent;
      border: 1px solid transparent;
      color: var(--text-muted);
      padding: 0.65rem 1.25rem;
      border-radius: var(--radius-sm);
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      text-decoration: none;
    }
    .c-tab-btn.active {
      background: var(--red2);
      color: #fff;
      border-color: var(--red2);
    }
    .c-tab-btn:hover:not(.active) {
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
    }
    .field-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-md);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .field-card-title {
      font-size: 1.15rem;
      color: var(--gold);
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 8px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding-bottom: 0.5rem;
    }
    .field-hint {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 4px;
    }
    .grid-2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.25rem;
    }
    .grid-3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.25rem;
    }
  </style>
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
      <a href="inquiries.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Inquiries &amp; Bookings</span>
        <?php if ($unread_count > 0): ?>
          <span style="background: var(--red2); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: 6px;"><?= $unread_count ?></span>
        <?php endif; ?>
      </a>
      <a href="content-manager.php" class="nav-item active">
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
        <span>Media &amp; Uploads</span>
      </a>

      <span class="nav-section-title">Configuration</span>
      <a href="settings.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Account &amp; Settings</span>
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
          <span>Website Content Editor (No HTML Required)</span>
        </div>
      </div>

      <div class="topbar-right">
        <a href="../pricing.html" target="_blank" class="btn-view-site">
          <span>View Pricing Page</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; margin-bottom: 0.35rem;">Visual Website Content Editor</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Edit any text, pricing rates, contact numbers, and packages in plain text. Zero HTML code required!</p>
      </div>

      <!-- Navigation Tabs -->
      <div class="content-nav-tabs">
        <a href="content-manager.php?tab=company" class="c-tab-btn <?= $active_tab === 'company' ? 'active' : '' ?>">🏢 Company &amp; Contact</a>
        <a href="content-manager.php?tab=pricing" class="c-tab-btn <?= $active_tab === 'pricing' ? 'active' : '' ?>">💰 Pricing &amp; Packages</a>
        <a href="content-manager.php?tab=hero" class="c-tab-btn <?= $active_tab === 'hero' ? 'active' : '' ?>">🌟 Homepage &amp; Hero</a>
        <a href="content-manager.php?tab=about" class="c-tab-btn <?= $active_tab === 'about' ? 'active' : '' ?>">📖 About Us &amp; Ezekiel</a>
        <a href="content-manager.php?tab=voice" class="c-tab-btn <?= $active_tab === 'voice' ? 'active' : '' ?>">🤖 AI Voice Assistant Answers</a>
      </div>

      <!-- Tab 1: Company & Contact -->
      <?php if ($active_tab === 'company'): ?>
        <form method="POST">
          <input type="hidden" name="tab" value="company">
          <div class="field-card">
            <div class="field-card-title">🏢 Contact &amp; Brand Information</div>
            
            <div class="grid-2">
              <div class="form-group">
                <label>Company Legal Name</label>
                <input type="text" name="company_name" value="<?= htmlspecialchars($site_content['company']['name'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Official Tagline</label>
                <input type="text" name="tagline" value="<?= htmlspecialchars($site_content['company']['tagline'] ?? '') ?>">
              </div>
            </div>

            <div class="grid-3" style="margin-top: 1rem;">
              <div class="form-group">
                <label>Primary Phone Number</label>
                <input type="text" name="phone_primary" value="<?= htmlspecialchars($site_content['company']['phone_primary'] ?? '') ?>">
                <div class="field-hint">e.g. 09050278999</div>
              </div>
              <div class="form-group">
                <label>Display Phone Number</label>
                <input type="text" name="phone_display" value="<?= htmlspecialchars($site_content['company']['phone_display'] ?? '') ?>">
                <div class="field-hint">e.g. 0905 027 8999</div>
              </div>
              <div class="form-group">
                <label>WhatsApp Number (with country code)</label>
                <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($site_content['company']['whatsapp_number'] ?? '') ?>">
                <div class="field-hint">e.g. 2349050278999</div>
              </div>
            </div>

            <div class="grid-2" style="margin-top: 1rem;">
              <div class="form-group">
                <label>Official Email Address</label>
                <input type="email" name="email_address" value="<?= htmlspecialchars($site_content['company']['email_address'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Working Hours</label>
                <input type="text" name="working_hours" value="<?= htmlspecialchars($site_content['company']['working_hours'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Head Office Physical Address</label>
              <textarea name="office_address" rows="2"><?= htmlspecialchars($site_content['company']['office_address'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Regional Support Hub</label>
              <input type="text" name="support_hub" value="<?= htmlspecialchars($site_content['company']['support_hub'] ?? '') ?>">
            </div>

            <div style="margin-top: 1.5rem;">
              <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem;">💾 Save Contact Information</button>
            </div>
          </div>
        </form>
      <?php endif; ?>

      <!-- Tab 2: Pricing & Packages -->
      <?php if ($active_tab === 'pricing'): ?>
        <form method="POST">
          <input type="hidden" name="tab" value="pricing">

          <!-- CAC Packages -->
          <div class="field-card">
            <div class="field-card-title">📝 CAC Business Registration Pricing (in Naira)</div>
            
            <div class="grid-3">
              <div class="form-group">
                <label>CAC Business Name Price (₦)</label>
                <input type="text" name="cac_business_name_price" value="<?= htmlspecialchars($site_content['pricing']['cac_business_name_price'] ?? '35,000') ?>">
                <div class="field-hint">Currently ₦35,000</div>
              </div>
              <div class="form-group">
                <label>CAC Limited Company (LLC) Price (₦)</label>
                <input type="text" name="cac_llc_price" value="<?= htmlspecialchars($site_content['pricing']['cac_llc_price'] ?? '75,000') ?>">
                <div class="field-hint">Currently ₦75,000</div>
              </div>
              <div class="form-group">
                <label>CAC Trustee / NGO Foundation (₦)</label>
                <input type="text" name="cac_trustee_price" value="<?= htmlspecialchars($site_content['pricing']['cac_trustee_price'] ?? '150,000') ?>">
                <div class="field-hint">Currently ₦150,000</div>
              </div>
            </div>
          </div>

          <!-- Social Media Packages -->
          <div class="field-card">
            <div class="field-card-title">📱 Social Media Management Packages (Monthly)</div>
            
            <div class="grid-3">
              <div class="form-group">
                <label>Starter Package Price (₦)</label>
                <input type="text" name="social_starter_price" value="<?= htmlspecialchars($site_content['pricing']['social_starter_price'] ?? '50,000') ?>">
                <div class="field-hint">Currently ₦50,000</div>
              </div>
              <div class="form-group">
                <label>Growth Package Price (₦)</label>
                <input type="text" name="social_growth_price" value="<?= htmlspecialchars($site_content['pricing']['social_growth_price'] ?? '120,000') ?>">
                <div class="field-hint">Currently ₦120,000</div>
              </div>
              <div class="form-group">
                <label>Premium Package Price (₦)</label>
                <input type="text" name="social_premium_price" value="<?= htmlspecialchars($site_content['pricing']['social_premium_price'] ?? '240,000') ?>">
                <div class="field-hint">Currently ₦240,000</div>
              </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Premium Package Platforms Managed</label>
              <input type="text" name="social_premium_platforms" value="<?= htmlspecialchars($site_content['pricing']['social_premium_platforms'] ?? '4 platforms managed') ?>">
              <div class="field-hint">e.g. 4 platforms managed</div>
            </div>
          </div>

          <!-- Meta Ads -->
          <div class="field-card">
            <div class="field-card-title">🎯 Meta Ads &amp; Media Buying (Facebook &amp; Instagram)</div>
            
            <div class="grid-3">
              <div class="form-group">
                <label>7-Day Basic Ads Campaign (₦)</label>
                <input type="text" name="ads_basic_price" value="<?= htmlspecialchars($site_content['pricing']['ads_basic_price'] ?? '60,000') ?>">
              </div>
              <div class="form-group">
                <label>14-Day Growth Ads Campaign (₦)</label>
                <input type="text" name="ads_growth_price" value="<?= htmlspecialchars($site_content['pricing']['ads_growth_price'] ?? '120,000') ?>">
              </div>
              <div class="form-group">
                <label>30-Day Premium Ads Campaign (₦)</label>
                <input type="text" name="ads_premium_price" value="<?= htmlspecialchars($site_content['pricing']['ads_premium_price'] ?? '180,000') ?>">
              </div>
            </div>
          </div>

          <!-- Website Design -->
          <div class="field-card">
            <div class="field-card-title">💻 Website Design &amp; Development Packages</div>
            
            <div class="grid-3">
              <div class="form-group">
                <label>Basic Website Design (₦)</label>
                <input type="text" name="web_basic_price" value="<?= htmlspecialchars($site_content['pricing']['web_basic_price'] ?? '100,000') ?>">
              </div>
              <div class="form-group">
                <label>Business Website Design (₦)</label>
                <input type="text" name="web_business_price" value="<?= htmlspecialchars($site_content['pricing']['web_business_price'] ?? '150,000') ?>">
              </div>
              <div class="form-group">
                <label>Premium Custom Website (₦)</label>
                <input type="text" name="web_premium_price" value="<?= htmlspecialchars($site_content['pricing']['web_premium_price'] ?? '300,000') ?>">
              </div>
            </div>
          </div>

          <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 1rem;">💾 Save &amp; Update All Pricing Rates</button>
          </div>
        </form>
      <?php endif; ?>

      <!-- Tab 3: Homepage & Hero -->
      <?php if ($active_tab === 'hero'): ?>
        <form method="POST">
          <input type="hidden" name="tab" value="hero">
          <div class="field-card">
            <div class="field-card-title">🌟 Homepage Hero Section</div>

            <div class="form-group">
              <label>Hero Announcement Badge</label>
              <input type="text" name="hero_badge" value="<?= htmlspecialchars($site_content['hero']['badge'] ?? '') ?>">
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Main Hero Headline</label>
              <textarea name="hero_headline" rows="2"><?= htmlspecialchars($site_content['hero']['headline'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Hero Subtitle / Description</label>
              <textarea name="hero_subtitle" rows="3"><?= htmlspecialchars($site_content['hero']['subtitle'] ?? '') ?></textarea>
            </div>

            <div class="grid-2" style="margin-top: 1rem;">
              <div class="form-group">
                <label>Completed Projects Stat</label>
                <input type="text" name="stat_projects" value="<?= htmlspecialchars($site_content['hero']['stat_projects'] ?? '500+') ?>">
              </div>
              <div class="form-group">
                <label>Happy Clients Stat</label>
                <input type="text" name="stat_clients" value="<?= htmlspecialchars($site_content['hero']['stat_clients'] ?? '350+') ?>">
              </div>
              <div class="form-group">
                <label>Client Satisfaction Rate</label>
                <input type="text" name="stat_satisfaction" value="<?= htmlspecialchars($site_content['hero']['stat_satisfaction'] ?? '98%') ?>">
              </div>
              <div class="form-group">
                <label>Revenue Generated Stat</label>
                <input type="text" name="stat_revenue" value="<?= htmlspecialchars($site_content['hero']['stat_revenue'] ?? '₦50M+') ?>">
              </div>
            </div>

            <div style="margin-top: 1.5rem;">
              <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem;">💾 Save Homepage Details</button>
            </div>
          </div>
        </form>
      <?php endif; ?>

      <!-- Tab 4: About & Founder -->
      <?php if ($active_tab === 'about'): ?>
        <form method="POST">
          <input type="hidden" name="tab" value="about">
          <div class="field-card">
            <div class="field-card-title">📖 About Ezekiel &amp; Glorifix Story</div>

            <div class="grid-2">
              <div class="form-group">
                <label>Founder &amp; CEO Name</label>
                <input type="text" name="founder_name" value="<?= htmlspecialchars($site_content['about']['founder_name'] ?? 'Soje Ezekiel') ?>">
              </div>
              <div class="form-group">
                <label>Executive Role</label>
                <input type="text" name="founder_role" value="<?= htmlspecialchars($site_content['about']['founder_role'] ?? 'CEO & Lead Digital Strategist') ?>">
              </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Founder Biography / Vision</label>
              <textarea name="founder_bio" rows="3"><?= htmlspecialchars($site_content['about']['founder_bio'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Company Mission Statement</label>
              <textarea name="mission" rows="3"><?= htmlspecialchars($site_content['about']['mission'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Company Vision Statement</label>
              <textarea name="vision" rows="3"><?= htmlspecialchars($site_content['about']['vision'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 1.5rem;">
              <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem;">💾 Save About Details</button>
            </div>
          </div>
        </form>
      <?php endif; ?>

      <!-- Tab 5: AI Voice Assistant -->
      <?php if ($active_tab === 'voice'): ?>
        <form method="POST">
          <input type="hidden" name="tab" value="voice">
          <div class="field-card">
            <div class="field-card-title">🤖 AI Voice Assistant Knowledge Base</div>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">These are the automated answers spoken by the floating AI Voice Agent on your website when customers ask questions.</p>

            <div class="form-group">
              <label>Voice Agent Answer for CAC Inquiries</label>
              <textarea name="va_cac_response" rows="3"><?= htmlspecialchars($site_content['voice_agent']['cac_response'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Voice Agent Answer for Social Media Packages</label>
              <textarea name="va_social_response" rows="3"><?= htmlspecialchars($site_content['voice_agent']['social_response'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
              <label>Voice Agent Answer for Website Design</label>
              <textarea name="va_web_response" rows="3"><?= htmlspecialchars($site_content['voice_agent']['web_response'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 1.5rem;">
              <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem;">💾 Save Voice Agent Answers</button>
            </div>
          </div>
        </form>
      <?php endif; ?>

    </div>
  </main>
</div>

<script src="js/admin.js?v=<?= filemtime(__DIR__ . '/js/admin.js') ?>"></script>
</body>
</html>
