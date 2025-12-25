// API helper for admin endpoints
// Development: uses localhost via Vite proxy (relative URLs)
// Production: uses VITE_API_BASE_URL from .env.prod

const getApiBaseUrl = () => {
  // In development mode, use relative URLs (Vite proxy handles it)
  if (import.meta.env.DEV) {
    return '';
  }
  
  // In production, use VITE_API_BASE_URL from .env.prod
  const envApiUrl = import.meta.env.VITE_API_BASE_URL;
  
  if (envApiUrl) {
    return envApiUrl;
  }
  
  // Fallback: use relative URLs if VITE_API_BASE_URL not set
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

