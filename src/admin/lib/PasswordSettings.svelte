<script>
  import { apiFetch } from './api.js';
  import Toast from './Toast.svelte';
  import { onMount } from 'svelte';

  let currentPassword = '';
  let newPassword = '';
  let confirmPassword = '';
  let newUsername = '';
  let currentUsername = '';
  let loading = false;
  let loadingUsername = false;
  let showToast = false;
  let toastMessage = '';
  let toastType = 'success';

  // Validation states
  let currentPasswordError = '';
  let newPasswordErrors = {
    minLength: false,
    hasCapital: false,
    hasSymbol: false,
    isWeak: false,
  };
  let confirmPasswordError = '';

  // Weak passwords to check
  const weakPasswords = ['admin123', 'password123', 'admin', 'password', '12345678', 'qwerty123'];

  onMount(async () => {
    // Fetch current admin info to get username
    try {
      const response = await apiFetch('/admin/me', {
        method: 'GET',
      });
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.admin) {
          currentUsername = data.data.admin.username;
          newUsername = data.data.admin.username;
        }
      }
    } catch (err) {
      console.error('Failed to fetch admin info:', err);
    }
  });

  async function getCsrfToken() {
    try {
      const response = await apiFetch('/csrf-token', {
        method: 'GET',
      });
      const data = await response.json();
      return data.token;
    } catch (err) {
      console.error('Failed to get CSRF token:', err);
      return null;
    }
  }

  function validatePassword(password) {
    const errors = {
      minLength: password.length >= 8,
      hasCapital: /[A-Z]/.test(password),
      hasSymbol: /[!@#$%^&*(),.?":{}|<>\[\]\\/_+\-=~`]/.test(password),
      isWeak: !weakPasswords.includes(password.toLowerCase()),
    };
    return errors;
  }

  function validateCurrentPassword() {
    if (!currentPassword) {
      currentPasswordError = '';
      return false;
    }
    currentPasswordError = '';
    return true;
  }

  function validateNewPassword() {
    if (!newPassword) {
      newPasswordErrors = {
        minLength: false,
        hasCapital: false,
        hasSymbol: false,
        isWeak: false,
      };
      return false;
    }

    newPasswordErrors = validatePassword(newPassword);
    return Object.values(newPasswordErrors).every(v => v === true);
  }

  function validateConfirmPassword() {
    if (!confirmPassword) {
      confirmPasswordError = '';
      return false;
    }

    if (confirmPassword !== newPassword) {
      confirmPasswordError = 'Konfirmasi password tidak cocok';
      return false;
    }

    confirmPasswordError = '';
    return true;
  }

  function isFormValid() {
    return (
      validateCurrentPassword() &&
      validateNewPassword() &&
      validateConfirmPassword() &&
      currentPassword &&
      newPassword &&
      confirmPassword
    );
  }

  function showToastMessage(message, type = 'success') {
    toastMessage = message;
    toastType = type;
    showToast = true;
    setTimeout(() => {
      showToast = false;
    }, 5000);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    
    if (!isFormValid()) {
      showToastMessage('Harap lengkapi semua field dengan benar', 'error');
      return;
    }

    loading = true;

    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch('/admin/password', {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: confirmPassword,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        showToastMessage('Password berhasil diperbarui!', 'success');
        // Reset form
        currentPassword = '';
        newPassword = '';
        confirmPassword = '';
        currentPasswordError = '';
        confirmPasswordError = '';
        newPasswordErrors = {
          minLength: false,
          hasCapital: false,
          hasSymbol: false,
          isWeak: false,
        };
      } else {
        // Handle validation errors from backend
        if (data.errors) {
          if (data.errors.current_password) {
            currentPasswordError = data.errors.current_password[0];
          }
          if (data.errors.new_password) {
            const backendErrors = data.errors.new_password;
            if (Array.isArray(backendErrors)) {
              showToastMessage(backendErrors[0], 'error');
            }
          }
          if (data.errors.new_password_confirmation) {
            confirmPasswordError = data.errors.new_password_confirmation[0];
          }
        }
        showToastMessage(data.message || 'Gagal memperbarui password', 'error');
      }
    } catch (err) {
      console.error('Update password error:', err);
      showToastMessage('Terjadi kesalahan. Silakan coba lagi.', 'error');
    } finally {
      loading = false;
    }
  }

  function validateUsername() {
    if (!newUsername || newUsername.trim() === '') {
      return false;
    }
    if (newUsername.length < 3) {
      return false;
    }
    if (newUsername.length > 50) {
      return false;
    }
    // Only allow alphanumeric and underscore
    if (!/^[a-zA-Z0-9_]+$/.test(newUsername)) {
      return false;
    }
    return true;
  }

  async function handleUsernameSubmit(e) {
    e.preventDefault();
    
    if (!validateUsername()) {
      showToastMessage('Username harus 3-50 karakter dan hanya boleh huruf, angka, dan underscore', 'error');
      return;
    }

    if (newUsername === currentUsername) {
      showToastMessage('Username baru harus berbeda dengan username saat ini', 'error');
      return;
    }

    loadingUsername = true;

    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch('/admin/username', {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({
          username: newUsername,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        showToastMessage('Username berhasil diperbarui!', 'success');
        currentUsername = newUsername;
      } else {
        showToastMessage(data.message || 'Gagal memperbarui username', 'error');
      }
    } catch (err) {
      console.error('Update username error:', err);
      showToastMessage('Terjadi kesalahan. Silakan coba lagi.', 'error');
    } finally {
      loadingUsername = false;
    }
  }
</script>

<div class="password-settings">
  <div class="settings-header">
    <h2>Pengaturan Akun</h2>
    <p class="subtitle">Kelola username dan password Anda</p>
  </div>

  <!-- Username Change Section -->
  <div class="settings-section">
    <h3 class="section-title">Ubah Username</h3>
    <form on:submit={handleUsernameSubmit} class="username-form">
      <div class="form-group">
        <label for="username">Username Baru</label>
        <input
          type="text"
          id="username"
          bind:value={newUsername}
          placeholder="Masukkan username baru"
          required
          disabled={loadingUsername}
          autocomplete="username"
          minlength="3"
          maxlength="50"
          pattern="[a-zA-Z0-9_]+"
        />
        <p class="form-hint">Username harus 3-50 karakter dan hanya boleh huruf, angka, dan underscore (_)</p>
      </div>
      <button type="submit" class="btn-submit" disabled={loadingUsername || !validateUsername() || newUsername === currentUsername}>
        {loadingUsername ? 'Memproses...' : 'Perbarui Username'}
      </button>
    </form>
  </div>

  <!-- Password Change Section -->
  <div class="settings-section">
    <h3 class="section-title">Ubah Password</h3>

  <form on:submit={handleSubmit} class="password-form">
    <div class="form-group">
      <label for="current-password">Password Saat Ini</label>
      <input
        type="password"
        id="current-password"
        bind:value={currentPassword}
        on:input={validateCurrentPassword}
        placeholder="Masukkan password saat ini"
        required
        disabled={loading}
        autocomplete="current-password"
        class:error={currentPasswordError}
      />
      {#if currentPasswordError}
        <span class="error-text">{currentPasswordError}</span>
      {/if}
    </div>

    <div class="form-group">
      <label for="new-password">Password Baru</label>
      <input
        type="password"
        id="new-password"
        bind:value={newPassword}
        on:input={validateNewPassword}
        placeholder="Masukkan password baru"
        required
        disabled={loading}
        autocomplete="new-password"
        class:error={Object.values(newPasswordErrors).some(v => v === false) && newPassword}
      />
      {#if newPassword}
        <div class="password-requirements">
          <div class="requirement" class:valid={newPasswordErrors.minLength}>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              {#if newPasswordErrors.minLength}
                <polyline points="20 6 9 17 4 12"></polyline>
              {:else}
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              {/if}
            </svg>
            <span>Minimal 8 karakter</span>
          </div>
          <div class="requirement" class:valid={newPasswordErrors.hasCapital}>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              {#if newPasswordErrors.hasCapital}
                <polyline points="20 6 9 17 4 12"></polyline>
              {:else}
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              {/if}
            </svg>
            <span>Minimal 1 huruf kapital (A-Z)</span>
          </div>
          <div class="requirement" class:valid={newPasswordErrors.hasSymbol}>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              {#if newPasswordErrors.hasSymbol}
                <polyline points="20 6 9 17 4 12"></polyline>
              {:else}
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              {/if}
            </svg>
            <span>Minimal 1 simbol (!@#$%^&*...)</span>
          </div>
          <div class="requirement" class:valid={newPasswordErrors.isWeak}>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              {#if newPasswordErrors.isWeak}
                <polyline points="20 6 9 17 4 12"></polyline>
              {:else}
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              {/if}
            </svg>
            <span>Tidak menggunakan password lemah</span>
          </div>
        </div>
      {/if}
    </div>

    <div class="form-group">
      <label for="confirm-password">Konfirmasi Password Baru</label>
      <input
        type="password"
        id="confirm-password"
        bind:value={confirmPassword}
        on:input={validateConfirmPassword}
        placeholder="Konfirmasi password baru"
        required
        disabled={loading}
        autocomplete="new-password"
        class:error={confirmPasswordError}
      />
      {#if confirmPasswordError}
        <span class="error-text">{confirmPasswordError}</span>
      {/if}
    </div>

    <button type="submit" class="btn-submit" disabled={loading || !isFormValid()}>
      {loading ? 'Memproses...' : 'Perbarui Password'}
    </button>
  </form>
  </div>
</div>

<Toast message={toastMessage} type={toastType} visible={showToast} on:close={() => showToast = false} />

<style>
  .password-settings {
    background: #ffffff;
    border-radius: 16px;
    padding: 2.5rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    max-width: 600px;
  }

  .settings-header {
    margin-bottom: 2.5rem;
  }

  .settings-section {
    margin-bottom: 3rem;
    padding-bottom: 2.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .settings-section:last-of-type {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }

  .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 1.5rem;
  }

  .username-form {
    margin-top: 1.5rem;
  }

  .form-hint {
    font-size: 0.8125rem;
    color: #64748b;
    margin-top: 0.5rem;
    line-height: 1.5;
  }

  .settings-header h2 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
  }

  .subtitle {
    color: #64748b;
    font-size: 0.9375rem;
    line-height: 1.6;
  }

  .password-form {
    margin-top: 2rem;
  }

  .form-group {
    margin-bottom: 1.75rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
  }

  .form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    color: #0f172a;
    transition: all 0.2s;
    font-family: inherit;
    box-sizing: border-box;
  }

  .form-group input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-group input:disabled {
    background: #f8fafc;
    cursor: not-allowed;
  }

  .form-group input.error {
    border-color: #ef4444;
  }

  .form-group input.error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
  }

  .error-text {
    display: block;
    color: #ef4444;
    font-size: 0.8125rem;
    margin-top: 0.5rem;
  }

  .password-requirements {
    margin-top: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
  }

  .requirement {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    margin-bottom: 0.625rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .requirement:last-child {
    margin-bottom: 0;
  }

  .requirement svg {
    flex-shrink: 0;
    stroke: #ef4444;
  }

  .requirement.valid {
    color: #10b981;
  }

  .requirement.valid svg {
    stroke: #10b981;
  }

  .btn-submit {
    width: 100%;
    padding: 14px;
    background: #10b981;
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 0.5rem;
  }

  .btn-submit:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 6px 10px -2px rgba(16, 185, 129, 0.3);
  }

  .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  @media (max-width: 768px) {
    .password-settings {
      padding: 1.75rem;
    }

    .settings-header h2 {
      font-size: 1.625rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }
  }
</style>

