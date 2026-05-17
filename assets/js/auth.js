/**
 * auth.js — Shared JS for Authentication & Profile Module
 * Smart Meal Planner & Health Nutrition System
 */

/* ── Password visibility toggle ── */
function initPasswordToggle() {
  document.querySelectorAll('[data-pw-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.pwToggle;
      const input = document.getElementById(targetId);
      if (!input) return;
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      btn.textContent = isText ? '👁' : '🙈';
    });
  });
}

/* ── BMI live display (profile edit) ── */
function initBMILive() {
  const weightInput = document.getElementById('weight');
  const heightInput = document.getElementById('height');
  const bmiDisplay  = document.getElementById('bmi-live');
  const bmiLabel    = document.getElementById('bmi-label');
  const bmiBar      = document.getElementById('bmi-bar-fill');

  if (!weightInput || !heightInput || !bmiDisplay) return;

  function calcBMI() {
    const w = parseFloat(weightInput.value);
    const h = parseFloat(heightInput.value) / 100; // cm → m
    if (!w || !h || h === 0) return;

    const bmi = (w / (h * h)).toFixed(1);
    bmiDisplay.textContent = bmi;

    let label = '', color = '';
    let pct   = 0;
    if      (bmi < 18.5) { label = 'Underweight'; color = '#70b4c9'; pct = Math.max(5, (bmi/18.5)*30); }
    else if (bmi < 25)   { label = 'Normal';       color = '#7cb87a'; pct = 30 + ((bmi-18.5)/(25-18.5))*30; }
    else if (bmi < 30)   { label = 'Overweight';   color = '#c9a870'; pct = 60 + ((bmi-25)/5)*20; }
    else                  { label = 'Obese';         color = '#c97070'; pct = Math.min(95, 80 + (bmi-30)*2); }

    if (bmiLabel)   bmiLabel.textContent = label;
    if (bmiBar)     { bmiBar.style.width = pct + '%'; bmiBar.style.background = color; }
  }

  weightInput.addEventListener('input', calcBMI);
  heightInput.addEventListener('input', calcBMI);
  calcBMI(); // run on load if values pre-filled
}

/* ── Register: password strength ── */
function initPasswordStrength() {
  const pwInput    = document.getElementById('password');
  const strengthEl = document.getElementById('pw-strength');
  if (!pwInput || !strengthEl) return;

  pwInput.addEventListener('input', () => {
    const val = pwInput.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', '#c97070', '#c9a870', '#70b4c9', '#7cb87a'];
    strengthEl.textContent = val.length === 0 ? '' : labels[score] || 'Weak';
    strengthEl.style.color = colors[score] || '#c97070';
  });
}

/* ── Register: confirm password match ── */
function initPasswordConfirm() {
  const pw1 = document.getElementById('password');
  const pw2 = document.getElementById('confirm_password');
  const msg = document.getElementById('confirm-msg');
  if (!pw1 || !pw2 || !msg) return;

  function check() {
    if (pw2.value === '') { msg.textContent = ''; return; }
    if (pw1.value === pw2.value) {
      msg.textContent = '✓ Passwords match';
      msg.style.color = '#7cb87a';
    } else {
      msg.textContent = '✗ Passwords do not match';
      msg.style.color = '#c97070';
    }
  }
  pw1.addEventListener('input', check);
  pw2.addEventListener('input', check);
}

/* ── Profile: edit/view mode toggle ── */
function initProfileToggle() {
  const editBtn  = document.getElementById('edit-btn');
  const cancelBtn= document.getElementById('cancel-btn');
  const form     = document.getElementById('profile-form');
  const viewMode = document.getElementById('view-mode');
  if (!editBtn) return;

  editBtn.addEventListener('click', () => {
    form?.classList.toggle('hidden');
    viewMode?.classList.toggle('hidden');
    editBtn.classList.toggle('hidden');
    cancelBtn?.classList.toggle('hidden');
  });
  cancelBtn?.addEventListener('click', () => {
    form?.classList.toggle('hidden');
    viewMode?.classList.toggle('hidden');
    editBtn.classList.toggle('hidden');
    cancelBtn?.classList.toggle('hidden');
  });
}

/* ── Auto-dismiss alerts ── */
function initAutoDismiss() {
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });
}

/* ── Init all ── */
document.addEventListener('DOMContentLoaded', () => {
  initPasswordToggle();
  initBMILive();
  initPasswordStrength();
  initPasswordConfirm();
  initProfileToggle();
  initAutoDismiss();
});
