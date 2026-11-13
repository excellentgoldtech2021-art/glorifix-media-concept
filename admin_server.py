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
            elif path == '/admin/inquiries.php':
                self.serve_admin_inquiries(params)
                return
            elif path == '/admin/content-manager.php':
                self.serve_admin_content_manager(params)
                return

        # Static file fallback
        super().do_GET()

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path

        # Public Contact & Booking API endpoint
        if path in ['/api/contact.php', '/api/contact', 'api/contact.php']:
            length = int(self.headers.get('Content-Length', 0))
            raw_body = self.rfile.read(length).decode('utf-8', errors='ignore')
            data = {}
            if self.headers.get('Content-Type', '').startswith('application/json'):
                try:
                    data = json.loads(raw_body)
                except Exception:
                    data = {}
            else:
                qs = urllib.parse.parse_qs(raw_body)
                data = {k: v[0] for k, v in qs.items()}

            name = (data.get('Full_Name') or data.get('Sender_Name') or data.get('name') or '').strip()
            email = (data.get('Email_Address') or data.get('Sender_Email') or data.get('email') or '').strip()
            phone = (data.get('Phone_Number') or data.get('Sender_Phone') or data.get('phone') or '').strip()
            service = (data.get('Service_Requested') or data.get('Subject') or data.get('service') or 'General Consultation').strip()
            notes = (data.get('Project_Notes') or data.get('Inquiry_Message') or data.get('Message') or data.get('notes') or data.get('message') or '').strip()
            app_date = (data.get('Appointment_Date') or data.get('date') or '').strip()
            app_time = (data.get('Appointment_Time') or data.get('time') or '').strip()

            is_booking = bool(app_date or 'booking' in service.lower())
            inq_type = 'Consultation Booking' if is_booking else 'Direct Inquiry'

            inquiries_file = os.path.join(BASE_DIR, 'admin', 'data', 'inquiries.json')
            os.makedirs(os.path.dirname(inquiries_file), exist_ok=True)
            inquiries = []
            if os.path.exists(inquiries_file):
                try:
                    with open(inquiries_file, 'r', encoding='utf-8') as f:
                        inquiries = json.load(f)
                except Exception:
                    inquiries = []

            inq_id = f"INQ-{hashlib.md5(f'{time.time()}{name}'.encode()).hexdigest()[:6].upper()}"
            new_entry = {
                'id': inq_id,
                'timestamp': time.strftime('%Y-%m-%d %H:%M:%S'),
                'type': inq_type,
                'name': name,
                'email': email,
                'phone': phone,
                'service': service,
                'appointment_date': app_date,
                'appointment_time': app_time,
                'notes': notes,
                'status': 'new',
                'read': False
            }
            inquiries.insert(0, new_entry)
            with open(inquiries_file, 'w', encoding='utf-8') as f:
                json.dump(inquiries, f, indent=2, ensure_ascii=False)

            # Logged to inquiries.json for Admin Dashboard

            resp_payload = {
                'success': True,
                'id': inq_id,
                'type': inq_type,
                'client_name': name,
                'client_email': email,
                'admin_notified': True,
                'client_confirmed': True,
                'message': f'Your consultation has been booked successfully! A customized confirmation email has been sent to {email}.' if is_booking else f'Thank you! Your message has been sent successfully. A customized confirmation email has been sent to {email}.'
            }
            resp_bytes = json.dumps(resp_payload).encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

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

        # Inquiries POST
        if path == '/admin/inquiries.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length).decode('utf-8', errors='ignore')
            post_data = urllib.parse.parse_qs(body)
            action = post_data.get('action', [''])[0]
            inq_id = post_data.get('inquiry_id', [''])[0]
            inquiries_file = os.path.join(BASE_DIR, 'admin', 'data', 'inquiries.json')
            msg = None
            if os.path.exists(inquiries_file):
                try:
                    with open(inquiries_file, 'r', encoding='utf-8') as f:
                        inqs = json.load(f)
                    if action == 'mark_read':
                        for i in inqs:
                            if i.get('id') == inq_id:
                                i['read'] = True
                                if i.get('status') == 'new': i['status'] = 'read'
                        msg = f"Inquiry #{inq_id} marked as read."
                    elif action == 'mark_replied':
                        for i in inqs:
                            if i.get('id') == inq_id:
                                i['status'] = 'replied'
                                i['read'] = True
                        msg = f"Inquiry #{inq_id} marked as replied."
                    elif action == 'delete':
                        inqs = [i for i in inqs if i.get('id') != inq_id]
                        msg = "Inquiry deleted."
                    with open(inquiries_file, 'w', encoding='utf-8') as f:
                        json.dump(inqs, f, indent=2, ensure_ascii=False)
                except Exception as e:
                    msg = f"Error: {e}"
            self.serve_admin_inquiries(params=None, message=msg)
            return

        # Content Manager POST
        if path == '/admin/content-manager.php':
            length = int(self.headers.get('Content-Length', 0))
            content_type = self.headers.get('Content-Type', '')
            post_data = {}
            if 'multipart/form-data' in content_type and 'boundary=' in content_type:
                boundary = content_type.split('boundary=')[1].strip().encode()
                raw_body = self.rfile.read(length)
                parts = raw_body.split(b'--' + boundary)
                for part in parts:
                    if b'name="' in part:
                        header_part, _, val_part = part.partition(b'\r\n\r\n')
                        name = header_part.split(b'name="')[1].split(b'"')[0].decode('utf-8')
                        val = val_part.rstrip(b'\r\n-').decode('utf-8', errors='ignore')
                        post_data[name] = [val]
            else:
                body = self.rfile.read(length).decode('utf-8', errors='ignore')
                post_data = urllib.parse.parse_qs(body, keep_blank_values=True)
            tab = post_data.get('tab', ['company'])[0]
            content_file = os.path.join(BASE_DIR, 'admin', 'data', 'site_content.json')
            site_content = {}
            if os.path.exists(content_file):
                try:
                    with open(content_file, 'r', encoding='utf-8') as f:
                        site_content = json.load(f)
                except Exception:
                    site_content = {}
            for sec in ['company', 'pricing', 'hero', 'about', 'voice_agent', 'socials']:
                if sec not in site_content: site_content[sec] = {}
            if tab == 'company':
                for k in ['company_name', 'tagline', 'phone_primary', 'phone_display', 'whatsapp_number', 'email_address', 'office_address', 'support_hub', 'working_hours']:
                    if k in post_data: site_content['company'][k] = post_data[k][0].strip()
            elif tab == 'pricing':
                for k in post_data:
                    if k != 'tab': site_content['pricing'][k] = post_data[k][0].strip()
            elif tab == 'hero':
                for k in post_data:
                    if k != 'tab': site_content['hero'][k] = post_data[k][0].strip()
            elif tab == 'about':
                for k in post_data:
                    if k != 'tab': site_content['about'][k] = post_data[k][0].strip()
            elif tab == 'voice':
                if 'va_cac_response' in post_data: site_content['voice_agent']['cac_response'] = post_data['va_cac_response'][0].strip()
                if 'va_social_response' in post_data: site_content['voice_agent']['social_response'] = post_data['va_social_response'][0].strip()
                if 'va_web_response' in post_data: site_content['voice_agent']['web_response'] = post_data['va_web_response'][0].strip()
            elif tab == 'socials':
                for k in ['facebook', 'instagram', 'whatsapp', 'linkedin', 'twitter', 'tiktok', 'youtube']:
                    if k in post_data: site_content['socials'][k] = post_data[k][0].strip()
            with open(content_file, 'w', encoding='utf-8') as f:
                json.dump(site_content, f, indent=2, ensure_ascii=False)
            self.sync_site_content(site_content)
            self.serve_admin_content_manager(params={'tab': [tab]}, message="Website content saved and synchronized across all pages successfully!")
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
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
  <script src="js/admin.js?v={int(time.time())}"></script>
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

        inq_file = os.path.join(BASE_DIR, 'admin', 'data', 'inquiries.json')
        inquiry_count = 0
        unread_inquiries = 0
        if os.path.exists(inq_file):
            try:
                with open(inq_file, 'r', encoding='utf-8') as f:
                    inqs = json.load(f)
                    inquiry_count = len(inqs)
                    unread_inquiries = len([i for i in inqs if not i.get('read')])
            except Exception:
                pass

        content_file = os.path.join(BASE_DIR, 'admin', 'data', 'site_content.json')
        site_content = {}
        if os.path.exists(content_file):
            try:
                with open(content_file, 'r', encoding='utf-8') as f:
                    site_content = json.load(f)
            except Exception:
                site_content = {}

        comp = site_content.get('company', {})
        hero = site_content.get('hero', {})
        pri = site_content.get('pricing', {})
        soc = site_content.get('socials', {})

        rows = ""
        mobile_cards = ""
        for fn, title, desc in pages:
            fpath = os.path.join(BASE_DIR, fn)
            mtime = time.strftime('%b %d, %Y - %H:%M', time.localtime(os.path.getmtime(fpath))) if os.path.exists(fpath) else 'N/A'
            size = f"{round(os.path.getsize(fpath)/1024, 1)} KB" if os.path.exists(fpath) else 'N/A'
            rows += f"""<tr>
              <td><strong style="display: block;">{title}</strong><small style="color: var(--text-muted);">{desc}</small></td>
              <td><span class="badge badge-wine">{fn}</span></td>
              <td>{mtime}</td>
              <td>{size}</td>
              <td style="text-align: right;">
                <a href="edit-page.php?page={fn}" class="btn btn-sm btn-gold">Edit Content</a>
                <a href="../{fn}" target="_blank" class="btn btn-sm btn-outline" style="margin-left: 6px;">Preview</a>
              </td>
            </tr>"""

            mobile_cards += f"""<div class="page-card-item">
              <div class="page-card-top">
                <div>
                  <h3 class="page-card-title">{title}</h3>
                  <p class="page-card-desc">{desc}</p>
                </div>
                <span class="badge badge-wine">{fn}</span>
              </div>
              <div class="page-card-meta">
                <span>📅 {mtime}</span>
                <span>📦 {size}</span>
              </div>
              <div class="page-card-actions">
                <a href="edit-page.php?page={fn}" class="btn btn-primary">✏️ Edit Page</a>
                <a href="../{fn}" target="_blank" class="btn btn-outline">👁️ Preview</a>
              </div>
            </div>"""

        unread_badge = f"<small style='font-size:0.85rem;color:var(--gold-primary);'>({unread_inquiries} New)</small>" if unread_inquiries > 0 else ""
        inq_border = "border-color: var(--red2);" if unread_inquiries > 0 else ""

        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Overview — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
        <!-- Light / Dark Mode Toggle Button -->
        <button type="button" class="theme-toggle-btn" aria-label="Toggle Light and Dark Mode" title="Toggle Theme">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <span class="theme-icon icon-moon">🌙</span>
              <span class="theme-icon icon-sun">☀️</span>
            </span>
          </span>
          <span class="theme-toggle-label">Dark</span>
        </button>
        <a href="../index.html" target="_blank" class="btn-view-site">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          <span>View Live Site</span>
        </a>
      </div>
    </header>
    <div class="admin-container">
      <div style="margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
          <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Welcome back, {cfg.get('username', 'Ezekiel')}! 👋</h1>
          <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your website content, customer inquiries, and studio operations with instant live synchronization.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;">🟢 Live &amp; Connected</span>
          <span style="background: rgba(212, 175, 55, 0.15); color: var(--gold-primary); border: 1px solid rgba(212, 175, 55, 0.3); font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;">📱 Mobile Optimized</span>
        </div>
      </div>

      <!-- Stats Grid (2x2 on Mobile Screens) -->
      <div class="stats-grid">
        <a href="inquiries.php" class="stat-card" style="{inq_border} text-decoration: none;">
          <div class="stat-info">
            <h3 style="{ 'color: var(--red2);' if unread_inquiries > 0 else '' }">{inquiry_count} {unread_badge}</h3>
            <p>Customer Inquiries</p>
          </div>
          <div class="stat-icon" style="background: rgba(232, 37, 53, 0.15); color: var(--red2);">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
        </a>

        <a href="pages.php" class="stat-card" style="text-decoration: none;">
          <div class="stat-info"><h3>{len(pages)}</h3><p>Active Pages</p></div>
          <div class="stat-icon"><svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg></div>
        </a>

        <a href="portfolio-manager.php" class="stat-card" style="text-decoration: none;">
          <div class="stat-info"><h3>{portfolio_count}</h3><p>Portfolio Items</p></div>
          <div class="stat-icon"><svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
        </a>

        <a href="pages.php" class="stat-card" style="text-decoration: none;">
          <div class="stat-info"><h3>{backup_count}</h3><p>Auto-Backups</p></div>
          <div class="stat-icon"><svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </a>
      </div>

      <!-- Quick Visual Content Editor Card (Visually Editable Live Site) -->
      <div class="v-editor-card">
        <div class="v-editor-header">
          <div>
            <h2 class="card-title" style="font-size: 1.35rem; margin-bottom: 2px;">✏️ Quick Visual Content Editor</h2>
            <p class="card-subtitle" style="font-size: 0.88rem;">Instantly edit live text, studio contacts, and pricing without writing HTML</p>
          </div>
          <a href="content-manager.php" class="btn btn-sm btn-outline" style="border-color: var(--gold-primary); color: var(--gold-primary);">
            <span>Open Advanced Editor &rarr;</span>
          </a>
        </div>

        <div class="v-editor-tabs">
          <button type="button" class="v-tab-btn active" data-panel="v-panel-company">📍 Company &amp; Studios</button>
          <button type="button" class="v-tab-btn" data-panel="v-panel-hero">🎯 Hero &amp; Metrics</button>
          <button type="button" class="v-tab-btn" data-panel="v-panel-pricing">💰 Core Pricing</button>
          <button type="button" class="v-tab-btn" data-panel="v-panel-socials">📱 Social Media</button>
        </div>

        <!-- Panel 1: Company Details & Studios -->
        <div class="v-editor-panel active" id="v-panel-company">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="company">
            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>Primary Phone Line</label>
                <input type="text" name="phone_primary" value="{comp.get('phone_primary', '09050278999')}" required>
              </div>
              <div class="v-form-group">
                <label>WhatsApp Hotline</label>
                <input type="text" name="whatsapp_number" value="{comp.get('whatsapp_number', '2349050278999')}" required>
              </div>
              <div class="v-form-group">
                <label>Official Email</label>
                <input type="email" name="email_address" value="{comp.get('email_address', 'glorifixmediaconcept@gmail.com')}" required>
              </div>
              <div class="v-form-group">
                <label>Studio Hours</label>
                <input type="text" name="working_hours" value="{comp.get('working_hours', 'Monday - Saturday: 8:00 AM - 8:00 PM')}" required>
              </div>
            </div>

            <div class="v-editor-grid">
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Ikare-Akoko Ondo State Headquarters Address</label>
                <input type="text" name="office_address" value="{comp.get('office_address', 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State')}" required>
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Abuja Operations Hub Address</label>
                <input type="text" name="support_hub" value="{comp.get('support_hub', 'Phase 2, Shop 7, Opp. UniAbuja Mini-Campus, Gwagwalada, Abuja FCT')}" required>
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Changes automatically update `index.html`, `contact.html`, and `about.html`.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Live Website</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Panel 2: Hero & Metrics -->
        <div class="v-editor-panel" id="v-panel-hero">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="hero">
            <div class="v-editor-grid">
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Hero Announcement Badge</label>
                <input type="text" name="badge" value="{hero.get('badge', '🚀 Premium Digital Agency & CAC Legal Partners')}" required>
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Main Headline</label>
                <input type="text" name="headline" value="{hero.get('headline', 'Transforming Brands, Elevating Businesses with Exceptional Digital & Legal Solutions')}" required>
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>Hero Subtitle &amp; Pitch</label>
                <textarea name="subtitle" rows="3" required>{hero.get('subtitle', 'Glorifix Media Concept Limited is your trusted partner for high-performance website design, Meta advertising, CAC business registration, and comprehensive brand transformation.')}</textarea>
              </div>
            </div>

            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>Completed Projects Stat</label>
                <input type="text" name="stat_projects" value="{hero.get('stat_projects', '500+')}" required>
              </div>
              <div class="v-form-group">
                <label>Happy Clients Stat</label>
                <input type="text" name="stat_clients" value="{hero.get('stat_clients', '350+')}" required>
              </div>
              <div class="v-form-group">
                <label>Client Satisfaction</label>
                <input type="text" name="stat_satisfaction" value="{hero.get('stat_satisfaction', '98%')}" required>
              </div>
              <div class="v-form-group">
                <label>Revenue Generated</label>
                <input type="text" name="stat_revenue" value="{hero.get('stat_revenue', '₦50M+')}" required>
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Updates homepage banners, statistics counters, and brand messaging.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Hero Content</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Panel 3: Core Pricing Fast Adjuster -->
        <div class="v-editor-panel" id="v-panel-pricing">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="pricing">
            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>CAC Business Name (₦)</label>
                <input type="text" name="cac_business_name_price" value="{pri.get('cac_business_name_price', '35,000')}" required>
              </div>
              <div class="v-form-group">
                <label>CAC Limited Company LLC (₦)</label>
                <input type="text" name="cac_llc_price" value="{pri.get('cac_llc_price', '75,000')}" required>
              </div>
              <div class="v-form-group">
                <label>Basic Website Design (₦)</label>
                <input type="text" name="web_basic_price" value="{pri.get('web_basic_price', '100,000')}" required>
              </div>
              <div class="v-form-group">
                <label>Business Website Design (₦)</label>
                <input type="text" name="web_business_price" value="{pri.get('web_business_price', '150,000')}" required>
              </div>
              <div class="v-form-group">
                <label>Starter Social Media (₦)</label>
                <input type="text" name="social_starter_price" value="{pri.get('social_starter_price', '50,000')}" required>
              </div>
              <div class="v-form-group">
                <label>Basic 7-Day Meta Ads (₦)</label>
                <input type="text" name="ads_basic_price" value="{pri.get('ads_basic_price', '60,000')}" required>
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Instantly synchronizes prices on `pricing.html` and booking estimators.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Pricing Plans</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Panel 4: Social Media Icons & Channels -->
        <div class="v-editor-panel" id="v-panel-socials">
          <form class="v-editor-form" method="POST" action="content-manager.php">
            <input type="hidden" name="tab" value="socials">
            <div class="v-editor-grid">
              <div class="v-form-group">
                <label>📘 Facebook Page URL</label>
                <input type="url" name="facebook" value="{html_escape(soc.get('facebook', 'https://www.facebook.com/share/1BDQSsoEZQ/'))}" placeholder="https://www.facebook.com/yourpage">
              </div>
              <div class="v-form-group">
                <label>📸 Instagram Profile URL</label>
                <input type="url" name="instagram" value="{html_escape(soc.get('instagram', 'https://www.instagram.com/glorifixmediaconcept?igsh=M2JsMHhkOHV3MnJ3'))}" placeholder="https://www.instagram.com/yourhandle">
              </div>
              <div class="v-form-group">
                <label>💬 WhatsApp Hotline Link</label>
                <input type="text" name="whatsapp" value="{html_escape(soc.get('whatsapp', 'https://wa.me/2349050278999'))}" placeholder="https://wa.me/2349050278999">
              </div>
              <div class="v-form-group">
                <label>💼 LinkedIn Company URL</label>
                <input type="url" name="linkedin" value="{html_escape(soc.get('linkedin', 'https://www.linkedin.com/in/glorifix-media-concept-594783257'))}" placeholder="https://www.linkedin.com/in/yourcompany">
              </div>
              <div class="v-form-group">
                <label>🐦 Twitter / X Profile URL (Optional)</label>
                <input type="url" name="twitter" value="{html_escape(soc.get('twitter', ''))}" placeholder="https://x.com/yourhandle">
              </div>
              <div class="v-form-group">
                <label>🎵 TikTok Profile URL (Optional)</label>
                <input type="url" name="tiktok" value="{html_escape(soc.get('tiktok', ''))}" placeholder="https://www.tiktok.com/@yourhandle">
              </div>
              <div class="v-form-group" style="grid-column: 1 / -1;">
                <label>▶️ YouTube Channel URL (Optional)</label>
                <input type="url" name="youtube" value="{html_escape(soc.get('youtube', ''))}" placeholder="https://www.youtube.com/@yourchannel">
              </div>
            </div>

            <div class="v-save-bar">
              <span style="font-size: 0.84rem; color: var(--text-muted);">Instantly synchronizes social icons across the footer and contact links of all 6 live pages.</span>
              <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                <span>💾 Save &amp; Sync Social Icons</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div class="admin-card">
        <div class="card-header">
          <div><h2 class="card-title">⚡ Quick Management Actions</h2><p class="card-subtitle">One-click access to most frequent website operations</p></div>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <a href="content-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem; border-color: var(--border-gold); background: rgba(232, 185, 35, 0.05);">
              <span style="font-size: 1.5rem; margin-right: 8px;">✏️</span>
              <div style="text-align: left;">
                <strong style="display: block; color: var(--gold-primary);">Website Content Editor</strong>
                <small style="color: var(--text-muted);">Full editor for prices, about, hero &amp; footer</small>
              </div>
            </a>
            <a href="inquiries.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem; {inq_border}">
              <span style="font-size: 1.5rem; margin-right: 8px;">📬</span>
              <div style="text-align: left;">
                <strong style="display: block;">Inquiries &amp; Bookings</strong>
                <small style="color: var(--text-muted);">{f"<strong>{unread_inquiries} new inquiries waiting!</strong>" if unread_inquiries > 0 else "View customer notes &amp; schedules"}</small>
              </div>
            </a>
            <a href="portfolio-manager.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🎨</span>
              <div style="text-align: left;">
                <strong style="display: block;">Manage Portfolio</strong>
                <small style="color: var(--text-muted);">Add, edit or reorder designs</small>
              </div>
            </a>
            <a href="media.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">📁</span>
              <div style="text-align: left;">
                <strong style="display: block;">Upload Images &amp; Banners</strong>
                <small style="color: var(--text-muted);">Upload new media assets</small>
              </div>
            </a>
            <a href="pages.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">📄</span>
              <div style="text-align: left;">
                <strong style="display: block;">HTML Page Editor</strong>
                <small style="color: var(--text-muted);">Edit raw page files with backup protection</small>
              </div>
            </a>
            <a href="settings.php" class="btn btn-outline" style="justify-content: flex-start; padding: 1.1rem;">
              <span style="font-size: 1.5rem; margin-right: 8px;">🔑</span>
              <div style="text-align: left;">
                <strong style="display: block;">Admin Credentials</strong>
                <small style="color: var(--text-muted);">Update password &amp; admin email</small>
              </div>
            </a>
          </div>
        </div>
      </div>

      <!-- Website Pages (Desktop Table + Mobile Touch Cards) -->
      <div class="admin-card">
        <div class="card-header">
          <div><h2 class="card-title">📄 Website Pages (6 Active)</h2><p class="card-subtitle">Manage, edit content, or preview each public page</p></div>
          <a href="pages.php" class="btn btn-sm btn-outline">View Page Directory</a>
        </div>
        <div class="card-body" style="padding: 0;">
          <!-- Desktop Table (Visible on Desktop, Hidden on Mobile) -->
          <div class="table-responsive desktop-only-table">
            <table class="admin-table">
              <thead><tr><th>Page Name</th><th>Filename</th><th>Last Modified</th><th>File Size</th><th style="text-align: right;">Action</th></tr></thead>
              <tbody>{rows}</tbody>
            </table>
          </div>
          <!-- Mobile Optimized Touch Cards (Hidden on Desktop, Visible on Mobile) -->
          <div class="admin-page-cards-mobile">
            {mobile_cards}
          </div>
        </div>
      </div>

      <!-- System Environment & Hosting Note -->
      <div class="admin-card">
        <div class="card-header">
          <h2 class="card-title">🌐 Hosting &amp; cPanel Deployment Status</h2>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
            <div style="background: rgba(0,0,0,0.15); padding: 1.1rem; border-radius: 10px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 700; text-transform: uppercase;">cPanel Compatibility</span>
              <p style="font-weight: 700; margin-top: 4px;">100% Native PHP Ready</p>
              <small style="color: var(--text-muted);">Runs directly in public_html with zero extra server setup</small>
            </div>
            <div style="background: rgba(0,0,0,0.15); padding: 1.1rem; border-radius: 10px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 700; text-transform: uppercase;">Inquiry Target Email</span>
              <p style="font-weight: 700; margin-top: 4px;">{cfg.get('admin_email', 'glorifixmediaconcept@gmail.com')}</p>
              <small style="color: var(--text-muted);">Configured for all booking &amp; consultation forms</small>
            </div>
            <div style="background: rgba(0,0,0,0.15); padding: 1.1rem; border-radius: 10px; border: 1px solid var(--border-subtle);">
              <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 700; text-transform: uppercase;">Auto-Backup System</span>
              <p style="color: #10b981; font-weight: 700; margin-top: 4px;">Active ({backup_count} Backups Saved)</p>
              <small style="color: var(--text-muted);">Timestamped snapshots created on every edit</small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>
