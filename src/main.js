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
  
  // Debug logging
  console.log('Router: Initializing app, path:', path);

  if (path === '/login' || path.startsWith('/admin')) {
    // Admin routes
    console.log('Router: Mounting AdminApp for path:', path);
    mount(AdminApp, {
      target: appElement,
    })
  } else {
    // Landing page
    console.log('Router: Mounting App (landing page) for path:', path);
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
