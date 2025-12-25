<script>
  import { onMount } from 'svelte';
  import { apiFetch } from '../lib/api.js';

  let users = [];
  let loading = true;
  let currentPage = 1;
  let totalPages = 1;
  let search = '';
  let planFilter = '';
  let statusFilter = '';
  let editingUser = null;
  let newPlan = '';
  let updating = false;

  async function fetchUsers() {
    loading = true;
    try {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15',
      });

      if (search) params.append('search', search);
      if (planFilter) params.append('plan', planFilter);
      if (statusFilter) params.append('status', statusFilter);

      const response = await apiFetch(`/admin/users?${params}`, {
        method: 'GET',
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          users = data.data.data || [];
          currentPage = data.data.current_page || 1;
          totalPages = data.data.last_page || 1;
        }
      }
    } catch (error) {
      console.error('Failed to fetch users:', error);
    } finally {
      loading = false;
    }
  }

  function handleSearch() {
    currentPage = 1;
    fetchUsers();
  }

  function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }

  function getPlanBadgeClass(plan) {
    return {
      free: 'badge-free',
      pro: 'badge-pro',
      vip: 'badge-vip',
    }[plan] || '';
  }

  async function updateUserPlan(userId) {
    if (!newPlan) return;
    
    updating = true;
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch(`/admin/users/${userId}/plan`, {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({ plan: newPlan }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          editingUser = null;
          newPlan = '';
          await fetchUsers();
        }
      } else {
        const error = await response.json();
        alert(error.message || 'Gagal update plan');
      }
    } catch (error) {
      console.error('Failed to update plan:', error);
      alert('Gagal update plan');
    } finally {
      updating = false;
    }
  }

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

  function formatExpiryDate(dateString) {
    if (!dateString) {
      return {
        formatted: '-',
        isExpired: false,
        daysLeft: 0,
      };
    }
    const date = new Date(dateString);
    const now = new Date();
    const isExpired = date < now;
    const timeDiff = date.getTime() - now.getTime();
    const daysLeft = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
    
    return {
      formatted: date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      }),
      isExpired,
      daysLeft,
    };
  }

  onMount(() => {
    fetchUsers();
  });
</script>

