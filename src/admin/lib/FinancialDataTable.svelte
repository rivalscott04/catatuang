<script>
  import { onMount } from 'svelte';
  import { apiFetch } from './api.js';
  import Toast from './Toast.svelte';
  import TableSkeleton from './TableSkeleton.svelte';

  export let activeTab = 'pemasukan'; // 'pemasukan' or 'pengeluaran'

  let users = [];
  let loading = true;
  let currentPage = 1;
  let totalPages = 1;
  let perPage = 15;
  let search = '';
  let showToast = false;
  let toastMessage = '';
  let toastType = 'success';

  async function fetchFinancialData() {
    loading = true;
    try {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: perPage.toString(),
      });

      if (search) params.append('search', search);

      const response = await apiFetch(`/admin/financial-data?${params}`, {
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
      console.error('Failed to fetch financial data:', error);
      showToastMessage('Gagal memuat data keuangan', 'error');
    } finally {
      loading = false;
    }
  }

  function handleSearch() {
    currentPage = 1;
    fetchFinancialData();
  }

  function formatPhoneNumber(phone) {
    if (!phone) return '-';
    let cleaned = phone.replace(/^(\+62|62|0)/, '');
    if (cleaned.length >= 10) {
      return `+62 ${cleaned.slice(0, 4)}-${cleaned.slice(4, 8)}-${cleaned.slice(8)}`;
    }
    return phone;
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

  function showToastMessage(message, type = 'success') {
    toastMessage = message;
    toastType = type;
    showToast = true;
  }

  // Watch for activeTab changes and refetch data
  $: if (activeTab) {
    currentPage = 1;
    fetchFinancialData();
  }

  onMount(() => {
    fetchFinancialData();
  });
</script>

<div class="financial-data-container">
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
    <button class="btn-search" on:click={handleSearch}>Cari</button>
  </div>

  {#if loading}
    <TableSkeleton rows={5} />
  {:else}
    <!-- Desktop Table View -->
    <div class="table-wrapper desktop-view">
      <table class="financial-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Phone</th>
            <th>Plan</th>
            {#if activeTab === 'pemasukan'}
              <th>Total Pemasukan</th>
              <th>Jumlah Transaksi</th>
            {:else}
              <th>Total Pengeluaran</th>
              <th>Jumlah Transaksi</th>
            {/if}
            <th>Tanggal Daftar</th>
          </tr>
        </thead>
        <tbody>
          {#each users as user, index}
            {@const rowNumber = (currentPage - 1) * perPage + index + 1}
            <tr>
              <td>{rowNumber}</td>
              <td>
                <div class="name-cell">
                  {user.name || '-'}
                </div>
              </td>
              <td>
                <div class="phone-cell">
                  <svg class="phone-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3.654 1.328a.678.678 0 00-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 004.168 6.608 17.569 17.569 0 006.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 00-.063-1.015l-2.307-1.794a.678.678 0 00-1.015.063l-.844 1.004a11.814 11.814 0 01-2.582-1.69 11.814 11.814 0 01-1.69-2.582l1.004-.844a.678.678 0 00.063-1.015l-1.794-2.307z" fill="currentColor"/>
                  </svg>
                  {formatPhoneNumber(user.phone_number)}
                </div>
              </td>
              <td>
                <span class="badge {getPlanBadgeClass(user.plan)}" title="Plan: {user.plan?.toUpperCase() || 'Free'}">
                  {user.plan?.toUpperCase() || '-'}
                </span>
              </td>
              {#if activeTab === 'pemasukan'}
                <td>
                  <span class="amount income">
                    {user.formatted_income || 'Rp 0'}
                  </span>
                </td>
                <td>
                  <span class="transaction-count">
                    {user.total_income_count || 0} transaksi
                  </span>
                </td>
              {:else}
                <td>
                  <span class="amount expense">
                    {user.formatted_expense || 'Rp 0'}
                  </span>
                </td>
                <td>
                  <span class="transaction-count">
                    {user.total_expense_count || 0} transaksi
                  </span>
                </td>
              {/if}
              <td>{formatDate(user.created_at)}</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-cards mobile-view">
      {#each users as user}
        <div class="financial-card">
          <div class="card-header">
            <div class="card-header-left">
              <h3 class="card-name">{user.name || '-'}</h3>
              <span class="badge {getPlanBadgeClass(user.plan)}">
                {user.plan?.toUpperCase() || '-'}
              </span>
            </div>
          </div>
          
          <div class="card-body">
            <div class="card-row">
              <span class="card-label">Phone:</span>
              <div class="phone-cell">
                <svg class="phone-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <path d="M3.654 1.328a.678.678 0 00-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 004.168 6.608 17.569 17.569 0 006.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 00-.063-1.015l-2.307-1.794a.678.678 0 00-1.015.063l-.844 1.004a11.814 11.814 0 01-2.582-1.69 11.814 11.814 0 01-1.69-2.582l1.004-.844a.678.678 0 00.063-1.015l-1.794-2.307z" fill="currentColor"/>
                </svg>
                {formatPhoneNumber(user.phone_number)}
              </div>
            </div>
            
            {#if activeTab === 'pemasukan'}
              <div class="card-row">
                <span class="card-label">Total Pemasukan:</span>
                <span class="amount income">
                  {user.formatted_income || 'Rp 0'}
                </span>
              </div>
              <div class="card-row">
                <span class="card-label">Jumlah Transaksi:</span>
                <span class="transaction-count">
                  {user.total_income_count || 0} transaksi
                </span>
              </div>
            {:else}
              <div class="card-row">
                <span class="card-label">Total Pengeluaran:</span>
                <span class="amount expense">
                  {user.formatted_expense || 'Rp 0'}
                </span>
              </div>
              <div class="card-row">
                <span class="card-label">Jumlah Transaksi:</span>
                <span class="transaction-count">
                  {user.total_expense_count || 0} transaksi
                </span>
              </div>
            {/if}
            
            <div class="card-row">
              <span class="card-label">Tanggal Daftar:</span>
              <span>{formatDate(user.created_at)}</span>
            </div>
          </div>
        </div>
      {/each}
    </div>

    {#if users.length === 0}
      <div class="empty-state">
        <svg class="empty-icon" width="64" height="64" viewBox="0 0 64 64" fill="none">
          <circle cx="32" cy="32" r="30" stroke="#e2e8f0" stroke-width="2"/>
          <path d="M32 20v24M20 32h24" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h3>Tidak ada data keuangan</h3>
        <p>Coba ubah filter atau cari dengan kata kunci lain</p>
      </div>
    {/if}
  {/if}

  {#if totalPages > 1}
    <div class="pagination">
      <button
        class="pagination-btn"
        disabled={currentPage === 1}
        on:click={() => {
          currentPage--;
          fetchFinancialData();
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
          fetchFinancialData();
        }}
      >
        Next
      </button>
    </div>
  {/if}
</div>

<Toast bind:visible={showToast} message={toastMessage} type={toastType} />

<style>
  .financial-data-container {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e2e8f0;
  }

  .filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }

  .filter-group {
    flex: 1;
    min-width: 200px;
  }

  .filter-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
  }

  .filter-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .btn-search {
    padding: 0.75rem 1.5rem;
    background: #10b981;
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    white-space: nowrap;
  }

  .btn-search:hover {
    background: #059669;
  }

  .table-wrapper {
    overflow-x: auto;
  }

  .financial-table {
    width: 100%;
    border-collapse: collapse;
  }

  .financial-table thead {
    background: #f8fafc;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .financial-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    border-bottom: 2px solid #e2e8f0;
  }

  .financial-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #475569;
  }

  .financial-table tbody tr {
    transition: all 0.2s;
  }

  .financial-table tbody tr:hover {
    background: #f8fafc;
  }

  .name-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .phone-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .phone-icon {
    color: #64748b;
    flex-shrink: 0;
  }

  .badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
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

  .amount {
    font-weight: 700;
    font-size: 1rem;
  }

  .amount.income {
    color: #10b981;
  }

  .amount.expense {
    color: #ef4444;
  }

  .transaction-count {
    color: #64748b;
    font-size: 0.875rem;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    gap: 1rem;
    text-align: center;
  }

  .empty-icon {
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  .empty-state p {
    margin: 0;
    color: #64748b;
    font-size: 0.9rem;
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
    cursor: pointer;
  }

  .pagination-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #10b981;
  }

  .pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-info {
    color: #475569;
    font-size: 0.9rem;
  }

  /* Mobile Card Styles */
  .mobile-cards {
    display: none;
  }

  .financial-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
  }

  .financial-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .card-header-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .card-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
  }

  .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
  }

  .card-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    flex-shrink: 0;
    min-width: 100px;
  }

  @media (max-width: 768px) {
    .financial-data-container {
      padding: 1rem;
    }

    .filters {
      flex-direction: column;
    }

    .filter-group {
      min-width: 100%;
    }

    .desktop-view {
      display: none;
    }

    .mobile-view {
      display: block;
    }

    .financial-card {
      padding: 0.875rem;
    }

    .card-name {
      font-size: 0.9375rem;
    }

    .card-label {
      font-size: 0.75rem;
      min-width: 80px;
    }

    .badge {
      font-size: 0.6875rem;
      padding: 0.25rem 0.625rem;
    }

    .amount {
      font-size: 0.875rem;
    }
  }

  @media (min-width: 769px) {
    .mobile-view {
      display: none;
    }

    .desktop-view {
      display: block;
    }
  }
</style>

