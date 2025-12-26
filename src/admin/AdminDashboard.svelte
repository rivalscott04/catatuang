<script>
  import { onMount, onDestroy, createEventDispatcher } from 'svelte';
  import StatsCard from './lib/StatsCard.svelte';
  import UsersTable from './lib/UsersTable.svelte';
  import PricingSettings from './lib/PricingSettings.svelte';
  import PasswordSettings from './lib/PasswordSettings.svelte';
  import Svend3rBarChart from './lib/Svend3rBarChart.svelte';
  import Svend3rLineChart from './lib/Svend3rLineChart.svelte';
  import FinancialDataTable from './lib/FinancialDataTable.svelte';
  import { apiFetch } from './lib/api.js';

  const dispatch = createEventDispatcher();

  export let admin;

  let stats = null;
  let loading = true;
  let activeTab = 'dashboard'; // dashboard, users, pricing, password, financial
  let financialTab = 'pemasukan'; // pemasukan, pengeluaran
  let refreshing = false;
  let sidebarOpen = false;


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

  async function fetchStats() {
    try {
      const response = await apiFetch('/admin/stats', {
        method: 'GET',
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          stats = data.data;
        }
      }
    } catch (error) {
      console.error('Failed to fetch stats:', error);
    }
  }

  async function handleLogout() {
    try {
      const csrfToken = await getCsrfToken();
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
      }

      await apiFetch('/admin/logout', {
        method: 'POST',
        headers: headers,
      });

      dispatch('logout');
    } catch (error) {
      console.error('Logout error:', error);
      // Still dispatch logout even if request fails
      dispatch('logout');
    }
  }

  async function refreshData() {
    refreshing = true;
    await fetchStats();
    refreshing = false;
  }

  // Handle outside click to close sidebar
  function handleOutsideClick(event) {
    if (sidebarOpen && !event.target.closest('.sidebar') && !event.target.closest('.mobile-menu-toggle')) {
      sidebarOpen = false;
    }
  }

  onMount(async () => {
    await fetchStats();
    loading = false;
    
    // Add event listener for outside click
    document.addEventListener('click', handleOutsideClick);
  });

  onDestroy(() => {
    // Clean up event listener
    document.removeEventListener('click', handleOutsideClick);
  });
</script>

