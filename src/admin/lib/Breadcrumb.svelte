<script>
  export let items = [];
</script>

<nav class="breadcrumb" aria-label="Breadcrumb">
  <ol class="breadcrumb-list">
    {#each items as item, index}
      <li class="breadcrumb-item">
        {#if index < items.length - 1}
          <button type="button" class="breadcrumb-link" on:click={item.onClick || (() => {})}>
            {#if item.icon}
              <span class="breadcrumb-icon" aria-hidden="true">
                {@html item.icon}
              </span>
            {/if}
            <span class="breadcrumb-text">{item.label}</span>
          </button>
          <span class="breadcrumb-separator" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </span>
        {:else}
          <span class="breadcrumb-current">
            {#if item.icon}
              <span class="breadcrumb-icon" aria-hidden="true">
                {@html item.icon}
              </span>
            {/if}
            <span class="breadcrumb-text">{item.label}</span>
          </span>
        {/if}
      </li>
    {/each}
  </ol>
</nav>

<style>
  .breadcrumb {
    margin-bottom: 1.5rem;
  }

  .breadcrumb-list {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .breadcrumb-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-family: inherit;
  }

  .breadcrumb-link:hover {
    color: #10b981;
    background: #f0fdf4;
  }

  .breadcrumb-link:focus {
    outline: 2px solid #10b981;
    outline-offset: 2px;
  }

  .breadcrumb-current {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #0f172a;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
  }

  .breadcrumb-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: inherit;
  }

  .breadcrumb-icon :global(svg) {
    width: 100%;
    height: 100%;
    stroke: currentColor;
    stroke-width: 2;
    fill: none;
  }

  .breadcrumb-text {
    white-space: nowrap;
  }

  .breadcrumb-separator {
    display: flex;
    align-items: center;
    color: #cbd5e1;
    margin: 0 0.25rem;
    flex-shrink: 0;
  }

  .breadcrumb-separator :global(svg) {
    width: 16px;
    height: 16px;
    stroke: currentColor;
    stroke-width: 2;
  }

  @media (max-width: 768px) {
    .breadcrumb {
      margin-bottom: 1.25rem;
    }

    .breadcrumb-link,
    .breadcrumb-current {
      font-size: 0.8125rem;
      padding: 0.375rem 0.5rem;
    }

    .breadcrumb-icon {
      width: 16px;
      height: 16px;
    }

    .breadcrumb-separator {
      margin: 0 0.125rem;
    }

    .breadcrumb-separator :global(svg) {
      width: 14px;
      height: 14px;
    }
  }
</style>

