<script>
  export let title;
  export let value;
  export let icon;
  export let color = '#10b981';
</script>

<div class="stats-card" style="--card-accent-color: {color};">
  <div class="card-icon" style="background: {color}15; color: {color};">
    {#if icon === 'users'}
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
      </svg>
    {:else if icon === 'active'}
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
    {:else if icon === 'revenue'}
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"></line>
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
      </svg>
    {:else if icon === 'warning'}
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
    {:else if icon === 'new'}
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="8.5" cy="7" r="4"></circle>
        <line x1="20" y1="8" x2="20" y2="14"></line>
        <line x1="23" y1="11" x2="17" y2="11"></line>
      </svg>
    {/if}
  </div>
  <div class="card-content">
    <h3 class="card-title">{title}</h3>
    <p class="card-value">{value}</p>
  </div>
</div>

<style>
  .stats-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.75rem;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
  }

  .stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--card-accent-color, #10b981);
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .stats-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-4px);
    border-color: #cbd5e1;
  }

  .stats-card:hover::before {
    opacity: 1;
  }

  .card-icon {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .stats-card:hover .card-icon {
    transform: scale(1.05);
  }

  .card-content {
    flex: 1;
    min-width: 0;
  }

  .card-title {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.625rem;
    font-weight: 500;
    letter-spacing: 0.01em;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.08em;
  }

  .card-value {
    font-size: 2rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    letter-spacing: -0.02em;
    word-break: break-word;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .stats-card {
      padding: 1.5rem;
      gap: 1rem;
    }

    .card-icon {
      width: 56px;
      height: 56px;
    }

    .card-value {
      font-size: 1.75rem;
    }
  }

  @media (max-width: 480px) {
    .stats-card {
      padding: 1.25rem;
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .card-icon {
      width: 52px;
      height: 52px;
    }

    .card-value {
      font-size: 1.625rem;
    }
  }
</style>

