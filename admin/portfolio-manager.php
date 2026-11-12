<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$portfolio_file = ROOT_DIR . '/assets/portfolio/portfolio_items.json';
$items = [];
if (file_exists($portfolio_file)) {
    $items = json_decode(file_get_contents($portfolio_file), true) ?: [];
}

$message = '';
$message_type = 'success';

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $cat = trim($_POST['cat'] ?? 'flyer');
        $img = trim($_POST['img'] ?? '');

        // If a file was uploaded directly in the form
        if (isset($_FILES['portfolio_upload']) && $_FILES['portfolio_upload']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['portfolio_upload'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $clean_f = 'item_' . (count($items) + 1) . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $cat) . '.' . $ext;
            $dest = ROOT_DIR . '/assets/portfolio/' . $clean_f;
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                $img = 'assets/portfolio/' . $clean_f;
            }
        }

        if (!empty($title) && !empty($img)) {
            $new_item = [
                'idx' => count($items) + 1,
                'cat' => $cat,
                'title' => $title,
                'alt' => $title,
                'img' => $img
            ];
            $items[] = $new_item;
            file_put_contents($portfolio_file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $message = "Portfolio item '{$title}' added successfully!";
        } else {
            $message = "Please provide both a Title and an Image.";
            $message_type = "danger";
        }
    } elseif ($action === 'edit') {
        $idx = intval($_POST['item_idx'] ?? 0);
        $found = false;
        foreach ($items as &$item) {
            if ($item['idx'] === $idx) {
                $item['title'] = trim($_POST['title'] ?? $item['title']);
                $item['alt'] = $item['title'];
                $item['cat'] = trim($_POST['cat'] ?? $item['cat']);
                if (!empty($_POST['img'])) {
                    $item['img'] = trim($_POST['img']);
                }
                $found = true;
                break;
            }
        }
        if ($found) {
            file_put_contents($portfolio_file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $message = "Portfolio item #{$idx} updated successfully!";
        }
    }
}

// Handle Delete GET
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['idx'])) {
    $del_idx = intval($_GET['idx']);
    $new_items = [];
    $counter = 1;
    foreach ($items as $item) {
        if ($item['idx'] !== $del_idx) {
            $item['idx'] = $counter++;
            $new_items[] = $item;
        }
    }
    $items = $new_items;
    file_put_contents($portfolio_file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $message = "Item #{$del_idx} removed from portfolio.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portfolio Manager — Glorifix Admin</title>
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
      <a href="portfolio-manager.php" class="nav-item active">
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
          <span>Portfolio Gallery Manager</span>
        </div>
      </div>

      <div class="topbar-right">
        <a href="../gallery.html" target="_blank" class="btn-view-site">
          <span>View Live Gallery</span>
        </a>
      </div>
    </header>

    <div class="admin-container">

      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Manage Portfolio Works</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Add new client projects, update flyer descriptions, change categories, or replace images.</p>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-auto-dismiss">
          <span><?= $message_type === 'success' ? '✅' : '⚠️' ?></span>
          <span><?= htmlspecialchars($message) ?></span>
        </div>
      <?php endif; ?>

      <!-- Add New Portfolio Item Form -->
      <div class="admin-card">
        <div class="card-header">
          <h3 class="card-title">➕ Add New Portfolio Design</h3>
        </div>
        <div class="card-body">
          <form method="POST" action="portfolio-manager.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Project Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Real Estate Promotional Flyer" required>
              </div>

              <div class="form-group">
                <label class="form-label">Category</label>
                <select name="cat" class="form-control">
                  <option value="flyer">Flyer & Print Design</option>
                  <option value="brand">Brand Identity & Logos</option>
                  <option value="web">Web Design & Development</option>
                  <option value="social">Social Media Ads & Graphics</option>
                  <option value="app">UI/UX & Mobile App</option>
                  <option value="video">Motion Graphics & Video</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Upload Image File OR Enter Path</label>
                <input type="file" name="portfolio_upload" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
                <input type="text" name="img" class="form-control" placeholder="or enter: assets/portfolio/item_1_brand.jpg" style="margin-top: 6px;">
              </div>
            </div>

            <button type="submit" class="btn btn-primary">
              💾 Save to Portfolio
            </button>
          </form>
        </div>
      </div>

      <!-- Portfolio Items List -->
      <div class="admin-card">
        <div class="card-header">
          <h3 class="card-title">🎨 Active Portfolio Items (<?= count($items) ?> Total)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width: 70px;">#</th>
                  <th style="width: 100px;">Preview</th>
                  <th>Title & Description</th>
                  <th>Category</th>
                  <th>Image Source</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td><strong>#<?= $item['idx'] ?></strong></td>
                    <td>
                      <div style="width: 60px; height: 60px; border-radius: 6px; overflow: hidden; background: #000; border: 1px solid var(--border-subtle);">
                        <img src="../<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/logo.png'">
                      </div>
                    </td>
                    <td>
                      <strong style="color: #fff;"><?= htmlspecialchars($item['title']) ?></strong>
                    </td>
                    <td>
                      <span class="badge badge-wine"><?= htmlspecialchars($item['cat']) ?></span>
                    </td>
                    <td>
                      <code style="font-size: 0.8rem;"><?= htmlspecialchars($item['img']) ?></code>
                    </td>
                    <td style="text-align: right;">
                      <a href="portfolio-manager.php?action=delete&idx=<?= $item['idx'] ?>" 
                         class="btn btn-sm btn-danger" 
                         onclick="return confirm('Delete this portfolio item?')">
                        🗑️ Delete
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script src="js/admin.js"></script>
</body>
</html>
