<script>
  import { onMount } from 'svelte';
  import { apiFetch } from '../lib/api.js';
  import Toast from './Toast.svelte';
  import EditPlanModal from './EditPlanModal.svelte';
  import TableSkeleton from './TableSkeleton.svelte';

  let users = [];
  let loading = true;
  let currentPage = 1;
  let totalPages = 1;
  let perPage = 15;
  let search = '';
  let planFilter = '';
  let statusFilter = '';
  let expiryFilter = ''; // active, expiring, expired
  let editingUser = null;
  let newPlan = '';
  let updating = false;
  let sortField = '';
  let sortDirection = 'asc';
  let showToast = false;
  let toastMessage = '';
  let toastType = 'success';
  let showModal = false;
  let selectedUser = null;
  let activeFilterChips = [];
  let showDeleteModal = false;
  let userToDelete = null;
  let deleting = false;
  let togglingUnlimited = false;

  async function fetchUsers() {
    loading = true;
    try {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: perPage.toString(),
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
          let fetchedUsers = data.data.data || [];
          
          // Apply expiry filter client-side
          if (expiryFilter) {
            fetchedUsers = fetchedUsers.filter(user => {
              const expiryInfo = formatExpiryDate(user.subscription_expires_at);
              if (expiryFilter === 'expired') return expiryInfo.isExpired;
              if (expiryFilter === 'expiring') return !expiryInfo.isExpired && expiryInfo.daysLeft <= 7 && expiryInfo.daysLeft > 0;
              if (expiryFilter === 'active') return !expiryInfo.isExpired && (expiryInfo.daysLeft > 7 || !user.subscription_expires_at);
              return true;
            });
          }
          
          // Apply sorting
          if (sortField) {
            fetchedUsers.sort((a, b) => {
              let aVal = null;
              let bVal = null;
              if (sortField === 'id') {
                aVal = Number(a.id) || 0;
                bVal = Number(b.id) || 0;
              } else if (sortField === 'name') {
                aVal = (a.name || '').toLowerCase();
                bVal = (b.name || '').toLowerCase();
              } else if (sortField === 'expires_at') {
                aVal = a.subscription_expires_at ? new Date(a.subscription_expires_at).getTime() : 0;
                bVal = b.subscription_expires_at ? new Date(b.subscription_expires_at).getTime() : 0;
              }
              
              if (aVal === null || bVal === null) return 0;
              if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
              if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
              return 0;
            });
          }
          
          users = fetchedUsers;
          currentPage = data.data.current_page || 1;
          totalPages = data.data.last_page || 1;
        }
      }
    } catch (error) {
      console.error('Failed to fetch users:', error);
      showToastMessage('Gagal memuat data users', 'error');
    } finally {
      loading = false;
    }
  }

  function handleSearch() {
    currentPage = 1;
    updateFilterChips();
    fetchUsers();
  }

  function handleSort(field) {
    if (sortField === field) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortField = field;
      sortDirection = 'asc';
    }
    fetchUsers();
  }

  function formatPhoneNumber(phone) {
    if (!phone) return '-';
    // Remove leading 0 or 62
    let cleaned = phone.replace(/^(\+62|62|0)/, '');
    // Format: 0877-7266-6911
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

  function isNewUser(createdAt) {
    if (!createdAt) return false;
    const created = new Date(createdAt);
    const now = new Date();
    const daysDiff = Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24));
    return daysDiff < 3;
  }

  function getCombinedStatus(user) {
    const expiryInfo = formatExpiryDate(user.subscription_expires_at);
    
    if (user.status === 'blocked') {
      return { text: 'Blocked', class: 'badge-blocked', tooltip: 'User diblokir' };
    }
    
    if (user.subscription_status === 'cancelled') {
      return { text: 'Cancelled', class: 'badge-cancelled', tooltip: 'Subscription dibatalkan' };
    }
    
    if (expiryInfo.isExpired) {
      return { text: 'Expired', class: 'badge-expired', tooltip: `Expired ${Math.abs(expiryInfo.daysLeft)} days ago` };
    }
    
    if (expiryInfo.daysLeft > 0 && expiryInfo.daysLeft <= 7) {
      return { text: 'Expiring Soon', class: 'badge-expiring', tooltip: `Expires in ${expiryInfo.daysLeft} days` };
    }
    
    if (user.subscription_status === 'active' && user.status === 'active') {
      if (!user.subscription_expires_at) {
        return { text: 'Active (Lifetime)', class: 'badge-active-lifetime', tooltip: 'Active subscription tanpa expiry' };
      }
      return { text: 'Active', class: 'badge-active', tooltip: `Active, expires in ${expiryInfo.daysLeft} days` };
    }
    
    return { text: 'Inactive', class: 'badge-inactive', tooltip: 'Subscription tidak aktif' };
  }

  function formatExpiryDate(dateString) {
    if (!dateString) {
      return {
        formatted: 'Lifetime',
        isExpired: false,
        daysLeft: Infinity,
        badgeClass: 'badge-lifetime',
      };
    }
    const date = new Date(dateString);
    const now = new Date();
    const isExpired = date < now;
    const timeDiff = date.getTime() - now.getTime();
    const daysLeft = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
    
    let badgeClass = 'badge-expiry-ok';
    if (isExpired) {
      badgeClass = 'badge-expiry-expired';
    } else if (daysLeft <= 7) {
      badgeClass = 'badge-expiry-warning';
    } else if (daysLeft <= 30) {
      badgeClass = 'badge-expiry-soon';
    }
    
    return {
      formatted: date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      }),
      isExpired,
      daysLeft,
      badgeClass,
      expiredDaysAgo: isExpired ? Math.abs(daysLeft) : 0,
    };
  }

  function openEditModal(user) {
    selectedUser = user;
    newPlan = user.plan || 'free';
    showModal = true;
  }

  function closeModal() {
    showModal = false;
    selectedUser = null;
    newPlan = '';
  }

  function handleActionClick(user) {
    // Action menu handler - bisa ditambahkan dropdown menu atau action lainnya
    console.log('Action clicked for user:', user);
    // Untuk sekarang, buka edit modal sebagai default action
    openEditModal(user);
  }

  function openDeleteModal(user) {
    userToDelete = user;
    showDeleteModal = true;
  }

  function closeDeleteModal() {
    showDeleteModal = false;
    userToDelete = null;
  }

  async function handleDeleteUser() {
    if (!userToDelete) return;
    
    deleting = true;
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch(`/admin/users/${userToDelete.id}`, {
        method: 'DELETE',
        headers: headers,
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          showToastMessage('User berhasil dihapus', 'success');
          closeDeleteModal();
          await fetchUsers();
        }
      } else {
        const error = await response.json();
        showToastMessage(error.message || 'Gagal menghapus user', 'error');
      }
    } catch (error) {
      console.error('Failed to delete user:', error);
      showToastMessage('Gagal menghapus user', 'error');
    } finally {
      deleting = false;
    }
  }

  async function handleSavePlan(event) {
    const { plan } = event.detail;
    if (!plan || !selectedUser) return;
    
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

      const response = await apiFetch(`/admin/users/${selectedUser.id}/plan`, {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({ plan }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          showToastMessage('Plan berhasil diupdate!', 'success');
          closeModal();
          await fetchUsers();
        }
      } else {
        const error = await response.json();
        showToastMessage(error.message || 'Gagal update plan', 'error');
      }
    } catch (error) {
      console.error('Failed to update plan:', error);
      showToastMessage('Gagal update plan', 'error');
    } finally {
      updating = false;
    }
  }

  async function handleToggleUnlimited(user, event) {
    event.stopPropagation();
    if (!user) return;
    
    const newValue = !user.is_unlimited;
    togglingUnlimited = true;
    
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      const response = await apiFetch(`/admin/users/${user.id}/unlimited`, {
        method: 'PUT',
        headers: headers,
        body: JSON.stringify({ is_unlimited: newValue }),
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          showToastMessage(
            newValue ? 'User di-set unlimited!' : 'Unlimited status dihapus!',
            'success'
          );
          await fetchUsers();
        }
      } else {
        const error = await response.json();
        showToastMessage(error.message || 'Gagal toggle unlimited', 'error');
      }
    } catch (error) {
      console.error('Failed to toggle unlimited:', error);
      showToastMessage('Gagal toggle unlimited', 'error');
    } finally {
      togglingUnlimited = false;
    }
  }

  function showToastMessage(message, type = 'success') {
    toastMessage = message;
    toastType = type;
    showToast = true;
  }

  function updateFilterChips() {
    activeFilterChips = [];
    if (planFilter) activeFilterChips.push({ type: 'plan', label: `Plan: ${planFilter.toUpperCase()}`, value: planFilter });
    if (statusFilter) activeFilterChips.push({ type: 'status', label: `Status: ${statusFilter}`, value: statusFilter });
    if (expiryFilter) {
      const labels = { active: 'Active', expiring: 'Expiring Soon', expired: 'Expired' };
      activeFilterChips.push({ type: 'expiry', label: labels[expiryFilter] || expiryFilter, value: expiryFilter });
    }
  }

  function removeFilterChip(chip) {
    if (chip.type === 'plan') planFilter = '';
    if (chip.type === 'status') statusFilter = '';
    if (chip.type === 'expiry') expiryFilter = '';
    updateFilterChips();
    handleSearch();
  }

  function clearAllFilters() {
    search = '';
    planFilter = '';
    statusFilter = '';
    expiryFilter = '';
    activeFilterChips = [];
    handleSearch();
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

  <!-- Quick Filter Chips -->
  <div class="quick-filters">
    <button 
      class="filter-chip {expiryFilter === 'active' ? 'active' : ''}"
      on:click={() => { expiryFilter = expiryFilter === 'active' ? '' : 'active'; updateFilterChips(); handleSearch(); }}
    >
      Active
    </button>
    <button 
      class="filter-chip {expiryFilter === 'expiring' ? 'active' : ''}"
      on:click={() => { expiryFilter = expiryFilter === 'expiring' ? '' : 'expiring'; updateFilterChips(); handleSearch(); }}
    >
      Expiring Soon
    </button>
    <button 
      class="filter-chip {expiryFilter === 'expired' ? 'active' : ''}"
      on:click={() => { expiryFilter = expiryFilter === 'expired' ? '' : 'expired'; updateFilterChips(); handleSearch(); }}
    >
      Expired
    </button>
  </div>

  <!-- Active Filter Chips -->
  {#if activeFilterChips.length > 0}
    <div class="active-filters">
      {#each activeFilterChips as chip}
        <span class="filter-tag">
          {chip.label}
          <button class="filter-tag-remove" on:click={() => removeFilterChip(chip)}>×</button>
        </span>
      {/each}
      <button class="clear-all-filters" on:click={clearAllFilters}>Clear All</button>
    </div>
  {/if}

  {#if loading}
    <TableSkeleton rows={5} />
  {:else}
    <!-- Desktop Table View -->
    <div class="table-wrapper desktop-view">
      <table class="users-table">
        <thead>
          <tr>
            <th>
              No
            </th>
            <th class="sortable" on:click={() => handleSort('name')}>
              Nama
            </th>
            <th>Phone</th>
            <th>Plan</th>
            <th>Status</th>
            <th class="sortable" on:click={() => handleSort('expires_at')}>
              Expires At
            </th>
            <th>Unlimited</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each users as user, index}
            {@const expiryInfo = formatExpiryDate(user.subscription_expires_at)}
            {@const statusInfo = getCombinedStatus(user)}
            {@const isNew = isNewUser(user.created_at)}
            {@const rowNumber = (currentPage - 1) * perPage + index + 1}
            <tr class:editing={editingUser === user.id}>
              <td>{rowNumber}</td>
              <td>
                <div class="name-cell">
                  {user.name || '-'}
                  {#if isNew}
                    <span class="badge-new" title="User baru (kurang dari 3 hari)">New</span>
                  {/if}
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
                <div class="plan-cell">
                  <span class="badge {getPlanBadgeClass(user.plan)}" title="Plan: {user.plan?.toUpperCase() || 'Free'}">
                    {user.plan?.toUpperCase() || '-'}
                  </span>
                  <button 
                    class="btn-edit-icon" 
                    on:click={() => openEditModal(user)}
                    title="Edit Plan"
                  >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                      <path d="M11.333 1.333a2.667 2.667 0 013.334 3.334L5.333 13.333l-4 1.334 1.334-4L11.333 1.333z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </td>
              <td>
                <span class="badge {statusInfo.class}" title={statusInfo.tooltip}>
                  {statusInfo.text}
                </span>
              </td>
              <td>
                {#if user.subscription_expires_at}
                  <div class="expiry-cell">
                    <span class="badge {expiryInfo.badgeClass}">
                      {#if expiryInfo.isExpired}
                        Expired {expiryInfo.expiredDaysAgo}d ago
                      {:else if expiryInfo.daysLeft <= 7}
                        {expiryInfo.formatted} ({expiryInfo.daysLeft}d)
                      {:else if expiryInfo.daysLeft <= 30}
                        {expiryInfo.formatted} ({expiryInfo.daysLeft}d)
                      {:else}
                        {expiryInfo.formatted}
                      {/if}
                    </span>
                  </div>
                {:else}
                  <span class="badge badge-lifetime" title="Tidak ada expiry date">Lifetime</span>
                {/if}
              </td>
              <td>
                <label class="toggle-switch">
                  <input
                    type="checkbox"
                    checked={user.is_unlimited || false}
                    on:change={(e) => handleToggleUnlimited(user, e)}
                    disabled={togglingUnlimited}
                  />
                  <span class="toggle-slider"></span>
                </label>
              </td>
              <td>
                <div class="action-buttons">
                  <button 
                    class="btn-action btn-action-edit" 
                    title="Edit Plan"
                    on:click={() => openEditModal(user)}
                  >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                      <path d="M11.333 1.333a2.667 2.667 0 013.334 3.334L5.333 13.333l-4 1.334 1.334-4L11.333 1.333z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button 
                    class="btn-action btn-action-delete" 
                    title="Hapus User"
                    on:click={() => openDeleteModal(user)}
                  >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                      <path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 011.334-1.334h2.666a1.333 1.333 0 011.334 1.334V4m2 0v9.333a1.333 1.333 0 01-1.334 1.334H4.667a1.333 1.333 0 01-1.334-1.334V4h10.667zM6.667 7.333v4M9.333 7.333v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-cards mobile-view">
      {#each users as user}
        {@const expiryInfo = formatExpiryDate(user.subscription_expires_at)}
        {@const statusInfo = getCombinedStatus(user)}
        {@const isNew = isNewUser(user.created_at)}
        <div class="user-card">
          <div class="card-header">
            <div class="card-header-left">
              <h3 class="card-name">
                {user.name || '-'}
                {#if isNew}
                  <span class="badge-new">New</span>
                {/if}
              </h3>
            </div>
            <div class="card-actions-mobile">
              <button 
                class="btn-action-mobile btn-action-edit" 
                title="Edit Plan"
                on:click={() => openEditModal(user)}
              >
                <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                  <path d="M11.333 1.333a2.667 2.667 0 013.334 3.334L5.333 13.333l-4 1.334 1.334-4L11.333 1.333z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button 
                class="btn-action-mobile btn-action-delete" 
                title="Hapus User"
                on:click={() => openDeleteModal(user)}
              >
                <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                  <path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 011.334-1.334h2.666a1.333 1.333 0 011.334 1.334V4m2 0v9.333a1.333 1.333 0 01-1.334 1.334H4.667a1.333 1.333 0 01-1.334-1.334V4h10.667zM6.667 7.333v4M9.333 7.333v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
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
            
            <div class="card-row">
              <span class="card-label">Plan:</span>
              <div class="plan-cell-mobile">
                <span class="badge {getPlanBadgeClass(user.plan)}">
                  {user.plan?.toUpperCase() || '-'}
                </span>
                <button 
                  class="btn-edit-icon-mobile" 
                  on:click={() => openEditModal(user)}
                  title="Edit Plan"
                >
                  <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                    <path d="M11.333 1.333a2.667 2.667 0 013.334 3.334L5.333 13.333l-4 1.334 1.334-4L11.333 1.333z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>
            </div>
            
            <div class="card-row">
              <span class="card-label">Status:</span>
              <span class="badge {statusInfo.class}">
                {statusInfo.text}
              </span>
            </div>
            
            <div class="card-row">
              <span class="card-label">Expires:</span>
              {#if user.subscription_expires_at}
                <span class="badge {expiryInfo.badgeClass}">
                  {#if expiryInfo.isExpired}
                    Expired {expiryInfo.expiredDaysAgo}d ago
                  {:else if expiryInfo.daysLeft <= 7}
                    {expiryInfo.formatted} ({expiryInfo.daysLeft}d)
                  {:else if expiryInfo.daysLeft <= 30}
                    {expiryInfo.formatted} ({expiryInfo.daysLeft}d)
                  {:else}
                    {expiryInfo.formatted}
                  {/if}
                </span>
              {:else}
                <span class="badge badge-lifetime">Lifetime</span>
              {/if}
            </div>
            
            <div class="card-row">
              <span class="card-label">Unlimited:</span>
              <label class="toggle-switch">
                <input
                  type="checkbox"
                  checked={user.is_unlimited || false}
                  on:change={(e) => handleToggleUnlimited(user, e)}
                  disabled={togglingUnlimited}
                />
                <span class="toggle-slider"></span>
              </label>
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
        <h3>Tidak ada data users</h3>
        <p>Coba ubah filter atau cari dengan kata kunci lain</p>
        {#if activeFilterChips.length > 0}
          <button class="btn-clear-filters" on:click={clearAllFilters}>Clear All Filters</button>
        {/if}
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
</div>

<EditPlanModal 
  bind:visible={showModal}
  user={selectedUser}
  currentPlan={selectedUser?.plan || 'free'}
  {loading}
  on:close={closeModal}
  on:save={handleSavePlan}
/>

<Toast bind:visible={showToast} message={toastMessage} type={toastType} />

<!-- Delete Confirmation Modal -->
{#if showDeleteModal}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
  <div 
    class="modal-overlay" 
    role="dialog" 
    aria-modal="true"
    aria-labelledby="delete-modal-title"
    on:click={closeDeleteModal} 
    on:keydown={(e) => e.key === "Escape" && closeDeleteModal()}
    tabindex="-1"
  >
    <div 
      class="delete-modal-content"
      role="document"
      on:click|stopPropagation
    >
      <div class="delete-modal-header">
        <div class="delete-icon-wrapper">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h2 id="delete-modal-title" class="delete-modal-title">Hapus User</h2>
        <p class="delete-modal-subtitle">
          Apakah Anda yakin ingin menghapus user <strong>{userToDelete?.name || '-'}</strong>?
        </p>
        <p class="delete-modal-warning">
          Tindakan ini tidak dapat dibatalkan. Semua data user akan dihapus secara permanen.
        </p>
      </div>

      <div class="delete-modal-body">
        <div class="delete-user-info">
          <div class="info-row">
            <span class="info-label">Nama:</span>
            <span class="info-value">{userToDelete?.name || '-'}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value">{userToDelete?.phone_number ? formatPhoneNumber(userToDelete.phone_number) : '-'}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Plan:</span>
            <span class="info-value">
              <span class="badge {getPlanBadgeClass(userToDelete?.plan)}">
                {userToDelete?.plan?.toUpperCase() || '-'}
              </span>
            </span>
          </div>
        </div>
      </div>

      <div class="delete-modal-footer">
        <button 
          class="btn-cancel" 
          on:click={closeDeleteModal}
          disabled={deleting}
        >
          Batal
        </button>
        <button 
          class="btn-delete" 
          on:click={handleDeleteUser}
          disabled={deleting}
        >
          {deleting ? 'Menghapus...' : 'Hapus User'}
        </button>
      </div>
    </div>
  </div>
{/if}

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
    margin-bottom: 1rem;
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
    border: none;
    cursor: pointer;
    white-space: nowrap;
  }

  .btn-search:hover {
    background: var(--color-primary-hover, #059669);
  }

  .quick-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }

  .filter-chip {
    padding: 0.5rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .filter-chip:hover {
    border-color: #10b981;
    background: #f0fdf4;
    color: #059669;
  }

  .filter-chip.active {
    border-color: #10b981;
    background: #10b981;
    color: #fff;
  }

  .active-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background: #ecfdf5;
    color: #059669;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
  }

  .filter-tag-remove {
    background: none;
    border: none;
    color: #059669;
    cursor: pointer;
    font-size: 1.125rem;
    line-height: 1;
    padding: 0;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
    transition: all 0.2s;
  }

  .filter-tag-remove:hover {
    background: #10b981;
    color: #fff;
  }

  .clear-all-filters {
    padding: 0.375rem 0.75rem;
    background: #f1f5f9;
    color: #64748b;
    border: none;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .clear-all-filters:hover {
    background: #e2e8f0;
    color: #1e293b;
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
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .users-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-heading, #0f172a);
    border-bottom: 2px solid #e2e8f0;
  }

  .users-table th.sortable {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
  }

  .users-table th.sortable:hover {
    background: #f1f5f9;
  }

  .users-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: var(--color-text-body, #475569);
  }

  .users-table tbody tr {
    transition: all 0.2s;
  }

  .users-table tbody tr:hover {
    background: #f8fafc;
  }

  .users-table tbody tr.editing {
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
  }

  .name-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .badge-new {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    background: #3b82f6;
    color: #fff;
    border-radius: 4px;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
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

  .plan-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-edit-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #64748b;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    opacity: 0;
  }

  .plan-cell:hover .btn-edit-icon {
    opacity: 1;
  }

  .btn-edit-icon:hover {
    background: #f1f5f9;
    color: #10b981;
  }

  .expiry-cell {
    display: flex;
    align-items: center;
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

  .badge-active {
    background: #d1fae5;
    color: #065f46;
  }

  .badge-active-lifetime {
    background: #dbeafe;
    color: #1e40af;
  }

  .badge-expiring {
    background: #fef3c7;
    color: #92400e;
  }

  .badge-expired {
    background: #fee2e2;
    color: #991b1b;
  }

  .badge-cancelled {
    background: #f3f4f6;
    color: #4b5563;
  }

  .badge-inactive {
    background: #f3f4f6;
    color: #6b7280;
  }

  .badge-blocked {
    background: #fee2e2;
    color: #991b1b;
  }

  .badge-lifetime {
    background: #dbeafe;
    color: #1e40af;
  }

  .badge-expiry-ok {
    background: #d1fae5;
    color: #065f46;
  }

  .badge-expiry-soon {
    background: #fef3c7;
    color: #92400e;
  }

  .badge-expiry-warning {
    background: #fed7aa;
    color: #9a3412;
  }

  .badge-expiry-expired {
    background: #fee2e2;
    color: #991b1b;
  }

  .action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  .btn-action {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #64748b;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .btn-action:hover {
    background: #f1f5f9;
  }

  .btn-action-edit:hover {
    color: var(--color-primary, #10b981);
  }

  .btn-action-delete:hover {
    color: #ef4444;
    background: #fef2f2;
  }

  .btn-action-menu {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #64748b;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .btn-action-menu:hover {
    background: #f1f5f9;
    color: #1e293b;
  }

  .card-actions-mobile {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  .btn-action-mobile {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #64748b;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .btn-action-mobile:hover {
    background: #f1f5f9;
  }

  .btn-action-mobile.btn-action-edit:hover {
    color: var(--color-primary, #10b981);
  }

  .btn-action-mobile.btn-action-delete:hover {
    color: #ef4444;
    background: #fef2f2;
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

  .btn-clear-filters {
    margin-top: 1rem;
    padding: 0.625rem 1.25rem;
    background: #10b981;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-clear-filters:hover {
    background: #059669;
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

  /* Mobile Card Styles */
  .mobile-cards {
    display: none;
  }

  .user-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
  }

  .user-card:hover {
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
  }

  .card-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-action-menu-mobile {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #64748b;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
  }

  .btn-action-menu-mobile:hover {
    background: #f1f5f9;
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
    min-width: 70px;
  }

  .plan-cell-mobile {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-edit-icon-mobile {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #64748b;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .btn-edit-icon-mobile:hover {
    background: #f1f5f9;
    color: #10b981;
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

    /* Hide desktop table on mobile */
    .desktop-view {
      display: none;
    }

    /* Show mobile cards on mobile */
    .mobile-view {
      display: block;
    }

    .quick-filters {
      gap: 0.375rem;
    }

    .filter-chip {
      padding: 0.375rem 0.75rem;
      font-size: 0.8125rem;
    }

    .active-filters {
      gap: 0.375rem;
    }

    .filter-tag {
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
    }

    .user-card {
      padding: 0.875rem;
    }

    .card-name {
      font-size: 0.9375rem;
    }

    .card-label {
      font-size: 0.75rem;
      min-width: 60px;
    }

    .badge {
      font-size: 0.6875rem;
      padding: 0.25rem 0.625rem;
    }

    .phone-cell {
      font-size: 0.8125rem;
    }
  }

  @media (min-width: 769px) {
    /* Hide mobile cards on desktop */
    .mobile-view {
      display: none;
    }

    /* Show desktop table on desktop */
    .desktop-view {
      display: block;
    }
  }

  /* Delete Modal Styles */
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

  .delete-modal-content {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    position: relative;
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

  .delete-modal-header {
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
  }

  .delete-icon-wrapper {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: #fef2f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
  }

  .delete-modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-heading, #0f172a);
    margin: 0 0 0.5rem 0;
  }

  .delete-modal-subtitle {
    color: var(--color-text-body, #475569);
    font-size: 0.95rem;
    margin: 0 0 0.5rem 0;
    line-height: 1.5;
  }

  .delete-modal-subtitle strong {
    color: var(--color-text-heading, #0f172a);
    font-weight: 600;
  }

  .delete-modal-warning {
    color: #ef4444;
    font-size: 0.875rem;
    margin: 0;
    font-weight: 500;
  }

  .delete-modal-body {
    padding: 1.5rem 2rem;
  }

  .delete-user-info {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e2e8f0;
  }

  .info-row:last-child {
    border-bottom: none;
  }

  .info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-muted, #64748b);
    min-width: 80px;
  }

  .info-value {
    font-size: 0.875rem;
    color: var(--color-text-heading, #0f172a);
    font-weight: 500;
    text-align: right;
  }

  .delete-modal-footer {
    padding: 1.5rem 2rem 2rem;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
  }

  .btn-cancel {
    padding: 0.75rem 1.5rem;
    background: #fff;
    color: var(--color-text-body, #475569);
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-cancel:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--color-text-heading, #0f172a);
  }

  .btn-cancel:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-delete {
    padding: 0.75rem 1.5rem;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-delete:hover:not(:disabled) {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
  }

  .btn-delete:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  /* Toggle Switch Styles */
  .toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
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
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 24px;
  }

  .toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
  }

  .toggle-switch input:checked + .toggle-slider {
    background-color: #10b981;
  }

  .toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
  }

  .toggle-switch input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .toggle-switch input:focus + .toggle-slider {
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  }

  @media (max-width: 640px) {
    .delete-modal-content {
      max-width: 100%;
    }

    .delete-modal-header {
      padding: 1.5rem 1.5rem 1rem;
    }

    .delete-modal-body {
      padding: 1rem 1.5rem;
    }

    .delete-modal-footer {
      padding: 1rem 1.5rem 1.5rem;
      flex-direction: column;
    }

    .btn-cancel,
    .btn-delete {
      width: 100%;
    }

    .delete-modal-title {
      font-size: 1.25rem;
    }
  }
</style>
