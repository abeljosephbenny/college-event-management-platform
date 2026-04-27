/**
 * Campus Events — Client-Side JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
  initToasts();
  initSearch();
  initCategoryTabs();
  initConfirmDialogs();
  initFormValidation();
});

/* ── Mobile Navigation ─────────────────────────────────────── */
function initMobileNav() {
  const hamburger = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');
  if (!hamburger || !navLinks) return;
  hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.navbar')) navLinks.classList.remove('open');
  });
}

/* ── Toast Notifications ───────────────────────────────────── */
function initToasts() {
  const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 300);
    }, 4000);
  });
}

function showToast(message, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast alert-${type}`;
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

/* ── Search & Filter ───────────────────────────────────────── */
function initSearch() {
  const searchInput = document.getElementById('event-search');
  if (!searchInput) return;
  searchInput.addEventListener('input', debounce((e) => {
    const query = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.event-card');
    cards.forEach(card => {
      const title = (card.querySelector('.event-title')?.textContent || '').toLowerCase();
      const desc = (card.querySelector('.event-desc')?.textContent || '').toLowerCase();
      card.closest('.grid > *') && (card.style.display = (title.includes(query) || desc.includes(query)) ? '' : 'none');
    });
  }, 300));
}

/* ── Category Tabs ─────────────────────────────────────────── */
function initCategoryTabs() {
  const tabs = document.querySelectorAll('.category-tab');
  if (!tabs.length) return;
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const category = tab.dataset.category;
      const cards = document.querySelectorAll('.event-card');
      cards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
          card.parentElement.style.display = '';
        } else {
          card.parentElement.style.display = 'none';
        }
      });
    });
  });
}

/* ── Confirm Dialogs ───────────────────────────────────────── */
function initConfirmDialogs() {
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });
}

/* ── Form Validation ───────────────────────────────────────── */
function initFormValidation() {
  const emailInputs = document.querySelectorAll('input[data-college-email]');
  emailInputs.forEach(input => {
    input.addEventListener('blur', () => {
      const domain = input.dataset.collegeEmail;
      if (input.value && !input.value.endsWith('@' + domain)) {
        input.classList.add('error');
        let hint = input.parentElement.querySelector('.form-error');
        if (!hint) {
          hint = document.createElement('div');
          hint.className = 'form-error';
          input.parentElement.appendChild(hint);
        }
        hint.textContent = `Please use your @${domain} email address`;
      } else {
        input.classList.remove('error');
        const hint = input.parentElement.querySelector('.form-error');
        if (hint) hint.remove();
      }
    });
  });
}

/* ── Role Selector Toggle ──────────────────────────────────── */
function toggleRoleFields(role) {
  const studentFields = document.getElementById('student-fields');
  const organizerFields = document.getElementById('organizer-fields');
  if (studentFields) studentFields.style.display = role === 'Student' ? 'block' : 'none';
  if (organizerFields) organizerFields.style.display = role === 'Organizer' ? 'block' : 'none';
}

/* ── Utilities ─────────────────────────────────────────────── */
function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}
