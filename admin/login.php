<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$config = load_config();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (verify_admin_credentials($username, $password)) {
            login_user($username);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials. Please verify your username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Glorifix Media Concept Limited</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-body">

  <div class="login-card">
    <div class="login-header">
      <img src="../assets/logo.png" alt="Glorifix Media Concept" class="login-logo" onerror="this.style.display='none'">
      <h1 class="login-title">Control Portal</h1>
      <p class="login-sub">Glorifix Media Concept Limited</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-auto-dismiss">
        <span>⚠️</span>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? 'Ezekiel') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.95rem;">
        Sign In to Dashboard
      </button>
    </form>

    <div class="login-footer">
      <p style="font-size: 0.8rem; color: var(--gold-primary);">Default Initial Login: <strong>Ezekiel</strong> / <strong>Ezekiel</strong></p>
      <p style="margin-top: 6px; font-size: 0.75rem; color: var(--text-muted);">&copy; <?= date('Y') ?> Glorifix Media Concept Limited. All rights reserved.</p>
    </div>
  </div>

  <script src="js/admin.js"></script>
</body>
</html>
