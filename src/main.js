import { mount } from 'svelte'
import './app.css'
import App from './App.svelte'
import AdminApp from './admin/AdminApp.svelte'

// Simple router based on pathname
const path = window.location.pathname;

if (path === '/login' || path.startsWith('/admin')) {
  // Admin routes
  mount(AdminApp, {
    target: document.getElementById('app'),
  })
} else {
  // Landing page
  mount(App, {
    target: document.getElementById('app'),
  })
}
