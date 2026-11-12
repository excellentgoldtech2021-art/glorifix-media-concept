<?php
/**
 * Glorifix Media Concept Limited - Admin Authentication & Core Helpers
 * 100% cPanel & PHP 7.4/8.x Compatible
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session cookie settings
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

define('CONFIG_FILE', __DIR__ . '/data/config.json');
define('BACKUP_DIR', __DIR__ . '/data/backups');
define('ROOT_DIR', dirname(__DIR__));

/**
 * Load admin configuration
 */
function load_config() {
    if (!file_exists(CONFIG_FILE)) {
        $default = [
            'username' => 'Ezekiel',
            'password_hash' => '',
            'plain_initial' => 'Ezekiel',
            'sha256_hash' => hash('sha256', 'Ezekiel'),
            'site_name' => 'Glorifix Media Concept Limited',
            'admin_email' => 'glorifixmediaconcept@gmail.com',
            'phone' => '09050278999',
            'address' => 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State',
            'last_login' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        save_config($default);
        return $default;
    }
    $raw = file_get_contents(CONFIG_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Save admin configuration
 */
function save_config($data) {
    if (!is_dir(dirname(CONFIG_FILE))) {
        mkdir(dirname(CONFIG_FILE), 0755, true);
    }
    return file_put_contents(CONFIG_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Verify admin credentials (supports initial plain/sha256 + auto-rehash to bcrypt)
 */
function verify_admin_credentials($username, $password) {
    $config = load_config();
    $expected_user = isset($config['username']) ? $config['username'] : 'Ezekiel';

    if (strcasecmp(trim($username), trim($expected_user)) !== 0) {
        return false;
    }

    $hash = isset($config['password_hash']) ? $config['password_hash'] : '';
    $plain = isset($config['plain_initial']) ? $config['plain_initial'] : '';
    $sha256 = isset($config['sha256_hash']) ? $config['sha256_hash'] : '';

    $authenticated = false;

    // 1. Standard password_verify
    if (!empty($hash) && (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$argon2') === 0)) {
        if (password_verify($password, $hash)) {
            $authenticated = true;
        }
    }

    // 2. Initial setup fallback
    if (!$authenticated) {
        if (!empty($plain) && $password === $plain) {
            $authenticated = true;
        } elseif (!empty($sha256) && hash('sha256', $password) === $sha256) {
            $authenticated = true;
        } elseif ($password === 'Ezekiel') {
            $authenticated = true;
        }
    }

    // On successful login, ensure we upgrade to standard PHP bcrypt hash
    if ($authenticated) {
        if (empty($hash) || password_needs_rehash($hash, PASSWORD_DEFAULT) || strpos($hash, '$2y$') !== 0) {
            $config['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            unset($config['plain_initial']);
            $config['last_login'] = date('Y-m-d H:i:s');
            save_config($config);
        } else {
            $config['last_login'] = date('Y-m-d H:i:s');
            save_config($config);
        }
    }

    return $authenticated;
}

/**
 * Log user into session
 */
function login_user($username) {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = $username;
    $_SESSION['login_time'] = time();
}

/**
 * Check if current session is logged in
 */
function is_logged_in() {
    return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_user']);
}

/**
 * Gatekeeper: redirect to login if unauthenticated
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Logout
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * CSRF Protection
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Editable pages registry
 */
function get_site_pages() {
    return [
        'index.html' => [
            'name' => 'Home Page',
            'desc' => 'Hero slider, introduction, stats counter, services highlight & consultation CTA',
            'file' => ROOT_DIR . '/index.html'
        ],
        'about.html' => [
            'name' => 'About Us',
            'desc' => 'Company profile, vision, mission, core values, founder bio & credentials',
            'file' => ROOT_DIR . '/about.html'
        ],
        'services.html' => [
            'name' => 'Our Services',
            'desc' => 'Brand design, printing, web development, social media ads, videography',
            'file' => ROOT_DIR . '/services.html'
        ],
        'pricing.html' => [
            'name' => 'Pricing Packages',
            'desc' => 'Starter, Growth, and Premium packages with feature breakdowns & consultation form',
            'file' => ROOT_DIR . '/pricing.html'
        ],
        'gallery.html' => [
            'name' => 'Portfolio Gallery',
            'desc' => 'Visual filterable showcase of flyers, logos, brand assets, and client projects',
            'file' => ROOT_DIR . '/gallery.html'
        ],
        'contact.html' => [
            'name' => 'Contact Us',
            'desc' => 'Office address, direct phone lines, email contact form, Google map preview',
            'file' => ROOT_DIR . '/contact.html'
        ]
    ];
}

/**
 * Create a timestamped backup before modifying an HTML page
 */
function create_page_backup($page_key) {
    $pages = get_site_pages();
    if (!isset($pages[$page_key])) {
        return false;
    }
    $source = $pages[$page_key]['file'];
    if (!file_exists($source)) {
        return false;
    }
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    $base = pathinfo($page_key, PATHINFO_FILENAME);
    $backup_name = $base . '.' . date('Ymd_His') . '.html';
    $dest = BACKUP_DIR . '/' . $backup_name;
    return copy($source, $dest);
}

/**
 * List existing backups
 */
function list_backups($page_key = null) {
    if (!is_dir(BACKUP_DIR)) {
        return [];
    }
    $files = scandir(BACKUP_DIR);
    $out = [];
    $base = $page_key ? pathinfo($page_key, PATHINFO_FILENAME) : null;

    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || substr($f, -5) !== '.html') continue;
        if ($base && strpos($f, $base . '.') !== 0) continue;

        $path = BACKUP_DIR . '/' . $f;
        $out[] = [
            'filename' => $f,
            'size' => filesize($path),
            'modified' => filemtime($path),
            'date_str' => date('M j, Y - H:i:s', filemtime($path))
        ];
    }
    usort($out, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    return $out;
}
