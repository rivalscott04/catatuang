<script>
  import { onMount, createEventDispatcher } from 'svelte';
  import { apiFetch } from './api.js';
  import Toast from './Toast.svelte';
  import TableSkeleton from './TableSkeleton.svelte';

  const dispatch = createEventDispatcher();

  export let userId = null;
  export let userName = '';
  export let type = 'pengeluaran'; // 'pemasukan' or 'pengeluaran'

  let transactions = [];
  let loading = true;
  let selectedMonth = new Date().toISOString().slice(0, 7); // YYYY-MM format
  let totalAmount = 0;
  let showToast = false;
  let toastMessage = '';
  let toastType = 'success';
  let generatingPdf = false;

  $: isIncome = type === 'pemasukan';
  $: isExpense = type === 'pengeluaran';
  $: pageTitle = isIncome ? 'Detail Pemasukan' : 'Detail Pengeluaran';
  $: apiEndpoint = isIncome ? 'incomes' : 'expenses';

  async function fetchTransactionDetails() {
    if (!userId) return;
    
    loading = true;
    try {
      const params = new URLSearchParams({
        month: selectedMonth,
      });

      const response = await apiFetch(`/admin/users/${userId}/${apiEndpoint}?${params}`, {
        method: 'GET',
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          transactions = data.data.transactions || [];
          totalAmount = data.data.total || 0;
        }
      } else {
        showToastMessage(`Gagal memuat detail ${isIncome ? 'pemasukan' : 'pengeluaran'}`, 'error');
      }
    } catch (error) {
      console.error(`Failed to fetch ${type} details:`, error);
      showToastMessage(`Gagal memuat detail ${isIncome ? 'pemasukan' : 'pengeluaran'}`, 'error');
    } finally {
      loading = false;
    }
  }

  async function generatePdf() {
    if (!userId) return;
    
    generatingPdf = true;
    try {
      const params = new URLSearchParams({
        month: selectedMonth,
      });

      const response = await apiFetch(`/admin/users/${userId}/${apiEndpoint}/pdf?${params}`, {
        method: 'GET',
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${type}_${userName}_${selectedMonth}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        showToastMessage('PDF berhasil diunduh', 'success');
      } else {
        showToastMessage('Gagal generate PDF', 'error');
      }
    } catch (error) {
      console.error('Failed to generate PDF:', error);
      showToastMessage('Gagal generate PDF', 'error');
    } finally {
      generatingPdf = false;
    }
  }

  function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount || 0);
  }

  function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  }

  function formatMonth(monthString) {
    if (!monthString) return '';
    const [year, month] = monthString.split('-');
    const date = new Date(year, month - 1, 1);
    return date.toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'long',
    });
  }

  function showToastMessage(message, type = 'success') {
    toastMessage = message;
    toastType = type;
    showToast = true;
  }

  $: if (userId && selectedMonth) {
    fetchTransactionDetails();
  }

  onMount(() => {
    if (userId) {
      fetchTransactionDetails();
    }
  });
</script>

