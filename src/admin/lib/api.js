// API helper for admin endpoints
// Development: uses localhost via Vite proxy (relative URLs)
// Production: uses VITE_API_BASE_URL from .env.prod

const getApiBaseUrl = () => {
  // In development mode, use relative URLs (Vite proxy handles it)
  if (import.meta.env.DEV) {
    return '';
  }
  
  // In production, use VITE_API_BASE_URL from .env.prod (injected at build time)
  const envApiUrl = import.meta.env.VITE_API_BASE_URL;
  
  if (envApiUrl) {
    return envApiUrl;
  }
  
  // Fallback: try to detect backend API URL from current domain
  const hostname = window.location.hostname;
  
  // If on catatuang.click, try api.catatuang.click
  if (hostname === 'catatuang.click' || hostname.includes('catatuang')) {
    const fallbackUrl = 'https://api.catatuang.click';
    console.warn('VITE_API_BASE_URL not set in build! Using fallback:', fallbackUrl);
    console.warn('Make sure to set VITE_API_BASE_URL in .env.prod and rebuild!');
    return fallbackUrl;
  }
  
  // Default fallback: use relative URLs (will fail in production if backend is on different domain)
  console.error('VITE_API_BASE_URL not set and no fallback available! API calls will fail.');
  console.error('Set VITE_API_BASE_URL in .env.prod and rebuild the application.');
  return '';
};

export const apiBaseUrl = getApiBaseUrl();

export async function apiFetch(endpoint, options = {}) {
  const url = `${apiBaseUrl}${endpoint}`;
  
  // Merge headers
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(options.headers || {}),
  };

  try {
    const response = await fetch(url, {
      credentials: 'include',
      ...options,
      headers,
    });

    // Log errors in production for debugging
    if (!response.ok && !import.meta.env.DEV) {
      console.error('API Error:', url, response.status, response.statusText);
    }

    return response;
  } catch (error) {
    console.error('API Fetch Error:', error, 'URL:', url);
    throw error;
  }
}

