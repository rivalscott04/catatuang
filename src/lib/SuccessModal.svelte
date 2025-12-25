<script>
  import { onMount } from "svelte";

  export let phoneNumber = "";
  export let botNumber = "6281234567890"; // Default, akan diambil dari backend env
  export let isOpen = false;
  export let onClose = () => {};

  let isMobile = false;
  let redirectTimeout = null;

  onMount(() => {
    // Deteksi mobile device
    isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    ) || window.innerWidth <= 768;
  });

  // Reactive statement untuk handle auto-redirect saat modal dibuka
  $: if (isOpen && isMobile) {
    // Clear previous timeout jika ada
    if (redirectTimeout) {
      clearTimeout(redirectTimeout);
    }
    
    // Auto-redirect untuk mobile setelah 1.5 detik
    const whatsappUrl = `https://wa.me/${botNumber}?text=Halo`;
    redirectTimeout = setTimeout(() => {
      window.location.href = whatsappUrl;
    }, 1500);
  }

  // Cleanup timeout saat modal ditutup
  $: if (!isOpen && redirectTimeout) {
    clearTimeout(redirectTimeout);
    redirectTimeout = null;
  }

  function handleWhatsAppClick() {
    const whatsappUrl = `https://wa.me/${botNumber}?text=Halo`;
    window.open(whatsappUrl, "_blank", "noopener,noreferrer");
  }

  function handleBackToHome() {
    onClose();
    window.location.href = "/";
  }
</script>

{#if isOpen}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
  <div
    class="modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="success-title"
    on:click={onClose}
    on:keydown={(e) => {
      if (e.key === "Escape" || e.key === "Enter") {
        onClose();
      }
    }}
    tabindex="0"
  >
    <div class="modal-content" role="document" on:click|stopPropagation>
      <button class="modal-close" on:click={onClose} aria-label="Tutup">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <div class="success-container">
        <!-- Success Icon -->
        <div class="success-icon-wrapper">
          <div class="success-icon checkmark-animation">
            <svg class="checkmark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
        </div>

        <h1 id="success-title" class="success-title">Registrasi Berhasil!</h1>
        <p class="success-subtitle">
          Akun dengan nomor <span class="phone-highlight">{phoneNumber}</span> telah dibuat
        </p>

        <!-- Instructions Card -->
        <div class="instructions-card">
          <h2 class="instructions-title">Mulai Chat dengan Bot</h2>
          <p class="instructions-text">
            Kirim pesan "Halo" ke nomor WhatsApp bot kami untuk mulai menggunakan CatatBot.
          </p>

          <!-- Bot Number Display -->
          <div class="bot-number-display">
            <p class="bot-number-label">Nomor WhatsApp Bot:</p>
            <p class="bot-number-value">{botNumber}</p>
          </div>

          <!-- WhatsApp Button -->
          <button
            type="button"
            class="whatsapp-button"
            on:click={handleWhatsAppClick}
          >
            <svg class="whatsapp-icon" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
            Buka WhatsApp
          </button>

          <!-- Instructions List -->
          <div class="instructions-list">
            <p class="instructions-list-title">Cara menggunakan:</p>
            <ol class="instructions-steps">
              <li class="step-item">
                <span class="step-number">1.</span>
                <span>Klik tombol "Buka WhatsApp" di atas</span>
              </li>
              <li class="step-item">
                <span class="step-number">2.</span>
                <span>Kirim pesan "Halo" ke bot</span>
              </li>
              <li class="step-item">
                <span class="step-number">3.</span>
                <span>Mulai catat transaksi dengan format: "Makan 25rb" atau "Gaji 5jt"</span>
              </li>
            </ol>
          </div>
        </div>

        <!-- Back to Home -->
        <button type="button" class="back-button" on:click={handleBackToHome}>
          <svg class="back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Kembali ke halaman utama
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
    animation: fadeIn 0.2s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  .modal-content {
    background: #f8fafc;
    border-radius: 24px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    padding: 2rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    animation: slideUp 0.3s ease-out;
  }

  @keyframes slideUp {
    from {
      transform: translateY(20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f1f5f9;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s;
    z-index: 10;
  }

  .modal-close:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .success-container {
    text-align: center;
  }

  .success-icon-wrapper {
    margin-bottom: 2rem;
  }

  .success-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: #10b981;
    border-radius: 50%;
    margin-bottom: 1.5rem;
  }

  @keyframes checkmark {
    0% {
      transform: scale(0);
    }
    50% {
      transform: scale(1.2);
    }
    100% {
      transform: scale(1);
    }
  }

  .checkmark-animation {
    animation: checkmark 0.6s ease-out;
  }

  .checkmark {
    width: 48px;
    height: 48px;
    color: white;
  }

  .success-title {
    font-size: 2rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
  }

  .success-subtitle {
    color: #64748b;
    margin-bottom: 2rem;
    font-size: 0.95rem;
  }

  .phone-highlight {
    font-weight: 600;
    color: #0f172a;
  }

  .instructions-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    text-align: left;
  }

  .instructions-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.75rem;
  }

  .instructions-text {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .bot-number-display {
    background: #f0fdf4;
    border: 1px solid #d1fae5;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .bot-number-label {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .bot-number-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
  }

  .whatsapp-button {
    width: 100%;
    background: #25d366;
    color: white;
    font-weight: 600;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
  }

  .whatsapp-button:hover {
    background: #20ba5a;
    transform: translateY(-2px);
    box-shadow: 0 6px 10px -2px rgba(0, 0, 0, 0.15);
  }

  .whatsapp-icon {
    width: 20px;
    height: 20px;
  }

  .instructions-list {
    margin-top: 1.5rem;
  }

  .instructions-list-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0f172a;
    margin-bottom: 0.5rem;
  }

  .instructions-steps {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .step-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .step-number {
    color: #10b981;
    font-weight: 700;
    flex-shrink: 0;
  }

  .back-button {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: none;
    border: none;
    color: #64748b;
    font-size: 0.875rem;
    cursor: pointer;
    transition: color 0.2s;
    padding: 0.5rem;
    margin-top: 0.5rem;
  }

  .back-button:hover {
    color: #10b981;
  }

  .back-icon {
    width: 16px;
    height: 16px;
  }

  @media (max-width: 640px) {
    .modal-content {
      padding: 1.5rem;
      max-height: 95vh;
    }

    .success-title {
      font-size: 1.75rem;
    }

    .instructions-card {
      padding: 1.25rem;
    }
  }
</style>

