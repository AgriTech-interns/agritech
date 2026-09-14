// ===========================================================
// settings.js — tab switching + AJAX save for settings.php
// ===========================================================

document.addEventListener('DOMContentLoaded', () => {

  /* ================= Toasts ================= */
  function showToast(message, isError = false) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${isError ? 'error' : ''}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  /* ================= Tab switching ================= */
  const tabs = document.querySelectorAll('.settings-tab');
  const panels = document.querySelectorAll('.settings-panel');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      const panel = document.getElementById(`panel-${tab.dataset.tab}`);
      if (panel) panel.classList.add('active');
    });
  });

  /* ================= Generic form POST helper ================= */
  async function postAction(action, formData) {
    formData.append('action', action);
    try {
      const res = await fetch('settings.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      return await res.json();
    } catch (err) {
      return { success: false, message: 'Network error. Please try again.' };
    }
  }

  function setButtonLoading(form, loading) {
    const btn = form.querySelector('button[type="submit"]');
    if (!btn) return;
    if (loading) {
      btn.dataset.originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Saving...';
    } else {
      btn.disabled = false;
      btn.textContent = btn.dataset.originalText || btn.textContent;
    }
  }

  // Wires a form up to a given backend action with the standard
  // loading-state + toast behavior. Extra is an optional callback
  // that runs with the parsed JSON result after a successful save.
  function bindForm(form, action, extra) {
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      setButtonLoading(form, true);
      const result = await postAction(action, new FormData(form));
      setButtonLoading(form, false);
      showToast(result.message, !result.success);
      if (result.success && typeof extra === 'function') extra(result, form);
    });
  }

  /* ================= Profile form ================= */
  bindForm(document.getElementById('profileForm'), 'update_profile');

  /* ================= Password form ================= */
  const passwordForm = document.getElementById('passwordForm');
  passwordForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const newPassword = passwordForm.new_password.value;
    const confirmPassword = passwordForm.confirm_password.value;
    if (newPassword !== confirmPassword) {
      showToast('New passwords do not match.', true);
      return;
    }

    setButtonLoading(passwordForm, true);
    const result = await postAction('change_password', new FormData(passwordForm));
    setButtonLoading(passwordForm, false);
    showToast(result.message, !result.success);
    if (result.success) passwordForm.reset();
  });

  /* ================= Role-specific settings form ================= */
  bindForm(document.getElementById('roleForm'), 'update_role_settings');

  /* ================= Notifications form ================= */
  bindForm(document.getElementById('notificationsForm'), 'update_notifications');

  /* ================= Preferences form (theme / language / currency) ================= */
  bindForm(document.getElementById('preferencesForm'), 'update_preferences', (result) => {
    if (result.theme) {
      document.body.setAttribute('data-theme', result.theme);
    }
  });

  // Live-preview the theme as soon as the user picks it, before saving
  document.querySelectorAll('input[name="theme"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.body.setAttribute('data-theme', radio.value);
    });
  });

  /* ================= Privacy form ================= */
  bindForm(document.getElementById('privacyForm'), 'update_privacy');

  /* ================= Delete account ================= */
  const deleteAccountBtn = document.getElementById('deleteAccountBtn');
  const deleteAccountModal = document.getElementById('deleteAccountModal');
  const deleteAccountForm = document.getElementById('deleteAccountForm');

  deleteAccountBtn?.addEventListener('click', () => {
    deleteAccountModal.classList.add('open');
  });

  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById(btn.dataset.closeModal).classList.remove('open');
    });
  });

  deleteAccountForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    setButtonLoading(deleteAccountForm, true);
    const result = await postAction('delete_account', new FormData(deleteAccountForm));
    setButtonLoading(deleteAccountForm, false);

    if (result.success) {
      showToast('Account deleted. Redirecting...');
      setTimeout(() => { window.location.href = result.redirect || 'login.php'; }, 1200);
    } else {
      showToast(result.message, true);
    }
  });

});