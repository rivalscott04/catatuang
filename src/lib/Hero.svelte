<script>
  import PhoneMockup from "./PhoneMockup.svelte";

  export let openRegisterModal;

  let phoneMockup;
</script>

<section class="hero-wrapper" id="hero">
  <div class="container hero-grid">
    <!-- Left Column: Copy -->
    <div class="hero-content">
      <h1>
        Catat keuangan.<br />
        <span class="highlight">Cukup lewat chat.</span>
      </h1>

      <p class="subheadline">
        Bot WhatsApp yang otomatis mencatat pemasukan dan pengeluaran harian
        tanpa ribet. Tidak perlu install aplikasi baru.
      </p>

      <div class="cta-group">
        <button class="btn btn-primary" on:click={() => openRegisterModal("free")}>
          Mulai Gratis
          <svg
            class="arrow-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            ></path></svg
          >
        </button>
        <button
          class="btn btn-secondary"
          on:click={() => phoneMockup.playDemo()}
        >
          Lihat Contoh
        </button>
      </div>

      <div class="trust-indicators">
        <div class="avatars">
          <!-- Simple CSS circles as placeholders for users -->
          <span class="av" style="background: #cbd5e1;"></span>
          <span class="av" style="background: #94a3b8;"></span>
          <span class="av" style="background: #64748b;"></span>
        </div>
        <span class="trust-text">Dipercaya 10.000+ pengguna aktif</span>
      </div>
    </div>

    <!-- Right Column: Visual -->
    <div class="hero-visual">
      <div class="blob"></div>
      <PhoneMockup bind:this={phoneMockup} />
    </div>
  </div>
</section>

<style>
  .hero-wrapper {
    width: 100%;
    min-height: 90vh; /* Almost full height but allows scroll hint */
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    padding-top: 40px;
    padding-bottom: 60px;
  }

  .hero-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
  }

  /* Typography */
  h1 {
    font-size: 3.5rem;
    line-height: 1.1;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
  }

  .highlight {
    color: var(--color-primary);
    position: relative;
    display: inline-block;
  }

  /* Subtle underline for highlight if needed, but color is enough */

  .subheadline {
    font-size: 1.25rem;
    color: var(--color-text-body);
    margin-bottom: 2.5rem;
    max-width: 500px;
    line-height: 1.6;
  }

  /* Buttons */
  .cta-group {
    display: flex;
    gap: 1rem;
    margin-bottom: 3rem;
  }

  .btn {
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .btn-primary {
    background: var(--color-primary);
    color: #fff;
    box-shadow:
      0 4px 6px -1px rgba(16, 185, 129, 0.2),
      0 2px 4px -1px rgba(16, 185, 129, 0.1);
  }

  .btn-primary:hover {
    background: var(--color-primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
  }

  .btn-secondary {
    background: transparent;
    color: var(--color-text-body);
    border: 1px solid #e2e8f0;
  }

  .btn-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
  }

  .arrow-icon {
    width: 18px;
  }

  /* Trust Indicators */
  .trust-indicators {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .avatars {
    display: flex;
  }
  .av {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #fff;
    margin-left: -10px;
  }
  .av:first-child {
    margin-left: 0;
  }

  .trust-text {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
  }

  /* Visual Column */
  .hero-visual {
    position: relative;
    display: flex;
    justify-content: center;
    /* Move it up a bit visually */
    margin-top: -20px;
  }

  /* Decorative Blob - nice subtle touch */
  .blob {
    position: absolute;
    width: 120%;
    height: 120%;
    background: radial-gradient(
      circle at 50% 50%,
      rgba(16, 185, 129, 0.15),
      transparent 70%
    );
    z-index: 1; /* Behind phone (phone uses z-index 10 in its container) */
    top: -10%;
    left: -10%;
    pointer-events: none;
  }

  /* Responsive */
  @media (max-width: 960px) {
    h1 {
      font-size: 2.5rem;
    }
    .hero-grid {
      grid-template-columns: 1fr;
      gap: 3rem;
      text-align: center;
    }
    .hero-content {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .hero-visual {
      margin-top: 0;
    }
    .subheadline {
      margin-left: auto;
      margin-right: auto;
    }
  }
</style>
