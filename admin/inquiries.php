<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$inquiries_file = __DIR__ . '/data/inquiries.json';
$inquiries = file_exists($inquiries_file) ? (json_decode(file_get_contents($inquiries_file), true) ?: []) : [];

$message = '';
$message_type = 'success';

// Handle POST actions (mark read, replied, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['inquiry_id'] ?? '';

    if ($action === 'mark_read' && !empty($id)) {
        foreach ($inquiries as &$inq) {
            if ($inq['id'] === $id) {
                $inq['read'] = true;
                if ($inq['status'] === 'new') $inq['status'] = 'read';
                break;
            }
        }
        file_put_contents($inquiries_file, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $message = "Inquiry #{$id} marked as read.";
    } elseif ($action === 'mark_replied' && !empty($id)) {
        foreach ($inquiries as &$inq) {
            if ($inq['id'] === $id) {
                $inq['status'] = 'replied';
                $inq['read'] = true;
                break;
            }
        }
        file_put_contents($inquiries_file, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $message = "Inquiry #{$id} status updated to Replied.";
    } elseif ($action === 'delete' && !empty($id)) {
        $inquiries = array_values(array_filter($inquiries, fn($inq) => $inq['id'] !== $id));
        file_put_contents($inquiries_file, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $message = "Inquiry deleted successfully.";
    }
}

// Calculate Stats
$total_count = count($inquiries);
$unread_count = count(array_filter($inquiries, fn($i) => empty($i['read'])));
$booking_count = count(array_filter($inquiries, fn($i) => stripos($i['type'] ?? '', 'booking') !== false));
$direct_count = $total_count - $booking_count;

// Search & Filter
$filter_type = $_GET['type'] ?? 'all';
$search_query = trim($_GET['q'] ?? '');

$filtered_inquiries = array_filter($inquiries, function($inq) use ($filter_type, $search_query) {
    if ($filter_type === 'unread' && !empty($inq['read'])) return false;
    if ($filter_type === 'booking' && stripos($inq['type'] ?? '', 'booking') === false) return false;
    if ($filter_type === 'inquiry' && stripos($inq['type'] ?? '', 'booking') !== false) return false;
    if ($filter_type === 'replied' && ($inq['status'] ?? '') !== 'replied') return false;

    if (!empty($search_query)) {
        $haystack = strtolower(($inq['name'] ?? '') . ' ' . ($inq['email'] ?? '') . ' ' . ($inq['phone'] ?? '') . ' ' . ($inq['service'] ?? '') . ' ' . ($inq['notes'] ?? ''));
        return strpos($haystack, strtolower($search_query)) !== false;
    }
    return true;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Inquiries &amp; Bookings — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
  <style>
    .badge-unread {
      background: var(--red2);
      color: #fff;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 10px;
      margin-left: 6px;
    }
    .status-pill {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .status-new { background: rgba(232, 37, 53, 0.2); color: #FF6B7A; border: 1px solid rgba(232, 37, 53, 0.4); }
    .status-read { background: rgba(255, 255, 255, 0.08); color: #D5C2C6; border: 1px solid rgba(255, 255, 255, 0.15); }
    .status-replied { background: rgba(37, 211, 102, 0.15); color: #4ADE80; border: 1px solid rgba(37, 211, 102, 0.3); }

    .inquiry-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-md);
      padding: 1.25rem;
      margin-bottom: 1rem;
      transition: all 0.2s ease;
    }
    .inquiry-card.is-unread {
      border-left: 4px solid var(--gold);
      background: linear-gradient(90deg, rgba(232, 185, 35, 0.04) 0%, var(--bg-card) 100%);
    }
    .inquiry-card:hover {
      border-color: var(--border-gold);
    }
    .inq-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.75rem;
      flex-wrap: wrap;
      gap: 8px;
    }
    .inq-customer {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .inq-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--red1), var(--gold));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: #fff;
      font-size: 1.1rem;
    }
    .inq-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #fff;
    }
    .inq-time {
      font-size: 0.8rem;
      color: var(--text-muted);
    }
    .inq-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      background: rgba(0, 0, 0, 0.25);
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      margin-bottom: 10px;
      font-size: 0.9rem;
    }
    .inq-grid-item span {
      display: block;
      font-size: 0.75rem;
      color: var(--gold);
      text-transform: uppercase;
      margin-bottom: 2px;
    }
    .inq-notes {
      font-size: 0.92rem;
      line-height: 1.5;
      color: var(--text-main);
      background: rgba(255, 255, 255, 0.03);
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      border-left: 3px solid var(--red2);
      margin-bottom: 12px;
    }
    .inq-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    .filter-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 1.5rem;
      overflow-x: auto;
      padding-bottom: 4px;
    }
    .filter-btn {
      padding: 6px 14px;
      border-radius: 20px;
      border: 1px solid var(--border-subtle);
      background: var(--bg-card);
      color: var(--text-muted);
      font-size: 0.88rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .filter-btn.active {
      background: var(--red2);
      border-color: var(--red2);
      color: #fff;
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
      <a href="inquiries.php" class="nav-item active">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Inquiries &amp; Bookings</span>
        <?php if ($unread_count > 0): ?>
          <span class="badge-unread"><?= $unread_count ?></span>
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

  <!-- Main Content Area -->
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="page-breadcrumb">
          <span>Customer Inquiries &amp; Consultation Bookings</span>
        </div>
      </div>

      <div class="topbar-right">
        <a href="../contact.html" target="_blank" class="btn-view-site">
          <span>View Contact Page</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- Header & Stats Overview -->
      <div style="margin-bottom: 1.75rem;">
        <h1 style="font-size: 1.75rem; margin-bottom: 0.35rem;">Customer Inquiries &amp; Bookings</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">All consultation bookings and contact notes submitted from your website are saved here in real time.</p>
      </div>

      <!-- Quick Metrics -->
      <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(232, 37, 53, 0.15); color: var(--red2);">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $unread_count ?></div>
            <div class="stat-label">New / Unread</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(232, 185, 35, 0.15); color: var(--gold);">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $booking_count ?></div>
            <div class="stat-label">Consultation Bookings</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $direct_count ?></div>
            <div class="stat-label">Direct Notes</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(37, 211, 102, 0.15); color: #4ade80;">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $total_count ?></div>
            <div class="stat-label">Total Inquiries Logged</div>
          </div>
        </div>
      </div>

      <!-- Filter Tabs & Search -->
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 1.25rem;">
        <div class="filter-tabs" style="margin-bottom: 0;">
          <a href="inquiries.php?type=all" class="filter-btn <?= $filter_type === 'all' ? 'active' : '' ?>">All (<?= $total_count ?>)</a>
          <a href="inquiries.php?type=unread" class="filter-btn <?= $filter_type === 'unread' ? 'active' : '' ?>">Unread (<?= $unread_count ?>)</a>
          <a href="inquiries.php?type=booking" class="filter-btn <?= $filter_type === 'booking' ? 'active' : '' ?>">📅 Bookings (<?= $booking_count ?>)</a>
          <a href="inquiries.php?type=inquiry" class="filter-btn <?= $filter_type === 'inquiry' ? 'active' : '' ?>">📩 Direct Notes (<?= $direct_count ?>)</a>
          <a href="inquiries.php?type=replied" class="filter-btn <?= $filter_type === 'replied' ? 'active' : '' ?>">✓ Replied</a>
        </div>

        <form method="GET" action="inquiries.php" style="display: flex; gap: 8px;">
          <input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>">
          <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search customer, phone..." style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 6px 12px; color: #fff; font-size: 0.88rem;">
          <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.88rem;">Search</button>
        </form>
      </div>

      <!-- Inquiries List -->
      <?php if (empty($filtered_inquiries)): ?>
        <div class="panel" style="text-align: center; padding: 3rem 1.5rem;">
          <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📭</div>
          <h3 style="color: #fff; margin-bottom: 0.5rem;">No Inquiries Found</h3>
          <p style="color: var(--text-muted); font-size: 0.92rem;">Customer consultation bookings and messages submitted on your website will appear here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($filtered_inquiries as $inq): 
            $is_unr = empty($inq['read']);
            $status = $inq['status'] ?? ($is_unr ? 'new' : 'read');
            $clean_phone = preg_replace('/[^0-9]/', '', $inq['phone'] ?? '');
        ?>
          <div class="inquiry-card <?= $is_unr ? 'is-unread' : '' ?>">
            <div class="inq-top">
              <div class="inq-customer">
                <div class="inq-avatar"><?= strtoupper(substr($inq['name'] ?? 'C', 0, 1)) ?></div>
                <div>
                  <div class="inq-title"><?= htmlspecialchars($inq['name'] ?? 'Customer') ?></div>
                  <div class="inq-time">
                    <?= htmlspecialchars($inq['timestamp'] ?? '') ?> &bull; Ref: <strong>#<?= htmlspecialchars($inq['id'] ?? '') ?></strong>
                  </div>
                </div>
              </div>

              <div>
                <span class="status-pill status-<?= $status ?>"><?= $status ?></span>
                <span style="font-size: 0.82rem; color: var(--gold); margin-left: 8px; font-weight: 600;">
                  <?= htmlspecialchars($inq['type'] ?? 'Consultation') ?>
                </span>
              </div>
            </div>

            <!-- Inq Details Grid -->
            <div class="inq-grid">
              <div class="inq-grid-item">
                <span>Email Address</span>
                <a href="mailto:<?= htmlspecialchars($inq['email'] ?? '') ?>" style="color: #fff; text-decoration: underline;">
                  <?= htmlspecialchars($inq['email'] ?? 'Not provided') ?>
                </a>
              </div>
              <div class="inq-grid-item">
                <span>Phone / WhatsApp</span>
                <?= htmlspecialchars($inq['phone'] ?? 'Not provided') ?>
              </div>
              <div class="inq-grid-item">
                <span>Service Requested</span>
                <strong style="color: #fff;"><?= htmlspecialchars($inq['service'] ?? 'General') ?></strong>
              </div>
              <?php if (!empty($inq['appointment_date'])): ?>
                <div class="inq-grid-item">
                  <span>📅 Scheduled Time</span>
                  <strong style="color: var(--gold);"><?= htmlspecialchars($inq['appointment_date']) ?> at <?= htmlspecialchars($inq['appointment_time'] ?? '10:00 AM') ?></strong>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($inq['notes'])): ?>
              <div class="inq-notes">
                <strong>Project Goals / Message:</strong><br>
                <?= nl2br(htmlspecialchars($inq['notes'])) ?>
              </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="inq-actions">
              <?php if (!empty($clean_phone)): ?>
                <a href="https://wa.me/<?= htmlspecialchars($clean_phone) ?>?text=Hello%20<?= urlencode($inq['name'] ?? '') ?>,%20thank%20you%20for%20contacting%20Glorifix%20Media%20Concept%20regarding%20<?= urlencode($inq['service'] ?? '') ?>." target="_blank" class="btn btn-secondary" style="background: rgba(37, 211, 102, 0.15); color: #4ade80; border-color: rgba(37, 211, 102, 0.3); font-size: 0.84rem; padding: 6px 12px;">
                  💬 Chat on WhatsApp
                </a>
              <?php endif; ?>

              <a href="mailto:<?= htmlspecialchars($inq['email'] ?? '') ?>?subject=Re:%20Glorifix%20Consultation%20(Ref:%20<?= urlencode($inq['id'] ?? '') ?>)" class="btn btn-secondary" style="font-size: 0.84rem; padding: 6px 12px;">
                ✉️ Send Email
              </a>

              <?php if ($status !== 'replied'): ?>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="action" value="mark_replied">
                  <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars($inq['id']) ?>">
                  <button type="submit" class="btn btn-secondary" style="font-size: 0.84rem; padding: 6px 12px;">✓ Mark Replied</button>
                </form>
              <?php endif; ?>

              <?php if ($is_unr): ?>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars($inq['id']) ?>">
                  <button type="submit" class="btn btn-secondary" style="font-size: 0.84rem; padding: 6px 12px;">Mark Read</button>
                </form>
              <?php endif; ?>

              <form method="POST" style="display: inline; margin-left: auto;" onsubmit="return confirm('Delete this inquiry?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars($inq['id']) ?>">
                <button type="submit" class="btn btn-danger" style="font-size: 0.84rem; padding: 6px 12px;">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </main>
</div>

<script src="js/admin.js"></script>
</body>
</html>
