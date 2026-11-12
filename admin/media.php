<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$message = '';
$message_type = 'success';

$allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
$upload_dir_base = ROOT_DIR . '/assets';

// Handle Upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    $target_sub = isset($_POST['target_folder']) ? $_POST['target_folder'] : 'uploads';
    $valid_subs = ['uploads', 'banners', 'portfolio', ''];
    if (!in_array($target_sub, $valid_subs)) {
        $target_sub = 'uploads';
    }

    $dest_dir = empty($target_sub) ? $upload_dir_base : $upload_dir_base . '/' . $target_sub;
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }

    $file = $_FILES['media_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_exts)) {
            $clean_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($file['name']));
            // If custom filename specified
            if (!empty($_POST['custom_name'])) {
                $custom = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($_POST['custom_name']));
                $clean_name = $custom . '.' . $ext;
            }

            $target_path = $dest_dir . '/' . $clean_name;
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $web_path = 'assets/' . ($target_sub ? $target_sub . '/' : '') . $clean_name;
                $message = "File uploaded successfully: <code>{$web_path}</code>";
            } else {
                $message = "Failed to move uploaded file. Check folder write permissions.";
                $message_type = "danger";
            }
        } else {
            $message = "Invalid file type. Allowed: " . implode(', ', $allowed_exts);
            $message_type = "danger";
        }
    } else {
        $message = "Upload error code: " . $file['error'];
        $message_type = "danger";
    }
}

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['file'])) {
    $rel_file = $_GET['file'];
    // Prevent directory traversal
    $clean_rel = str_replace(['..', '\\'], '', $rel_file);
    $clean_rel = ltrim($clean_rel, '/');
    $full_path = ROOT_DIR . '/' . $clean_rel;

    if (strpos($full_path, $upload_dir_base) === 0 && file_exists($full_path) && !is_dir($full_path)) {
        if (unlink($full_path)) {
            $message = "Deleted file: " . htmlspecialchars($clean_rel);
        } else {
            $message = "Could not delete file.";
            $message_type = "danger";
        }
    }
}

// Fetch media files
$filter_folder = isset($_GET['folder']) ? $_GET['folder'] : 'all';
$scanned_items = [];

function scan_media_folder($dir, $prefix = 'assets/') {
    $items = [];
    if (!is_dir($dir)) return $items;
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . '/' . $f;
        if (is_file($path)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'])) {
                $items[] = [
                    'name' => $f,
                    'path' => $prefix . $f,
                    'full_path' => $path,
                    'size' => filesize($path),
                    'mtime' => filemtime($path),
                    'ext' => $ext
                ];
            }
        }
    }
    return $items;
}

if ($filter_folder === 'banners') {
    $scanned_items = scan_media_folder(ROOT_DIR . '/assets/banners', 'assets/banners/');
} elseif ($filter_folder === 'portfolio') {
    $scanned_items = scan_media_folder(ROOT_DIR . '/assets/portfolio', 'assets/portfolio/');
} elseif ($filter_folder === 'uploads') {
    $scanned_items = scan_media_folder(ROOT_DIR . '/assets/uploads', 'assets/uploads/');
} else {
    $scanned_items = array_merge(
        scan_media_folder(ROOT_DIR . '/assets', 'assets/'),
        scan_media_folder(ROOT_DIR . '/assets/banners', 'assets/banners/'),
        scan_media_folder(ROOT_DIR . '/assets/portfolio', 'assets/portfolio/'),
        scan_media_folder(ROOT_DIR . '/assets/uploads', 'assets/uploads/')
    );
}

// Sort newest first
usort($scanned_items, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Media & Uploads — Glorifix Admin</title>
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
      <a href="index.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="pages.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Edit Pages</span>
      </a>
      <a href="portfolio-manager.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Portfolio Gallery</span>
      </a>
      <a href="media.php" class="nav-item active">
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
          <span>Media & Uploads</span>
        </div>
      </div>

      <div class="topbar-right">
        <a href="../index.html" target="_blank" class="btn-view-site">
          <span>View Site</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Media & Asset Manager</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Upload new photographs, replace banner graphics, copy image paths for use in your pages, or manage assets.</p>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-auto-dismiss">
          <span><?= $message_type === 'success' ? '✅' : '⚠️' ?></span>
          <span><?= $message ?></span>
        </div>
      <?php endif; ?>

      <!-- Upload Form Card -->
      <div class="admin-card">
        <div class="card-header">
          <h3 class="card-title">📤 Upload New Media</h3>
          <span class="badge badge-gold">JPG, PNG, WEBP, SVG</span>
        </div>
        <div class="card-body">
          <form method="POST" action="media.php" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Select File to Upload</label>
                <input type="file" name="media_file" class="form-control" required accept=".jpg,.jpeg,.png,.webp,.svg,.gif">
              </div>

              <div class="form-group">
                <label class="form-label">Target Folder</label>
                <select name="target_folder" class="form-control">
                  <option value="uploads">assets/uploads/ (General Media)</option>
                  <option value="banners">assets/banners/ (Hero Banners & Sliders)</option>
                  <option value="portfolio">assets/portfolio/ (Portfolio Work)</option>
                  <option value="">assets/ (Brand Logo & Core Assets)</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Custom Filename (Optional)</label>
                <input type="text" name="custom_name" class="form-control" placeholder="e.g. new_flyer_sample">
              </div>
            </div>

            <button type="submit" class="btn btn-primary">
              🚀 Upload Media File
            </button>
          </form>
        </div>
      </div>

      <!-- Filter Bar -->
      <div style="display: flex; gap: 8px; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="media.php?folder=all" class="btn btn-sm <?= $filter_folder === 'all' ? 'btn-gold' : 'btn-outline' ?>">All Assets (<?= count($scanned_items) ?>)</a>
        <a href="media.php?folder=banners" class="btn btn-sm <?= $filter_folder === 'banners' ? 'btn-gold' : 'btn-outline' ?>">Hero Banners</a>
        <a href="media.php?folder=portfolio" class="btn btn-sm <?= $filter_folder === 'portfolio' ? 'btn-gold' : 'btn-outline' ?>">Portfolio Designs</a>
        <a href="media.php?folder=uploads" class="btn btn-sm <?= $filter_folder === 'uploads' ? 'btn-gold' : 'btn-outline' ?>">User Uploads</a>
      </div>

      <!-- Media Items Grid -->
      <div class="admin-card">
        <div class="card-header">
          <h3 class="card-title">🖼️ Asset Gallery (<?= count($scanned_items) ?> Files)</h3>
          <span style="font-size: 0.85rem; color: var(--text-muted);">Click "Copy Link" to paste into page HTML</span>
        </div>
        <div class="card-body">
          <div class="media-grid">
            <?php foreach ($scanned_items as $item): ?>
              <div class="media-item">
                <div class="media-thumb-wrap">
                  <img src="../<?= htmlspecialchars($item['path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                </div>
                <div class="media-info">
                  <span class="media-name" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></span>
                  <span class="media-meta"><?= round($item['size'] / 1024, 1) ?> KB &bull; <?= strtoupper($item['ext']) ?></span>
                  
                  <div class="media-actions">
                    <button type="button" class="btn btn-sm btn-outline" style="flex: 1;" data-copy="<?= htmlspecialchars($item['path']) ?>">
                      📋 Copy Link
                    </button>
                    <a href="media.php?action=delete&file=<?= urlencode($item['path']) ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Delete this media file permanently?')">
                      🗑️
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script src="js/admin.js"></script>
</body>
</html>