<div class="admin-dashboard">
  <nav class="admin-navbar">
    <div class="nav-content">
      <div class="nav-brand">
        <button class="mobile-menu-toggle" on:click={() => sidebarOpen = !sidebarOpen} aria-label="Toggle menu">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
            {#if sidebarOpen}
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            {:else}
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            {/if}
          </svg>
        </button>
        <img src="/catatuang_logo.svg" alt="CatatUang Logo" class="logo-img" />
        <span class="brand-text">Admin Dashboard</span>
      </div>
      
      <div class="nav-actions">
        <div class="admin-info">
          <span class="admin-name desktop-only">{admin?.name || admin?.username}</span>
        </div>
        <button class="btn-logout" on:click={handleLogout}>
          <span class="logout-text">Logout</span>
          <svg class="logout-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </button>
      </div>
    </div>
  </nav>

  <div class="dashboard-content">
    {#if sidebarOpen}
      <button 
        class="sidebar-overlay" 
        on:click={() => sidebarOpen = false}
        type="button"
        aria-label="Close sidebar"
      ></button>
    {/if}
    <div class="sidebar" class:open={sidebarOpen}>
      <button
        class="sidebar-item"
        class:active={activeTab === 'dashboard'}
        on:click={() => {
          activeTab = 'dashboard';
          sidebarOpen = false;
        }}
      >
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        Dashboard
      </button>
      
      <button
        class="sidebar-item"
        class:active={activeTab === 'users'}
        on:click={() => {
          activeTab = 'users';
          sidebarOpen = false;
        }}
      >
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        Users
      </button>
      
      <button
        class="sidebar-item"
        class:active={activeTab === 'pricing'}
        on:click={() => {
          activeTab = 'pricing';
          sidebarOpen = false;
        }}
      >
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="1" x2="12" y2="23"></line>
          <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        Pricing
      </button>
      
      <button
        class="sidebar-item"
        class:active={activeTab === 'password'}
        on:click={() => {
          activeTab = 'password';
          sidebarOpen = false;
        }}
      >
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        Password
      </button>
      
      <button
        class="sidebar-item"
        class:active={activeTab === 'financial'}
        on:click={() => {
          activeTab = 'financial';
          sidebarOpen = false;
        }}
      >
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="1" x2="12" y2="23"></line>
          <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        Data Keuangan
      </button>
    </div>

    <main class="main-content">
      {#if loading}
        <div class="loading-state">
          <div class="spinner"></div>
          <p>Memuat data...</p>
        </div>
      {:else if activeTab === 'dashboard'}
        <div class="dashboard-tab">
          <div class="tab-header">
            <h1>Dashboard</h1>
            <button class="btn-refresh" on:click={refreshData} disabled={refreshing}>
              <svg 
                viewBox="0 0 24 24" 
                width="16" 
                height="16" 
                fill="none" 
                stroke="currentColor" 
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class:spinning={refreshing}
              >
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
              <span>{refreshing ? 'Memuat...' : 'Refresh'}</span>
            </button>
          </div>

          {#if stats}
            <div class="stats-grid">
              <StatsCard
                title="Total Users"
                value={stats.users.total}
                icon="users"
                color="#10b981"
              />
              <StatsCard
                title="Active Users"
                value={stats.users.active}
                icon="active"
                color="#3b82f6"
              />
              <StatsCard
                title="New Users (7 days)"
                value={stats.users.new_last_7_days || 0}
                icon="new"
                color="#06b6d4"
              />
              <StatsCard
                title="Monthly Revenue"
                value={stats.revenue.formatted_monthly}
                icon="revenue"
                color="#8b5cf6"
              />
              <StatsCard
                title="Expiring Soon"
                value={stats.users.expiring_soon}
                icon="warning"
                color="#f59e0b"
              />
            </div>

            <div class="users-by-plan">
              <h2>Users by Plan</h2>
              <div class="plan-stats">
                <div class="plan-stat-item">
                  <span class="plan-label">Free</span>
                  <span class="plan-count">{stats.users_by_plan.free}</span>
                </div>
                <div class="plan-stat-item">
                  <span class="plan-label">Pro</span>
                  <span class="plan-count">{stats.users_by_plan.pro}</span>
                </div>
                <div class="plan-stat-item">
                  <span class="plan-label">VIP</span>
                  <span class="plan-count">{stats.users_by_plan.vip}</span>
                </div>
              </div>
            </div>

            {#if stats.analytics}
              <div class="analytics-section">
                <h2>Analytics</h2>
                <div class="analytics-grid">
                  <div class="analytics-card">
                    <Svend3rBarChart
                      title="User Growth (Last 4 Weeks)"
                      data={stats.analytics.user_growth || []}
                      color="#10b981"
                      height={300}
                      isRevenue={false}
                      legendLabel="New Users"
                    />
                  </div>
                  
                  <div class="analytics-card">
                    <Svend3rLineChart
                      title="Revenue Trend (Last 4 Weeks)"
                      data={stats.analytics.revenue_trend || []}
                      color="#8b5cf6"
                      height={300}
                      isRevenue={true}
                      legendLabel="Revenue (Rupiah)"
                    />
                  </div>
                </div>

                <div class="plan-distribution">
                  <h3>Plan Distribution</h3>
                  <div class="distribution-chart">
                    {#each stats.analytics.plan_distribution || [] as plan}
                      {@const total = stats.analytics.plan_distribution.reduce((sum, p) => sum + p.value, 0)}
                      {@const percentage = total > 0 ? (plan.value / total) * 100 : 0}
                      <div class="distribution-item">
                        <div class="distribution-header">
                          <span class="distribution-name">{plan.name}</span>
                          <span class="distribution-value">{plan.value} ({Math.round(percentage)}%)</span>
                        </div>
                        <div class="distribution-bar">
                          <div
                            class="distribution-fill"
                            style="width: {percentage}%; background: {plan.color};"
                          ></div>
                        </div>
                      </div>
                    {/each}
                  </div>
                </div>
              </div>
            {/if}
          {/if}
        </div>
      {:else if activeTab === 'users'}
        <div class="users-tab">
          <div class="tab-header">
            <h1>Users Management</h1>
          </div>
          <UsersTable />
        </div>
      {:else if activeTab === 'pricing'}
        <div class="pricing-tab">
          <div class="tab-header">
            <h1>Pricing Settings</h1>
          </div>
          <PricingSettings on:updated={refreshData} />
        </div>
      {:else if activeTab === 'password'}
        <div class="password-tab">
          <div class="tab-header">
            <h1>Password Settings</h1>
          </div>
          <PasswordSettings />
        </div>
      {:else if activeTab === 'financial'}
        <div class="financial-tab">
          <div class="tab-header">
            <h1>Data Keuangan</h1>
          </div>
          
          <div class="financial-tabs">
            <button
              class="financial-tab-btn"
              class:active={financialTab === 'pemasukan'}
              on:click={() => financialTab = 'pemasukan'}
            >
              Pemasukan
            </button>
            <button
              class="financial-tab-btn"
              class:active={financialTab === 'pengeluaran'}
              on:click={() => financialTab = 'pengeluaran'}
            >
              Pengeluaran
            </button>
          </div>
          
          <FinancialDataTable activeTab={financialTab} />
        </div>
      {/if}
    </main>
  </div>
</div>

<style>
  .admin-dashboard {
    min-height: 100vh;
    background: #f8fafc;
    font-family: var(--font-primary, 'Outfit', system-ui, -apple-system, sans-serif);
  }

  .admin-navbar {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, 0.95);
  }

  .nav-content {
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .nav-brand {
    display: flex;
    align-items: center;
    gap: 0.875rem;
  }

  .mobile-menu-toggle {
    display: none;
    background: transparent;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: #0f172a;
    transition: all 0.2s;
  }

  .mobile-menu-toggle:hover {
    background: #f1f5f9;
    border-radius: 6px;
  }

  .mobile-menu-toggle svg {
    display: block;
  }

  .logo-img {
    height: 36px;
    width: auto;
  }

  .brand-text {
    font-size: 1.375rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
  }

  .nav-actions {
    display: flex;
    align-items: center;
    gap: 1.25rem;
  }

  .admin-info {
    display: flex;
    align-items: center;
  }

  .admin-name {
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
  }

  .btn-logout {
    padding: 0.625rem 1.25rem;
    background: #ef4444;
    color: #ffffff;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    letter-spacing: 0.01em;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-logout:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
  }

  .btn-logout:active {
    transform: translateY(0);
  }

  .logout-icon {
    display: none;
  }

  .desktop-only {
    display: block;
  }

  .dashboard-content {
    display: flex;
    max-width: 1600px;
    margin: 0 auto;
    min-height: calc(100vh - 73px);
  }

  .sidebar {
    width: 260px;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    padding: 2rem 0;
    position: sticky;
    top: 73px;
    height: calc(100vh - 73px);
    overflow-y: auto;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .sidebar-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.875rem 1.75rem;
    background: transparent;
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: left;
    border: none;
    cursor: pointer;
  }

  .sidebar-item:hover {
    background: #f1f5f9;
    color: #10b981;
  }

  .sidebar-item.active {
    background: linear-gradient(90deg, #f0fdf4 0%, #ffffff 100%);
    color: #10b981;
    border-right: 3px solid #10b981;
    font-weight: 600;
  }

  .sidebar-item svg {
    flex-shrink: 0;
  }

  .main-content {
    flex: 1;
    padding: 2.5rem;
    background: #f8fafc;
  }

  .tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem;
  }

  .tab-header h1 {
    font-size: 2.25rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.03em;
    line-height: 1.2;
  }

  .btn-refresh {
    padding: 0.625rem 1.5rem;
    background: #10b981;
    color: #ffffff;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    letter-spacing: 0.01em;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-refresh svg {
    flex-shrink: 0;
  }

  .btn-refresh svg.spinning {
    animation: spin 1s linear infinite;
  }

  .btn-refresh:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
  }

  .btn-refresh:active:not(:disabled) {
    transform: translateY(0);
  }

  .btn-refresh:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    gap: 1.25rem;
  }

  .loading-state p {
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
  }

  .spinner {
    width: 44px;
    height: 44px;
    border: 4px solid #e2e8f0;
    border-top-color: #10b981;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.75rem;
    margin-bottom: 2.5rem;
  }

  .users-by-plan {
    background: #ffffff;
    border-radius: 16px;
    padding: 2.5rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }

  .users-by-plan h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2rem;
    letter-spacing: -0.02em;
  }

  .plan-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.25rem;
  }

  .plan-stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .plan-stat-item:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  }

  .plan-label {
    font-size: 0.8125rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.1em;
  }

  .plan-count {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
    letter-spacing: -0.02em;
  }

  .analytics-section {
    margin-top: 2.5rem;
  }

  .analytics-section h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2rem;
    letter-spacing: -0.02em;
  }

  .analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.75rem;
    margin-bottom: 2.5rem;
  }

  .analytics-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }

  .plan-distribution {
    background: #ffffff;
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }

  .plan-distribution h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 1.5rem;
    letter-spacing: -0.01em;
  }

  .distribution-chart {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .distribution-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .distribution-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .distribution-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
  }

  .distribution-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
  }

  .distribution-bar {
    width: 100%;
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
  }

  .distribution-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Responsive Design */
  @media (max-width: 1024px) {
    .nav-content {
      padding: 0 1.5rem;
    }

    .main-content {
      padding: 2rem;
    }

    .stats-grid {
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
    }

    .analytics-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  }

  @media (max-width: 768px) {
    .mobile-menu-toggle {
      display: block;
    }

    .dashboard-content {
      flex-direction: column;
      position: relative;
    }

    .sidebar {
      position: fixed;
      top: 73px;
      left: 0;
      width: 280px;
      height: calc(100vh - 73px);
      z-index: 999;
      transform: translateX(-100%);
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
      border-right: 1px solid #e2e8f0;
      padding: 1.5rem 0;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }

    .sidebar.open {
      transform: translateX(0);
    }

    .sidebar-item {
      border-right: none;
      border-left: 3px solid transparent;
      padding: 1rem 1.5rem;
    }

    .sidebar-item.active {
      border-left-color: #10b981;
      background: #f0fdf4;
    }

    .sidebar-overlay {
      position: fixed;
      top: 73px;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 998;
      animation: fadeIn 0.2s ease;
      border: none;
      padding: 0;
      cursor: pointer;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    .main-content {
      padding: 1.5rem;
    }

    .tab-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .tab-header h1 {
      font-size: 1.875rem;
    }

    .stats-grid {
      grid-template-columns: 1fr;
      gap: 1.25rem;
    }

    .users-by-plan {
      padding: 1.75rem;
    }

    .plan-stats {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .analytics-card {
      padding: 1.5rem;
    }

    .plan-distribution {
      padding: 1.5rem;
    }

    .nav-content {
      padding: 0 0.75rem;
      gap: 0.5rem;
    }

    .nav-brand {
      gap: 0.5rem;
      flex: 1;
      min-width: 0;
    }

    .logo-img {
      height: 28px;
    }

    .brand-text {
      font-size: 0.875rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .nav-actions {
      gap: 0.5rem;
      flex-shrink: 0;
    }

    .admin-info {
      display: none;
    }

    .btn-logout {
      padding: 0.5rem;
      min-width: 40px;
    }

    .logout-text {
      display: none;
    }

    .logout-icon {
      display: block;
    }
  }

  @media (max-width: 480px) {
    .nav-content {
      padding: 0 0.5rem;
    }

    .brand-text {
      font-size: 0.75rem;
    }

    .main-content {
      padding: 1.25rem;
    }

    .tab-header h1 {
      font-size: 1.625rem;
    }

    .users-by-plan {
      padding: 1.5rem;
    }

    .users-by-plan h2 {
      font-size: 1.25rem;
      margin-bottom: 1.5rem;
    }

    .plan-count {
      font-size: 2rem;
    }
  }

  .financial-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
  }

  .financial-tab-btn {
    padding: 0.875rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    bottom: -2px;
  }

  .financial-tab-btn:hover {
    color: #10b981;
    background: #f0fdf4;
  }

  .financial-tab-btn.active {
    color: #10b981;
    border-bottom-color: #10b981;
    font-weight: 600;
  }
</style>