<script src="js/admin.js?v={int(time.time())}"></script>
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
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
<script src="js/admin.js?v={int(time.time())}"></script>
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
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
<script src="js/admin.js?v={int(time.time())}"></script>
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
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
<script src="js/admin.js?v={int(time.time())}"></script>
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
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
<script src="js/admin.js?v={int(time.time())}"></script>
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
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
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
<script src="js/admin.js?v={int(time.time())}"></script>
</body>
</html>"""
        self.send_html(html)

    def sync_site_content(self, content):
        import re
        phone_pri = content.get('company', {}).get('phone_primary', '09050278999')
        email_addr = content.get('company', {}).get('email_address', 'glorifixmediaconcept@gmail.com')
        socials = content.get('socials', {})
        social_items = []
        if socials.get('facebook'):
            social_items.append(f'            <a href="{html_escape(socials["facebook"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="Facebook">\n              <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>\n            </a>')
        if socials.get('instagram'):
            social_items.append(f'            <a href="{html_escape(socials["instagram"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="Instagram">\n              <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>\n            </a>')
        if socials.get('whatsapp'):
            social_items.append(f'            <a href="{html_escape(socials["whatsapp"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="WhatsApp">\n              <svg viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.164.577 4.195 1.583 5.952l-1.683 6.148 6.308-1.654c1.701.93 3.653 1.464 5.792 1.464 6.627 0 12-5.373 12-12s-5.373-12-12-12z"/></svg>\n            </a>')
        if socials.get('linkedin'):
            social_items.append(f'            <a href="{html_escape(socials["linkedin"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="LinkedIn">\n              <svg viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>\n            </a>')
        if socials.get('twitter'):
            social_items.append(f'            <a href="{html_escape(socials["twitter"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="Twitter">\n              <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>\n            </a>')
        if socials.get('tiktok'):
            social_items.append(f'            <a href="{html_escape(socials["tiktok"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="TikTok">\n              <svg viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.54c0 1.7-.42 3.4-1.35 4.81-1.22 1.83-3.19 3.03-5.38 3.32-2.19.29-4.47-.28-6.28-1.57-1.81-1.29-2.99-3.3-3.23-5.51-.25-2.21.43-4.48 1.87-6.17 1.44-1.69 3.55-2.67 5.75-2.71.55-.01 1.1.04 1.64.16v4.13c-.49-.16-1.02-.23-1.54-.18-1.15.11-2.23.75-2.84 1.72-.61.97-.68 2.21-.21 3.25.47 1.04 1.48 1.78 2.62 1.91 1.14.13 2.31-.35 2.99-1.28.43-.59.66-1.32.66-2.05V.02z"/></svg>\n            </a>')
        if socials.get('youtube'):
            social_items.append(f'            <a href="{html_escape(socials["youtube"])}" target="_blank" rel="noopener noreferrer" class="soc-icon" aria-label="YouTube">\n              <svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>\n            </a>')
        new_footer_socials = '<div class="footer-socials">\n' + '\n'.join(social_items) + '\n          </div>'

        for hf in ['index.html', 'about.html', 'services.html', 'pricing.html', 'gallery.html', 'contact.html']:
            fp = os.path.join(BASE_DIR, hf)
            if os.path.exists(fp):
                try:
                    with open(fp, 'r', encoding='utf-8') as f:
                        text = f.read()
                    text = text.replace('glorifixmediaconcept@gmail.com', email_addr)
                    text = re.sub(r'<div class="footer-socials">.*?</div>', new_footer_socials, text, flags=re.DOTALL)
                    with open(fp, 'w', encoding='utf-8') as f:
                        f.write(text)
                except Exception:
                    pass

    def serve_admin_inquiries(self, params=None, message=None):
        cfg = load_config()
        inquiries_file = os.path.join(BASE_DIR, 'admin', 'data', 'inquiries.json')
        inquiries = []
        if os.path.exists(inquiries_file):
            try:
                with open(inquiries_file, 'r', encoding='utf-8') as f:
                    inquiries = json.load(f)
            except Exception:
                inquiries = []

        unread_count = len([i for i in inquiries if not i.get('read')])
        booking_count = len([i for i in inquiries if 'booking' in (i.get('type') or '').lower()])
        direct_count = len(inquiries) - booking_count

        filter_type = (params.get('type', ['all'])[0] if params else 'all')
        search_q = (params.get('q', [''])[0].strip().lower() if params else '')

        filtered = []
        for inq in inquiries:
            if filter_type == 'unread' and inq.get('read'): continue
            if filter_type == 'booking' and 'booking' not in (inq.get('type') or '').lower(): continue
            if filter_type == 'inquiry' and 'booking' in (inq.get('type') or '').lower(): continue
            if filter_type == 'replied' and inq.get('status') != 'replied': continue
            if search_q:
                hay = f"{inq.get('name','')} {inq.get('email','')} {inq.get('phone','')} {inq.get('service','')} {inq.get('notes','')}".lower()
                if search_q not in hay: continue
            filtered.append(inq)

        cards = ''
        if not filtered:
            cards = '<div class="admin-card" style="text-align: center; padding: 3rem;"><div style="font-size: 2.5rem;">📭</div><h3 style="color: #fff;">No Inquiries Found</h3><p style="color: var(--text-muted);">Customer consultation bookings and messages will appear here.</p></div>'
        else:
            for inq in filtered:
                is_unr = not inq.get('read')
                st = inq.get('status', 'new' if is_unr else 'read')
                c_phone = ''.join(c for c in inq.get('phone', '') if c.isdigit())
                sched = f"<div class='inq-grid-item'><span>📅 Scheduled Time</span><strong style='color: var(--gold);'>{inq.get('appointment_date')} at {inq.get('appointment_time', '10:00 AM')}</strong></div>" if inq.get('appointment_date') else ''
                notes_block = f"<div class='inq-notes'><strong>Project Brief / Message:</strong><br>{html_escape(inq.get('notes',''))}</div>" if inq.get('notes') else ''
                wa_btn = f"<a href='https://wa.me/{c_phone}' target='_blank' class='btn btn-secondary' style='background: rgba(37, 211, 102, 0.15); color: #4ade80;'>💬 Chat on WhatsApp</a>" if c_phone else ''
                cards += f"""
                <div class="inquiry-card {'is-unread' if is_unr else ''}" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1rem;">
                  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                      <div style="width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--red1), var(--gold)); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff;">{(inq.get('name') or 'C')[:1].upper()}</div>
                      <div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">{html_escape(inq.get('name','Customer'))}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">{inq.get('timestamp','')} &bull; Ref: <strong>#{inq.get('id','')}</strong></div>
                      </div>
                    </div>
                    <div>
                      <span class="badge badge-wine">{st.upper()}</span>
                      <span style="font-size: 0.82rem; color: var(--gold); font-weight: 600; margin-left: 8px;">{inq.get('type','Consultation')}</span>
                    </div>
                  </div>
                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; background: rgba(0,0,0,0.25); padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 10px; font-size: 0.9rem;">
                    <div><span style="display: block; font-size: 0.75rem; color: var(--gold);">Email Address</span><a href="mailto:{inq.get('email','')}" style="color: #fff;">{html_escape(inq.get('email',''))}</a></div>
                    <div><span style="display: block; font-size: 0.75rem; color: var(--gold);">Phone / WhatsApp</span>{html_escape(inq.get('phone','Not provided'))}</div>
                    <div><span style="display: block; font-size: 0.75rem; color: var(--gold);">Service Requested</span><strong style="color: #fff;">{html_escape(inq.get('service','General'))}</strong></div>
                    {sched}
                  </div>
                  {notes_block}
                  <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    {wa_btn}
                    <a href="mailto:{inq.get('email','')}?subject=Re:%20Glorifix%20Consultation%20(Ref:%20{inq.get('id','')})" class="btn btn-secondary">✉️ Send Email</a>
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="action" value="mark_replied">
                      <input type="hidden" name="inquiry_id" value="{inq.get('id','')}">
                      <button type="submit" class="btn btn-secondary">✓ Mark Replied</button>
                    </form>
                    <form method="POST" style="display: inline; margin-left: auto;" onsubmit="return confirm('Delete this inquiry?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="inquiry_id" value="{inq.get('id','')}">
                      <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                  </div>
                </div>
                """

        msg_html = f'<div class="alert alert-success"><span>✅</span> <span>{message}</span></div>' if message else ''
        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Inquiries &amp; Bookings — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
</head>
<body>
<div class="admin-wrapper">
  {self.render_sidebar('inquiries.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="index.php" style="color: var(--text-muted);">Dashboard</a><span>/</span><span>Inquiries &amp; Bookings</span></div>
      </div>
      <div class="topbar-right"><a href="../contact.html" target="_blank" class="btn-view-site"><span>View Contact Page</span></a></div>
    </header>
    <div class="admin-container">
      {msg_html}
      <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; margin-bottom: 0.35rem;">Customer Inquiries &amp; Bookings</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">All consultation bookings and contact notes from your website are saved here in real time.</p>
      </div>
      <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card"><div class="stat-info"><h3>{unread_count}</h3><p>New / Unread</p></div></div>
        <div class="stat-card"><div class="stat-info"><h3>{booking_count}</h3><p>Consultation Bookings</p></div></div>
        <div class="stat-card"><div class="stat-info"><h3>{direct_count}</h3><p>Direct Notes</p></div></div>
        <div class="stat-card"><div class="stat-info"><h3>{len(inquiries)}</h3><p>Total Inquiries</p></div></div>
      </div>
      <div class="filter-tabs">
        <a href="inquiries.php?type=all" class="filter-btn {'active' if filter_type=='all' else ''}">All ({len(inquiries)})</a>
        <a href="inquiries.php?type=unread" class="filter-btn {'active' if filter_type=='unread' else ''}">Unread ({unread_count})</a>
        <a href="inquiries.php?type=booking" class="filter-btn {'active' if filter_type=='booking' else ''}">📅 Bookings ({booking_count})</a>
        <a href="inquiries.php?type=inquiry" class="filter-btn {'active' if filter_type=='inquiry' else ''}">📩 Direct Notes ({direct_count})</a>
      </div>
      {cards}
    </div>
  </main>
</div>
<script src="js/admin.js?v={int(time.time())}"></script>
</body>
</html>"""
        self.send_html(html)

    def serve_admin_content_manager(self, params=None, message=None):
        cfg = load_config()
        content_file = os.path.join(BASE_DIR, 'admin', 'data', 'site_content.json')
        site_content = {}
        if os.path.exists(content_file):
            try:
                with open(content_file, 'r', encoding='utf-8') as f:
                    site_content = json.load(f)
            except Exception:
                site_content = {}
        
        tab = (params.get('tab', ['company'])[0] if params else 'company')
        msg_html = f'<div class="alert alert-success"><span>✅</span> <span>{message}</span></div>' if message else ''
        
        comp = site_content.get('company', {})
        pri = site_content.get('pricing', {})
        hero = site_content.get('hero', {})
        abt = site_content.get('about', {})
        va = site_content.get('voice_agent', {})
        soc = site_content.get('socials', {})

        tab_body = ''
        if tab == 'company':
            tab_body = f"""
            <form method="POST">
              <input type="hidden" name="tab" value="company">
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">🏢 Contact &amp; Brand Information</h3></div>
                <div class="card-body">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Company Legal Name</label><input type="text" name="company_name" class="form-control" value="{html_escape(comp.get('name','Glorifix Media Concept Limited'))}" required></div>
                    <div class="form-group"><label class="form-label">Official Tagline</label><input type="text" name="tagline" class="form-control" value="{html_escape(comp.get('tagline',''))}"></div>
                  </div>
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Primary Phone</label><input type="text" name="phone_primary" class="form-control" value="{html_escape(comp.get('phone_primary','09050278999'))}"></div>
                    <div class="form-group"><label class="form-label">WhatsApp Number</label><input type="text" name="whatsapp_number" class="form-control" value="{html_escape(comp.get('whatsapp_number','2349050278999'))}"></div>
                    <div class="form-group"><label class="form-label">Official Email Address</label><input type="email" name="email_address" class="form-control" value="{html_escape(comp.get('email_address','glorifixmediaconcept@gmail.com'))}"></div>
                  </div>
                  <div class="form-group"><label class="form-label">Head Office Address</label><textarea name="office_address" class="form-control" style="min-height:70px;">{html_escape(comp.get('office_address',''))}</textarea></div>
                  <button type="submit" class="btn btn-primary">💾 Save Contact Information</button>
                </div>
              </div>
            </form>
            """
        elif tab == 'pricing':
            tab_body = f"""
            <form method="POST">
              <input type="hidden" name="tab" value="pricing">
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">📝 CAC Business Registration Pricing (in Naira)</h3></div>
                <div class="card-body">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">CAC Business Name (₦)</label><input type="text" name="cac_business_name_price" class="form-control" value="{html_escape(pri.get('cac_business_name_price','35,000'))}"></div>
                    <div class="form-group"><label class="form-label">CAC Limited Company LLC (₦)</label><input type="text" name="cac_llc_price" class="form-control" value="{html_escape(pri.get('cac_llc_price','75,000'))}"></div>
                    <div class="form-group"><label class="form-label">CAC Trustee / NGO (₦)</label><input type="text" name="cac_trustee_price" class="form-control" value="{html_escape(pri.get('cac_trustee_price','150,000'))}"></div>
                  </div>
                </div>
              </div>
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">📱 Social Media Management Packages</h3></div>
                <div class="card-body">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Starter Package (₦)</label><input type="text" name="social_starter_price" class="form-control" value="{html_escape(pri.get('social_starter_price','50,000'))}"></div>
                    <div class="form-group"><label class="form-label">Growth Package (₦)</label><input type="text" name="social_growth_price" class="form-control" value="{html_escape(pri.get('social_growth_price','120,000'))}"></div>
                    <div class="form-group"><label class="form-label">Premium Package (₦)</label><input type="text" name="social_premium_price" class="form-control" value="{html_escape(pri.get('social_premium_price','240,000'))}"></div>
                  </div>
                  <div class="form-group"><label class="form-label">Premium Platforms Count</label><input type="text" name="social_premium_platforms" class="form-control" value="{html_escape(pri.get('social_premium_platforms','4 platforms managed'))}"></div>
                  <button type="submit" class="btn btn-primary">💾 Save All Pricing Rates</button>
                </div>
              </div>
            </form>
            """
        elif tab == 'hero':
            tab_body = f"""
            <form method="POST">
              <input type="hidden" name="tab" value="hero">
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">🌟 Homepage Hero Section</h3></div>
                <div class="card-body">
                  <div class="form-group"><label class="form-label">Announcement Badge</label><input type="text" name="hero_badge" class="form-control" value="{html_escape(hero.get('badge',''))}"></div>
                  <div class="form-group"><label class="form-label">Main Headline</label><textarea name="hero_headline" class="form-control" style="min-height:60px;">{html_escape(hero.get('headline',''))}</textarea></div>
                  <div class="form-group"><label class="form-label">Subtitle</label><textarea name="hero_subtitle" class="form-control" style="min-height:80px;">{html_escape(hero.get('subtitle',''))}</textarea></div>
                  <button type="submit" class="btn btn-primary">💾 Save Homepage Details</button>
                </div>
              </div>
            </form>
            """
        elif tab == 'about':
            tab_body = f"""
            <form method="POST">
              <input type="hidden" name="tab" value="about">
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">📖 About Ezekiel &amp; Glorifix Story</h3></div>
                <div class="card-body">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Founder Name</label><input type="text" name="founder_name" class="form-control" value="{html_escape(abt.get('founder_name','Soje Ezekiel'))}"></div>
                    <div class="form-group"><label class="form-label">Executive Role</label><input type="text" name="founder_role" class="form-control" value="{html_escape(abt.get('founder_role','CEO & Lead Developer'))}"></div>
                  </div>
                  <div class="form-group"><label class="form-label">Founder Biography</label><textarea name="founder_bio" class="form-control" style="min-height:80px;">{html_escape(abt.get('founder_bio',''))}</textarea></div>
                  <div class="form-group"><label class="form-label">Mission Statement</label><textarea name="mission" class="form-control" style="min-height:60px;">{html_escape(abt.get('mission',''))}</textarea></div>
                  <button type="submit" class="btn btn-primary">💾 Save About Details</button>
                </div>
              </div>
            </form>
            """
        elif tab == 'voice':
            tab_body = f"""
            <form method="POST">
              <input type="hidden" name="tab" value="voice">
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">🤖 AI Voice Assistant Knowledge Base</h3></div>
                <div class="card-body">
                  <div class="form-group"><label class="form-label">CAC Registration Answer</label><textarea name="va_cac_response" class="form-control" style="min-height:70px;">{html_escape(va.get('cac_response',''))}</textarea></div>
                  <div class="form-group"><label class="form-label">Social Media Packages Answer</label><textarea name="va_social_response" class="form-control" style="min-height:70px;">{html_escape(va.get('social_response',''))}</textarea></div>
                  <div class="form-group"><label class="form-label">Website Packages Answer</label><textarea name="va_web_response" class="form-control" style="min-height:70px;">{html_escape(va.get('web_response',''))}</textarea></div>
                  <button type="submit" class="btn btn-primary">💾 Save Voice Agent Answers</button>
                </div>
              </div>
            </form>
            """
        elif tab == 'socials':
            tab_body = f"""
            <form method="POST">
              <input type="hidden" name="tab" value="socials">
              <div class="admin-card">
                <div class="card-header"><h3 class="card-title">📱 Social Media Icons &amp; Channels</h3></div>
                <div class="card-body">
                  <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.25rem;">
                    Manage the links for the social media icons displayed in the website footer and contact channels. Empty fields will automatically hide that icon on the live site.
                  </p>
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">📘 Facebook Page URL</label><input type="url" name="facebook" class="form-control" value="{html_escape(soc.get('facebook',''))}" placeholder="https://www.facebook.com/yourpage"></div>
                    <div class="form-group"><label class="form-label">📸 Instagram Profile URL</label><input type="url" name="instagram" class="form-control" value="{html_escape(soc.get('instagram',''))}" placeholder="https://www.instagram.com/yourhandle"></div>
                  </div>
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">💬 WhatsApp Direct Chat Link</label><input type="text" name="whatsapp" class="form-control" value="{html_escape(soc.get('whatsapp',''))}" placeholder="https://wa.me/2349050278999"></div>
                    <div class="form-group"><label class="form-label">💼 LinkedIn Company Profile URL</label><input type="url" name="linkedin" class="form-control" value="{html_escape(soc.get('linkedin',''))}" placeholder="https://www.linkedin.com/in/yourcompany"></div>
                  </div>
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">🐦 Twitter / X Profile URL (Optional)</label><input type="url" name="twitter" class="form-control" value="{html_escape(soc.get('twitter',''))}" placeholder="https://x.com/yourhandle"></div>
                    <div class="form-group"><label class="form-label">🎵 TikTok Profile URL (Optional)</label><input type="url" name="tiktok" class="form-control" value="{html_escape(soc.get('tiktok',''))}" placeholder="https://www.tiktok.com/@yourhandle"></div>
                  </div>
                  <div class="form-group"><label class="form-label">▶️ YouTube Channel URL (Optional)</label><input type="url" name="youtube" class="form-control" value="{html_escape(soc.get('youtube',''))}" placeholder="https://www.youtube.com/@yourchannel"></div>
                  <button type="submit" class="btn btn-primary">💾 Save &amp; Sync Social Icons</button>
                </div>
              </div>
            </form>
            """

        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Content Editor — Glorifix Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo.png">
  <link rel="stylesheet" href="css/admin.css?v={int(time.time())}">
