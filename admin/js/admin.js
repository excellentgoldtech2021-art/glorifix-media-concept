/**
 * Glorifix Media Concept Limited - Admin JavaScript Utilities
 */

document.addEventListener('DOMContentLoaded', function() {
  // 1. Mobile Sidebar Toggle
  const toggleBtn = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      sidebar.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // 2. Toast Notification Function
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

  // 3. Copy to Clipboard
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

  // 4. Password Confirmation Verification on Settings Page
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

  // 5. Auto dismiss alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.alert-auto-dismiss');
  autoAlerts.forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity 0.4s ease';
      setTimeout(() => alert.remove(), 400);
    }, 4500);
  });
});
