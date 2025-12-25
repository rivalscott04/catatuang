<script>
  import { onMount } from 'svelte';
  
  export let message = '';
  export let type = 'success'; // success, error, warning, info
  export let duration = 3000;
  export let visible = false;
  
  let timeoutId;
  
  $: if (visible) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      visible = false;
    }, duration);
  }
  
  onMount(() => {
    return () => {
      clearTimeout(timeoutId);
    };
  });
</script>

{#if visible}
  <div class="toast toast-{type}" role="alert">
    <div class="toast-icon">
      {#if type === 'success'}
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" fill="currentColor"/>
        </svg>
      {:else if type === 'error'}
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" fill="currentColor"/>
        </svg>
      {:else if type === 'warning'}
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" fill="currentColor"/>
        </svg>
      {:else}
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" fill="currentColor"/>
        </svg>
      {/if}
    </div>
    <p class="toast-message">{message}</p>
    <button class="toast-close" on:click={() => visible = false} aria-label="Close">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
{/if}

<style>
  .toast {
    position: fixed;
    top: 20px;
    right: 20px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    min-width: 300px;
    max-width: 500px;
    animation: slideIn 0.3s ease-out;
    border-left: 4px solid;
  }
  
  .toast-success {
    border-left-color: #10b981;
  }
  
  .toast-error {
    border-left-color: #ef4444;
  }
  
  .toast-warning {
    border-left-color: #f59e0b;
  }
  
  .toast-info {
    border-left-color: #3b82f6;
  }
  
  .toast-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .toast-success .toast-icon {
    color: #10b981;
  }
  
  .toast-error .toast-icon {
    color: #ef4444;
  }
  
  .toast-warning .toast-icon {
    color: #f59e0b;
  }
  
  .toast-info .toast-icon {
    color: #3b82f6;
  }
  
  .toast-message {
    flex: 1;
    margin: 0;
    font-size: 0.9rem;
    color: #1e293b;
    font-weight: 500;
  }
  
  .toast-close {
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
  }
  
  .toast-close:hover {
    background: #f1f5f9;
    color: #1e293b;
  }
  
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @media (max-width: 768px) {
    .toast {
      right: 10px;
      left: 10px;
      min-width: auto;
      max-width: none;
    }
  }
</style>

