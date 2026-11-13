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
    
    // 4. Sync Social Media Links across all HTML pages
    $socials = $content['socials'] ?? [];
    if (!empty($socials)) {
        $social_items = [];
        if (!empty($socials['facebook'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['facebook']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="Facebook">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>' . "\n"
                            . '            </a>';
        }
        if (!empty($socials['instagram'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['instagram']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="Instagram">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>' . "\n"
                            . '            </a>';
        }
        if (!empty($socials['whatsapp'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['whatsapp']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="WhatsApp">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm5.78 14.15c-.24.68-1.41 1.26-1.95 1.34-.52.08-1.2.11-3.48-.84-2.73-1.13-4.48-3.9-4.62-4.08-.13-.19-1.12-1.49-1.12-2.85 0-1.35.71-2.02.96-2.29.25-.28.56-.35.75-.35.19 0 .37 0 .54.01.17.01.4-.07.62.47.24.57.82 2 .89 2.15.07.15.12.33.02.53-.1.19-.15.31-.29.48-.15.17-.31.37-.44.5-.15.15-.31.31-.13.62.17.31.78 1.29 1.68 2.09 1.16 1.03 2.13 1.35 2.44 1.5.31.15.48.13.66-.08.19-.21.78-.91.99-1.22.21-.31.42-.26.7-.15.29.11 1.83.86 2.14 1.02.31.15.52.23.6.35.08.13.08.76-.16 1.44z"/></svg>' . "\n"
                            . '            </a>';
        }
        if (!empty($socials['linkedin'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['linkedin']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="LinkedIn">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>' . "\n"
                            . '            </a>';
        }
        if (!empty($socials['twitter'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['twitter']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="Twitter">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>' . "\n"
                            . '            </a>';
        }
        if (!empty($socials['tiktok'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['tiktok']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="TikTok">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.54c0 1.7-.42 3.4-1.35 4.81-1.22 1.83-3.19 3.03-5.38 3.32-2.19.29-4.47-.28-6.28-1.57-1.81-1.29-2.99-3.3-3.23-5.51-.25-2.21.43-4.48 1.87-6.17 1.44-1.69 3.55-2.67 5.75-2.71.55-.01 1.1.04 1.64.16v4.13c-.49-.16-1.02-.23-1.54-.18-1.15.11-2.23.75-2.84 1.72-.61.97-.68 2.21-.21 3.25.47 1.04 1.48 1.78 2.62 1.91 1.14.13 2.31-.35 2.99-1.28.43-.59.66-1.32.66-2.05V.02z"/></svg>' . "\n"
                            . '            </a>';
        }
        if (!empty($socials['youtube'])) {
            $social_items[] = '            <a href="' . htmlspecialchars($socials['youtube']) . '" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="YouTube">' . "\n"
                            . '              <svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>' . "\n"
                            . '            </a>';
        }
        $new_footer_socials = '<div class="footer-socials">' . "\n" . implode("\n", $social_items) . "\n          </div>";

        foreach ($html_files as $hf) {
            $file_path = $root . '/' . $hf;
            if (!file_exists($file_path)) continue;
            $html = file_get_contents($file_path);
            $html = preg_replace('/<div class="footer-socials">.*?<\/div>/s', $new_footer_socials, $html);
            file_put_contents($file_path, $html);
        }
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
    } elseif ($tab === 'socials') {
        $site_content['socials']['facebook'] = trim($_POST['facebook'] ?? '');
        $site_content['socials']['instagram'] = trim($_POST['instagram'] ?? '');
        $site_content['socials']['whatsapp'] = trim($_POST['whatsapp'] ?? '');
        $site_content['socials']['linkedin'] = trim($_POST['linkedin'] ?? '');
        $site_content['socials']['twitter'] = trim($_POST['twitter'] ?? '');
        $site_content['socials']['tiktok'] = trim($_POST['tiktok'] ?? '');
        $site_content['socials']['youtube'] = trim($_POST['youtube'] ?? '');
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
      padding-bottom: 0.75rem;
      flex-wrap: wrap;
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
      text-decoration: none;
      transition: all 0.22s ease;
    }
    @media (max-width: 768px) {
      .content-nav-tabs {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
        padding-bottom: 1rem !important;
      }
      .c-tab-btn {
        width: 100% !important;
        justify-content: center !important;
        text-align: center !important;
        padding: 10px 8px !important;
        font-size: 0.82rem !important;
        white-space: normal !important;
        border-radius: 10px !important;
        line-height: 1.25 !important;
        border: 1px solid var(--border-subtle) !important;
        background: rgba(255, 255, 255, 0.04) !important;
      }
      .content-nav-tabs .c-tab-btn:last-child {
        grid-column: span 2 !important;
      }
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
        <a href="content-manager.php?tab=voice" class="c-tab-btn <?= $active_tab === 'voice' ? 'active' : '' ?>">🤖 AI Voice Assistant</a>
        <a href="content-manager.php?tab=socials" class="c-tab-btn <?= $active_tab === 'socials' ? 'active' : '' ?>">📱 Social Media Links</a>
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
 
      <!-- Tab 6: Social Media Icons & Profiles -->
      <?php if ($active_tab === 'socials'): 
        $soc = $site_content['socials'] ?? [];
      ?>
        <form method="POST">
          <input type="hidden" name="tab" value="socials">
          <div class="field-card">
            <div class="field-card-title">📱 Social Media Icons &amp; Channels</div>
            <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.25rem;">
              Manage the links for the social media icons displayed in the website footer and contact channels. Empty fields will automatically hide that icon on the live site.
            </p>

            <div class="grid-2">
              <div class="form-group">
                <label>📘 Facebook Page URL</label>
                <input type="url" name="facebook" value="<?= htmlspecialchars($soc['facebook'] ?? '') ?>" placeholder="https://www.facebook.com/yourpage">
                <div class="field-hint">Your official Facebook page or profile link</div>
              </div>

              <div class="form-group">
                <label>📸 Instagram Profile URL</label>
                <input type="url" name="instagram" value="<?= htmlspecialchars($soc['instagram'] ?? '') ?>" placeholder="https://www.instagram.com/yourhandle">
                <div class="field-hint">Your brand's Instagram handle link</div>
              </div>

              <div class="form-group">
                <label>💬 WhatsApp Direct Chat Link</label>
                <input type="text" name="whatsapp" value="<?= htmlspecialchars($soc['whatsapp'] ?? '') ?>" placeholder="https://wa.me/2349050278999">
                <div class="field-hint">Full link with country code (e.g. https://wa.me/2349050278999)</div>
              </div>

              <div class="form-group">
                <label>💼 LinkedIn Company Profile URL</label>
                <input type="url" name="linkedin" value="<?= htmlspecialchars($soc['linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/in/yourcompany">
                <div class="field-hint">Corporate business page or executive profile</div>
              </div>

              <div class="form-group">
                <label>🐦 Twitter / X Profile URL (Optional)</label>
                <input type="url" name="twitter" value="<?= htmlspecialchars($soc['twitter'] ?? '') ?>" placeholder="https://x.com/yourhandle">
                <div class="field-hint">Enter link to display the Twitter / X icon in footer</div>
              </div>

              <div class="form-group">
                <label>🎵 TikTok Profile URL (Optional)</label>
                <input type="url" name="tiktok" value="<?= htmlspecialchars($soc['tiktok'] ?? '') ?>" placeholder="https://www.tiktok.com/@yourhandle">
                <div class="field-hint">Enter link to display the TikTok icon in footer</div>
              </div>
            </div>

            <div class="form-group" style="margin-top: 1.25rem;">
              <label>▶️ YouTube Channel URL (Optional)</label>
              <input type="url" name="youtube" value="<?= htmlspecialchars($soc['youtube'] ?? '') ?>" placeholder="https://www.youtube.com/@yourchannel">
              <div class="field-hint">Enter link to display the YouTube icon in footer</div>
            </div>

            <div style="margin-top: 1.75rem;">
              <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem;">💾 Save &amp; Sync Social Icons</button>
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
