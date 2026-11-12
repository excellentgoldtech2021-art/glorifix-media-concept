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

  // 2. Mobile Sidebar & Backdrop Toggle
  const toggleBtn = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  let backdrop = document.querySelector('.sidebar-backdrop');
  
  if (!backdrop && sidebar) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
  }

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const isOpen = sidebar.classList.toggle('open');
      if (backdrop) backdrop.classList.toggle('active', isOpen);
    });

    if (backdrop) {
      backdrop.addEventListener('click', function() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('active');
      });
    }

    document.addEventListener('click', function(e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('active');
      }
    });
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
      
      fetch(this.action || 'content-manager.php', {
        method: 'POST',
        body: formData
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
});
