<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$pages = get_site_pages();

$selected_page = isset($_GET['page']) ? basename($_GET['page']) : 'index.html';
if (!isset($pages[$selected_page])) {
    $selected_page = 'index.html';
}

$target_file = $pages[$selected_page]['file'];
$message = '';
$message_type = 'success';

// Handle Save POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_html') {
        $new_content = isset($_POST['page_content']) ? $_POST['page_content'] : '';
        if (!empty($new_content)) {
            // 1. Create automatic backup first
            create_page_backup($selected_page);
            // 2. Write new content
            if (file_put_contents($target_file, $new_content) !== false) {
                $message = "Page '{$selected_page}' successfully updated! A safe backup was archived.";
            } else {
                $message = "Error writing file. Please check file permissions on cPanel.";
                $message_type = "danger";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'save_quick_fields') {
        $current_html = file_get_contents($target_file);
        $new_title = isset($_POST['field_title']) ? trim($_POST['field_title']) : '';
        $new_headline = isset($_POST['field_headline']) ? trim($_POST['field_headline']) : '';
        $new_phone = isset($_POST['field_phone']) ? trim($_POST['field_phone']) : '';
        $new_email = isset($_POST['field_email']) ? trim($_POST['field_email']) : '';

        create_page_backup($selected_page);

        // Update Title tag
        if (!empty($new_title)) {
            $current_html = preg_replace('/<title>(.*?)<\/title>/is', '<title>' . htmlspecialchars($new_title) . '</title>', $current_html);
        }

        // Update Phone numbers
        if (!empty($new_phone)) {
            $current_html = str_replace('09050278999', $new_phone, $current_html);
            $current_html = str_replace('0905 027 8999', $new_phone, $current_html);
        }

        // Update Email addresses
        if (!empty($new_email)) {
            $current_html = str_replace('glorifixmediaconcept@gmail.com', $new_email, $current_html);
        }

        if (file_put_contents($target_file, $current_html) !== false) {
            $message = "Key section fields updated successfully for '{$selected_page}'!";
        } else {
            $message = "Error updating fields.";
            $message_type = "danger";
        }
    }
}

// Load current page content
$page_content = file_exists($target_file) ? file_get_contents($target_file) : '';

// Extract current <title>
preg_match('/<title>(.*?)<\/title>/is', $page_content, $title_matches);
$extracted_title = isset($title_matches[1]) ? trim($title_matches[1]) : '';

