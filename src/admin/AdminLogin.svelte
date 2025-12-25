<script>
  import { createEventDispatcher } from 'svelte';
  import { apiFetch } from './lib/api.js';

  const dispatch = createEventDispatcher();

  let username = '';
  let password = '';
  let error = '';
  let loading = false;

  async function getCsrfToken() {
    try {
      const response = await apiFetch('/csrf-token', {
        method: 'GET',
      });
      
      if (!response.ok) {
        console.error('CSRF token response not OK:', response.status);
        return null;
      }
      
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        console.error('CSRF token response is not JSON:', contentType);
        return null;
      }
      
      const data = await response.json();
      return data.token;
    } catch (err) {
      console.error('Failed to get CSRF token:', err);
      return null;
    }
  }

  async function handleLogin(e) {
    e.preventDefault();
    error = '';
    loading = true;

    try {
      if (!username || !password) {
        error = 'Username dan password wajib diisi';
        loading = false;
        return;
      }

      const csrfToken = await getCsrfToken();

      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch('/admin/login', {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({
          username: username.trim(),
          password: password,
        }),
      });

      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        console.error('Login response is not JSON:', text.substring(0, 200));
        error = 'Terjadi kesalahan server. Silakan coba lagi.';
        return;
      }

      const data = await response.json();

      if (response.ok && data.success) {
        // Login successful, dispatch event to parent
        dispatch('login');
      } else {
        error = data.message || 'Login gagal. Silakan coba lagi.';
      }
    } catch (err) {
      console.error('Login error:', err);
      error = 'Terjadi kesalahan. Silakan coba lagi.';
    } finally {
      loading = false;
    }
  }
</script>

<div class="login-page">
  <div class="login-container">
    <div class="logo">
      <img src="/catatuang_logo.svg" alt="CatatUang Logo" />
    </div>
    
    <div class="login-header">
      <h1>Admin Login</h1>
      <p class="subtitle">Masuk ke dashboard admin</p>
    </div>

    {#if error}
      <div class="error-message">{error}</div>
    {/if}

    <form on:submit={handleLogin} class="login-form">
          <div class="form-group">
            <label for="username">Username atau Email</label>
            <input
              type="text"
              id="username"
              bind:value={username}
              placeholder="Masukkan username atau email"
              required
              disabled={loading}
              autocomplete="username"
            />
          </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          bind:value={password}
          placeholder="Masukkan password"
          required
          disabled={loading}
          autocomplete="current-password"
        />
      </div>

      <button type="submit" class="btn-submit" disabled={loading}>
        {loading ? 'Memproses...' : 'Masuk'}
      </button>
    </form>
  </div>
</div>

<style>
  .login-page {
    min-height: 100vh;
    background: var(--color-bg, #f8fafc);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }

  .login-container {
    background: var(--color-card-bg, #fff);
    border-radius: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    padding: 3rem;
    width: 100%;
    max-width: 420px;
  }

  .logo {
    text-align: center;
    margin-bottom: 2rem;
  }

  .logo img {
    max-width: 180px;
    height: auto;
  }

  .login-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .login-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text-heading, #0f172a);
    margin-bottom: 0.5rem;
  }

  .subtitle {
    color: #64748b;
    font-size: 0.95rem;
  }

  .error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
  }

  .login-form {
    margin-top: 1.5rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-heading, #0f172a);
    margin-bottom: 0.5rem;
  }

  .form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    color: var(--color-text-heading, #0f172a);
    transition: all 0.2s;
    font-family: inherit;
  }

  .form-group input:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-group input:disabled {
    background: #f8fafc;
    cursor: not-allowed;
  }

  .btn-submit {
    width: 100%;
    padding: 14px;
    background: var(--color-primary, #10b981);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 0.5rem;
  }

  .btn-submit:hover:not(:disabled) {
    background: var(--color-primary-hover, #059669);
    transform: translateY(-1px);
    box-shadow: 0 6px 10px -2px rgba(16, 185, 129, 0.3);
  }

  .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  @media (max-width: 640px) {
    .login-container {
      padding: 2rem 1.5rem;
    }

    .login-header h1 {
      font-size: 1.75rem;
    }
  }
</style>

