<script>
  import { onMount, createEventDispatcher } from 'svelte';
  import { apiFetch } from '../lib/api.js';

  const dispatch = createEventDispatcher();

  let pricings = [];
  let loading = true;
  let saving = {};

  async function getCsrfToken() {
    try {
      const response = await apiFetch('/csrf-token', {
        method: 'GET',
      });
      const data = await response.json();
      return data.token;
    } catch (err) {
      console.error('Failed to get CSRF token:', err);
      return null;
    }
  }

  async function fetchPricings() {
    loading = true;
    try {
      const response = await apiFetch('/admin/pricing', {
        method: 'GET',
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          pricings = data.data;
        }
      }
    } catch (error) {
      console.error('Failed to fetch pricings:', error);
    } finally {
      loading = false;
    }
  }

  async function updatePricing(pricing) {
    saving[pricing.id] = true;
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch(`/admin/pricing/${pricing.id}`, {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({
          price: parseInt(pricing.price),
          is_active: pricing.is_active,
          description: pricing.description,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // Update local state
        const index = pricings.findIndex(p => p.id === pricing.id);
        if (index !== -1) {
          pricings[index] = { ...pricings[index], ...data.data };
        }
        dispatch('updated');
      } else {
        alert(data.message || 'Gagal mengupdate harga');
      }
    } catch (error) {
      console.error('Failed to update pricing:', error);
      alert('Terjadi kesalahan saat mengupdate harga');
    } finally {
      saving[pricing.id] = false;
    }
  }

  function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(price);
  }

  onMount(() => {
    fetchPricings();
  });
</script>

<div class="pricing-settings">
  {#if loading}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Memuat data pricing...</p>
    </div>
  {:else}
    <div class="pricing-list">
      {#each pricings as pricing}
        <div class="pricing-card">
          <div class="pricing-header">
            <div>
              <h3 class="plan-name">{pricing.plan?.toUpperCase() || 'N/A'}</h3>
              <p class="plan-description">{pricing.description || '-'}</p>
            </div>
            <label class="toggle-switch">
              <input
                type="checkbox"
                bind:checked={pricing.is_active}
                on:change={() => updatePricing(pricing)}
              />
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="pricing-form">
            <div class="form-group">
              <label for="price-{pricing.id}">Harga (Rupiah)</label>
              <div class="price-input-wrapper">
                <span class="currency-prefix">Rp</span>
                <input
                  id="price-{pricing.id}"
                  type="number"
                  bind:value={pricing.price}
                  min="0"
                  step="1000"
                  class="price-input"
                  placeholder="0"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="description-{pricing.id}">Deskripsi</label>
              <input
                id="description-{pricing.id}"
                type="text"
                bind:value={pricing.description}
                class="description-input"
                placeholder="Deskripsi plan"
              />
            </div>

            <div class="pricing-actions">
              <div class="current-price">
                Harga saat ini: <strong>{formatPrice(pricing.price)}</strong>
              </div>
              <button
                class="btn-save"
                on:click={() => updatePricing(pricing)}
                disabled={saving[pricing.id]}
              >
                {saving[pricing.id] ? 'Menyimpan...' : 'Simpan Perubahan'}
              </button>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .pricing-settings {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e2e8f0;
  }

  .loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    gap: 1rem;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: var(--color-primary, #10b981);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .pricing-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .pricing-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.2s;
  }

  .pricing-card:hover {
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
  }

  .pricing-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-heading, #0f172a);
    margin-bottom: 0.25rem;
  }

  .plan-description {
    color: var(--color-text-body, #475569);
    font-size: 0.9rem;
  }

  .toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
  }

  .toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e0;
    transition: 0.3s;
    border-radius: 26px;
  }

  .toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
  }

  .toggle-switch input:checked + .toggle-slider {
    background-color: var(--color-primary, #10b981);
  }

  .toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
  }

  .pricing-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-heading, #0f172a);
  }

  .price-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }

  .currency-prefix {
    position: absolute;
    left: 12px;
    color: var(--color-text-body, #475569);
    font-weight: 600;
    z-index: 1;
  }

  .price-input {
    width: 100%;
    padding: 12px 16px;
    padding-left: 48px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.2s;
  }

  .price-input:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .description-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;
  }

  .description-input:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .pricing-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .current-price {
    color: var(--color-text-body, #475569);
    font-size: 0.9rem;
  }

  .current-price strong {
    color: var(--color-text-heading, #0f172a);
    font-size: 1.1rem;
  }

  .btn-save {
    padding: 0.75rem 1.5rem;
    background: var(--color-primary, #10b981);
    color: #fff;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s;
  }

  .btn-save:hover:not(:disabled) {
    background: var(--color-primary-hover, #059669);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  @media (max-width: 768px) {
    .pricing-settings {
      padding: 1rem;
    }

    .pricing-header {
      flex-direction: column;
      gap: 1rem;
    }

    .pricing-actions {
      flex-direction: column;
      gap: 1rem;
      align-items: stretch;
    }

    .btn-save {
      width: 100%;
    }
  }
</style>

