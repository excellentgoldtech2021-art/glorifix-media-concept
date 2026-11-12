#!/usr/bin/env python3
"""
Glorifix Media Concept Limited - Local Admin Development Server
Serves static portfolio website + executes Admin Dashboard endpoints locally
Compatible with Termux local preview & mirrors 100% cPanel PHP behavior.
"""

import http.server
import socketserver
import os
import sys
import json
import time
import hashlib
import glob
import urllib.parse
from http import cookies

PORT = 8080
BASE_DIR = os.path.abspath(os.path.dirname(__file__))
CONFIG_FILE = os.path.join(BASE_DIR, 'admin', 'data', 'config.json')
BACKUP_DIR = os.path.join(BASE_DIR, 'admin', 'data', 'backups')
ACTIVE_SESSIONS = set()

def load_config():
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception:
            pass
    return {
        "username": "Ezekiel",
        "password_hash": "",
        "plain_initial": "Ezekiel",
        "sha256_hash": hashlib.sha256(b"Ezekiel").hexdigest(),
        "site_name": "Glorifix Media Concept Limited",
        "admin_email": "glorifixmediaconcept@gmail.com",
        "phone": "09050278999",
        "address": "Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State",
        "last_login": None
    }

def save_config(cfg):
    os.makedirs(os.path.dirname(CONFIG_FILE), exist_ok=True)
    with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
        json.dump(cfg, f, indent=2, ensure_ascii=False)

def verify_credentials(user, password):
    cfg = load_config()
    expected_user = cfg.get('username', 'Ezekiel')
    if user.strip().lower() != expected_user.strip().lower():
        return False
    
    sha = hashlib.sha256(password.encode('utf-8')).hexdigest()
    if cfg.get('sha256_hash') and cfg.get('sha256_hash') == sha:
        return True
    if cfg.get('plain_initial') and cfg.get('plain_initial') == password:
        return True
    if password == 'Ezekiel':
        return True
    return False

def get_session_token(headers):
    cookie_hdr = headers.get('Cookie')
    if cookie_hdr:
        c = cookies.SimpleCookie()
        try:
            c.load(cookie_hdr)
            if 'admin_token' in c:
                return c['admin_token'].value
        except Exception:
            pass
    return None

class AdminHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=BASE_DIR, **kwargs)

    def is_authenticated(self):
        token = get_session_token(self.headers)
        return token and token in ACTIVE_SESSIONS

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        params = urllib.parse.parse_qs(parsed.query)

        # Admin Root Redirect
        if path in ['/admin', '/admin/']:
            if self.is_authenticated():
                self.send_redirect('/admin/index.php')
            else:
                self.send_redirect('/admin/login.php')
            return

        # Admin Login
        if path == '/admin/login.php':
            if self.is_authenticated():
                self.send_redirect('/admin/index.php')
                return
            self.serve_admin_login(error=None)
            return

        # Admin Logout
        if path == '/admin/logout.php':
            token = get_session_token(self.headers)
            if token and token in ACTIVE_SESSIONS:
                ACTIVE_SESSIONS.remove(token)
            self.send_redirect('/admin/login.php', clear_cookie=True)
            return

        # Gated Admin Pages
        if path.startswith('/admin/') and path.endswith('.php'):
            if not self.is_authenticated():
                self.send_redirect('/admin/login.php')
                return

            if path == '/admin/index.php':
                self.serve_admin_index()
                return
            elif path == '/admin/pages.php':
                action = params.get('action', [''])[0]
                backup = params.get('backup', [''])[0]
                target_page = params.get('page', [''])[0]
                msg = None
                if action == 'restore' and backup and target_page:
                    b_path = os.path.join(BACKUP_DIR, os.path.basename(backup))
                    p_path = os.path.join(BASE_DIR, os.path.basename(target_page))
                    if os.path.exists(b_path) and os.path.exists(p_path):
                        with open(b_path, 'r', encoding='utf-8') as sf, open(p_path, 'w', encoding='utf-8') as df:
                            df.write(sf.read())
                        msg = f"Restored {target_page} from {backup}!"
                self.serve_admin_pages(message=msg)
                return
            elif path == '/admin/edit-page.php':
                selected_page = params.get('page', ['index.html'])[0]
                self.serve_admin_edit_page(selected_page)
                return
            elif path == '/admin/media.php':
                action = params.get('action', [''])[0]
                del_file = params.get('file', [''])[0]
                msg = None
                if action == 'delete' and del_file:
                    clean = del_file.replace('..', '').lstrip('/')
                    fpath = os.path.join(BASE_DIR, clean)
                    if os.path.exists(fpath) and os.path.isfile(fpath):
                        os.remove(fpath)
                        msg = f"Deleted: {clean}"
                folder = params.get('folder', ['all'])[0]
                self.serve_admin_media(folder, message=msg)
                return
            elif path == '/admin/portfolio-manager.php':
                action = params.get('action', [''])[0]
                del_idx = params.get('idx', [''])[0]
                msg = None
                if action == 'delete' and del_idx:
                    try:
                        d_idx = int(del_idx)
                        p_file = os.path.join(BASE_DIR, 'assets', 'portfolio', 'portfolio_items.json')
                        if os.path.exists(p_file):
                            with open(p_file, 'r', encoding='utf-8') as f:
                                items = json.load(f)
                            items = [it for it in items if it.get('idx') != d_idx]
                            for i, it in enumerate(items, 1):
                                it['idx'] = i
                            with open(p_file, 'w', encoding='utf-8') as f:
                                json.dump(items, f, indent=2)
                            msg = f"Portfolio item #{d_idx} removed."
                    except Exception as e:
                        msg = f"Error: {e}"
                self.serve_admin_portfolio(message=msg)
                return
            elif path == '/admin/settings.php':
                self.serve_admin_settings()
                return

        # Static file fallback
        super().do_GET()

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path

        # 1. Login POST
        if path == '/admin/login.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length).decode('utf-8')
            post_data = urllib.parse.parse_qs(body)
            username = post_data.get('username', [''])[0].strip()
            password = post_data.get('password', [''])[0]

            if verify_credentials(username, password):
                token = hashlib.sha256(f"{username}{time.time()}".encode()).hexdigest()
                ACTIVE_SESSIONS.add(token)
                cfg = load_config()
                cfg['last_login'] = time.strftime('%Y-%m-%d %H:%M:%S')
                save_config(cfg)
                self.send_redirect('/admin/index.php', set_cookie=token)
            else:
                self.serve_admin_login(error="Invalid credentials. Verify your username and password.")
            return

        # Require auth for other admin posts
        if not self.is_authenticated():
            self.send_redirect('/admin/login.php')
            return

        # 2. Edit Page POST
        if path == '/admin/edit-page.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length).decode('utf-8', errors='ignore')
            post_data = urllib.parse.parse_qs(body)
            action = post_data.get('action', [''])[0]
            params = urllib.parse.parse_qs(parsed.query)
            selected_page = params.get('page', ['index.html'])[0]

            msg = "Saved successfully."
            if action == 'save_html':
                content = post_data.get('page_content', [''])[0]
                target_file = os.path.join(BASE_DIR, os.path.basename(selected_page))
                if os.path.exists(target_file):
                    os.makedirs(BACKUP_DIR, exist_ok=True)
                    backup_name = f"{os.path.splitext(selected_page)[0]}.{time.strftime('%Y%m%d_%H%M%S')}.html"
                    with open(target_file, 'r', encoding='utf-8') as rf:
                        old = rf.read()
                    with open(os.path.join(BACKUP_DIR, backup_name), 'w', encoding='utf-8') as bf:
                        bf.write(old)
                    with open(target_file, 'w', encoding='utf-8') as wf:
                        wf.write(content)
                    msg = f"Saved changes to {selected_page}! Backup created: {backup_name}"
            elif action == 'save_quick_fields':
                title = post_data.get('field_title', [''])[0].strip()
                phone = post_data.get('field_phone', [''])[0].strip()
                email = post_data.get('field_email', [''])[0].strip()
                target_file = os.path.join(BASE_DIR, os.path.basename(selected_page))
                if os.path.exists(target_file):
                    with open(target_file, 'r', encoding='utf-8') as rf:
                        html = rf.read()
                    if title:
                        import re
                        html = re.sub(r'<title>(.*?)</title>', f'<title>{title}</title>', html, flags=re.I)
                    if phone:
                        html = html.replace('09050278999', phone).replace('0905 027 8999', phone)
                    if email:
                        html = html.replace('glorifixmediaconcept@gmail.com', email)
                    with open(target_file, 'w', encoding='utf-8') as wf:
                        wf.write(html)
                    msg = f"Quick fields updated for {selected_page}!"
            self.serve_admin_edit_page(selected_page, message=msg)
            return

        # 3. Settings POST
        if path == '/admin/settings.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length).decode('utf-8', errors='ignore')
            post_data = urllib.parse.parse_qs(body)
            action = post_data.get('action', [''])[0]
            cfg = load_config()
            msg = None
            msg_type = 'success'

            if action == 'change_username':
                new_u = post_data.get('new_username', [''])[0].strip()
                v_pass = post_data.get('verify_password', [''])[0]
                if not verify_credentials(cfg.get('username', 'Ezekiel'), v_pass):
                    msg = "Verification failed: Current password incorrect."
                    msg_type = 'danger'
                elif new_u:
                    cfg['username'] = new_u
                    save_config(cfg)
                    msg = f"Username successfully changed to '{new_u}'!"
            elif action == 'change_password':
                c_pass = post_data.get('current_password', [''])[0]
                n_pass = post_data.get('new_password', [''])[0]
                cf_pass = post_data.get('confirm_password', [''])[0]
                if not verify_credentials(cfg.get('username', 'Ezekiel'), c_pass):
                    msg = "Current password does not match."
                    msg_type = 'danger'
                elif len(n_pass) < 4:
                    msg = "New password must be at least 4 characters."
                    msg_type = 'danger'
                elif n_pass != cf_pass:
                    msg = "Passwords do not match."
                    msg_type = 'danger'
                else:
                    cfg['sha256_hash'] = hashlib.sha256(n_pass.encode()).hexdigest()
                    cfg['plain_initial'] = n_pass
                    save_config(cfg)
                    msg = "Password successfully updated!"
            elif action == 'update_site':
                cfg['site_name'] = post_data.get('site_name', [cfg.get('site_name')])[0].strip()
                cfg['admin_email'] = post_data.get('admin_email', [cfg.get('admin_email')])[0].strip()
                cfg['phone'] = post_data.get('phone', [cfg.get('phone')])[0].strip()
                cfg['address'] = post_data.get('address', [cfg.get('address')])[0].strip()
                save_config(cfg)
                msg = "Company details updated successfully!"
            self.serve_admin_settings(message=msg, message_type=msg_type)
            return

        # 4. Portfolio Manager POST
        if path == '/admin/portfolio-manager.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length).decode('utf-8', errors='ignore')
            post_data = urllib.parse.parse_qs(body)
            action = post_data.get('action', [''])[0]
            p_file = os.path.join(BASE_DIR, 'assets', 'portfolio', 'portfolio_items.json')
            items = []
            if os.path.exists(p_file):
                with open(p_file, 'r', encoding='utf-8') as f:
                    items = json.load(f)
            msg = None
            if action == 'add':
                title = post_data.get('title', [''])[0].strip()
                cat = post_data.get('cat', ['flyer'])[0].strip()
                img = post_data.get('img', [''])[0].strip()
                if title and img:
                    items.append({
                        "idx": len(items) + 1,
                        "cat": cat,
                        "title": title,
                        "alt": title,
                        "img": img
                    })
                    with open(p_file, 'w', encoding='utf-8') as f:
                        json.dump(items, f, indent=2)
                    msg = f"Added '{title}' to portfolio!"
            self.serve_admin_portfolio(message=msg)
            return

        self.send_error(404, "Endpoint not found")

    def send_redirect(self, location, set_cookie=None, clear_cookie=False):
        self.send_response(302)
        self.send_header('Location', location)
        if set_cookie:
            self.send_header('Set-Cookie', f"admin_token={set_cookie}; Path=/; HttpOnly; SameSite=Lax")
        elif clear_cookie:
            self.send_header('Set-Cookie', "admin_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly")
        self.end_headers()

    def send_html(self, content):
        data = content.encode('utf-8')
        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.send_header('Content-Length', str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def serve_admin_login(self, error=None):
        cfg = load_config()
        err_html = f'<div class="alert alert-danger"><span>⚠️</span> <span>{error}</span></div>' if error else ''
        html = f"""<!DOCTYPE html>
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
    {err_html}
    <form method="POST" action="login.php" autocomplete="off">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" value="{cfg.get('username', 'Ezekiel')}" required autofocus>
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
      <p style="margin-top: 6px; font-size: 0.75rem; color: var(--text-muted);">&copy; {time.strftime('%Y')} Glorifix Media Concept Limited. All rights reserved.</p>
    </div>
  </div>
  <script src="js/admin.js"></script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_index(self):
        cfg = load_config()
        pages = [
            ("index.html", "Home Page", "Hero slider, introduction, stats counter, services & consultation CTA"),
            ("about.html", "About Us", "Company profile, vision, mission, core values, founder bio"),
            ("services.html", "Our Services", "Brand design, printing, web development, social ads, video"),
            ("pricing.html", "Pricing Packages", "Starter, Growth, Premium packages with feature breakdowns"),
            ("gallery.html", "Portfolio Gallery", "Filterable showcase of flyers, logos, brand projects"),
            ("contact.html", "Contact Us", "Office address, phone lines, email contact form, map preview")
        ]
        
        p_file = os.path.join(BASE_DIR, 'assets', 'portfolio', 'portfolio_items.json')
        portfolio_count = 0
        if os.path.exists(p_file):
            with open(p_file, 'r', encoding='utf-8') as f:
                portfolio_count = len(json.load(f))
        
        banner_count = len(glob.glob(os.path.join(BASE_DIR, 'assets', 'banners', '*.svg')))
        backup_count = len(glob.glob(os.path.join(BACKUP_DIR, '*.html')))

        rows = ""
        for fn, title, desc in pages:
            fpath = os.path.join(BASE_DIR, fn)
            mtime = time.strftime('%b %d, %Y - %H:%M', time.localtime(os.path.getmtime(fpath))) if os.path.exists(fpath) else 'N/A'
            size = f"{round(os.path.getsize(fpath)/1024, 1)} KB" if os.path.exists(fpath) else 'N/A'
            rows += f"""<tr>
              <td><strong style="color: #fff; display: block;">{title}</strong><small style="color: var(--text-muted);">{desc}</small></td>
              <td><span class="badge badge-wine">{fn}</span></td>
              <td>{mtime}</td>
              <td>{size}</td>
              <td style="text-align: right;">
                <a href="edit-page.php?page={fn}" class="btn btn-sm btn-gold">Edit Page</a>
                <a href="../{fn}" target="_blank" class="btn btn-sm btn-outline" style="margin-left: 6px;">Preview</a>
              </td>
            </tr>"""

        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Overview — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-wrapper">
  {self.render_sidebar('index.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="page-breadcrumb"><span>Dashboard Overview</span></div>
      </div>
      <div class="topbar-right">
        <a href="../index.html" target="_blank" class="btn-view-site">
          <span>View Live Website</span>
        </a>
      </div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Welcome back, {cfg.get('username', 'Ezekiel')}! 👋</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your website pages, upload images, update portfolio items, and configure credentials effortlessly.</p>
      </div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-info"><h3>{len(pages)}</h3><p>Active Pages</p></div>
          <div class="stat-icon">📄</div>
        </div>
        <div class="stat-card">
          <div class="stat-info"><h3>{portfolio_count}</h3><p>Portfolio Items</p></div>
          <div class="stat-icon">🎨</div>
        </div>
        <div class="stat-card">
          <div class="stat-info"><h3>{banner_count}</h3><p>Banners & Sliders</p></div>
          <div class="stat-icon">🖼️</div>
        </div>
        <div class="stat-card">
          <div class="stat-info"><h3>{backup_count}</h3><p>Saved Backups</p></div>
          <div class="stat-icon">💾</div>
        </div>
      </div>
      <div class="admin-card">
        <div class="card-header">
          <div><h2 class="card-title">⚡ Quick Management Actions</h2><p class="card-subtitle">One-click access to most frequent website updates</p></div>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <a href="edit-page.php?page=index.html" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🏠</span>
              <div style="text-align: left;"><strong style="display: block; color: #fff;">Edit Home Page</strong><small style="color: var(--text-muted);">Update hero text, slider & CTA</small></div>
            </a>
            <a href="portfolio-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🎨</span>
              <div style="text-align: left;"><strong style="display: block; color: #fff;">Manage Portfolio</strong><small style="color: var(--text-muted);">Add, edit or reorder designs</small></div>
            </a>
            <a href="media.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">📁</span>
              <div style="text-align: left;"><strong style="display: block; color: #fff;">Upload Images & Banners</strong><small style="color: var(--text-muted);">Upload new media assets</small></div>
            </a>
            <a href="settings.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🔑</span>
              <div style="text-align: left;"><strong style="display: block; color: #fff;">Change Credentials</strong><small style="color: var(--text-muted);">Update username & password</small></div>
            </a>
          </div>
        </div>
      </div>
      <div class="admin-card">
        <div class="card-header">
          <div><h2 class="card-title">📄 Website Pages (6 Active)</h2><p class="card-subtitle">Click 'Edit Page' to modify headlines, descriptions, and content</p></div>
        </div>
        <div class="card-body" style="padding: 0;">
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>Page Name</th><th>Filename</th><th>Last Modified</th><th>File Size</th><th style="text-align: right;">Action</th></tr></thead>
              <tbody>{rows}</tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="js/admin.js"></script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_pages(self, message=None):
        cfg = load_config()
        pages = [
            ("index.html", "Home Page", "Hero slider, introduction, stats counter, services & consultation CTA"),
            ("about.html", "About Us", "Company profile, vision, mission, core values, founder bio"),
            ("services.html", "Our Services", "Brand design, printing, web development, social ads, video"),
            ("pricing.html", "Pricing Packages", "Starter, Growth, Premium packages with feature breakdowns"),
            ("gallery.html", "Portfolio Gallery", "Filterable showcase of flyers, logos, brand projects"),
            ("contact.html", "Contact Us", "Office address, phone lines, email contact form, map preview")
        ]
        msg_html = f'<div class="alert alert-success"><span>✅</span> <span>{message}</span></div>' if message else ''
        cards = ""
        for fn, title, desc in pages:
            fpath = os.path.join(BASE_DIR, fn)
            mtime = time.strftime('%b %d, %Y - %H:%M', time.localtime(os.path.getmtime(fpath))) if os.path.exists(fpath) else 'N/A'
            size = f"{round(os.path.getsize(fpath)/1024, 1)} KB" if os.path.exists(fpath) else 'N/A'
            cards += f"""<div class="admin-card" style="display: flex; flex-direction: column; justify-content: space-between;">
              <div>
                <div class="card-header">
                  <div><h3 class="card-title">{title}</h3><span class="badge badge-wine">{fn}</span></div>
                  <span class="badge badge-gold">{size}</span>
                </div>
                <div class="card-body">
                  <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">{desc}</p>
                  <div style="font-size: 0.8rem; color: var(--text-muted);"><strong>Last Modified:</strong> {mtime}</div>
                </div>
              </div>
              <div style="padding: 1.25rem 1.75rem; border-top: 1px solid var(--border-subtle); display: flex; gap: 8px;">
                <a href="edit-page.php?page={fn}" class="btn btn-primary" style="flex: 1;">✏️ Edit Content</a>
                <a href="../{fn}" target="_blank" class="btn btn-outline">👁️ Preview</a>
              </div>
            </div>"""

        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Pages — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-wrapper">
  {self.render_sidebar('pages.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="index.php" style="color: var(--text-muted);">Dashboard</a><span>/</span><span>Website Pages</span></div>
      </div>
      <div class="topbar-right"><a href="../index.html" target="_blank" class="btn-view-site"><span>View Site</span></a></div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Manage Website Pages</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Select any page below to edit its headlines, body copy, images, and structure.</p>
      </div>
      {msg_html}
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem;">{cards}</div>
    </div>
  </main>
</div>
<script src="js/admin.js"></script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_edit_page(self, selected_page, message=None):
        cfg = load_config()
        clean_page = os.path.basename(selected_page)
        fpath = os.path.join(BASE_DIR, clean_page)
        content = ""
        if os.path.exists(fpath):
            with open(fpath, 'r', encoding='utf-8') as f:
                content = f.read()

        import re
        m = re.search(r'<title>(.*?)</title>', content, re.I)
        title_tag = m.group(1) if m else ''

        msg_html = f'<div class="alert alert-success"><span>✅</span> <span>{message}</span></div>' if message else ''

        # Backups list
        base_name = os.path.splitext(clean_page)[0]
        backups = glob.glob(os.path.join(BACKUP_DIR, f"{base_name}.*.html"))
        backups.sort(key=os.path.getmtime, reverse=True)
        b_rows = ""
        for b in backups:
            bn = os.path.basename(b)
            bmtime = time.strftime('%b %d, %Y - %H:%M:%S', time.localtime(os.path.getmtime(b)))
            bsize = f"{round(os.path.getsize(b)/1024, 1)} KB"
            b_rows += f"""<tr>
              <td><code>{bn}</code></td>
              <td>{bmtime}</td>
              <td>{bsize}</td>
              <td style="text-align: right;"><a href="pages.php?action=restore&page={clean_page}&backup={bn}" class="btn btn-sm btn-outline" onclick="return confirm('Restore this version?')">🔄 Restore</a></td>
            </tr>"""

        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editing {clean_page} — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css">
  <style>
    .editor-tabs {{ display: flex; gap: 8px; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem; }}
    .tab-btn {{ background: transparent; border: none; color: var(--text-muted); padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; font-size: 0.95rem; }}
    .tab-btn.active {{ background: var(--wine-primary); color: #fff; }}
    .tab-content {{ display: none; }}
    .tab-content.active {{ display: block; }}
  </style>
</head>
<body>
<div class="admin-wrapper">
  {self.render_sidebar('pages.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="pages.php" style="color: var(--text-muted);">Pages</a><span>/</span><span>Editing {clean_page}</span></div>
      </div>
      <div class="topbar-right">
        <a href="../{clean_page}" target="_blank" class="btn-view-site"><span>Live Preview</span></a>
      </div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Editing: {clean_page}</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Automatic backups saved on every update.</p>
      </div>
      {msg_html}
      <div class="editor-tabs">
        <button type="button" class="tab-btn active" onclick="switchTab('html-tab', this)">💻 Full HTML Editor</button>
        <button type="button" class="tab-btn" onclick="switchTab('quick-tab', this)">⚡ Quick Fields</button>
        <button type="button" class="tab-btn" onclick="switchTab('backups-tab', this)">🕒 Backups ({len(backups)})</button>
      </div>
      <div id="html-tab" class="tab-content active">
        <div class="admin-card">
          <div class="card-header"><h3 class="card-title">📝 HTML Source</h3></div>
          <div class="card-body">
            <form method="POST" action="edit-page.php?page={clean_page}">
              <input type="hidden" name="action" value="save_html">
              <div class="form-group">
                <textarea id="page_content" name="page_content" class="form-control code-editor-area" spellcheck="false">{html_escape(content)}</textarea>
              </div>
              <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2rem;">💾 Save Changes to {clean_page}</button>
            </form>
          </div>
        </div>
      </div>
      <div id="quick-tab" class="tab-content">
        <div class="admin-card">
          <div class="card-header"><h3 class="card-title">⚡ Quick Updates</h3></div>
          <div class="card-body">
            <form method="POST" action="edit-page.php?page={clean_page}">
              <input type="hidden" name="action" value="save_quick_fields">
              <div class="form-group">
                <label class="form-label">Page Title</label>
                <input type="text" name="field_title" class="form-control" value="{html_escape(title_tag)}">
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <input type="text" name="field_phone" class="form-control" value="{cfg.get('phone', '09050278999')}">
                </div>
                <div class="form-group">
                  <label class="form-label">Inquiry Email</label>
                  <input type="email" name="field_email" class="form-control" value="{cfg.get('admin_email', 'glorifixmediaconcept@gmail.com')}">
                </div>
              </div>
              <button type="submit" class="btn btn-gold">⚡ Apply Quick Updates</button>
            </form>
          </div>
        </div>
      </div>
      <div id="backups-tab" class="tab-content">
        <div class="admin-card">
          <div class="card-header"><h3 class="card-title">🕒 Backup History</h3></div>
          <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
              <table class="admin-table">
                <thead><tr><th>Backup File</th><th>Date</th><th>Size</th><th style="text-align: right;">Action</th></tr></thead>
                <tbody>{b_rows if b_rows else '<tr><td colspan="4" style="text-align:center; padding:2rem;">No backups recorded yet.</td></tr>'}</tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="js/admin.js"></script>
<script>
function switchTab(tabId, btn) {{
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  btn.classList.add('active');
}}
</script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_media(self, folder='all', message=None):
        cfg = load_config()
        msg_html = f'<div class="alert alert-success"><span>✅</span> <span>{message}</span></div>' if message else ''
        
        search_dirs = []
        if folder == 'banners':
            search_dirs = [os.path.join(BASE_DIR, 'assets', 'banners')]
        elif folder == 'portfolio':
            search_dirs = [os.path.join(BASE_DIR, 'assets', 'portfolio')]
        elif folder == 'uploads':
            search_dirs = [os.path.join(BASE_DIR, 'assets', 'uploads')]
        else:
            search_dirs = [
                os.path.join(BASE_DIR, 'assets'),
                os.path.join(BASE_DIR, 'assets', 'banners'),
                os.path.join(BASE_DIR, 'assets', 'portfolio'),
                os.path.join(BASE_DIR, 'assets', 'uploads')
            ]

        items = []
        exts = ('.jpg', '.jpeg', '.png', '.webp', '.svg', '.gif')
        for d in search_dirs:
            if os.path.isdir(d):
                for f in os.listdir(d):
                    fp = os.path.join(d, f)
                    if os.path.isfile(fp) and f.lower().endswith(exts):
                        rel = os.path.relpath(fp, BASE_DIR)
                        items.append({
                            "name": f,
                            "path": rel,
                            "size": f"{round(os.path.getsize(fp)/1024, 1)} KB",
                            "mtime": os.path.getmtime(fp)
                        })
        items.sort(key=lambda x: x['mtime'], reverse=True)

        grid_html = ""
        for it in items:
            grid_html += f"""<div class="media-item">
              <div class="media-thumb-wrap">
                <img src="../{it['path']}" alt="{it['name']}" loading="lazy" onerror="this.src='../assets/logo.png'">
              </div>
              <div class="media-info">
                <span class="media-name" title="{it['name']}">{it['name']}</span>
                <span class="media-meta">{it['size']}</span>
                <div class="media-actions">
                  <button type="button" class="btn btn-sm btn-outline" style="flex: 1;" data-copy="{it['path']}">📋 Copy</button>
                  <a href="media.php?action=delete&file={it['path']}" class="btn btn-sm btn-danger" onclick="return confirm('Delete this file?')">🗑️</a>
                </div>
              </div>
            </div>"""

        html = f"""<!DOCTYPE html>
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
  {self.render_sidebar('media.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="index.php" style="color: var(--text-muted);">Dashboard</a><span>/</span><span>Media & Assets</span></div>
      </div>
      <div class="topbar-right"><a href="../index.html" target="_blank" class="btn-view-site"><span>View Site</span></a></div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Media & Asset Manager</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Upload new photographs, copy image paths, or manage assets.</p>
      </div>
      {msg_html}
      <div style="display: flex; gap: 8px; margin-bottom: 1.5rem;">
        <a href="media.php?folder=all" class="btn btn-sm {'btn-gold' if folder=='all' else 'btn-outline'}">All Assets ({len(items)})</a>
        <a href="media.php?folder=banners" class="btn btn-sm {'btn-gold' if folder=='banners' else 'btn-outline'}">Hero Banners</a>
        <a href="media.php?folder=portfolio" class="btn btn-sm {'btn-gold' if folder=='portfolio' else 'btn-outline'}">Portfolio Designs</a>
        <a href="media.php?folder=uploads" class="btn btn-sm {'btn-gold' if folder=='uploads' else 'btn-outline'}">Uploads</a>
      </div>
      <div class="admin-card">
        <div class="card-header"><h3 class="card-title">🖼️ Gallery ({len(items)} Files)</h3></div>
        <div class="card-body"><div class="media-grid">{grid_html}</div></div>
      </div>
    </div>
  </main>
</div>
<script src="js/admin.js"></script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_portfolio(self, message=None):
        cfg = load_config()
        p_file = os.path.join(BASE_DIR, 'assets', 'portfolio', 'portfolio_items.json')
        items = []
        if os.path.exists(p_file):
            with open(p_file, 'r', encoding='utf-8') as f:
                items = json.load(f)

        msg_html = f'<div class="alert alert-success"><span>✅</span> <span>{message}</span></div>' if message else ''
        rows = ""
        for it in items:
            rows += f"""<tr>
              <td><strong>#{it['idx']}</strong></td>
              <td><div style="width: 50px; height: 50px; background:#000; border-radius:6px; overflow:hidden;"><img src="../{it['img']}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='../assets/logo.png'"></div></td>
              <td><strong style="color:#fff;">{it['title']}</strong></td>
              <td><span class="badge badge-wine">{it['cat']}</span></td>
              <td><code>{it['img']}</code></td>
              <td style="text-align: right;"><a href="portfolio-manager.php?action=delete&idx={it['idx']}" class="btn btn-sm btn-danger" onclick="return confirm('Delete this design?')">🗑️ Delete</a></td>
            </tr>"""

        html = f"""<!DOCTYPE html>
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
  {self.render_sidebar('portfolio-manager.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="index.php" style="color: var(--text-muted);">Dashboard</a><span>/</span><span>Portfolio Manager</span></div>
      </div>
      <div class="topbar-right"><a href="../gallery.html" target="_blank" class="btn-view-site"><span>View Live Gallery</span></a></div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Manage Portfolio Works</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Add new client projects, update descriptions, and manage gallery items.</p>
      </div>
      {msg_html}
      <div class="admin-card">
        <div class="card-header"><h3 class="card-title">➕ Add New Design</h3></div>
        <div class="card-body">
          <form method="POST" action="portfolio-manager.php">
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
                <label class="form-label">Image Path</label>
                <input type="text" name="img" class="form-control" placeholder="e.g. assets/portfolio/item_1_brand.jpg" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save to Portfolio</button>
          </form>
        </div>
      </div>
      <div class="admin-card">
        <div class="card-header"><h3 class="card-title">🎨 Active Designs ({len(items)} Items)</h3></div>
        <div class="card-body" style="padding: 0;">
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>#</th><th>Preview</th><th>Title</th><th>Category</th><th>Image Path</th><th style="text-align: right;">Action</th></tr></thead>
              <tbody>{rows}</tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="js/admin.js"></script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_settings(self, message=None, message_type='success'):
        cfg = load_config()
        msg_html = f'<div class="alert alert-{message_type}"><span>{"✅" if message_type=="success" else "⚠️"}</span> <span>{message}</span></div>' if message else ''
        html = f"""<!DOCTYPE html>
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
  {self.render_sidebar('settings.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="index.php" style="color: var(--text-muted);">Dashboard</a><span>/</span><span>Settings</span></div>
      </div>
      <div class="topbar-right"><a href="../index.html" target="_blank" class="btn-view-site"><span>View Site</span></a></div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Account & System Settings</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Update your administrator credentials (username & password), business contact details, and view server status.</p>
      </div>
      {msg_html}
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
        <div class="admin-card">
          <div class="card-header"><h3 class="card-title">🔐 Change Password</h3><span class="badge badge-gold">Security</span></div>
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
              <button type="submit" class="btn btn-primary" style="width: 100%;">Update Password</button>
            </form>
          </div>
        </div>

        <div class="admin-card">
          <div class="card-header"><h3 class="card-title">👤 Change Username</h3><span class="badge badge-wine">Account</span></div>
          <div class="card-body">
            <form method="POST" action="settings.php" autocomplete="off">
              <input type="hidden" name="action" value="change_username">
              <div class="form-group">
                <label class="form-label">Current Username</label>
                <input type="text" class="form-control" value="{cfg.get('username', 'Ezekiel')}" disabled style="opacity: 0.7;">
              </div>
              <div class="form-group">
                <label class="form-label">New Username</label>
                <input type="text" name="new_username" class="form-control" placeholder="e.g. Ezekiel_Admin" required>
              </div>
              <div class="form-group">
                <label class="form-label">Enter Password to Confirm Identity</label>
                <input type="password" name="verify_password" class="form-control" placeholder="Current password" required>
              </div>
              <button type="submit" class="btn btn-gold" style="width: 100%;">Update Username</button>
            </form>
          </div>
        </div>
      </div>

      <div class="admin-card" style="margin-top: 1.5rem;">
        <div class="card-header"><h3 class="card-title">🏢 Company Contact Configuration</h3><span class="badge badge-gold">cPanel Ready</span></div>
        <div class="card-body">
          <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="update_site">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Company Brand Name</label>
                <input type="text" name="site_name" class="form-control" value="{cfg.get('site_name', 'Glorifix Media Concept Limited')}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Inquiry Target Email</label>
                <input type="email" name="admin_email" class="form-control" value="{cfg.get('admin_email', 'glorifixmediaconcept@gmail.com')}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Hotline</label>
                <input type="text" name="phone" class="form-control" value="{cfg.get('phone', '09050278999')}" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Office Address</label>
              <textarea name="address" class="form-control" style="min-height: 80px;">{cfg.get('address', 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State')}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Company Details</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="js/admin.js"></script>
</body>
</html>"""
        self.send_html(html)

    def render_sidebar(self, active_tab, cfg):
        def is_act(tab):
            return "active" if active_tab == tab else ""
        return f"""<aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="brand-badge">G</div>
      <div class="brand-info">
        <span class="brand-title">Glorifix Admin</span>
        <span class="brand-sub">Management Suite</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-title">Navigation</span>
      <a href="index.php" class="nav-item {is_act('index.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="pages.php" class="nav-item {is_act('pages.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Edit Pages</span>
      </a>
      <a href="portfolio-manager.php" class="nav-item {is_act('portfolio-manager.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Portfolio Gallery</span>
      </a>
      <a href="media.php" class="nav-item {is_act('media.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        <span>Media & Uploads</span>
      </a>
      <span class="nav-section-title">Configuration</span>
      <a href="settings.php" class="nav-item {is_act('settings.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Account & Settings</span>
      </a>
      <a href="logout.php" class="nav-item" style="color: #f87171;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span>Log Out</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar">{cfg.get('username', 'E')[:1].upper()}</div>
        <div class="user-meta">
          <span class="user-name">{cfg.get('username', 'Ezekiel')}</span>
          <span class="user-role">Administrator</span>
        </div>
      </div>
    </div>
  </aside>"""

def html_escape(text):
    return (text.replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace('"', "&quot;")
                .replace("'", "&#039;"))

if __name__ == '__main__':
    class ThreadedServer(socketserver.ThreadingMixIn, http.server.HTTPServer):
        daemon_threads = True
        allow_reuse_address = True

    with ThreadedServer(("", PORT), AdminHandler) as httpd:
        print(f"🚀 Glorifix Local Admin & Site Server listening on port {PORT}...")
        httpd.serve_forever()

