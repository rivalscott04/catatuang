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
    display_order: 0,
    show_on_main: true,
    badge_text: '',
  };
  let creating = false;
  
  // Delete confirmation modal state
  let showDeleteModal = false;
  let pricingToDelete = null;
  
  // Edit modal state
  let showEditModal = false;
  let editingPricing = null;

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
          display_order: parseInt(String(pricing.display_order || 0)),
          show_on_main: pricing.show_on_main !== undefined ? pricing.show_on_main : true,
          badge_text: pricing.badge_text || null,
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
          price: parseInt(String(newPricing.price || 0)),
          is_active: newPricing.is_active,
          description: newPricing.description || '',
          features: newPricing.features || [],
          display_order: parseInt(String(newPricing.display_order || 0)),
          show_on_main: newPricing.show_on_main !== undefined ? newPricing.show_on_main : true,
          badge_text: newPricing.badge_text || null,
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
          display_order: 0,
          show_on_main: true,
          badge_text: '',
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

  function openEditModal(pricing) {
    editingPricing = JSON.parse(JSON.stringify(pricing)); // Deep copy
    showEditModal = true;
  }

  function closeEditModal() {
    editingPricing = null;
    showEditModal = false;
  }

  async function saveEditPricing() {
    if (!editingPricing) return;
    
    saving[editingPricing.id] = true;
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch(`/admin/pricing/${editingPricing.id}`, {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({
          price: parseInt(editingPricing.price),
          is_active: editingPricing.is_active,
          description: editingPricing.description,
          features: editingPricing.features || [],
          display_order: parseInt(String(editingPricing.display_order || 0)),
          show_on_main: editingPricing.show_on_main !== undefined ? editingPricing.show_on_main : true,
          badge_text: editingPricing.badge_text || null,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // Update local state
        const index = pricings.findIndex(p => p.id === editingPricing.id);
        if (index !== -1) {
          pricings[index] = { ...pricings[index], ...data.data };
        }
        dispatch('updated');
        
        showToastMessage('Perubahan berhasil disimpan!', 'success');
        
        // Trigger refresh di halaman utama dengan custom event
        const event = new CustomEvent('pricing-updated', { 
          detail: { pricingId: editingPricing.id, plan: editingPricing.plan },
          bubbles: true 
        });
        window.dispatchEvent(event);
        
        // Also try localStorage event as fallback (works across tabs)
        localStorage.setItem('pricing-updated', Date.now().toString());
        
        closeEditModal();
      } else {
        showToastMessage(data.message || 'Gagal mengupdate harga', 'error');
      }
    } catch (error) {
      console.error('Failed to update pricing:', error);
      showToastMessage('Terjadi kesalahan saat mengupdate harga', 'error');
    } finally {
      saving[editingPricing.id] = false;
    }
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
    <div class="pricing-table-container">
      <table class="pricing-table">
        <thead>
          <tr>
            <th>Urutan</th>
            <th>Plan</th>
            <th>Harga</th>
            <th>Deskripsi</th>
            <th>Fitur</th>
            <th>Badge</th>
            <th>Status</th>
            <th>Tampilkan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          {#each pricings as pricing}
            <tr>
              <td class="order-cell">
                <input
                  type="number"
                  class="order-input"
                  bind:value={pricing.display_order}
                  min="0"
                  on:change={() => updatePricing(pricing)}
                  on:blur={() => updatePricing(pricing)}
                />
              </td>
              <td class="plan-name-cell">
                <strong>{pricing.plan?.toUpperCase() || 'N/A'}</strong>
              </td>
              <td class="price-cell">
                {formatPrice(pricing.price)}
              </td>
              <td class="description-cell">
                <span class="description-text" title={pricing.description || '-'}>
                  {pricing.description || '-'}
                </span>
              </td>
              <td class="features-cell">
                <span class="features-count">
                  {pricing.features?.length || 0} fitur
                </span>
              </td>
              <td class="badge-cell">
                {#if pricing.badge_text}
                  <span class="badge-preview">{pricing.badge_text}</span>
                {:else}
                  <span class="badge-empty">-</span>
                {/if}
              </td>
              <td class="status-cell">
                <label class="toggle-switch">
                  <input
                    type="checkbox"
                    bind:checked={pricing.is_active}
                    on:change={() => updatePricing(pricing)}
                  />
                  <span class="toggle-slider"></span>
                </label>
              </td>
              <td class="status-cell">
                <label class="toggle-switch">
                  <input
                    type="checkbox"
                    bind:checked={pricing.show_on_main}
                    on:change={() => updatePricing(pricing)}
                  />
                  <span class="toggle-slider"></span>
                </label>
              </td>
              <td class="actions-cell">
                <div class="action-buttons">
                  <button
                    class="btn-edit"
                    on:click={() => openEditModal(pricing)}
                    title="Edit Plan"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
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
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div>

<!-- Create Modal -->
{#if showCreateModal}
  <div 
    class="modal-backdrop" 
    role="button"
    tabindex="0"
    on:click={() => showCreateModal = false}
    on:keydown={(e) => e.key === 'Escape' && (showCreateModal = false)}
  >
    <div 
      class="modal-content" 
      role="dialog"
      aria-modal="true"
      aria-labelledby="create-modal-title"
      tabindex="-1"
      on:click|stopPropagation
      on:keydown|stopPropagation
    >
      <div class="modal-header">
        <h2 id="create-modal-title">Tambah Plan Baru</h2>
        <button class="modal-close" on:click={() => showCreateModal = false} aria-label="Tutup modal">×</button>
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
          <label for="new-display-order">Urutan Tampil</label>
          <input
            id="new-display-order"
            type="number"
            bind:value={newPricing.display_order}
            min="0"
            step="1"
            class="form-input"
            placeholder="0"
            disabled={creating}
          />
          <small class="form-hint">Angka lebih kecil akan tampil lebih dulu</small>
        </div>

        <div class="form-group">
          <label for="new-badge-text">Badge Text (Opsional)</label>
          <input
            id="new-badge-text"
            type="text"
            bind:value={newPricing.badge_text}
            class="form-input"
            placeholder="contoh: Populer, Layak Dicoba, Terlaris"
            maxlength="50"
            disabled={creating}
          />
          <small class="form-hint">Teks yang akan muncul sebagai badge di kartu plan</small>
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

        <div class="form-group">
          <label class="checkbox-label">
            <input
              type="checkbox"
              bind:checked={newPricing.show_on_main}
              disabled={creating}
            />
            <span>Tampilkan di halaman utama</span>
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

<!-- Edit Modal -->
{#if showEditModal && editingPricing}
  <div 
    class="modal-backdrop" 
    role="button"
    tabindex="0"
    on:click={closeEditModal}
    on:keydown={(e) => e.key === 'Escape' && closeEditModal()}
  >
    <div 
      class="modal-content" 
      role="dialog"
      aria-modal="true"
      aria-labelledby="edit-modal-title"
      tabindex="-1"
      on:click|stopPropagation
      on:keydown|stopPropagation
    >
      <div class="modal-header">
        <h2 id="edit-modal-title">Edit Plan: {editingPricing.plan?.toUpperCase()}</h2>
        <button class="modal-close" on:click={closeEditModal} aria-label="Tutup modal">×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label for="edit-price">Harga (Rupiah)</label>
          <div class="price-input-wrapper">
            <span class="currency-prefix">Rp</span>
            <input
              id="edit-price"
              type="number"
              bind:value={editingPricing.price}
              min="0"
              step="1000"
              class="price-input"
              placeholder="0"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="edit-description">Deskripsi</label>
          <input
            id="edit-description"
            type="text"
            bind:value={editingPricing.description}
            class="form-input"
            placeholder="Deskripsi plan"
          />
        </div>

        <div class="form-group">
          <label for="edit-features">Fitur (satu per baris)</label>
          <textarea
            id="edit-features"
            class="features-textarea"
            placeholder="Masukkan fitur, satu per baris&#10;Contoh:&#10;200 chat text /bulan&#10;Upload struk otomatis (50/bulan)"
            value={Array.isArray(editingPricing.features) ? editingPricing.features.join('\n') : ''}
            on:input={(e) => {
              const text = e.currentTarget.value;
              editingPricing.features = text ? text.split('\n').filter(f => f.trim()) : [];
            }}
          ></textarea>
          <small class="form-hint">Setiap baris akan menjadi satu fitur</small>
        </div>

        <div class="form-group">
          <label for="edit-display-order">Urutan Tampil</label>
          <input
            id="edit-display-order"
            type="number"
            bind:value={editingPricing.display_order}
            min="0"
            step="1"
            class="form-input"
            placeholder="0"
          />
          <small class="form-hint">Angka lebih kecil akan tampil lebih dulu</small>
        </div>

        <div class="form-group">
          <label for="edit-badge-text">Badge Text (Opsional)</label>
          <input
            id="edit-badge-text"
            type="text"
            bind:value={editingPricing.badge_text}
            class="form-input"
            placeholder="contoh: Populer, Layak Dicoba, Terlaris"
            maxlength="50"
          />
          <small class="form-hint">Teks yang akan muncul sebagai badge di kartu plan</small>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input
              type="checkbox"
              bind:checked={editingPricing.is_active}
            />
            <span>Aktifkan plan ini</span>
          </label>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input
              type="checkbox"
              bind:checked={editingPricing.show_on_main}
            />
            <span>Tampilkan di halaman utama</span>
          </label>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-cancel" on:click={closeEditModal} disabled={saving[editingPricing.id]}>
          Batal
        </button>
        <button class="btn-save" on:click={saveEditPricing} disabled={saving[editingPricing.id]}>
          {saving[editingPricing.id] ? 'Menyimpan...' : 'Simpan Perubahan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Delete Confirmation Modal -->
{#if showDeleteModal && pricingToDelete}
  <div 
    class="modal-backdrop" 
    role="button"
    tabindex="0"
    on:click={closeDeleteModal}
    on:keydown={(e) => e.key === 'Escape' && closeDeleteModal()}
  >
    <div 
      class="modal-content delete-modal" 
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-modal-title"
      tabindex="-1"
      on:click|stopPropagation
      on:keydown|stopPropagation
    >
      <div class="modal-header">
        <h2 id="delete-modal-title">Hapus Plan</h2>
        <button class="modal-close" on:click={closeDeleteModal} aria-label="Tutup modal">×</button>
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

  .pricing-table-container {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
  }

  .pricing-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
  }

  .pricing-table thead {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
  }

  .pricing-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--color-text-heading, #0f172a);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .pricing-table tbody tr {
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s;
  }

  .pricing-table tbody tr:hover {
    background: #f8fafc;
  }

  .pricing-table tbody tr:last-child {
    border-bottom: none;
  }

  .pricing-table td {
    padding: 1rem;
    vertical-align: middle;
  }

  .plan-name-cell {
    font-weight: 600;
    color: var(--color-text-heading, #0f172a);
  }

  .plan-name-cell strong {
    font-size: 1rem;
  }

  .price-cell {
    font-weight: 600;
    color: var(--color-text-heading, #0f172a);
    font-size: 1rem;
  }

  .description-cell {
    max-width: 300px;
  }

  .description-text {
    display: block;
    color: var(--color-text-body, #475569);
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .features-cell {
    color: var(--color-text-body, #475569);
    font-size: 0.875rem;
  }

  .features-count {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    border-radius: 12px;
    font-weight: 500;
  }

  .status-cell {
    text-align: center;
  }

  .actions-cell {
    text-align: right;
  }

  .order-cell {
    text-align: center;
    width: 80px;
  }

  .order-input {
    width: 60px;
    padding: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .order-input:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .badge-cell {
    text-align: center;
    min-width: 120px;
  }

  .badge-preview {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--color-primary, #10b981);
    color: #fff;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .badge-empty {
    color: #94a3b8;
    font-size: 0.875rem;
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
    gap: 0.5rem;
    align-items: center;
    justify-content: flex-end;
  }

  .btn-edit {
    padding: 0.5rem;
    background: var(--color-primary, #10b981);
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .btn-edit:hover {
    background: var(--color-primary-hover, #059669);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
  }

  .btn-delete {
    padding: 0.5rem;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .btn-delete:hover:not(:disabled) {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
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
    outline: none;
  }

  .modal-backdrop:focus {
    outline: none;
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

    .pricing-table-container {
      overflow-x: auto;
    }

    .pricing-table {
      min-width: 800px;
    }

    .pricing-table th,
    .pricing-table td {
      padding: 0.75rem 0.5rem;
      font-size: 0.875rem;
    }

    .description-cell {
      max-width: 150px;
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

