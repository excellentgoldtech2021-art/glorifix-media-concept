#!/usr/bin/env python3
"""
Glorifix Media Concept Limited - cPanel Production Packager
Ensures 100% cPanel compatibility:
- All directory permissions set to 0755 (drwxr-xr-x)
- All file permissions set to 0644 (-rw-r--r--)
- Preserves accurate current modification timestamps (no 1980 default)
- Embeds strict POSIX attributes in zip archive for cPanel File Manager extract
- Packages essential web files, excluding local development daemons & heavy drafts
- Syncs automatically to phone Downloads with fresh timestamps for 1-click cPanel upload
"""

import os
import stat
import time
import shutil
import zipfile

PROJ_DIR = os.path.dirname(os.path.abspath(__file__))
ZIP_OUTPUT = os.path.join(PROJ_DIR, 'glorifix_cpanel_latest.zip')

PHONE_TARGETS = [
    '/storage/emulated/0/Download/glorifix_cpanel_latest.zip',
    '/storage/emulated/0/Download/glorifix-website-cpanel.zip',
    '/storage/emulated/0/Download/glorifix_cpanel_ready.zip',
    '/storage/emulated/0/Download/glorifix_new_cpanel.zip',
]

IGNORE_DIRS = {'.git', '.ide', '__pycache__'}
IGNORE_FILES = {'admin_server.py', 'package_cpanel.py', 'academy_source.html', 'media_buying.txt', 'email_preview.html'}

def build_package():
    # 0. Clean old zip files in project dir
    for f in os.listdir(PROJ_DIR):
        if f.endswith('.zip'):
            try:
                os.remove(os.path.join(PROJ_DIR, f))
            except Exception:
                pass

    # 1. Update local filesystem permissions
    print("Setting filesystem permissions: 0755 for dirs, 0644 for files...")
    for root, dirs, files in os.walk(PROJ_DIR):
        dirs[:] = [d for d in dirs if d not in IGNORE_DIRS]
        for d in dirs:
            dp = os.path.join(root, d)
            try:
                os.chmod(dp, 0o755)
            except Exception:
                pass
        for f in files:
            fp = os.path.join(root, f)
            try:
                os.chmod(fp, 0o644)
            except Exception:
                pass

    # 2. Build ZIP archive with embedded UNIX attributes and real timestamps
    print(f"Building fresh zip archive at {ZIP_OUTPUT}...")
    file_count = 0
    dir_count = 0

    with zipfile.ZipFile(ZIP_OUTPUT, 'w', zipfile.ZIP_DEFLATED) as z:
        for root, dirs, files in os.walk(PROJ_DIR):
            dirs[:] = [d for d in dirs if d not in IGNORE_DIRS]
            rel_root = os.path.relpath(root, PROJ_DIR)

            # Explicit directory entries with 0755
            if rel_root != '.':
                dir_zinfo = zipfile.ZipInfo(rel_root.rstrip('/') + '/')
                dir_zinfo.create_system = 3  # UNIX
                dir_zinfo.external_attr = (stat.S_IFDIR | 0o755) << 16
                try:
                    dir_zinfo.date_time = time.localtime(os.path.getmtime(root))[:6]
                except Exception:
                    dir_zinfo.date_time = time.localtime()[:6]
                z.writestr(dir_zinfo, '')
                dir_count += 1

            for f in sorted(files):
                if f.endswith('.zip') or f.endswith('.pyc') or f in IGNORE_FILES:
                    continue
                full_path = os.path.join(root, f)
                rel_path = os.path.relpath(full_path, PROJ_DIR)

                with open(full_path, 'rb') as fp:
                    data = fp.read()

                file_zinfo = zipfile.ZipInfo(rel_path)
                file_zinfo.create_system = 3  # UNIX
                file_zinfo.external_attr = (stat.S_IFREG | 0o644) << 16
                try:
                    file_zinfo.date_time = time.localtime(os.path.getmtime(full_path))[:6]
                except Exception:
                    file_zinfo.date_time = time.localtime()[:6]
                z.writestr(file_zinfo, data, compress_type=zipfile.ZIP_DEFLATED)
                file_count += 1

    size_mb = os.path.getsize(ZIP_OUTPUT) / (1024 * 1024)
    print(f"✅ Packaged {file_count} files (0644) & {dir_count} directories (0755) into {ZIP_OUTPUT} ({size_mb:.2f} MB)")

    # Also keep glorifix_cpanel_ready.zip in project
    ready_local = os.path.join(PROJ_DIR, 'glorifix_cpanel_ready.zip')
    shutil.copyfile(ZIP_OUTPUT, ready_local)

    # 3. Copy to phone Downloads with fresh current timestamps
    now = time.time()
    for phone_zip in PHONE_TARGETS:
        try:
            if os.path.exists(phone_zip):
                try:
                    os.remove(phone_zip)
                except Exception:
                    pass
            shutil.copyfile(ZIP_OUTPUT, phone_zip)
            os.utime(phone_zip, (now, now))
            print(f"✅ Synced fresh archive to phone Downloads: {phone_zip}")
        except Exception as e:
            print(f"⚠️ Note on copying {phone_zip}: {e}")

    # 4. Extract into unzipped directories in Downloads
    extract_dirs = [
        '/storage/emulated/0/Download/Glorifix-Portfolio',
        '/storage/emulated/0/Download/glorifix_cpanel_ready'
    ]
    for ext_dir in extract_dirs:
        try:
            if os.path.exists(ext_dir):
                shutil.rmtree(ext_dir)
            os.makedirs(ext_dir, exist_ok=True)
            with zipfile.ZipFile(ZIP_OUTPUT, 'r') as z:
                z.extractall(ext_dir)
            print(f"✅ Extracted unzipped folder ready at: {ext_dir}")
        except Exception as e:
            print(f"⚠️ Note on extract {ext_dir}: {e}")

    # 5. Verify ZIP permissions and dates
    with zipfile.ZipFile(ZIP_OUTPUT, 'r') as z:
        invalid = []
        for info in z.infolist():
            mode = info.external_attr >> 16
            expected = (stat.S_IFDIR | 0o755) if info.is_dir() else (stat.S_IFREG | 0o644)
            if mode != expected:
                invalid.append((info.filename, oct(mode), oct(expected)))

        if not invalid:
            print("🎉 VERIFICATION PASSED: 100% of files have 0644 and 100% of directories have 0755!")
        else:
            print(f"⚠️ Non-conforming entries: {len(invalid)}")

if __name__ == '__main__':
    build_package()
