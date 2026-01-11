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
    const diff = expiredAt - now;
    
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
      if (expiredAt - now <= 0) {
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
      <div class="header">
        <h1>Checkout</h1>
        <p class="subtitle">Selesaikan pembayaran untuk upgrade paket Anda</p>
      </div>

      <div class="checkout-content">
        <!-- Order Summary -->
        <div class="order-summary">
          <h2>Ringkasan Pesanan</h2>
          <div class="summary-item">
            <span class="label">Paket</span>
            <span class="value">{getPlanName(checkoutData.plan)}</span>
          </div>
          <div class="summary-item">
            <span class="label">Harga</span>
            <span class="value">{formatPrice(checkoutData.amount)}</span>
          </div>
          {#if checkoutData.fee > 0}
            <div class="summary-item">
              <span class="label">Biaya Admin</span>
              <span class="value">{formatPrice(checkoutData.fee)}</span>
            </div>
          {/if}
          <div class="summary-divider"></div>
          <div class="summary-item total">
            <span class="label">Total Pembayaran</span>
            <span class="value">{formatPrice(checkoutData.total_payment)}</span>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="payment-section">
          <h2>Metode Pembayaran</h2>
          <div class="payment-method">
            <div class="payment-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <div class="payment-info">
              <h3>QRIS</h3>
              <p>Scan QR code dengan aplikasi e-wallet atau mobile banking Anda</p>
            </div>
          </div>

          {#if expiredAt}
            <div class="expiry-warning">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span>Waktu tersisa: <strong>{timeRemaining || formatTimeRemaining(expiredAt)}</strong></span>
            </div>
          {/if}

          <!-- QR Code -->
          <div class="qris-container">
            {#if paymentUrl}
              <div class="qris-iframe-wrapper">
                <iframe
                  src="{paymentUrl}"
                  class="qris-iframe"
                  frameborder="0"
                  allow="payment"
                  title="Pakasir Payment"
                ></iframe>
              </div>
              <div class="qris-fallback">
                <p>Jika QR code tidak muncul, klik tombol di bawah:</p>
                <a href={paymentUrl} target="_blank" rel="noopener noreferrer" class="payment-link-button">
                  Buka Halaman Pembayaran
                </a>
              </div>
            {/if}
          </div>

          <div class="payment-instructions">
            <h3>Cara Pembayaran:</h3>
            <ol>
              <li>Buka aplikasi e-wallet atau mobile banking Anda</li>
              <li>Pilih fitur Scan QR atau QRIS</li>
              <li>Scan QR code di atas</li>
              <li>Periksa nominal dan konfirmasi pembayaran</li>
              <li>Tunggu konfirmasi otomatis (halaman akan otomatis terupdate)</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="checkout-footer">
        <a href="/" class="cancel-link">Batalkan Pembayaran</a>
        <p class="footer-note">
          Setelah pembayaran berhasil, paket Anda akan otomatis di-upgrade
        </p>
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

  .checkout-content {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 2rem;
    margin-bottom: 2rem;
  }

  .order-summary {
    background: var(--color-bg);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
    height: fit-content;
  }

  .order-summary h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 1rem;
  }

  .summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
  }

  .summary-item.total {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 2px solid var(--color-border);
    font-size: 1.125rem;
    font-weight: 700;
  }

  .summary-item .label {
    color: var(--color-text-body);
  }

  .summary-item .value {
    color: var(--color-text-heading);
    font-weight: 600;
  }

  .summary-item.total .value {
    color: var(--color-primary);
    font-size: 1.25rem;
  }

  .summary-divider {
    height: 1px;
    background: var(--color-border);
    margin: 0.75rem 0;
  }

  .payment-section {
    background: var(--color-bg);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
  }

  .payment-section h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 1rem;
  }

  .payment-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-card-bg);
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid var(--color-border);
  }

  .payment-icon {
    width: 48px;
    height: 48px;
    background: var(--color-primary);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .payment-icon svg {
    width: 24px;
    height: 24px;
  }

  .payment-info h3 {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 0.25rem;
  }

  .payment-info p {
    font-size: 0.875rem;
    color: var(--color-text-body);
    margin: 0;
  }

  .expiry-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    color: #92400e;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
  }

  .expiry-warning svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
  }

  .qris-container {
    background: var(--color-card-bg);
    border-radius: 16px;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    border: 2px solid var(--color-border);
    min-height: 300px;
  }

  .qris-image {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
  }

  .qris-iframe-wrapper {
    width: 100%;
    height: 100%;
    min-height: 300px;
  }

  .qris-iframe {
    width: 100%;
    height: 100%;
    min-height: 300px;
    border: none;
    border-radius: 12px;
  }

  .qris-fallback {
    margin-top: 1rem;
    text-align: center;
    padding: 1rem;
    background: var(--color-bg);
    border-radius: 12px;
    width: 100%;
  }

  .qris-fallback p {
    font-size: 0.875rem;
    color: var(--color-text-body);
    margin-bottom: 0.75rem;
  }

  .payment-link-button {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: var(--color-primary);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s;
  }

  .payment-link-button:hover {
    background: var(--color-primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
  }

  .payment-instructions {
    background: var(--color-card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
  }

  .payment-instructions h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 1rem;
  }

  .payment-instructions ol {
    margin: 0;
    padding-left: 1.5rem;
    color: var(--color-text-body);
    font-size: 0.875rem;
    line-height: 1.8;
  }

  .payment-instructions li {
    margin-bottom: 0.5rem;
  }

  .checkout-footer {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid var(--color-border);
  }

  .cancel-link {
    display: inline-block;
    color: var(--color-text-body);
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
    transition: color 0.2s;
  }

  .cancel-link:hover {
    color: var(--color-text-heading);
  }

  .footer-note {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    margin: 0;
  }

  @media (max-width: 768px) {
    .checkout-page {
      padding: 1rem;
    }

    .checkout-container {
      padding: 1.5rem;
    }

    .checkout-content {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }

    .header h1 {
      font-size: 2rem;
    }

    .qris-container {
      min-height: 250px;
    }

    .qris-iframe-wrapper {
      min-height: 250px;
    }

    .qris-iframe {
      min-height: 250px;
    }
  }
</style>
