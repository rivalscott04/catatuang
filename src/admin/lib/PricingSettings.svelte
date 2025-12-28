<script>
  import { onMount, createEventDispatcher } from 'svelte';
  import { apiFetch } from '../lib/api.js';
  import Toast from './Toast.svelte';

  const dispatch = createEventDispatcher();

  let pricings = [];
  let loading = true;
  let saving = {};
  let deleting = {};
  let showToast = false;
  let toastMessage = '';
  let toastType = 'success';
  
  // Create modal state
  let showCreateModal = false;
  let newPricing = {
    plan: '',
    price: 0,
    description: '',
    features: [],
    is_active: true,
  };
  let creating = false;
  
  // Delete confirmation modal state
  let showDeleteModal = false;
  let pricingToDelete = null;

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
        if (data.success && data.data) {
          // Ensure features is always an array
          pricings = data.data.map(p => ({
            ...p,
            features: Array.isArray(p.features) ? p.features : (p.features ? [p.features] : [])
          }));
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
          features: pricing.features || [],
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
        
        // Show toast
        showToastMessage('Perubahan berhasil disimpan!', 'success');
        console.log('Toast should be shown now');
        
        // Trigger refresh di halaman utama dengan custom event
        const event = new CustomEvent('pricing-updated', { 
          detail: { pricingId: pricing.id, plan: pricing.plan },
          bubbles: true 
        });
        window.dispatchEvent(event);
        console.log('Pricing updated event dispatched');
        
        // Also try localStorage event as fallback (works across tabs)
        localStorage.setItem('pricing-updated', Date.now().toString());
      } else {
        showToastMessage(data.message || 'Gagal mengupdate harga', 'error');
      }
    } catch (error) {
      console.error('Failed to update pricing:', error);
      showToastMessage('Terjadi kesalahan saat mengupdate harga', 'error');
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

  async function createPricing() {
    if (!newPricing.plan.trim()) {
      showToastMessage('Nama plan harus diisi', 'error');
      return;
    }

    creating = true;
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch('/admin/pricing', {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({
          plan: newPricing.plan.trim().toLowerCase(),
          price: parseInt(newPricing.price) || 0,
          is_active: newPricing.is_active,
          description: newPricing.description || '',
          features: newPricing.features || [],
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        showToastMessage('Plan berhasil ditambahkan!', 'success');
        showCreateModal = false;
        // Reset form
        newPricing = {
          plan: '',
          price: 0,
          description: '',
          features: [],
          is_active: true,
        };
        // Refresh list
        await fetchPricings();
        dispatch('updated');
      } else {
        showToastMessage(data.message || 'Gagal menambahkan plan', 'error');
      }
    } catch (error) {
      console.error('Failed to create pricing:', error);
      showToastMessage('Terjadi kesalahan saat menambahkan plan', 'error');
    } finally {
      creating = false;
    }
  }

  function openDeleteModal(pricing) {
    pricingToDelete = pricing;
    showDeleteModal = true;
  }

  function closeDeleteModal() {
    pricingToDelete = null;
    showDeleteModal = false;
  }

  async function deletePricing() {
    if (!pricingToDelete) return;

    deleting[pricingToDelete.id] = true;
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch(`/admin/pricing/${pricingToDelete.id}`, {
        method: 'DELETE',
        headers: headers,
      });

      const data = await response.json();

      if (response.ok && data.success) {
        showToastMessage('Plan berhasil dihapus!', 'success');
        closeDeleteModal();
        // Refresh list
        await fetchPricings();
        dispatch('updated');
      } else {
        showToastMessage(data.message || 'Gagal menghapus plan', 'error');
      }
    } catch (error) {
      console.error('Failed to delete pricing:', error);
      showToastMessage('Terjadi kesalahan saat menghapus plan', 'error');
    } finally {
      deleting[pricingToDelete.id] = false;
    }
  }

  function showToastMessage(message, type = 'success') {
    // Reset toast first to ensure it shows
    showToast = false;
    toastMessage = '';
    toastType = 'success';
    
    // Use setTimeout to ensure reactivity
    setTimeout(() => {
      toastMessage = message;
      toastType = type;
      showToast = true;
      console.log('showToastMessage called:', { message, type, showToast });
    }, 10);
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
    <div class="pricing-header-section">
      <h2>Daftar Pricing Plans</h2>
      <button class="btn-add-plan" on:click={() => showCreateModal = true}>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Tambah Plan Baru
      </button>
    </div>
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

            <div class="form-group">
              <label for="features-{pricing.id}">Fitur (satu per baris)</label>
              <textarea
                id="features-{pricing.id}"
                class="features-textarea"
                placeholder="Masukkan fitur, satu per baris&#10;Contoh:&#10;200 chat text /bulan&#10;Upload struk otomatis (50/bulan)"
                value={Array.isArray(pricing.features) ? pricing.features.join('\n') : ''}
                on:input={(e) => {
                  const target = e.currentTarget;
                  const text = target.value;
                  pricing.features = text ? text.split('\n').filter(f => f.trim()) : [];
                }}
              ></textarea>
              <small class="form-hint">Setiap baris akan menjadi satu fitur</small>
            </div>

            <div class="pricing-actions">
              <div class="current-price">
                Harga saat ini: <strong>{formatPrice(pricing.price)}</strong>
              </div>
              <div class="action-buttons">
                <button
                  class="btn-save"
                  on:click={() => updatePricing(pricing)}
                  disabled={saving[pricing.id]}
                >
                  {saving[pricing.id] ? 'Menyimpan...' : 'Simpan Perubahan'}
                </button>
                <button
                  class="btn-delete"
                  on:click={() => openDeleteModal(pricing)}
                  disabled={deleting[pricing.id]}
                  title="Hapus Plan"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Create Modal -->
{#if showCreateModal}
  <div class="modal-backdrop" on:click={() => showCreateModal = false}>
    <div class="modal-content" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Tambah Plan Baru</h2>
        <button class="modal-close" on:click={() => showCreateModal = false}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label for="new-plan-name">Nama Plan *</label>
          <input
            id="new-plan-name"
            type="text"
            bind:value={newPricing.plan}
            placeholder="contoh: unlimited, premium, basic"
            class="form-input"
            disabled={creating}
          />
          <small class="form-hint">Nama plan akan otomatis diubah ke lowercase</small>
        </div>

        <div class="form-group">
          <label for="new-plan-price">Harga (Rupiah) *</label>
          <div class="price-input-wrapper">
            <span class="currency-prefix">Rp</span>
            <input
              id="new-plan-price"
              type="number"
              bind:value={newPricing.price}
              min="0"
              step="1000"
              class="price-input"
              placeholder="0"
              disabled={creating}
            />
          </div>
        </div>

        <div class="form-group">
          <label for="new-plan-description">Deskripsi</label>
          <input
            id="new-plan-description"
            type="text"
            bind:value={newPricing.description}
            class="form-input"
            placeholder="Deskripsi plan"
            disabled={creating}
          />
        </div>

        <div class="form-group">
          <label for="new-plan-features">Fitur (satu per baris)</label>
          <textarea
            id="new-plan-features"
            class="features-textarea"
            placeholder="Masukkan fitur, satu per baris&#10;Contoh:&#10;Unlimited chat text&#10;Upload struk unlimited"
            value={Array.isArray(newPricing.features) ? newPricing.features.join('\n') : ''}
            on:input={(e) => {
              const text = e.currentTarget.value;
              newPricing.features = text ? text.split('\n').filter(f => f.trim()) : [];
            }}
            disabled={creating}
          ></textarea>
          <small class="form-hint">Setiap baris akan menjadi satu fitur</small>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input
              type="checkbox"
              bind:checked={newPricing.is_active}
              disabled={creating}
            />
            <span>Aktifkan plan ini</span>
          </label>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-cancel" on:click={() => showCreateModal = false} disabled={creating}>
          Batal
        </button>
        <button class="btn-save" on:click={createPricing} disabled={creating || !newPricing.plan.trim()}>
          {creating ? 'Menambahkan...' : 'Tambah Plan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Delete Confirmation Modal -->
{#if showDeleteModal && pricingToDelete}
  <div class="modal-backdrop" on:click={closeDeleteModal}>
    <div class="modal-content delete-modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Hapus Plan</h2>
        <button class="modal-close" on:click={closeDeleteModal}>×</button>
      </div>
      
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus plan <strong>{pricingToDelete.plan?.toUpperCase()}</strong>?</p>
        <p class="warning-text">Tindakan ini tidak dapat dibatalkan. Plan yang dihapus tidak dapat dikembalikan.</p>
        {#if ['free', 'pro', 'vip'].includes(pricingToDelete.plan)}
          <p class="info-text">Note: Plan essential (free, pro, vip) tidak dapat dihapus. Anda dapat menonaktifkannya saja.</p>
        {/if}
      </div>

      <div class="modal-footer">
        <button class="btn-cancel" on:click={closeDeleteModal} disabled={deleting[pricingToDelete.id]}>
          Batal
        </button>
        <button 
          class="btn-delete-confirm" 
          on:click={deletePricing} 
          disabled={deleting[pricingToDelete.id] || ['free', 'pro', 'vip'].includes(pricingToDelete.plan)}
        >
          {deleting[pricingToDelete.id] ? 'Menghapus...' : 'Ya, Hapus'}
        </button>
      </div>
    </div>
  </div>
{/if}

<Toast message={toastMessage} type={toastType} bind:visible={showToast} on:close={() => showToast = false} />

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

  .features-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    resize: vertical;
    min-height: 120px;
    transition: all 0.2s;
  }

  .features-textarea:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-hint {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--color-text-body, #475569);
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

  .pricing-header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
  }

  .pricing-header-section h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-heading, #0f172a);
    margin: 0;
  }

  .btn-add-plan {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--color-primary, #10b981);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-add-plan:hover {
    background: var(--color-primary-hover, #059669);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .action-buttons {
    display: flex;
    gap: 0.75rem;
    align-items: center;
  }

  .btn-delete {
    padding: 0.75rem;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .btn-delete:hover:not(:disabled) {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
  }

  .btn-delete:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  /* Modal Styles */
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal-content {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-heading, #0f172a);
    margin: 0;
  }

  .modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #64748b;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
  }

  .modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;
  }

  .form-input:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-input:disabled {
    background: #f1f5f9;
    cursor: not-allowed;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.95rem;
  }

  .checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-cancel:hover:not(:disabled) {
    background: #e2e8f0;
  }

  .btn-cancel:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .delete-modal .modal-body {
    text-align: center;
  }

  .delete-modal .modal-body p {
    margin: 0.5rem 0;
    color: var(--color-text-body, #475569);
  }

  .warning-text {
    color: #dc2626 !important;
    font-weight: 600;
  }

  .info-text {
    color: #f59e0b !important;
    font-size: 0.875rem;
    margin-top: 1rem !important;
  }

  .btn-delete-confirm {
    padding: 0.75rem 1.5rem;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-delete-confirm:hover:not(:disabled) {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
  }

  .btn-delete-confirm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  @media (max-width: 768px) {
    .pricing-settings {
      padding: 1rem;
    }

    .pricing-header-section {
      flex-direction: column;
      gap: 1rem;
      align-items: stretch;
    }

    .btn-add-plan {
      width: 100%;
      justify-content: center;
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

    .action-buttons {
      flex-direction: column;
    }

    .btn-save,
    .btn-delete {
      width: 100%;
    }

    .modal-content {
      margin: 1rem;
      max-width: calc(100% - 2rem);
    }

    .modal-footer {
      flex-direction: column;
    }

    .btn-cancel,
    .btn-save,
    .btn-delete-confirm {
      width: 100%;
    }
  }
</style>

