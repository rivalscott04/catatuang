<script>
  import { onMount } from 'svelte';
  import AdminLogin from './AdminLogin.svelte';
  import AdminDashboard from './AdminDashboard.svelte';
  import { apiFetch } from './lib/api.js';

  let isAuthenticated = false;
  let loading = true;
  let admin = null;
  let currentPath = window.location.pathname;

  // Handle route changes
  function handleRoute() {
    currentPath = window.location.pathname;
    
    // If on /login and authenticated, redirect to /admin
    if (currentPath === '/login' && isAuthenticated) {
      window.history.pushState({}, '', '/admin');
      currentPath = '/admin';
    }
    
    // If on /admin and not authenticated, redirect to /login
    if (currentPath.startsWith('/admin') && !isAuthenticated && !loading) {
      window.history.pushState({}, '', '/login');
      currentPath = '/login';
    }
  }

  async function checkAuth() {
    try {
      const response = await apiFetch('/admin/me', {
        method: 'GET',
      });

      // If 401 or 404, user is not authenticated (expected)
      if (response.status === 401 || response.status === 404) {
        isAuthenticated = false;
        return;
      }

      if (response.ok) {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const data = await response.json();
          if (data.success) {
            isAuthenticated = true;
            admin = data.data.admin;
          }
        }
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      isAuthenticated = false;
    } finally {
      loading = false;
      handleRoute();
    }
  }

  async function handleLogin() {
    // After successful login, check auth again to get admin data
    await checkAuth();
    // Redirect to /admin after login
    if (isAuthenticated) {
      window.history.pushState({}, '', '/admin');
      currentPath = '/admin';
    }
  }

  function handleLogout() {
    isAuthenticated = false;
    admin = null;
    // Redirect to /login after logout
    window.history.pushState({}, '', '/login');
    currentPath = '/login';
  }

  onMount(() => {
    checkAuth();
    
    // Listen to popstate for browser back/forward
    window.addEventListener('popstate', handleRoute);
    
    return () => {
      window.removeEventListener('popstate', handleRoute);
    };
  });
</script>

{#if loading}
  <div class="loading-container">
    <div class="spinner"></div>
  </div>
{:else if currentPath === '/login' || (!isAuthenticated && currentPath.startsWith('/admin'))}
  <AdminLogin on:login={handleLogin} />
{:else if isAuthenticated && currentPath.startsWith('/admin')}
  <AdminDashboard {admin} on:logout={handleLogout} />
{:else}
  <!-- Default: show login if path doesn't match -->
  <AdminLogin on:login={handleLogin} />
{/if}

<style>
  .loading-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-bg, #f8fafc);
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: var(--color-primary, #10b981);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
</style>

