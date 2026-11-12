<?php
require_once __DIR__ . '/auth.php';
require_login();

$config = load_config();
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Change Username
    if ($action === 'change_username') {
        $new_user = trim($_POST['new_username'] ?? '');
        $current_pass = $_POST['verify_password'] ?? '';

        if (empty($new_user)) {
            $message = 'Please enter a valid new username.';
            $message_type = 'danger';
        } elseif (!verify_admin_credentials($config['username'], $current_pass)) {
            $message = 'Verification failed: Current password is incorrect.';
            $message_type = 'danger';
        } else {
            $old_user = $config['username'];
            $config['username'] = $new_user;
            save_config($config);
            $_SESSION['admin_user'] = $new_user;
            $message = "Username successfully changed from '{$old_user}' to '{$new_user}'!";
        }
    }

    // 2. Change Password
    elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!verify_admin_credentials($config['username'], $current_pass)) {
            $message = 'Current password does not match.';
            $message_type = 'danger';
        } elseif (strlen($new_pass) < 4) {
            $message = 'New password must be at least 4 characters long.';
            $message_type = 'danger';
        } elseif ($new_pass !== $confirm_pass) {
            $message = 'New password and confirmation do not match.';
            $message_type = 'danger';
        } else {
            $config['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
            $config['sha256_hash'] = hash('sha256', $new_pass);
            unset($config['plain_initial']);
            save_config($config);
            $message = "Password successfully updated! Your account is secured with the new password.";
        }
    }

    // 3. Update Site Info
    elseif ($action === 'update_site') {
        $config['site_name'] = trim($_POST['site_name'] ?? $config['site_name']);
        $config['admin_email'] = trim($_POST['admin_email'] ?? $config['admin_email']);
        $config['phone'] = trim($_POST['phone'] ?? $config['phone']);
        $config['address'] = trim($_POST['address'] ?? $config['address']);
        save_config($config);
        $message = "Company and contact details successfully updated!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings — Glorifix Admin</title>
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
      <a href="media.php" class="nav-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        <span>Media & Uploads</span>
      </a>

      <span class="nav-section-title">Configuration</span>
      <a href="settings.php" class="nav-item active">
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
          <span>Account & Security Settings</span>
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
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Account & System Settings</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Update your administrator credentials (username & password), business contact details, and view server status.</p>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-auto-dismiss">
          <span><?= $message_type === 'success' ? '✅' : '⚠️' ?></span>
          <span><?= htmlspecialchars($message) ?></span>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">

        <!-- 1. Change Password Card -->
        <div class="admin-card">
          <div class="card-header">
            <h3 class="card-title">🔐 Change Password</h3>
            <span class="badge badge-gold">Security</span>
          </div>
          <div class="card-body">
            <form method="POST" action="settings.php" id="password-form" autocomplete="off">
              <input type="hidden" name="action" value="change_password">

              <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
              </div>

              <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" required minlength="4">
              </div>

              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="4">
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%;">
                Update Password
              </button>
            </form>
          </div>
        </div>

        <!-- 2. Change Username Card -->
        <div class="admin-card">
          <div class="card-header">
            <h3 class="card-title">👤 Change Username</h3>
            <span class="badge badge-wine">Account</span>
          </div>
          <div class="card-body">
            <form method="POST" action="settings.php" autocomplete="off">
              <input type="hidden" name="action" value="change_username">

              <div class="form-group">
                <label class="form-label">Current Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($config['username'] ?? 'Ezekiel') ?>" disabled style="opacity: 0.7;">
              </div>

              <div class="form-group">
                <label class="form-label">New Username</label>
                <input type="text" name="new_username" class="form-control" placeholder="e.g. Ezekiel_Admin" required>
              </div>

              <div class="form-group">
                <label class="form-label">Enter Password to Confirm Identity</label>
                <input type="password" name="verify_password" class="form-control" placeholder="Current password" required>
              </div>

              <button type="submit" class="btn btn-gold" style="width: 100%;">
                Update Username
              </button>
            </form>
          </div>
        </div>

      </div>

      <!-- 3. Company Contact Info Card -->
      <div class="admin-card" style="margin-top: 1.5rem;">
        <div class="card-header">
          <h3 class="card-title">🏢 Company Contact & Inquiries Configuration</h3>
          <span class="badge badge-gold">cPanel Ready</span>
        </div>
        <div class="card-body">
          <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="update_site">

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Company Brand Name</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($config['site_name'] ?? 'Glorifix Media Concept Limited') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Inquiry Target Email (Booking & Contact Forms)</label>
                <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($config['admin_email'] ?? 'glorifixmediaconcept@gmail.com') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Phone Hotline / Consultation Line</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($config['phone'] ?? '09050278999') ?>" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Office Physical Address</label>
              <textarea name="address" class="form-control" style="min-height: 80px;"><?= htmlspecialchars($config['address'] ?? 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
              💾 Save Company Details
            </button>
          </form>
        </div>
      </div>

      <!-- 4. Security & Environment Card -->
      <div class="admin-card">
        <div class="card-header">
          <h3 class="card-title">🛡️ Security & Hosting Environment</h3>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            <div>
              <span style="font-size: 0.8rem; color: var(--gold-primary);">Last Successful Login</span>
              <p style="color: #fff; font-weight: 600; margin-top: 2px;"><?= htmlspecialchars($config['last_login'] ?? 'Active session') ?></p>
            </div>

            <div>
              <span style="font-size: 0.8rem; color: var(--gold-primary);">Password Encryption</span>
              <p style="color: #6ee7b7; font-weight: 600; margin-top: 2px;">BCRYPT / Argon2 (Native PHP)</p>
            </div>

            <div>
              <span style="font-size: 0.8rem; color: var(--gold-primary);">cPanel Target Path</span>
              <p style="color: #fff; font-weight: 600; margin-top: 2px;"><code>public_html/admin/</code></p>
            </div>

            <div>
              <span style="font-size: 0.8rem; color: var(--gold-primary);">Direct File Protection</span>
              <p style="color: #6ee7b7; font-weight: 600; margin-top: 2px;">.htaccess Deny From All Active</p>
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
