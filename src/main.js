import { mount } from 'svelte'
import './app.css'
import App from './App.svelte'
import AdminApp from './admin/AdminApp.svelte'

// Simple router based on pathname
function initApp() {
  const appElement = document.getElementById('app');
  if (!appElement) {
    // Retry after a short delay if app element not found
    setTimeout(initApp, 10);
    return;
  }

  const path = window.location.pathname;

  if (path === '/login' || path.startsWith('/admin')) {
    // Admin routes
    mount(AdminApp, {
      target: appElement,
    })
  } else {
    // Landing page
    mount(App, {
      target: appElement,
    })
  }
}

// Ensure DOM is ready before mounting
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  // DOM is already ready
  initApp();
}
