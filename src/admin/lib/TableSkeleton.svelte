<script>
  export let rows = 5;
</script>

<div class="skeleton-container">
  <table class="skeleton-table">
    <thead>
      <tr>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      {#each Array(rows) as _, i}
        <tr>
          <td><div class="skeleton-line"></div></td>
          <td><div class="skeleton-line"></div></td>
          <td><div class="skeleton-line"></div></td>
          <td><div class="skeleton-badge"></div></td>
          <td><div class="skeleton-badge"></div></td>
          <td><div class="skeleton-line"></div></td>
          <td><div class="skeleton-badge"></div></td>
          <td><div class="skeleton-button"></div></td>
        </tr>
      {/each}
    </tbody>
  </table>
</div>

<style>
  .skeleton-container {
    width: 100%;
  }
  
  .skeleton-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .skeleton-table thead {
    background: #f8fafc;
  }
  
  .skeleton-table th {
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
  }
  
  .skeleton-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .skeleton-line,
  .skeleton-badge,
  .skeleton-button {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: loading 1.5s ease-in-out infinite;
    border-radius: 4px;
  }
  
  .skeleton-line {
    height: 16px;
    width: 80%;
  }
  
  .skeleton-badge {
    height: 24px;
    width: 60px;
    border-radius: 6px;
  }
  
  .skeleton-button {
    height: 32px;
    width: 100px;
    border-radius: 6px;
  }
  
  @keyframes loading {
    0% {
      background-position: 200% 0;
    }
    100% {
      background-position: -200% 0;
    }
  }
</style>