<div class="expense-detail-container">
  <div class="detail-header">
    <div class="header-left">
      <button class="btn-back" on:click={() => dispatch('back')}>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Kembali
      </button>
      <div class="header-info">
        <h1>{pageTitle}</h1>
        <p class="user-name">{userName}</p>
      </div>
    </div>
    <div class="header-actions">
      <button 
        class="btn-generate-pdf" 
        class:income={isIncome}
        class:expense={isExpense}
        on:click={generatePdf}
        disabled={generatingPdf || loading}
      >
        {#if generatingPdf}
          <svg class="spinner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
          </svg>
          Generating...
        {:else}
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
          </svg>
          Generate PDF
        {/if}
      </button>
    </div>
  </div>

  <div class="filters-section" class:income={isIncome} class:expense={isExpense}>
    <div class="filter-group">
      <label for="month-filter">Filter Bulan:</label>
      <input
        id="month-filter"
        type="month"
        bind:value={selectedMonth}
        class="month-input"
      />
    </div>
    <div class="total-summary" class:income={isIncome} class:expense={isExpense}>
      <span class="total-label">Total {isIncome ? 'Pemasukan' : 'Pengeluaran'} {formatMonth(selectedMonth)}:</span>
      <span class="total-amount" class:income={isIncome} class:expense={isExpense}>
        {formatCurrency(totalAmount)}
      </span>
    </div>
  </div>

  {#if loading}
    <TableSkeleton rows={5} />
  {:else}
    <div class="table-wrapper">
      <table class="expense-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Jumlah</th>
            <th>Deskripsi</th>
            <th>Sumber</th>
          </tr>
        </thead>
        <tbody>
          {#each transactions as transaction, index}
            <tr>
              <td>{index + 1}</td>
              <td>{formatDate(transaction.tanggal)}</td>
              <td class="amount-cell" class:income={isIncome} class:expense={isExpense}>
                {formatCurrency(transaction.amount)}
              </td>
              <td>{transaction.description || '-'}</td>
              <td>
                <span class="source-badge">{transaction.source || '-'}</span>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>

      {#if transactions.length === 0}
        <div class="empty-state">
          <svg class="empty-icon" width="64" height="64" viewBox="0 0 64 64" fill="none">
            <circle cx="32" cy="32" r="30" stroke="#e2e8f0" stroke-width="2"/>
            <path d="M32 20v24M20 32h24" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <h3>Tidak ada data {isIncome ? 'pemasukan' : 'pengeluaran'}</h3>
          <p>Belum ada {isIncome ? 'pemasukan' : 'pengeluaran'} untuk bulan {formatMonth(selectedMonth)}</p>
        </div>
      {/if}
    </div>
  {/if}
</div>

<Toast bind:visible={showToast} message={toastMessage} type={toastType} />

<style>
  .expense-detail-container {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e2e8f0;
  }

  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
  }

  .btn-back {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-back:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .header-info h1 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
  }

  .user-name {
    margin: 0.25rem 0 0 0;
    font-size: 0.9375rem;
    color: #64748b;
  }

  .header-actions {
    display: flex;
    gap: 1rem;
  }

  .btn-generate-pdf {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-generate-pdf.income {
    background: #10b981;
  }

  .btn-generate-pdf.income:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
  }

  .btn-generate-pdf.expense {
    background: #ef4444;
  }

  .btn-generate-pdf.expense:hover:not(:disabled) {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
  }

  .btn-generate-pdf:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .spinner-icon {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .filters-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    border-radius: 12px;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .filters-section.income {
    background: #f0fdf4;
  }

  .filters-section.expense {
    background: #fef2f2;
  }

  .filter-group {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .filter-group label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #475569;
  }

  .month-input {
    padding: 0.625rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s;
  }

  .month-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .total-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .total-label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #475569;
  }

  .total-amount {
    font-size: 1.25rem;
    font-weight: 700;
  }

  .total-amount.income {
    color: #10b981;
  }

  .total-amount.expense {
    color: #ef4444;
  }

  .table-wrapper {
    overflow-x: auto;
  }

  .expense-table {
    width: 100%;
    border-collapse: collapse;
  }

  .expense-table thead {
    background: #f8fafc;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .expense-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    border-bottom: 2px solid #e2e8f0;
  }

  .expense-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #475569;
  }

  .expense-table tbody tr {
    transition: all 0.2s;
  }

  .expense-table tbody tr:hover {
    background: #f8fafc;
  }

  .amount-cell {
    font-weight: 600;
  }

  .amount-cell.income {
    color: #10b981;
  }

  .amount-cell.expense {
    color: #ef4444;
  }

  .source-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
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

  @media (max-width: 768px) {
    .expense-detail-container {
      padding: 1rem;
    }

    .detail-header {
      flex-direction: column;
      gap: 1rem;
    }

    .header-left {
      width: 100%;
    }

    .header-actions {
      width: 100%;
    }

    .btn-generate-pdf {
      width: 100%;
      justify-content: center;
    }

    .filters-section {
      flex-direction: column;
      align-items: stretch;
    }

    .filter-group {
      flex-direction: column;
      align-items: stretch;
    }

    .total-summary {
      flex-direction: column;
      align-items: stretch;
      gap: 0.5rem;
    }
  }
</style>

