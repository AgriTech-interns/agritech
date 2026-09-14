// Shared front-end behaviour for the auth pages.

document.addEventListener('DOMContentLoaded', () => {

  // --- Forgot password form: disable button after submit to avoid double sends
  const forgotForm = document.getElementById('forgotForm');
  if (forgotForm) {
    forgotForm.addEventListener('submit', () => {
      const btn = document.getElementById('submitBtn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Sending...';
      }
    });
  }

  // --- Verify code input: digits only, auto-submit-friendly
  const codeInput = document.getElementById('code');
  if (codeInput) {
    codeInput.addEventListener('input', () => {
      codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
    });
  }

  // --- Reset password form: live match check
  const resetForm = document.getElementById('resetForm');
  if (resetForm) {
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');

    const checkMatch = () => {
      if (confirm.value && password.value !== confirm.value) {
        confirm.setCustomValidity('Passwords do not match');
      } else {
        confirm.setCustomValidity('');
      }
    };

    password.addEventListener('input', checkMatch);
    confirm.addEventListener('input', checkMatch);
  }
});