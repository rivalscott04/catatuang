<script>
  import { onMount } from "svelte";

  // Get API base URL
  const getApiBaseUrl = () => {
    if (import.meta.env.DEV) {
      return '';
    }
    const envApiUrl = import.meta.env.VITE_API_BASE_URL;
    if (envApiUrl) {
      return envApiUrl;
    }
    const hostname = window.location.hostname;
    if (hostname === 'catatuang.click' || hostname.includes('catatuang')) {
      return 'https://api.catatuang.click';
    }
    return '';
  };

  const apiBaseUrl = getApiBaseUrl();

  // Get token from URL
  let token = '';
  let loading = true;
  let error = '';
  let user = null;
  let currentPlan = '';
  let availablePlans = [];
  let selectedPlan = '';
  let processing = false;
  let upgradeSuccess = false;
  let successData = null;

  onMount(() => {
    // Check if this is success page
    const path = window.location.pathname;
    const search = window.location.search;
    
    if (path === '/upgrade/success' || search.includes('token=')) {
      // Extract token from query string
      const urlParams = new URLSearchParams(window.location.search);
      token = urlParams.get('token') || '';
      
      if (token) {
        loadSuccessInfo();
      } else {
        error = 'Token tidak ditemukan';
        loading = false;
      }
      return;
    }

    // Extract token from URL path
    const match = path.match(/\/upgrade\/([a-zA-Z0-9]{64})/);
    if (match) {
      token = match[1];
      
      // Check if plan is specified in URL (magic link with specific plan)
      const urlParams = new URLSearchParams(window.location.search);
      const planParam = urlParams.get('plan');
      
      if (planParam && (planParam === 'pro' || planParam === 'vip')) {
        // Direct to checkout with specified plan
        window.location.href = `/checkout?token=${token}&plan=${planParam}`;
        return;
      }
      
      validateToken();
    } else {
      error = 'Token tidak valid';
      loading = false;
    }
  });

  async function validateToken() {
    try {
      console.log('Validating token:', token);
      console.log('API Base URL:', apiBaseUrl);
      
      const response = await fetch(`${apiBaseUrl}/api/upgrade/validate/${token}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
        },
      });

      console.log('Validation response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Validation failed:', response.status, errorText);
        try {
          const errorData = JSON.parse(errorText);
          error = errorData.message || 'Token tidak valid atau sudah expired';
        } catch {
          error = 'Token tidak valid atau sudah expired';
        }
        loading = false;
        return;
      }

      const data = await response.json();
      console.log('Validation response data:', data);

      if (data.success) {
        user = data.data.user;
        currentPlan = data.data.current_plan || '';
        console.log('Current plan from validateToken:', currentPlan);
        await loadPlans();
      } else {
        error = data.message || 'Token tidak valid atau sudah expired';
        loading = false;
      }
    } catch (err) {
      console.error('Validation error:', err);
      error = 'Terjadi kesalahan saat memvalidasi token. Pastikan koneksi internet Anda stabil.';
      loading = false;
    }
  }

  async function loadPlans() {
    try {
      console.log('Loading plans for token:', token);
      
      const response = await fetch(`${apiBaseUrl}/api/upgrade/${token}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
        },
      });

      console.log('Load plans response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Load plans failed:', response.status, errorText);
        try {
          const errorData = JSON.parse(errorText);
          error = errorData.message || 'Gagal memuat paket yang tersedia';
        } catch {
          error = 'Gagal memuat paket yang tersedia';
        }
        loading = false;
        return;
      }

      const data = await response.json();
      console.log('Load plans response data:', data);

      if (data.success) {
        // Always update currentPlan from loadPlans response (most reliable source)
        // This ensures we have the most up-to-date value
        if (data.data.current_plan) {
          currentPlan = data.data.current_plan;
          console.log('Current plan updated from loadPlans:', currentPlan);
        } else {
          console.warn('current_plan not found in loadPlans response, keeping existing:', currentPlan);
        }
        
        // Filter out unlimited plan (double check on frontend)
        availablePlans = (data.data.available_plans || []).filter(plan => plan.plan !== 'unlimited');
        console.log('Available plans:', availablePlans);
        if (availablePlans.length > 0) {
          selectedPlan = availablePlans[0].plan;
        }
        loading = false;
      } else {
        error = data.message || 'Gagal memuat paket yang tersedia';
        loading = false;
      }
    } catch (err) {
      console.error('Load plans error:', err);
      error = 'Terjadi kesalahan saat memuat paket. Pastikan koneksi internet Anda stabil.';
      loading = false;
    }
  }

  async function processUpgrade() {
    if (!selectedPlan) {
      error = 'Silakan pilih paket terlebih dahulu';
      return;
    }

    processing = true;
    error = '';

    try {
      const response = await fetch(`${apiBaseUrl}/api/upgrade/process`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          token: token,
          plan: selectedPlan,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // Redirect to checkout page (always use relative URL)
        const checkoutUrl = `/checkout?token=${token}&plan=${selectedPlan}`;
        window.location.href = checkoutUrl;
      } else {
        error = data.message || 'Gagal memproses upgrade';
        processing = false;
      }
    } catch (err) {
      console.error('Upgrade error:', err);
      error = 'Terjadi kesalahan saat memproses upgrade';
      processing = false;
    }
  }

  function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(price);
  }

  async function loadSuccessInfo() {
    try {
      const response = await fetch(`${apiBaseUrl}/api/upgrade/success?token=${token}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
        },
      });

      const data = await response.json();

      if (response.ok && data.success) {
        successData = data.data;
        upgradeSuccess = true;
        loading = false;
      } else {
        error = data.message || 'Gagal memuat informasi upgrade';
        loading = false;
      }
    } catch (err) {
      console.error('Load success info error:', err);
      error = 'Terjadi kesalahan saat memuat informasi';
      loading = false;
    }
  }

  function getPlanName(plan) {
    if (!plan) return '-';
    const names = {
      free: 'Free Trial',
      pro: 'Pro',
      vip: 'VIP',
    };
    return names[plan.toLowerCase()] || plan.toUpperCase();
  }
</script>

<div class="upgrade-page">
  {#if loading}
    <div class="loading-container">
      <div class="spinner"></div>
      <p>Memuat halaman upgrade...</p>
    </div>
  {:else if error}
    <div class="error-container">
      <div class="error-icon">⚠️</div>
      <h1>Oops!</h1>
      <p>{error}</p>
      <a href="/" class="back-link">Kembali ke halaman utama</a>
    </div>
  {:else if upgradeSuccess && successData}
    <div class="success-container">
      <div class="success-icon">✓</div>
      <h1>Upgrade Berhasil!</h1>
      <p>Paket kamu sudah di-upgrade ke <strong>{getPlanName(successData.plan)}</strong></p>
      <a href="/" class="back-link">Kembali ke halaman utama</a>
    </div>
  {:else}
    <div class="upgrade-container">
      <div class="header">
        <h1>Upgrade Paket</h1>
        <p class="subtitle">Pilih paket yang sesuai dengan kebutuhanmu</p>
        {#if user}
          <div class="user-info">
            <p>Nomor WhatsApp: <strong>{user.phone_number}</strong></p>
            <p>Paket saat ini: <strong>{currentPlan ? getPlanName(currentPlan) : 'Memuat...'}</strong></p>
          </div>
        {/if}
      </div>

      {#if error}
        <div class="error-message">{error}</div>
      {/if}

      {#if availablePlans.length === 0}
        <div class="no-plans">
          <p>Tidak ada paket yang tersedia untuk upgrade</p>
        </div>
      {:else}
        <div class="plans-grid">
          {#each availablePlans as plan}
            {@const isSelected = selectedPlan === plan.plan}
            <button
              type="button"
              class="plan-card"
              class:selected={isSelected}
              on:click={() => selectedPlan = plan.plan}
              on:keydown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  selectedPlan = plan.plan;
                }
              }}
              aria-label="Pilih paket {getPlanName(plan.plan)}"
              aria-pressed={isSelected}
            >
              <div class="plan-header">
                {#if plan.badge_text}
                  <span class="badge">{plan.badge_text}</span>
                {/if}
                <h2>{getPlanName(plan.plan)}</h2>
                <div class="price">{formatPrice(plan.price)}<span class="period">/bulan</span></div>
              </div>
              {#if plan.description}
                <p class="plan-description">{plan.description}</p>
              {/if}
              {#if plan.features && plan.features.length > 0}
                <ul class="features-list">
                  {#each plan.features as feature}
                    <li>{feature}</li>
                  {/each}
                </ul>
              {/if}
              <div class="select-indicator">
                {#if isSelected}
                  <span class="selected-badge">✓ Dipilih</span>
                {/if}
              </div>
            </button>
          {/each}
        </div>

        <div class="actions">
          <button
            class="upgrade-button"
            disabled={processing || !selectedPlan}
            on:click={processUpgrade}
          >
            {#if processing}
              Memproses...
            {:else}
              Upgrade ke {getPlanName(selectedPlan)}
            {/if}
          </button>
          <a href="/" class="cancel-link">Batal</a>
        </div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .upgrade-page {
    min-height: 100vh;
    background: var(--color-bg);
    padding: 2rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .loading-container,
  .error-container,
  .success-container {
    background: var(--color-card-bg);
    border-radius: 24px;
    padding: 3rem 2rem;
    text-align: center;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--color-border);
  }

  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid var(--color-border);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .error-icon,
  .success-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .error-container h1,
  .success-container h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 0.5rem;
  }

  .loading-container p {
    color: var(--color-text-body);
    margin-top: 1rem;
  }

  .error-container p,
  .success-container p {
    color: var(--color-text-body);
    margin-bottom: 1.5rem;
  }

  .back-link {
    display: inline-block;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--color-primary);
    border-radius: 12px;
    transition: all 0.2s;
  }

  .back-link:hover {
    background: var(--color-primary);
    color: white;
  }

  .upgrade-container {
    background: var(--color-card-bg);
    border-radius: 24px;
    padding: 2.5rem;
    max-width: 900px;
    width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--color-border);
  }

  .header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 0.5rem;
  }

  .subtitle {
    color: var(--color-text-body);
    font-size: 1.125rem;
    margin-bottom: 1rem;
  }

  .user-info {
    background: var(--color-bg);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
    font-size: 0.875rem;
    color: var(--color-text-body);
    border: 1px solid var(--color-border);
  }

  .user-info p {
    margin: 0.25rem 0;
  }

  .user-info strong {
    color: var(--color-text-heading);
  }

  .error-message {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .no-plans {
    text-align: center;
    padding: 3rem;
    color: var(--color-text-body);
  }

  .plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .plan-card {
    border: 2px solid var(--color-border);
    border-radius: 16px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    background: var(--color-card-bg);
    width: 100%;
    text-align: left;
    font-family: inherit;
    font-size: inherit;
  }

  .plan-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1);
  }

  .plan-card.selected {
    border-color: var(--color-primary);
    background: #f0fdf4;
  }

  .plan-header {
    margin-bottom: 1rem;
  }

  .badge {
    display: inline-block;
    background: var(--color-primary);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
  }

  .plan-card h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 0.5rem;
  }

  .price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 0.5rem;
  }

  .period {
    font-size: 1rem;
    font-weight: 400;
    color: var(--color-text-body);
  }

  .plan-description {
    color: var(--color-text-body);
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem 0;
  }

  .features-list li {
    padding: 0.5rem 0;
    color: var(--color-text-body);
    font-size: 0.875rem;
    position: relative;
    padding-left: 1.5rem;
  }

  .features-list li:before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--color-primary);
    font-weight: 700;
  }

  .select-indicator {
    margin-top: 1rem;
    text-align: center;
  }

  .selected-badge {
    display: inline-block;
    background: var(--color-primary);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 8px;
  }

  .actions {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid var(--color-border);
  }

  .upgrade-button {
    background: var(--color-primary);
    color: white;
    font-weight: 600;
    font-size: 1.125rem;
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    max-width: 400px;
    margin-bottom: 1rem;
  }

  .upgrade-button:hover:not(:disabled) {
    background: var(--color-primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
  }

  .upgrade-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .cancel-link {
    display: inline-block;
    color: var(--color-text-body);
    text-decoration: none;
    font-size: 0.875rem;
  }

  .cancel-link:hover {
    color: var(--color-text-heading);
  }

  @media (max-width: 640px) {
    .upgrade-page {
      padding: 1rem;
    }

    .upgrade-container {
      padding: 1.5rem;
    }

    .header h1 {
      font-size: 2rem;
    }

    .plans-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

