<script>
  import { createEventDispatcher } from 'svelte';
  
  export let visible = false;
  export let user = null;
  export let currentPlan = 'free';
  export let loading = false;
  
  const dispatch = createEventDispatcher();
  
  let selectedPlan = 'free';
  
  $: if (visible && user) {
    selectedPlan = currentPlan;
  }
  
  function getUserName() {
    return user && typeof user === 'object' && 'name' in user ? user.name : '-';
  }
  
  function getUserPhone() {
    return user && typeof user === 'object' && 'phone_number' in user ? user.phone_number : '-';
  }
  
  function handleClose() {
    if (!loading) {
      visible = false;
      dispatch('close');
    }
  }
  
  function handleSave() {
    if (!loading && selectedPlan) {
      dispatch('save', { plan: selectedPlan });
    }
  }
  
  function handleBackdropClick(e) {
    if (e.target === e.currentTarget && !loading) {
      handleClose();
    }
  }
</script>

{#if visible}
  <div class="modal-backdrop" on:click={handleBackdropClick} on:keydown={(e) => e.key === 'Escape' && handleClose()} tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-container">
      <div class="modal-header">
        <h2 id="modal-title">Update Plan User</h2>
        <button class="modal-close" on:click={handleClose} disabled={loading} aria-label="Close">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>
      
      <div class="modal-body">
        {#if user}
          <div class="user-info">
            <div class="info-item">
              <span class="info-label">Nama:</span>
              <span class="info-value">{getUserName()}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Phone:</span>
              <span class="info-value">{getUserPhone()}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Plan Saat Ini:</span>
              <span class="badge badge-{currentPlan}">{currentPlan.toUpperCase()}</span>
            </div>
          </div>
        {/if}
        
          <div class="plan-selector">
          <div class="plan-label">Pilih Plan Baru:</div>
          <div class="plan-options">
            <label class="plan-option {selectedPlan === 'free' ? 'selected' : ''}" for="plan-free">
              <input type="radio" id="plan-free" bind:group={selectedPlan} value="free" disabled={loading} />
              <div class="plan-content">
                <div class="plan-name">Free</div>
                <div class="plan-desc">Trial 3 hari gratis</div>
              </div>
            </label>
            
            <label class="plan-option {selectedPlan === 'pro' ? 'selected' : ''}" for="plan-pro">
              <input type="radio" id="plan-pro" bind:group={selectedPlan} value="pro" disabled={loading} />
              <div class="plan-content">
                <div class="plan-name">Pro</div>
                <div class="plan-desc">Rp 29rb/bulan</div>
              </div>
            </label>
            
            <label class="plan-option {selectedPlan === 'vip' ? 'selected' : ''}" for="plan-vip">
              <input type="radio" id="plan-vip" bind:group={selectedPlan} value="vip" disabled={loading} />
              <div class="plan-content">
                <div class="plan-name">VIP</div>
                <div class="plan-desc">Rp 79rb/bulan</div>
              </div>
            </label>
            
            <label class="plan-option {selectedPlan === 'unlimited' ? 'selected' : ''}" for="plan-unlimited">
              <input type="radio" id="plan-unlimited" bind:group={selectedPlan} value="unlimited" disabled={loading} />
              <div class="plan-content">
                <div class="plan-name">Unlimited</div>
                <div class="plan-desc">Akses seumur hidup (Lifetime)</div>
              </div>
            </label>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" on:click={handleClose} disabled={loading}>
          Batal
        </button>
        <button class="btn-save" on:click={handleSave} disabled={loading || !selectedPlan}>
          {loading ? 'Menyimpan...' : 'Simpan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
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
    animation: fadeIn 0.2s ease-out;
  }
  
  .modal-container {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    animation: slideUp 0.3s ease-out;
  }
  
  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #64748b;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .modal-close:hover:not(:disabled) {
    background: #f1f5f9;
    color: #1e293b;
  }
  
  .modal-close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .user-info {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }
  
  .info-item:last-child {
    margin-bottom: 0;
  }
  
  .info-label {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
  }
  
  .info-value {
    font-size: 0.875rem;
    color: #1e293b;
    font-weight: 600;
  }
  
  .plan-selector {
    margin-top: 1rem;
  }
  
  .plan-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.75rem;
  }
  
  .plan-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .plan-option {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
  }
  
  .plan-option:hover {
    border-color: #10b981;
    background: #f0fdf4;
  }
  
  .plan-option.selected {
    border-color: #10b981;
    background: #ecfdf5;
  }
  
  .plan-option input[type="radio"] {
    margin-right: 0.75rem;
    cursor: pointer;
  }
  
  .plan-content {
    flex: 1;
  }
  
  .plan-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }
  
  .plan-desc {
    font-size: 0.875rem;
    color: #64748b;
  }
  
  .badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }
  
  .badge-free {
    background: #ecfdf5;
    color: #059669;
  }
  
  .badge-pro {
    background: #064e3b;
    color: #fff;
  }
  
  .badge-vip {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #fff;
  }
  
  .badge-unlimited {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: #fff;
  }
  
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }
  
  .btn-cancel,
  .btn-save {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-cancel {
    background: #f1f5f9;
    color: #64748b;
  }
  
  .btn-cancel:hover:not(:disabled) {
    background: #e2e8f0;
  }
  
  .btn-save {
    background: #10b981;
    color: #fff;
  }
  
  .btn-save:hover:not(:disabled) {
    background: #059669;
  }
  
  .btn-cancel:disabled,
  .btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
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
</style>

