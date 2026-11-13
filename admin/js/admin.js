/**
 * Glorifix Media Concept Limited - Admin JavaScript Utilities
 * Theme Switcher (Light/Dark Mode), Mobile Drawer & Visual Quick Content Editor
 */

// Immediate Theme Initialization (runs before DOM to prevent flash)
(function() {
  const savedTheme = localStorage.getItem('glorifix_admin_theme') || 'dark';
  if (savedTheme === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
    if (document.body) document.body.classList.add('light-mode');
  } else {
    document.documentElement.setAttribute('data-theme', 'dark');
    if (document.body) document.body.classList.remove('light-mode');
  }
})();

document.addEventListener('DOMContentLoaded', function() {
  // Sync body theme class with saved theme
  const savedTheme = localStorage.getItem('glorifix_admin_theme') || 'dark';
  if (savedTheme === 'light') {
    document.body.classList.add('light-mode');
  } else {
    document.body.classList.remove('light-mode');
  }

  // 1. Light / Dark Theme Switcher Logic
  const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
  
  function updateThemeUI(isLight) {
    themeToggleBtns.forEach(btn => {
      const label = btn.querySelector('.theme-toggle-label');
      if (label) {
        label.textContent = isLight ? 'Light' : 'Dark';
      }
      btn.setAttribute('title', isLight ? 'Switch to Dark Mode' : 'Switch to Light Mode');
    });
  }

  updateThemeUI(document.body.classList.contains('light-mode'));

  themeToggleBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const isCurrentlyLight = document.body.classList.contains('light-mode');
      const newTheme = isCurrentlyLight ? 'dark' : 'light';

      if (newTheme === 'light') {
        document.body.classList.add('light-mode');
        document.documentElement.setAttribute('data-theme', 'light');
      } else {
        document.body.classList.remove('light-mode');
        document.documentElement.setAttribute('data-theme', 'dark');
      }

      localStorage.setItem('glorifix_admin_theme', newTheme);
      updateThemeUI(newTheme === 'light');

      if (window.showToast) {
        window.showToast(`Switched to ${newTheme === 'light' ? 'Light Luxury' : 'Deep Wine'} Mode`, 'info');
      }
    });
  });

  // 2. Mobile Sidebar, Close Button & Floating Bottom Dock
  const toggleBtn = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  let backdrop = document.querySelector('.sidebar-backdrop');
  
  if (!backdrop && sidebar) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('active');
  }

  function openSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('active');
  }

  // Ensure close button exists and has click listener attached
  const sidebarHeader = document.querySelector('.sidebar-header');
  let closeBtn = sidebarHeader ? sidebarHeader.querySelector('.sidebar-close-btn') : null;
  if (!closeBtn && sidebarHeader) {
    closeBtn = document.createElement('button');
    closeBtn.className = 'sidebar-close-btn';
    closeBtn.setAttribute('aria-label', 'Close Menu');
    closeBtn.innerHTML = '<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>';
    sidebarHeader.appendChild(closeBtn);
  }
  if (closeBtn) {
    closeBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      closeSidebar();
    });
  }

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', closeSidebar);
    }

    document.addEventListener('click', function(e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target) && !e.target.closest('.admin-dock-menu-btn')) {
        closeSidebar();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });

    // Touch swipe left to dismiss drawer
    let touchStartX = 0;
    let touchEndX = 0;
    sidebar.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    sidebar.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      if (touchStartX - touchEndX > 50) {
        closeSidebar();
      }
    }, { passive: true });

    // Close sidebar on mobile when a nav item is clicked
    sidebar.querySelectorAll('.nav-item').forEach(link => {
      link.addEventListener('click', function() {
        if (window.innerWidth <= 991) {
          closeSidebar();
        }
      });
    });
  }

  // Auto-inject Mobile Floating Bottom Dock (Dashboard, Inquiries, Content, Menu)
  if (!document.querySelector('.admin-mobile-dock') && sidebar) {
    const currentPath = window.location.pathname;
    const isDash = currentPath.endsWith('index.php') || currentPath.endsWith('/admin/');
    const isInq = currentPath.includes('inquiries.php');
    const isContent = currentPath.includes('content-manager.php') || currentPath.includes('pages.php') || currentPath.includes('edit-page.php');
    
    // Find unread inquiries badge if present
    const unreadEl = document.querySelector('.sidebar-nav a[href*="inquiries.php"] span[style*="background"], .badge-unread');
    const badgeText = unreadEl ? unreadEl.textContent.trim() : '';

    const dock = document.createElement('nav');
    dock.className = 'admin-mobile-dock';
    dock.setAttribute('aria-label', 'Mobile Navigation Dock');
    dock.innerHTML = `
      <a href="index.php" class="admin-dock-item ${isDash ? 'active' : ''}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="inquiries.php" class="admin-dock-item ${isInq ? 'active' : ''}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Inquiries</span>
        ${badgeText ? `<span class="admin-dock-badge">${badgeText}</span>` : ''}
      </a>
      <a href="content-manager.php" class="admin-dock-item ${isContent ? 'active' : ''}">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span>Content</span>
      </a>
      <button type="button" class="admin-dock-item admin-dock-menu-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span>Menu</span>
      </button>
    `;

    document.body.appendChild(dock);

    const menuDockBtn = dock.querySelector('.admin-dock-menu-btn');
    if (menuDockBtn) {
      menuDockBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('open')) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });
    }
  }

  // 3. Quick Visual Content Editor Tab Switcher
  const vTabBtns = document.querySelectorAll('.v-tab-btn');
  vTabBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const targetPanelId = this.dataset.panel;
      vTabBtns.forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.v-editor-panel').forEach(p => p.classList.remove('active'));
      
      this.classList.add('active');
      const panel = document.getElementById(targetPanelId);
      if (panel) panel.classList.add('active');
    });
  });

  // 4. Quick Visual Content Editor Form AJAX Submission
  const vForms = document.querySelectorAll('.v-editor-form');
  vForms.forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : 'Save';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳ Saving...</span>';
      }

      const formData = new FormData(this);
      const urlEncoded = new URLSearchParams(formData);
      
      fetch(this.action || 'content-manager.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: urlEncoded
      })
      .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.text();
      })
      .then(() => {
        if (window.showToast) {
          window.showToast('✅ Website content updated & synchronized across all live pages!', 'success');
        }
      })
      .catch(err => {
        // Fallback standard submit if fetch fails
        this.submit();
      })
      .finally(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
    });
  });

  // 5. Toast Notification Function
  window.showToast = function(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'danger' || type === 'error') icon = '⚠️';
    
    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  };

  // 6. Copy to Clipboard
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const text = this.getAttribute('data-copy');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          window.showToast('Copied: ' + text, 'success');
        }).catch(() => {
          fallbackCopy(text);
        });
      } else {
        fallbackCopy(text);
      }
    });
  });

  function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      window.showToast('Copied: ' + text, 'success');
    } catch (err) {
      window.showToast('Failed to copy', 'danger');
    }
    document.body.removeChild(ta);
  }

  // 7. Password Confirmation Verification on Settings Page
  const settingsForm = document.getElementById('password-form');
  if (settingsForm) {
    settingsForm.addEventListener('submit', function(e) {
      const newPass = document.getElementById('new_password');
      const confirmPass = document.getElementById('confirm_password');
      if (newPass && confirmPass && newPass.value !== confirmPass.value) {
        e.preventDefault();
        window.showToast('New passwords do not match!', 'danger');
        confirmPass.focus();
      }
    });
  }

  // 8. Auto dismiss alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.alert-auto-dismiss');
  autoAlerts.forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity 0.4s ease';
      setTimeout(() => alert.remove(), 400);
    }, 4500);
  });

  // 9. Floating Scroll Navigation Dock (Scroll to Top & Bottom)
  if (!document.querySelector('.admin-scroll-dock')) {
    const adminScrollDock = document.createElement('div');
    adminScrollDock.className = 'admin-scroll-dock';
    adminScrollDock.setAttribute('aria-label', 'Scroll Navigation');
    adminScrollDock.innerHTML = `
      <button type="button" class="admin-scroll-btn admin-scroll-up" aria-label="Scroll to top" title="Scroll to Top">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
      </button>
      <div class="admin-scroll-divider"></div>
      <button type="button" class="admin-scroll-btn admin-scroll-down" aria-label="Scroll to bottom" title="Scroll to Bottom">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>
    `;
    document.body.appendChild(adminScrollDock);

    const adminUp = adminScrollDock.querySelector('.admin-scroll-up');
    const adminDown = adminScrollDock.querySelector('.admin-scroll-down');

    adminUp.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    adminDown.addEventListener('click', (e) => {
      e.preventDefault();
      const maxScroll = Math.max(
        document.body.scrollHeight,
        document.documentElement.scrollHeight
      );
      window.scrollTo({ top: maxScroll, behavior: 'smooth' });
    });

    window.addEventListener('scroll', () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      if (scrollTop > 80) {
        adminScrollDock.classList.add('is-visible');
      } else {
        adminScrollDock.classList.remove('is-visible');
      }
    }, { passive: true });
  }
});