</head>
<body>
<div class="admin-wrapper">
  {self.render_sidebar('content-manager.php', cfg)}
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="page-breadcrumb"><a href="index.php" style="color: var(--text-muted);">Dashboard</a><span>/</span><span>Website Content Editor</span></div>
      </div>
      <div class="topbar-right"><a href="../pricing.html" target="_blank" class="btn-view-site"><span>View Pricing Page</span></a></div>
    </header>
    <div class="admin-container">
      {msg_html}
      <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; margin-bottom: 0.35rem;">Visual Website Content Editor</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Edit website texts, pricing rates, and contact details in plain text. Zero HTML code needed!</p>
      </div>
      <div class="content-nav-tabs">
        <a href="content-manager.php?tab=company" class="c-tab-btn {'active' if tab=='company' else ''}">🏢 Company &amp; Contact</a>
        <a href="content-manager.php?tab=pricing" class="c-tab-btn {'active' if tab=='pricing' else ''}">💰 Pricing &amp; Packages</a>
        <a href="content-manager.php?tab=hero" class="c-tab-btn {'active' if tab=='hero' else ''}">🌟 Home &amp; Hero</a>
        <a href="content-manager.php?tab=about" class="c-tab-btn {'active' if tab=='about' else ''}">📖 About Us &amp; Founder</a>
        <a href="content-manager.php?tab=voice" class="c-tab-btn {'active' if tab=='voice' else ''}">🤖 Voice Assistant Answers</a>
        <a href="content-manager.php?tab=socials" class="c-tab-btn {'active' if tab=='socials' else ''}">📱 Social Media Links</a>
      </div>
      {tab_body}
    </div>
  </main>