$page_backups = list_backups($selected_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editing <?= htmlspecialchars($selected_page) ?> — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
  <style>
    .editor-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 1.25rem;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 0.5rem;
    }
    .tab-btn {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 0.6rem 1.2rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }
    .tab-btn.active {
      background: var(--wine-primary);
      color: #fff;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
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
          <a href="pages.php" style="color: var(--text-muted);">Pages</a>
          <span>/</span>
          <span>Editing <?= htmlspecialchars($selected_page) ?></span>
        </div>
      </div>

      <div class="topbar-right">
        <!-- Page switcher dropdown -->
        <select onchange="window.location.href='edit-page.php?page=' + this.value" class="form-control" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.88rem;">
          <?php foreach ($pages as $k => $info): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $k === $selected_page ? 'selected' : '' ?>>
              <?= htmlspecialchars($info['name']) ?> (<?= htmlspecialchars($k) ?>)
            </option>
          <?php endforeach; ?>
        </select>

        <a href="../<?= htmlspecialchars($selected_page) ?>" target="_blank" class="btn-view-site">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          <span>Live Preview</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Editing: <?= htmlspecialchars($pages[$selected_page]['name']) ?></h1>
          <p style="color: var(--text-muted); font-size: 0.9rem;">File location: <code><?= htmlspecialchars($selected_page) ?></code> &bull; Safe backups automatically recorded.</p>
        </div>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-auto-dismiss">
          <span><?= $message_type === 'success' ? '✅' : '⚠️' ?></span>
          <span><?= htmlspecialchars($message) ?></span>
        </div>
      <?php endif; ?>

      <!-- Tab Buttons -->
      <div class="editor-tabs">
        <button type="button" class="tab-btn active" onclick="switchTab('html-tab', this)">
          💻 Full HTML Code Editor
        </button>
        <button type="button" class="tab-btn" onclick="switchTab('quick-tab', this)">
          ⚡ Quick Section Fields
        </button>
        <button type="button" class="tab-btn" onclick="switchTab('backups-tab', this)">
          🕒 Version Backups (<?= count($page_backups) ?>)
        </button>
      </div>

      <!-- Tab 1: Full HTML Code Editor -->
      <div id="html-tab" class="tab-content active">
        <div class="admin-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">📝 HTML Source Editor</h3>
              <p class="card-subtitle">Modify content, tags, images, and text directly. Backups are created automatically on Save.</p>
            </div>
            <div style="display: flex; gap: 8px;">
              <button type="button" onclick="formatEditor()" class="btn btn-sm btn-outline">Wrap Lines</button>
            </div>
          </div>
          <div class="card-body">
            <form method="POST" action="edit-page.php?page=<?= urlencode($selected_page) ?>">
              <input type="hidden" name="action" value="save_html">

              <div class="form-group">
                <textarea id="page_content" name="page_content" class="form-control code-editor-area" spellcheck="false"><?= htmlspecialchars($page_content) ?></textarea>
              </div>

              <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <span style="color: var(--text-muted); font-size: 0.85rem;">
                  💾 Saving creates a timestamped rollback file in <code>admin/data/backups/</code>
                </span>
                <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2rem;">
                  💾 Save Changes to <?= htmlspecialchars($selected_page) ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Tab 2: Quick Section Fields -->
      <div id="quick-tab" class="tab-content">
        <div class="admin-card">
          <div class="card-header">
            <h3 class="card-title">⚡ Quick Meta & Contact Updates</h3>
            <p class="card-subtitle">Convenient inputs to update page title, phone numbers, and inquiry email without touching code</p>
          </div>
          <div class="card-body">
            <form method="POST" action="edit-page.php?page=<?= urlencode($selected_page) ?>">
              <input type="hidden" name="action" value="save_quick_fields">

              <div class="form-group">
                <label class="form-label">Page Browser Title (&lt;title&gt;)</label>
                <input type="text" name="field_title" class="form-control" value="<?= htmlspecialchars($extracted_title) ?>">
                <small style="color: var(--text-muted);">Displays in Google search and browser tabs</small>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Contact Phone Number</label>
                  <input type="text" name="field_phone" class="form-control" value="<?= htmlspecialchars($config['phone'] ?? '09050278999') ?>">
                </div>

                <div class="form-group">
                  <label class="form-label">Inquiry Target Email</label>
                  <input type="email" name="field_email" class="form-control" value="<?= htmlspecialchars($config['admin_email'] ?? 'glorifixmediaconcept@gmail.com') ?>">
                </div>
              </div>

              <button type="submit" class="btn btn-gold" style="margin-top: 1rem;">
                ⚡ Apply Quick Updates
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Tab 3: Backups / Rollback -->
      <div id="backups-tab" class="tab-content">
        <div class="admin-card">
          <div class="card-header">
            <h3 class="card-title">🕒 Backup History for <?= htmlspecialchars($selected_page) ?></h3>
            <p class="card-subtitle">Review previous revisions or roll back with a single click</p>
          </div>
          <div class="card-body" style="padding: 0;">
            <?php if (empty($page_backups)): ?>
              <div style="padding: 2.5rem; text-align: center; color: var(--text-muted);">
                <p>No previous backups recorded yet for this page. Backups will appear here as soon as you save edits.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="admin-table">
                  <thead>
                    <tr>
                      <th>Backup File</th>
                      <th>Date Saved</th>
                      <th>Size</th>
                      <th style="text-align: right;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($page_backups as $b): ?>
                      <tr>
                        <td><code><?= htmlspecialchars($b['filename']) ?></code></td>
                        <td><?= $b['date_str'] ?></td>
                        <td><?= round($b['size'] / 1024, 1) ?> KB</td>
                        <td style="text-align: right;">
                          <a href="pages.php?action=restore&page=<?= urlencode($selected_page) ?>&backup=<?= urlencode($b['filename']) ?>" 
                             class="btn btn-sm btn-outline" 
                             onclick="return confirm('Restore this backup version? Current page will be backed up before restoring.')">
                            🔄 Restore Version
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script src="js/admin.js"></script>
<script>
function switchTab(tabId, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  btn.classList.add('active');
}

function formatEditor() {
  const ta = document.getElementById('page_content');
  if (ta.style.whiteSpace === 'pre') {
    ta.style.whiteSpace = 'pre-wrap';
  } else {
    ta.style.whiteSpace = 'pre';
  }
}
</script>
</body>
</html>
