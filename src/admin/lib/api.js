// API helper for admin endpoints
// Detects if we're in dev mode (Vite proxy) or production (same origin)

const getApiBaseUrl = () => {
  // Check if we're accessing via Vite dev server (port 5173) or Laravel (port 8000)
  const hostname = window.location.hostname;
  const port = window.location.port;
  
  // If accessing via Vite dev server (port 5173), use Laravel backend URL
  // Otherwise, use relative URLs (Laravel serves SPA)
  if (import.meta.env.DEV && (port === '5173' || port === '')) {
    // Development via Vite: proxy should handle, but fallback to direct Laravel URL
    // Try relative first (proxy), if fails, will need to use full URL
    return '';
  }
  
  // Production or direct Laravel access: use relative URLs
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

  const response = await fetch(url, {
    credentials: 'include',
    ...options,
    headers,
  });

  return response;
}