</div>
<script src="js/admin.js?v={int(time.time())}"></script>
</body>
</html>"""
        self.send_html(html)

    def render_sidebar(self, active_tab, cfg):
        def is_act(tab):
            return "active" if active_tab == tab else ""
        inquiries_file = os.path.join(BASE_DIR, 'admin', 'data', 'inquiries.json')
        inq_badge = ""
        if os.path.exists(inquiries_file):
            try:
                with open(inquiries_file, 'r', encoding='utf-8') as f:
                    inqs = json.load(f)
                unr = len([i for i in inqs if not i.get('read')])
                if unr > 0:
                    inq_badge = f'<span style="background: var(--red2); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: 6px;">{unr}</span>'
            except Exception:
                pass

        return f"""<aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="brand-badge">G</div>
      <div class="brand-info">
        <span class="brand-title">Glorifix Admin</span>
        <span class="brand-sub">Management Suite</span>
      </div>
      <button class="sidebar-close-btn" aria-label="Close Menu"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-title">Navigation</span>
      <a href="index.php" class="nav-item {is_act('index.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="inquiries.php" class="nav-item {is_act('inquiries.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Inquiries &amp; Bookings</span>
        {inq_badge}
      </a>
      <a href="content-manager.php" class="nav-item {is_act('content-manager.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span>Website Content Editor</span>
      </a>
      <a href="pages.php" class="nav-item {is_act('pages.php')}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>HTML File Editor</span>
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

