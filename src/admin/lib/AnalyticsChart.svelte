<script>
  export let data = [];
  export let type = 'bar'; // 'bar' or 'line'
  export let title = '';
  export let color = '#10b981';
  export let height = 200;

  $: maxValue = data.length > 0 ? Math.max(...data.map(d => d.count || d.revenue || 0), 1) : 1;
  $: chartHeight = height - 60; // Reserve space for labels
  $: barWidth = data.length > 0 ? Math.max((100 / data.length) - 2, 1) : 0;
</script>

<div class="analytics-chart">
  {#if title}
    <h3 class="chart-title">{title}</h3>
  {/if}
  
  <div class="chart-container" style="height: {height}px;">
    <svg class="chart-svg" viewBox="0 0 100 {height}">
      <!-- Grid lines -->
      {#each Array(5) as _, i}
        {@const yPos = 20 + (i * 20)}
        <line
          x1="0"
          y1={yPos}
          x2="100"
          y2={yPos}
          class="grid-line"
        />
        <text
          x="0"
          y={yPos + 2}
          class="grid-label"
          text-anchor="start"
        >
          {Math.round(maxValue * (1 - i / 4))}
        </text>
      {/each}

      <!-- Chart bars or line -->
      {#each data as item, index}
        {@const value = item.count || item.revenue || 0}
        {@const percentage = (value / maxValue) * 100}
        {@const xPos = (index / data.length) * 100 + (100 / data.length / 2)}
        {@const barHeight = (percentage / 100) * 60}
        {@const yPos = 80 - barHeight}

        {#if type === 'bar'}
          <!-- Bar -->
          <rect
            x={xPos - barWidth / 2}
            y={yPos}
            width={barWidth}
            height={barHeight}
            fill={color}
            opacity="0.8"
            class="chart-bar"
          >
            <title>{item.label || item.date}: {value}</title>
          </rect>
        {:else}
          <!-- Line chart -->
          {#if index > 0}
            {@const prevValue = data[index - 1].count || data[index - 1].revenue || 0}
            {@const prevPercentage = (prevValue / maxValue) * 100}
            {@const prevBarHeight = (prevPercentage / 100) * 60}
            {@const prevYPos = 80 - prevBarHeight}
            {@const prevXPos = ((index - 1) / data.length) * 100 + (100 / data.length / 2)}
            
            <line
              x1={prevXPos}
              y1={prevYPos}
              x2={xPos}
              y2={yPos}
              stroke={color}
              stroke-width="2"
              class="chart-line"
            />
          {/if}
          
          <circle
            cx={xPos}
            cy={yPos}
            r="2"
            fill={color}
            class="chart-point"
          >
            <title>{item.label || item.date}: {value}</title>
          </circle>
        {/if}

        <!-- X-axis labels (show every nth or last item) -->
        {@const labelInterval = Math.max(Math.ceil(data.length / 8), 1)}
        {#if index % labelInterval === 0 || index === data.length - 1}
          <text
            x={xPos}
            y="95"
            class="axis-label"
            text-anchor="middle"
          >
            {item.label || (item.date ? item.date.split('-')[2] : '')}
          </text>
        {/if}
      {/each}
    </svg>
  </div>
</div>

<style>
  .analytics-chart {
    width: 100%;
  }

  .chart-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 1rem;
    letter-spacing: -0.01em;
  }

  .chart-container {
    width: 100%;
    position: relative;
    overflow: hidden;
  }

  .chart-svg {
    width: 100%;
    height: 100%;
    overflow: visible;
  }

  .grid-line {
    stroke: #e2e8f0;
    stroke-width: 1;
  }

  .grid-label {
    font-size: 10px;
    fill: #64748b;
    font-weight: 500;
  }

  .chart-bar {
    transition: opacity 0.2s ease;
    cursor: pointer;
  }

  .chart-bar:hover {
    opacity: 1;
  }

  .chart-line {
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .chart-point {
    transition: r 0.2s ease;
    cursor: pointer;
  }

  .chart-point:hover {
    r: 4;
  }

  .axis-label {
    font-size: 10px;
    fill: #64748b;
    font-weight: 500;
  }

  @media (max-width: 768px) {
    .chart-title {
      font-size: 1rem;
    }

    .axis-label {
      font-size: 9px;
    }
  }
</style>

