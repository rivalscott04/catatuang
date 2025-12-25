<script>
  import Hero from "./lib/Hero.svelte";
  import Terms from "./lib/Terms.svelte";
  import Privacy from "./lib/Privacy.svelte";
  import SuccessModal from "./lib/SuccessModal.svelte";
  import { onMount } from "svelte";

  // API helper - same as admin
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
      return fallbackUrl;
    }
    
    // Default fallback: use relative URLs
    return '';
  };

  const apiBaseUrl = getApiBaseUrl();

  let showRegisterModal = false;
  let showSuccessModal = false;
  let phoneNumber = "";
  let name = "";
  let error = "";
  let loading = false;
  let selectedPlan = "free"; // free, pro, vip
  let isPlanLocked = false; // true jika plan sudah ditentukan dari tombol
  let currentPage = "home"; // home, syarat, privasi
  let registeredPhone = "";
  let botNumber = "6281234567890"; // Default, akan diambil dari API saat mount
  let pricings = [];
  let pricingLoading = true;

  // Handle hash routing
  function handleHashChange() {
    const hash = window.location.hash;
    if (hash === "#syarat" || hash === "#syarat-ketentuan") {
      currentPage = "syarat";
    } else if (hash === "#privasi" || hash === "#kebijakan-privasi") {
      currentPage = "privasi";
    } else {
      currentPage = "home";
    }
  }

  onMount(() => {
    handleHashChange();
    window.addEventListener("hashchange", handleHashChange);
    
    // Fetch bot number from backend
    (async () => {
      try {
        const response = await fetch(`${apiBaseUrl}/api/bot-number`, {
          method: "GET",
          credentials: "include",
        });
        if (response.ok) {
          const data = await response.json();
          botNumber = data.bot_number || "6281234567890";
        }
      } catch (err) {
        console.error("Failed to fetch bot number:", err);
        // Keep default value
      }
    })();

    // Fetch pricing from backend
    (async () => {
      pricingLoading = true;
      try {
        const response = await fetch(`${apiBaseUrl}/api/pricing`, {
          method: "GET",
          credentials: "include",
          headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
          },
        });
        
        if (response.ok) {
          const data = await response.json();
          console.log("Pricing API response:", data);
          console.log("Response data type:", typeof data.data, "Is array:", Array.isArray(data.data));
          
          if (data.success) {
            if (data.data && Array.isArray(data.data)) {
              // Ensure features is always an array
              pricings = data.data.map(p => ({
                ...p,
                features: Array.isArray(p.features) ? p.features : (p.features ? [p.features] : [])
              }));
              console.log("Pricing data loaded:", pricings);
              console.log("Number of pricings:", pricings.length);
            } else {
              console.warn("Pricing data is not an array:", data.data);
              pricings = [];
            }
          } else {
            console.error("API returned success=false:", data);
            pricings = [];
          }
        } else {
          const errorText = await response.text();
          console.error("Failed to fetch pricing, status:", response.status, errorText);
          pricings = [];
        }
      } catch (err) {
        console.error("Failed to fetch pricing:", err);
        pricings = [];
      } finally {
        pricingLoading = false;
      }
    })();
    
    return () => {
      window.removeEventListener("hashchange", handleHashChange);
    };
  });

  function openRegisterModal(plan = null) {
    // Jika plan tidak ditentukan (null), user bisa pilih sendiri
    if (plan === null) {
      selectedPlan = "free";
      isPlanLocked = false;
    } else {
      // Jika plan ditentukan, lock dan set plan tersebut
      selectedPlan = plan;
      isPlanLocked = true;
    }
    showRegisterModal = true;
    document.body.style.overflow = "hidden";
  }

  function closeRegisterModal() {
    showRegisterModal = false;
    document.body.style.overflow = "";
    phoneNumber = "";
    name = "";
    error = "";
    selectedPlan = "free";
    isPlanLocked = false;
  }

  function closeSuccessModal() {
    showSuccessModal = false;
    document.body.style.overflow = "";
    registeredPhone = "";
  }

  function getPlanName(plan) {
    const plans = {
      free: "Trial 3 Hari (Gratis)",
      pro: "Pro (Rp 29rb/bulan)",
      vip: "VIP (Rp 79rb/bulan)",
    };
    return plans[plan] || plans.free;
  }

  function formatPrice(price) {
    if (price === 0) return "Gratis";
    if (price >= 1000) {
      return `${Math.floor(price / 1000)}rb`;
    }
    return price.toString();
  }

  function getPricingByPlan(plan) {
    return pricings.find(p => p.plan === plan);
  }

  async function getCsrfToken() {
    try {
      const response = await fetch(`${apiBaseUrl}/csrf-token`, {
        method: "GET",
        credentials: "include",
      });
      const data = await response.json();
      return data.token;
    } catch (err) {
      console.error("Failed to get CSRF token:", err);
      return null;
    }
  }

  async function handleRegister(e) {
    e.preventDefault();
    error = "";
    loading = true;

    try {
      // Validate phone number
      if (!phoneNumber || phoneNumber.trim() === "") {
        error = "Nomor WhatsApp wajib diisi";
        loading = false;
        return;
      }

      // Validate name
      if (!name || name.trim() === "") {
        error = "Nama wajib diisi";
        loading = false;
        return;
      }

      const headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      };

      // Clean phone number (remove spaces, dashes, etc.)
      const cleanPhone = phoneNumber.replace(/\s+/g, "").replace(/-/g, "");

      const response = await fetch(`${apiBaseUrl}/api/register`, {
        method: "POST",
        headers: headers,
        credentials: "include",
        body: JSON.stringify({
          phone_number: cleanPhone,
          name: name.trim(),
          plan: selectedPlan,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        // Close register modal
        closeRegisterModal();
        
        // Set registered phone and show success modal
        registeredPhone = cleanPhone;
        showSuccessModal = true;
        document.body.style.overflow = "hidden";
      } else {
        // Handle validation errors
        if (data.errors) {
          const firstError = Object.values(data.errors)[0];
          error = Array.isArray(firstError) ? firstError[0] : firstError;
        } else {
          error = data.message || "Terjadi kesalahan. Silakan coba lagi.";
        }
      }
    } catch (err) {
      console.error("Registration error:", err);
      error = "Terjadi kesalahan. Silakan coba lagi.";
    } finally {
      loading = false;
    }
  }

  // Close modal on escape key
  onMount(() => {
    function handleEscape(e) {
      if (e.key === "Escape" && showRegisterModal) {
        closeRegisterModal();
      }
    }
    window.addEventListener("keydown", handleEscape);
    return () => window.removeEventListener("keydown", handleEscape);
  });
</script>

<div class="app-container">
  <nav class="navbar">
    <div class="container nav-content">
      <a href="#hero" class="logo">
        <div class="logo-icon">
          <img src="/catatuang_logo.svg" alt="CatatUang Logo" />
        </div>
      </a>

      <div class="nav-links">
        <a href="#features">Fitur</a>
        <a href="#testimonials">Testimoni</a>
        <a href="#pricing">Harga</a>
        <a href="#faq">FAQ</a>
        <button class="btn-nav" on:click={() => openRegisterModal(null)}>Daftar</button>
      </div>

      <!-- Mobile Menu Icon (Placeholder) -->
      <div class="mobile-menu-btn">
        <svg
          viewBox="0 0 24 24"
          width="24"
          height="24"
          stroke="currentColor"
          stroke-width="2"
          fill="none"
          ><line x1="3" y1="12" x2="21" y2="12"></line><line
            x1="3"
            y1="6"
            x2="21"
            y2="6"
          ></line><line x1="3" y1="18" x2="21" y2="18"></line></svg
        >
      </div>
    </div>
  </nav>

  <main>
    {#if currentPage === "syarat"}
      <Terms />
    {:else if currentPage === "privasi"}
      <Privacy />
    {:else}
      <Hero {openRegisterModal} />

      <section id="features" class="section">
      <div class="container">
        <h2>
          Pintar Atur Uang.<br /><span class="highlight">Tanpa Ribet.</span>
        </h2>
        <div class="grid-3">
          <div class="feature-card">
            <div class="icon-box">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="w-8 h-8"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                ><path d="M12 20h9" /><path
                  d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"
                /></svg
              >
            </div>
            <h3>Pencatatan Instan</h3>
            <p>
              Cukup ketik "Makan 20rb" atau "Gaji 5jt", bot langsung mencatat
              detiknya juga. Semudah chatting dengan teman.
            </p>
          </div>
          <div class="feature-card">
            <div class="icon-box">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="w-8 h-8"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                ><rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line
                  x1="16"
                  y1="2"
                  x2="16"
                  y2="6"
                /><line x1="8" y1="2" x2="8" y2="6" /><line
                  x1="3"
                  y1="10"
                  x2="21"
                  y2="10"
                /></svg
              >
            </div>
            <h3>Laporan Harian</h3>
            <p>
              Setiap malam, dapatkan ringkasan pemasukan & pengeluaran. Langsung
              tahu sisa saldo tanpa perlu buka aplikasi banking.
            </p>
          </div>
          <div class="feature-card">
            <div class="icon-box">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="w-8 h-8"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                ><path d="M21.21 15.89A10 10 0 1 1 8 2.83" /><path
                  d="M22 12A10 10 0 0 0 12 2v10z"
                /></svg
              >
            </div>
            <h3>Analisis Cerdas</h3>
            <p>
              Kategori otomatis mendeteksi kebutuhan vs keinginan. Pantau
              kesehatan finansialmu dengan grafik visual yang simpel.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="section alt-bg">
      <div class="container message-container">
        <div class="text-center mb-12">
          <h2>Kata Mereka yang Sudah Hemat</h2>
          <p class="subheadline mx-auto">
            Bergabung dengan ribuan pengguna yang sudah memperbaiki kesehatan
            finansial mereka.
          </p>
        </div>
        <div class="grid-3">
          <!-- Testimonial 1 -->
          <div class="testimonial-card">
            <div class="stars">★★★★★</div>
            <p class="quote">
              "Dulu males banget catat pengeluaran karena harus buka aplikasi
              berat. Pake CatatBot, tinggal chat kayak curhat ke temen, beres!"
            </p>
            <div class="user-info">
              <span class="user-name">Sarah Wijaya</span>
              <span class="user-role">Freelancer</span>
            </div>
          </div>
          <!-- Testimonial 2 -->
          <div class="testimonial-card">
            <div class="stars">★★★★★</div>
            <p class="quote">
              "Fitur 'Sisa Saldo' nya ngebantu banget buat ngerem jajan di akhir
              bulan. Wajib coba buat anak kos!"
            </p>
            <div class="user-info">
              <span class="user-name">Budi Santoso</span>
              <span class="user-role">Mahasiswa</span>
            </div>
          </div>
          <!-- Testimonial 3 -->
          <div class="testimonial-card">
            <div class="stars">★★★★★</div>
            <p class="quote">
              "Simpel banget. Suami juga jadi rajin lapor pengeluaran belanja
              karena gampang pakenya. Top banget."
            </p>
            <div class="user-info">
              <span class="user-name">Rina Amelia</span>
              <span class="user-role">Ibu Rumah Tangga</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="section">
      <div class="container pl-container">
        <div class="pricing-header">
          <h2>Investasi untuk Masa Depanmu</h2>
          <p class="pricing-sub">
            Mulai atur keuangan tanpa biaya, upgrade kapan saja sesuai
            kebutuhanmu.
          </p>
        </div>

        {#if pricingLoading}
          <div class="pricing-loading">
            <p>Memuat data pricing...</p>
          </div>
        {:else if pricings.length === 0}
          <div class="pricing-loading">
            <p>Tidak ada data pricing tersedia</p>
          </div>
        {:else}
          <div class="pricing-wrapper three-col">
            {#each pricings as pricing}
              {@const isPro = pricing.plan === 'pro'}
              {@const isFree = pricing.plan === 'free'}
              {@const planName = pricing.plan === 'free' ? 'Trial 3 Hari' : pricing.plan.toUpperCase()}
              {@const buttonText = pricing.plan === 'free' ? 'Mulai Trial' : `Upgrade ke ${planName}`}
              
              <div class="pricing-card {isPro ? 'pro-card' : 'plain'}">
                {#if isPro}
                  <div class="popular-tag">Populer</div>
                {/if}
                <div class="card-header">
                  <h3 class="plan-name {isPro ? 'text-white' : ''}">{planName}</h3>
                  <div class="price-container {isPro ? 'text-white' : ''}">
                    {#if isFree}
                      <span class="currency">Gratis</span>
                    {:else}
                      <span class="currency">Rp</span>
                      <span class="amount">{formatPrice(pricing.price)}</span>
                      <span class="frequency {isPro ? 'text-white-opacity' : ''}">/bulan</span>
                    {/if}
                  </div>
                  <p class="description {isPro ? 'text-white-opacity' : ''}">
                    {pricing.description || '-'}
                  </p>
                </div>
                <div class="divider {isPro ? 'opacity-20' : ''}"></div>
                <ul class="features-list {isPro ? 'text-white' : ''}">
                  {#if pricing.features && pricing.features.length > 0}
                    {#each pricing.features as feature}
                      <li>
                        <span class="check-icon {isPro ? 'pro' : isFree ? 'basic' : ''}"
                          ><svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                            ><polyline points="20 6 9 17 4 12"></polyline></svg
                          ></span
                        >
                        {feature}
                      </li>
                    {/each}
                  {:else}
                    <li>Tidak ada fitur tersedia</li>
                  {/if}
                </ul>
                <button 
                  class="btn-block {isPro ? 'btn-primary-bright' : 'btn-outline'}" 
                  on:click={() => openRegisterModal(pricing.plan)}
                >
                  {buttonText}
                </button>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </section>

    <section id="faq" class="section">
      <div class="container narrow-container">
        <h2>Sering Ditanyakan</h2>
        <div class="faq-grid">
          <div class="faq-item">
            <h4>Apakah data saya aman?</h4>
            <p>
              Ya, kami menggunakan enkripsi end-to-end standar industri. Kami
              tidak bisa membaca detail pesan chat personalmu, hanya pesan yang
              dikirim ke bot.
            </p>
          </div>
          <div class="faq-item">
            <h4>Cara mulainya bagaimana?</h4>
            <p>
              Cukup klik tombol "Mulai Trial" dan kirim pesan "Halo" ke nomor
              bot kami.
            </p>
          </div>
          <div class="faq-item">
            <h4>Bagaimana jika masa trial habis?</h4>
            <p>
              Anda bisa memilih untuk upgrade ke Pro atau VIP. Data Anda akan
              tetap aman tersimpan.
            </p>
          </div>
        </div>
      </div>
    </section>

    {/if}

    <footer class="footer">
      <div class="container">
        <div class="footer-grid">
          <!-- Column 1: Brand -->
          <div class="footer-brand">
            <div class="footer-logo">
              <div class="logo-icon">
                <img src="/catatuang_logo.svg" alt="CatatUang Logo" />
              </div>
            </div>
            <p class="footer-tagline">
              Asisten keuangan pribadi di dalam WhatsApp kamu. Catat, pantau,
              dan hemat lebih banyak mulai hari ini.
            </p>
            <div class="footer-socials">
              <a
                href="https://instagram.com"
                aria-label="Instagram"
                target="_blank"
              >
                <svg
                  viewBox="0 0 24 24"
                  width="20"
                  height="20"
                  fill="currentColor"
                >
                  <path
                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                  />
                </svg>
              </a>
              <a
                href="https://twitter.com"
                aria-label="Twitter"
                target="_blank"
              >
                <svg
                  viewBox="0 0 24 24"
                  width="20"
                  height="20"
                  fill="currentColor"
                >
                  <path
                    d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"
                  />
                </svg>
              </a>
              <a
                href="https://linkedin.com"
                aria-label="LinkedIn"
                target="_blank"
              >
                <svg
                  viewBox="0 0 24 24"
                  width="20"
                  height="20"
                  fill="currentColor"
                >
                  <path
                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
                  />
                </svg>
              </a>
            </div>
          </div>

          <!-- Column 2: Produk -->
          <div class="footer-column">
            <h4>Produk</h4>
            <ul>
              <li><a href="#features">Fitur</a></li>
              <li><a href="#pricing">Harga</a></li>
              <li><a href="#testimonials">Testimoni</a></li>
              <li><a href="#faq">FAQ</a></li>
            </ul>
          </div>

          <!-- Column 3: Perusahaan -->
          <div class="footer-column">
            <h4>Perusahaan</h4>
            <ul>
              <li><a href="#privasi">Kebijakan Privasi</a></li>
              <li><a href="#syarat">Syarat & Ketentuan</a></li>
            </ul>
          </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
          <p class="copyright">
            © {new Date().getFullYear()} CatatBot Indonesia. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </main>

  <!-- Register Modal -->
  {#if showRegisterModal}
    <!-- svelte-ignore a11y-click-events-have-key-events -->
    <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
    <div 
      class="modal-overlay" 
      role="dialog" 
      aria-modal="true"
      aria-labelledby="modal-title"
      on:click={closeRegisterModal} 
      on:keydown={(e) => e.key === "Escape" && closeRegisterModal()}
      tabindex="-1"
    >
      <div 
        class="modal-content"
        role="document"
        on:click|stopPropagation
      >
        <button class="modal-close" on:click={closeRegisterModal} aria-label="Tutup">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>

        <div class="modal-header">
          <div class="modal-logo">
            <img src="/catatuang_logo.svg" alt="CatatUang Logo" />
          </div>
          <h2 id="modal-title" class="modal-title">Daftar Sekarang</h2>
          <p class="modal-subtitle">Mulai atur keuanganmu dengan mudah via WhatsApp</p>
        </div>

        <form on:submit={handleRegister} class="modal-form">
          {#if error}
            <div class="error-message">{error}</div>
          {/if}

          <!-- Plan Selection -->
          <div class="form-group">
            <label for="plan_select" class="form-label">
              Pilih Paket {#if isPlanLocked}<span class="form-hint">(Tidak dapat diubah)</span>{/if}
            </label>
            {#if isPlanLocked}
              <!-- Readonly display jika plan sudah ditentukan -->
              <div class="plan-display">
                <div class="plan-badge plan-badge-{selectedPlan}">
                  {getPlanName(selectedPlan)}
                </div>
                <p class="form-hint">
                  {#if selectedPlan === "free"}
                    Mulai dengan trial gratis 3 hari, upgrade kapan saja
                  {:else if selectedPlan === "pro"}
                    Paket Pro dengan fitur lengkap untuk analisis mendalam
                  {:else if selectedPlan === "vip"}
                    Paket VIP dengan prioritas dan fitur premium
                  {/if}
                </p>
              </div>
            {:else}
              <!-- Dropdown untuk pilih paket jika dari tombol "Daftar" -->
              <select
                id="plan_select"
                bind:value={selectedPlan}
                class="form-input plan-select"
                disabled={loading}
              >
                <option value="free">Trial 3 Hari (Gratis)</option>
                <option value="pro">Pro (Rp 29rb/bulan)</option>
                <option value="vip">VIP (Rp 79rb/bulan)</option>
              </select>
              <p class="form-hint">
                {#if selectedPlan === "free"}
                  Mulai dengan trial gratis 3 hari, upgrade kapan saja
                {:else if selectedPlan === "pro"}
                  Paket Pro dengan fitur lengkap untuk analisis mendalam
                {:else if selectedPlan === "vip"}
                  Paket VIP dengan prioritas dan fitur premium
                {/if}
              </p>
            {/if}
          </div>

          <div class="form-group">
            <label for="phone_number" class="form-label">
              Nomor WhatsApp <span class="required">*</span>
            </label>
            <div class="phone-input-wrapper">
              <span class="phone-prefix">+62</span>
              <input
                type="tel"
                id="phone_number"
                bind:value={phoneNumber}
                placeholder="81234567890"
                class="form-input"
                required
                disabled={loading}
                aria-required="true"
              />
            </div>
            <p class="form-hint">Masukkan nomor WhatsApp tanpa +62 (contoh: 81234567890)</p>
          </div>

          <div class="form-group">
            <label for="name" class="form-label">
              Nama <span class="required">*</span>
            </label>
            <input
              type="text"
              id="name"
              bind:value={name}
              placeholder="Nama lengkap"
              class="form-input"
              required
              disabled={loading}
              aria-required="true"
            />
            <p class="form-hint">Nama akan digunakan untuk personalisasi pesan bot</p>
          </div>

          <button type="submit" class="btn-submit" disabled={loading}>
            {loading ? "Mendaftar..." : "Daftar Sekarang"}
          </button>
        </form>

        <p class="modal-footer-text">
          Dengan mendaftar, Anda menyetujui syarat & ketentuan kami
        </p>
      </div>
    </div>
  {/if}

  <!-- Success Modal -->
  <SuccessModal
    isOpen={showSuccessModal}
    phoneNumber={registeredPhone}
    botNumber={botNumber}
    onClose={closeSuccessModal}
  />
</div>

<style>
  .app-container {
    min-height: 100vh;
    background-color: var(--color-bg);
    width: 100%;
    overflow-x: hidden;
  }

  .navbar {
    height: 70px;
    display: flex;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  }

  .nav-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--color-text-heading);
  }

  .logo-icon {
    color: var(--color-primary);
    width: 225px;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .logo-icon img {
    width: 100%;
    height: auto;
    object-fit: contain;
  }

  .nav-links {
    display: flex;
    align-items: center;
    gap: 2rem;
  }

  .nav-links a {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--color-text-body);
    transition: color 0.2s;
  }

  .nav-links a:hover {
    color: var(--color-primary);
  }

  .btn-nav {
    padding: 8px 20px;
    background: var(--color-primary);
    color: #fff !important;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
    border: none;
    cursor: pointer;
  }

  .btn-nav:hover {
    background: var(--color-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 10px -2px rgba(16, 185, 129, 0.3);
    color: #fff !important;
  }

  .mobile-menu-btn {
    display: none;
    cursor: pointer;
  }

  @media (max-width: 768px) {
    .nav-links {
      display: none;
    }
    .mobile-menu-btn {
      display: block;
    }
    
    .logo-icon {
      width: 150px;
      max-width: 100%;
    }
  }

  /* Utility & Section Styles */
  :global(html) {
    scroll-behavior: smooth;
    scroll-padding-top: 80px; /* Offset for sticky navbar */
  }

  .section {
    padding: 4rem 0;
    border-top: 1px solid #f1f5f9;
  }

  /* First section needs no top border usually, but let's keep it for consistency or remove */
  #features {
    border-top: none;
  } /* Assuming Hero flows into Features, or Features is first content block */

  .alt-bg {
    /* Subtle Minty Gradient */
    background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 100%);
    border-top: 1px solid #dcfce7;
    border-bottom: 1px solid #dcfce7;
  }

  h2 {
    font-size: 3rem;
    color: var(--color-text-heading);
    margin-bottom: 4rem;
    text-align: center;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.02em;
  }

  .highlight {
    color: var(--color-primary);
    background: linear-gradient(to right, #10b981, #059669);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Features Grid */
  .grid-3 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .feature-card {
    background: #fff;
    padding: 2rem;
    border-radius: 20px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    transition: all 0.3s ease;
  }

  .feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    border-color: #e2e8f0;
  }

  .icon-box {
    width: 56px;
    height: 56px;
    background: #ecfdf5;
    color: #10b981;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
  }

  .icon-box svg {
    width: 28px;
    height: 28px;
  }

  .feature-card h3 {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
    color: var(--color-text-heading);
    font-weight: 700;
  }

  .feature-card p {
    color: var(--color-text-body);
    font-size: 1rem;
    line-height: 1.6;
  }

  /* Pricing Styles */
  .pl-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
  }

  .pricing-loading {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--color-text-body);
  }

  .pricing-header {
    text-align: center;
    margin-bottom: 4rem;
    max-width: 600px;
  }

  .pricing-sub {
    font-size: 1.1rem;
    color: #64748b;
    margin-top: 1rem;
  }

  .pricing-card {
    background: #fff;
    border-radius: 24px;
    width: 100%;
    flex: 1;
    min-width: 280px;
    max-width: 360px;
    position: relative;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease;
  }

  .pricing-card.plain {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
  }

  .popular-tag {
    /* Adjusted for Pro Card */
    background: #d1fae5;
    color: #064e3b;
    text-align: center;
    padding: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-radius: 20px 20px 0 0;
    margin-bottom: -10px;
    position: relative;
    z-index: 1;
  }

  .card-header {
    padding: 1.75rem 2rem 0;
    text-align: left;
  }

  .plan-name {
    font-size: 1.2rem;
    color: #0f172a;
    margin-bottom: 1rem;
    font-weight: 600;
  }

  .price-container {
    display: flex;
    align-items: baseline;
    justify-content: flex-start;
    margin-bottom: 0.75rem;
    color: #0f172a;
  }

  .currency {
    font-size: 1.75rem;
    font-weight: 700;
    margin-right: 4px;
  }

  .amount {
    font-size: 3rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1;
  }

  .frequency {
    font-size: 1rem;
    color: #64748b;
    margin-left: 4px;
    font-weight: 400;
  }

  .description {
    color: #64748b;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
  }

  .divider {
    height: 1px;
    background: #f1f5f9;
    width: 100%;
  }

  .features-list {
    padding: 1.5rem 2rem;
    list-style: none;
    text-align: left;
  }

  .features-list li {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    color: #334155;
    font-size: 0.9rem;
    line-height: 1.4;
    gap: 0.5rem;
  }

  .check-icon {
    width: 20px;
    height: 20px;
    background: #d1fae5;
    color: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .check-icon svg {
    width: 14px;
    height: 14px;
  }

  .btn-block {
    width: calc(100% - 4rem);
    margin: 0 2rem 2rem;
    padding: 14px;
    font-weight: 700;
    border-radius: 12px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
  }

  .btn-outline {
    background: transparent;
    color: var(--color-primary);
    border: 2px solid #e2e8f0;
    box-shadow: none;
  }

  .btn-outline:hover {
    border-color: var(--color-primary);
    background: #f0fdf4;
    color: var(--color-primary-hover);
    transform: translateY(-2px);
  }

  /* FAQ */
  .narrow-container {
    max-width: 800px;
  }

  .faq-grid {
    display: grid;
    gap: 1.5rem;
  }

  .faq-item {
    background: #fff;
    padding: 2rem;
    border-radius: 20px;
    border: 1px solid #f1f5f9;
  }

  .faq-item h4 {
    font-size: 1.15rem;
    margin-bottom: 0.75rem;
    color: var(--color-text-heading);
    font-weight: 600;
  }

  .faq-item p {
    color: var(--color-text-body);
  }

  .footer {
    padding: 4rem 0 2rem;
    background: #fff;
    border-top: 1px solid #f1f5f9;
  }

  .footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
  }

  .footer-brand {
    max-width: 350px;
  }

  .footer-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 1rem;
  }

  .footer-logo .logo-icon {
    width: 140px;
    height: auto;
  }

  .footer-logo-text {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--color-text-heading);
  }

  .footer-tagline {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
  }

  .footer-socials {
    display: flex;
    gap: 1rem;
  }

  .footer-socials a {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    transition: all 0.2s;
  }

  .footer-socials a:hover {
    background: var(--color-primary);
    color: #fff;
    transform: translateY(-2px);
  }

  .footer-column h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text-heading);
    margin-bottom: 1rem;
  }

  .footer-column ul {
    list-style: none;
    padding: 0;
  }

  .footer-column li {
    margin-bottom: 0.75rem;
  }

  .footer-column a {
    color: #64748b;
    font-size: 0.95rem;
    transition: color 0.2s;
  }

  .footer-column a:hover {
    color: var(--color-primary);
  }

  .footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 2rem;
    border-top: 1px solid #f1f5f9;
  }

  .copyright {
    color: #94a3b8;
    font-size: 0.875rem;
  }

  /* Testimonials */
  .message-container {
    max-width: 1200px;
  }
  .text-center {
    text-align: center;
  }
  .mb-12 {
    margin-bottom: 3rem;
  }
  .mx-auto {
    margin-left: auto;
    margin-right: auto;
  }
  .testimonial-card {
    background: #fff;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    border: 1px solid #f1f5f9;
    transition: transform 0.2s;
  }
  .testimonial-card:hover {
    transform: translateY(-5px);
  }
  .stars {
    color: #fbbf24; /* Amber 400 */
    font-size: 1.2rem;
    margin-bottom: 1rem;
    letter-spacing: 2px;
  }
  .quote {
    font-style: italic;
    color: #475569;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    position: relative;
  }
  /* Optional: Could add a quote icon ::before */
  .quote::before {
    content: "“";
    font-size: 4rem;
    color: #e2e8f0;
    position: absolute;
    top: -2rem;
    left: -1rem;
    font-family: serif;
    opacity: 0.5;
    z-index: 0;
  }
  .user-info {
    border-top: 1px solid #f1f5f9;
    padding-top: 1rem;
    display: flex;
    flex-direction: column;
  }
  .user-name {
    font-weight: 700;
    color: #0f172a;
  }
  .user-role {
    font-size: 0.875rem;
    color: #64748b;
  }

  /* Pricing Revisions */
  .pricing-wrapper.three-col {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
    width: 100%;
    max-width: 1100px;
    align-items: stretch;
  }

  .pricing-card.pro-card {
    background: #064e3b; /* Emerald 900 */
    border: none;
    color: #fff;
    box-shadow:
      0 20px 25px -5px rgba(0, 0, 0, 0.1),
      0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  .pricing-card.pro-card .plan-name {
    color: #fff;
  }

  .pricing-card.pro-card .features-list li {
    color: rgba(255, 255, 255, 0.95);
  }

  /* Text Utils for Pro Card */
  .text-white {
    color: #fff !important;
  }
  .text-white-opacity {
    color: rgba(255, 255, 255, 0.7);
  }
  .opacity-20 {
    opacity: 0.2;
  }

  .check-icon.basic {
    background: #d1fae5;
    color: #10b981;
  }

  .check-icon.pro {
    background: rgba(255, 255, 255, 0.1);
    color: #34d399; /* Emerald 400 */
  }

  /* Buttons */
  .btn-primary-bright {
    width: calc(100% - 5rem);
    margin: 0 2.5rem 2.5rem;
    padding: 16px;
    font-weight: 700;
    border-radius: 16px;
    font-size: 1rem;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;

    background: #10b981; /* Default primary */
    color: #fff; /* White text */
    border: none;
  }
  .btn-primary-bright:hover {
    background: #34d399;
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
  }

  .popular-tag {
    /* Adjusted for Pro Card */
    background: #d1fae5;
    color: #064e3b;
  }

  @media (max-width: 768px) {
    h2 {
      font-size: 2rem;
      margin-bottom: 2.5rem;
    }
    .amount {
      font-size: 2.5rem;
    }
    .feature-card {
      padding: 2rem;
    }
    .pricing-card {
      max-width: 100%;
    }
    
    /* Footer mobile fixes */
    .footer {
      padding: 3rem 0 1.5rem;
    }
    
    .footer-grid {
      grid-template-columns: 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    
    .footer-brand {
      max-width: 100%;
    }
    
    .footer-bottom {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
      text-align: left;
    }
  }

  /* Register Modal Styles */
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
    background: #fff;
    border-radius: 24px;
    width: 100%;
    max-width: 480px;
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
  }

  .modal-close:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .modal-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .modal-logo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 1rem;
    color: var(--color-primary);
  }

  .modal-logo img {
    width: 150px;
    height: auto;
    object-fit: contain;
  }

  .modal-logo-text {
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--color-text-heading);
  }

  .modal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 0.5rem;
  }

  .modal-subtitle {
    color: #64748b;
    font-size: 0.95rem;
  }

  .modal-form {
    margin-bottom: 1.5rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-heading);
    margin-bottom: 0.5rem;
  }

  .required {
    color: #ef4444;
  }

  .phone-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }

  .phone-prefix {
    position: absolute;
    left: 12px;
    color: #64748b;
    font-size: 0.875rem;
    pointer-events: none;
    z-index: 1;
  }

  .form-input {
    width: 100%;
    padding: 12px 16px;
    padding-left: 48px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    color: var(--color-text-heading);
    transition: all 0.2s;
    font-family: inherit;
  }

  .form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-input:disabled {
    background: #f8fafc;
    cursor: not-allowed;
  }

  .form-group:last-of-type .form-input {
    padding-left: 16px;
  }

  .plan-select {
    padding-left: 16px;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 40px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
  }

  .plan-select:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-hint {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
  }

  .plan-display {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
  }

  .plan-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
  }

  .plan-badge-free {
    background: #ecfdf5;
    color: #059669;
  }

  .plan-badge-pro {
    background: #064e3b;
    color: #fff;
  }

  .plan-badge-vip {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #fff;
  }

  .error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .btn-submit {
    width: 100%;
    padding: 14px;
    background: var(--color-primary);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 0.5rem;
  }

  .btn-submit:hover:not(:disabled) {
    background: var(--color-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 10px -2px rgba(16, 185, 129, 0.3);
  }

  .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .modal-footer-text {
    text-align: center;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 1rem;
  }

  @media (max-width: 640px) {
    .modal-content {
      padding: 1.5rem;
      max-height: 95vh;
    }

    .modal-title {
      font-size: 1.5rem;
    }
  }
</style>
