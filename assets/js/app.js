// ============================================================
//  SnowLearn — app.js (global utilities)
// ============================================================

// ------ Modal helpers ------
function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.remove('open');
    document.body.style.overflow = '';
  }
}

// Close on backdrop click & .modal-close buttons
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      const overlay = btn.closest('.modal-overlay');
      if (overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  });

  // Mobile sidebar toggle
  const toggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Auto-dismiss alerts after 5s
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 5000);
  });
});

// ------ Toast system ------
const Toast = {
  show(msg, type = 'info', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    const icons = {
      success: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>',
      danger:  '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
      warning: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>',
      info:    '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    };
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `${icons[type] || ''}<span>${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => {
      t.style.transition = 'opacity 0.3s, transform 0.3s';
      t.style.opacity = '0';
      t.style.transform = 'translateX(40px)';
      setTimeout(() => t.remove(), 350);
    }, duration);
  },
  success: (msg) => Toast.show(msg, 'success'),
  danger:  (msg) => Toast.show(msg, 'danger'),
  warning: (msg) => Toast.show(msg, 'warning'),
  info:    (msg) => Toast.show(msg, 'info'),
};

// ------ Table live search ------
function tableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ------ AJAX helper ------
async function fetchJSON(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      ...options,
    });
    return await res.json();
  } catch (e) {
    console.error('fetchJSON error:', e);
    return { error: e.message };
  }
}

// ------ Confirm dialog ------
function confirmAction(msg, fn) {
  if (confirm(msg)) fn();
}

// ------ Progress bar animate ------
function animateProgress(el, target) {
  let current = 0;
  const step = target / 40;
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.style.width = current + '%';
    if (current >= target) clearInterval(timer);
  }, 16);
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.progress-fill[data-target]').forEach(bar => {
    const target = parseFloat(bar.dataset.target) || 0;
    animateProgress(bar, target);
  });
});
