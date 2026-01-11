<script>
  import { onMount, onDestroy } from "svelte";

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

  // Get token and plan from URL
  let token = '';
  let plan = '';
  let loading = true;
  let error = '';
  let checkoutData = null;
  let paymentUrl = '';
  let qrCodeUrl = '';
  let expiredAt = null;
  let checkingPayment = false;
  let paymentStatus = 'pending'; // pending, completed, expired

  onMount(() => {
    const urlParams = new URLSearchParams(window.location.search);
    token = urlParams.get('token') || '';
    plan = urlParams.get('plan') || '';

    if (!token || !plan) {
      error = 'Token atau paket tidak ditemukan';
      loading = false;
      return;
    }

    initializeCheckout();
  });

  async function initializeCheckout() {
    try {
      loading = true;
      error = '';

      // Get checkout data from backend
      const response = await fetch(`${apiBaseUrl}/api/upgrade/checkout`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          token: token,
          plan: plan,
        }),
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        error = data.message || 'Gagal memuat halaman checkout';
        loading = false;
        return;
      }

      checkoutData = data.data;
      paymentUrl = data.data.payment_url;
      qrCodeUrl = data.data.qr_code_url || '';
      expiredAt = data.data.expired_at ? new Date(data.data.expired_at) : null;

      // Start polling payment status
      startPaymentPolling();

      loading = false;
    } catch (err) {
      console.error('Checkout initialization error:', err);
      error = 'Terjadi kesalahan saat memuat halaman checkout';
      loading = false;
    }
  }

  function startPaymentPolling() {
    // Poll every 5 seconds
    const pollInterval = setInterval(async () => {
      if (paymentStatus === 'completed' || paymentStatus === 'expired') {
        clearInterval(pollInterval);
        return;
      }

      await checkPaymentStatus();
    }, 5000);

    // Stop polling after 15 minutes (900 seconds)
    setTimeout(() => {
      clearInterval(pollInterval);
      if (paymentStatus === 'pending') {
        paymentStatus = 'expired';
      }
    }, 900000);
  }

  async function checkPaymentStatus() {
    if (checkingPayment) return;

    try {
      checkingPayment = true;
      const response = await fetch(`${apiBaseUrl}/api/upgrade/payment-status?token=${token}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
        },
      });

      const data = await response.json();

      if (data.success && data.data.status === 'completed') {
        paymentStatus = 'completed';
        checkingPayment = false;
        
        // Redirect to success page after 2 seconds
        setTimeout(() => {
          window.location.href = `/upgrade/success?token=${token}`;
        }, 2000);
      }
    } catch (err) {
      console.error('Payment status check error:', err);
    } finally {
      checkingPayment = false;
    }
  }

  function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(price);
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

  function formatTimeRemaining(expiredAt) {
    if (!expiredAt) return '';
    
    const now = new Date();
    const expired = expiredAt instanceof Date ? expiredAt : new Date(expiredAt);
    const diff = expired.getTime() - now.getTime();
    
    if (diff <= 0) return 'Kedaluwarsa';
    
    const minutes = Math.floor(diff / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  }

  let timeRemaining = '';
  let timerInterval = null;
  
  // Update timer when expiredAt changes
  $: if (expiredAt) {
    // Clear existing interval
    if (timerInterval) {
      clearInterval(timerInterval);
    }
    
    // Initial update
    timeRemaining = formatTimeRemaining(expiredAt);
    
    // Update every second
    timerInterval = setInterval(() => {
      timeRemaining = formatTimeRemaining(expiredAt);
      const now = new Date();
      const expired = expiredAt instanceof Date ? expiredAt : new Date(expiredAt);
      if (expired.getTime() - now.getTime() <= 0) {
        clearInterval(timerInterval);
        paymentStatus = 'expired';
        timeRemaining = 'Kedaluwarsa';
      }
    }, 1000);
  }
  
  // Cleanup on component destroy
  onDestroy(() => {
    if (timerInterval) {
      clearInterval(timerInterval);
    }
  });
</script>

<div class="checkout-page">
  {#if loading}
    <div class="loading-container">
      <div class="spinner"></div>
      <p>Memuat halaman checkout...</p>
    </div>
  {:else if error}
    <div class="error-container">
      <div class="error-icon">⚠️</div>
      <h1>Oops!</h1>
      <p>{error}</p>
      <a href="/" class="back-link">Kembali ke halaman utama</a>
    </div>
  {:else if paymentStatus === 'completed'}
    <div class="success-container">
      <div class="success-icon">✓</div>
      <h1>Pembayaran Berhasil!</h1>
      <p>Mengarahkan ke halaman sukses...</p>
    </div>
  {:else if checkoutData}
    <div class="checkout-container">
      <!-- Compact Header with Order Summary -->
      <div class="checkout-header">
        <div class="header-content">
          <h1>Bayar Sekarang</h1>
          <div class="order-summary-compact">
            <div class="summary-row">
              <span class="summary-label">Paket {getPlanName(checkoutData.plan)}</span>
              <span class="summary-total">{formatPrice(checkoutData.total_payment)}</span>
            </div>
            {#if checkoutData.fee > 0}
              <div class="summary-note">Termasuk biaya admin {formatPrice(checkoutData.fee)}</div>
            {/if}
          </div>
        </div>
        {#if expiredAt}
          <div class="timer-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>{timeRemaining || formatTimeRemaining(expiredAt)}</span>
          </div>
        {/if}
      </div>

      <!-- Main Payment Area -->
      <div class="payment-main">
        <!-- QR Code Section -->
        <div class="qris-section">
          <div class="qris-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>Scan QR Code untuk Bayar</span>
          </div>
          
          <div class="qris-wrapper">
            {#if paymentUrl}
              <iframe
                src="{paymentUrl}"
                class="qris-iframe"
                frameborder="0"
                allow="payment"
                title="QRIS Payment"
              ></iframe>
              <div class="qris-fallback">
                <a href={paymentUrl} target="_blank" rel="noopener noreferrer" class="open-payment-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                  </svg>
                  Buka di Halaman Baru
                </a>
              </div>
            {/if}
          </div>
        </div>

        <!-- Simple Instructions -->
        <div class="instructions">
          <div class="instruction-step">
            <div class="step-number">1</div>
            <span>Buka aplikasi e-wallet atau mobile banking</span>
          </div>
          <div class="instruction-step">
            <div class="step-number">2</div>
            <span>Scan QR code di atas</span>
          </div>
          <div class="instruction-step">
            <div class="step-number">3</div>
            <span>Konfirmasi pembayaran</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="checkout-footer">
        <p class="footer-note">
          Pembayaran akan dikonfirmasi otomatis setelah berhasil
        </p>
        <a href="/" class="cancel-link">Batalkan</a>
      </div>
    </div>
  {/if}
</div>

<style>
  .checkout-page {
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

  .checkout-container {
    background: var(--color-card-bg);
    border-radius: 24px;
    padding: 2rem;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--color-border);
  }

  .checkout-header {
    text-align: center;
    margin-bottom: 2rem;
    position: relative;
  }

  .header-content h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 1rem;
  }

  .order-summary-compact {
    background: var(--color-bg);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    border: 1px solid var(--color-border);
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .summary-label {
    color: var(--color-text-body);
    font-size: 0.95rem;
  }

  .summary-total {
    color: var(--color-primary);
    font-size: 1.5rem;
    font-weight: 700;
  }

  .summary-note {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    margin-top: 0.5rem;
    text-align: right;
  }

  .timer-badge {
    position: absolute;
    top: 0;
    right: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 20px;
    color: #92400e;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .timer-badge svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }

  .payment-main {
    margin-bottom: 2rem;
  }

  .qris-section {
    margin-bottom: 2rem;
  }

  .qris-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    color: var(--color-text-heading);
    font-weight: 600;
    font-size: 0.95rem;
  }

  .qris-label svg {
    width: 20px;
    height: 20px;
    color: var(--color-primary);
  }

  .qris-wrapper {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 2px solid var(--color-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 320px;
    position: relative;
  }

  .qris-iframe {
    width: 100%;
    min-height: 300px;
    border: none;
    border-radius: 12px;
    background: white;
  }

  .qris-fallback {
    margin-top: 1rem;
    width: 100%;
  }

  .open-payment-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: var(--color-primary);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
    width: 100%;
    justify-content: center;
  }

  .open-payment-btn svg {
    width: 18px;
    height: 18px;
  }

  .open-payment-btn:hover {
    background: var(--color-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .instructions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .instruction-step {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--color-bg);
    border-radius: 10px;
    font-size: 0.9rem;
    color: var(--color-text-body);
  }

  .step-number {
    width: 28px;
    height: 28px;
    background: var(--color-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
  }

  .checkout-footer {
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-border);
  }

  .footer-note {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    margin: 0 0 1rem 0;
    line-height: 1.5;
  }

  .cancel-link {
    display: inline-block;
    color: var(--color-text-body);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.2s;
  }

  .cancel-link:hover {
    color: var(--color-text-heading);
  }

  @media (max-width: 768px) {
    .checkout-page {
      padding: 1rem;
    }

    .checkout-container {
      padding: 1.5rem;
      max-width: 100%;
    }

    .checkout-header {
      margin-bottom: 1.5rem;
    }

    .header-content h1 {
      font-size: 1.5rem;
    }

    .timer-badge {
      position: static;
      margin: 0.75rem auto 0;
      display: inline-flex;
    }

    .qris-wrapper {
      min-height: 280px;
      padding: 1rem;
    }

    .qris-iframe {
      min-height: 260px;
    }

    .summary-total {
      font-size: 1.25rem;
    }

    .instructions {
      gap: 0.5rem;
    }

    .instruction-step {
      font-size: 0.85rem;
      padding: 0.625rem;
    }
  }
</style>