<div class="users-table-container">
  <div class="filters">
    <div class="filter-group">
      <input
        type="text"
        placeholder="Cari nama atau nomor telepon..."
        bind:value={search}
        on:keydown={(e) => e.key === 'Enter' && handleSearch()}
        class="filter-input"
      />
    </div>
    
    <div class="filter-group">
      <select bind:value={planFilter} on:change={handleSearch} class="filter-select">
        <option value="">Semua Plan</option>
        <option value="free">Free</option>
        <option value="pro">Pro</option>
        <option value="vip">VIP</option>
      </select>
    </div>

    <div class="filter-group">
      <select bind:value={statusFilter} on:change={handleSearch} class="filter-select">
        <option value="">Semua Status</option>
        <option value="active">Active</option>
        <option value="blocked">Blocked</option>
      </select>
    </div>

    <button class="btn-search" on:click={handleSearch}>Cari</button>
  </div>

  {#if loading}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Memuat data users...</p>
    </div>
  {:else}
    <div class="table-wrapper">
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Phone</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Subscription</th>
            <th>Expires At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each users as user}
            {@const expiryInfo = formatExpiryDate(user.subscription_expires_at)}
            <tr>
              <td>{user.id}</td>
              <td>{user.name || '-'}</td>
              <td>{user.phone_number}</td>
              <td>
                {#if editingUser === user.id}
                  <select bind:value={newPlan} class="plan-select">
                    <option value="free">Free</option>
                    <option value="pro">Pro</option>
                    <option value="vip">VIP</option>
                  </select>
                {:else}
                  <span class="badge {getPlanBadgeClass(user.plan)}">
                    {user.plan?.toUpperCase() || '-'}
                  </span>
                {/if}
              </td>
              <td>
                <span class="badge badge-{user.status}">
                  {user.status?.toUpperCase() || '-'}
                </span>
              </td>
              <td>
                {#if user.subscription_status === 'active'}
                  <span class="text-success">Active</span>
                {:else}
                  <span class="text-muted">Inactive</span>
                {/if}
              </td>
              <td>
                {#if user.subscription_expires_at}
                  <span class:expired={expiryInfo.isExpired} class:expiring-soon={!expiryInfo.isExpired && expiryInfo.daysLeft <= 7}>
                    {expiryInfo.formatted}
                    {#if !expiryInfo.isExpired && expiryInfo.daysLeft <= 30}
                      <span class="days-left">({expiryInfo.daysLeft}d)</span>
                    {/if}
                  </span>
                {:else}
                  <span class="text-muted">-</span>
                {/if}
              </td>
              <td>
                {#if editingUser === user.id}
                  <div class="action-buttons">
                    <button 
                      class="btn-save" 
                      on:click={() => updateUserPlan(user.id)}
                      disabled={updating || !newPlan}
                    >
                      {updating ? 'Saving...' : 'Save'}
                    </button>
                    <button 
                      class="btn-cancel" 
                      on:click={() => {
                        editingUser = null;
                        newPlan = '';
                      }}
                      disabled={updating}
                    >
                      Cancel
                    </button>
                  </div>
                {:else}
                  <button 
                    class="btn-edit" 
                    on:click={() => {
                      editingUser = user.id;
                      newPlan = user.plan || 'free';
                    }}
                  >
                    Update Plan
                  </button>
                {/if}
              </td>
            </tr>
          {/each}
        </tbody>
      </table>

      {#if users.length === 0}
        <div class="empty-state">
          <p>Tidak ada data users</p>
        </div>
      {/if}
    </div>

    {#if totalPages > 1}
      <div class="pagination">
        <button
          class="pagination-btn"
          disabled={currentPage === 1}
          on:click={() => {
            currentPage--;
            fetchUsers();
          }}
        >
          Previous
        </button>
        
        <span class="pagination-info">
          Page {currentPage} of {totalPages}
        </span>
        
        <button
          class="pagination-btn"
          disabled={currentPage === totalPages}
          on:click={() => {
            currentPage++;
            fetchUsers();
          }}
        >
          Next
        </button>
      </div>
    {/if}
  {/if}
</div>

<style>
  .users-table-container {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e2e8f0;
  }

  .filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .filter-group {
    flex: 1;
    min-width: 200px;
  }

  .filter-input,
  .filter-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
  }

  .filter-input:focus,
  .filter-select:focus {
    outline: none;
    border-color: var(--color-primary, #10b981);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .btn-search {
    padding: 0.75rem 1.5rem;
    background: var(--color-primary, #10b981);
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-search:hover {
    background: var(--color-primary-hover, #059669);
  }

  .table-wrapper {
    overflow-x: auto;
  }

  .users-table {
    width: 100%;
    border-collapse: collapse;
  }

  .users-table thead {
    background: #f8fafc;
  }

  .users-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-heading, #0f172a);
    border-bottom: 2px solid #e2e8f0;
  }

  .users-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: var(--color-text-body, #475569);
  }

  .users-table tbody tr:hover {
    background: #f8fafc;
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

  .badge-active {
    background: #d1fae5;
    color: #065f46;
  }

  .badge-blocked {
    background: #fee2e2;
    color: #991b1b;
  }

  .text-success {
    color: #059669;
    font-weight: 500;
  }

  .text-muted {
    color: #94a3b8;
  }

  .expired {
    color: #dc2626;
    font-weight: 600;
  }

  .expiring-soon {
    color: #f59e0b;
    font-weight: 500;
  }

  .days-left {
    font-size: 0.75rem;
    color: #64748b;
    margin-left: 0.25rem;
  }

  .plan-select {
    padding: 0.375rem 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
  }

  .plan-select:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }

  .btn-edit,
  .btn-save,
  .btn-cancel {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
  }

  .btn-edit {
    background: #10b981;
    color: #fff;
  }

  .btn-edit:hover {
    background: #059669;
  }

  .btn-save {
    background: #10b981;
    color: #fff;
  }

  .btn-save:hover:not(:disabled) {
    background: #059669;
  }

  .btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .btn-cancel {
    background: #f1f5f9;
    color: #64748b;
  }

  .btn-cancel:hover:not(:disabled) {
    background: #e2e8f0;
  }

  .btn-cancel:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .loading-state,
  .empty-state {
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

  .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
  }

  .pagination-btn {
    padding: 0.5rem 1rem;
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s;
  }

  .pagination-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: var(--color-primary, #10b981);
  }

  .pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-info {
    color: var(--color-text-body, #475569);
    font-size: 0.9rem;
  }

  @media (max-width: 768px) {
    .users-table-container {
      padding: 1rem;
    }

    .filters {
      flex-direction: column;
    }

    .filter-group {
      min-width: 100%;
    }

    .users-table {
      font-size: 0.8rem;
    }

    .users-table th,
    .users-table td {
      padding: 0.75rem 0.5rem;
    }
  }
</style>

