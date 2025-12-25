<script>
  import { onMount } from 'svelte';
  import * as d3 from 'd3';

  export let data = [];
  export let title = '';
  export let color = '#8b5cf6';
  export let height = 300;
  export let margin = { top: 20, right: 20, bottom: 60, left: 50 };
  export let isRevenue = false; // If true, format as Rupiah
  export let legendLabel = ''; // Label for legend

  let svgElement;
  let chartWidth = 0;
  let chartHeight = 0;

  $: if (data && data.length > 0 && svgElement) {
    drawChart();
  }

  function drawChart() {
    if (!svgElement || !data || data.length === 0) return;

    // Clear previous content
    d3.select(svgElement).selectAll('*').remove();

    // Calculate dimensions
    const containerWidth = svgElement.parentElement.clientWidth || 800;
    chartWidth = containerWidth - margin.left - margin.right;
    chartHeight = height - margin.top - margin.bottom;

    // Set up scales
    const xScale = d3.scaleBand()
      .domain(data.map(d => d.label || d.date))
      .range([0, chartWidth])
      .padding(0.1);

    // Format number function
    const formatNumber = (value) => {
      if (isRevenue) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
      }
      return new Intl.NumberFormat('id-ID').format(value);
    };

    const maxValue = d3.max(data, d => d.count || d.revenue || 0) || 1;
    const yScale = d3.scaleLinear()
      .domain([0, maxValue * 1.1])
      .nice()
      .range([chartHeight, 0]);

    // Create SVG
    const svg = d3.select(svgElement)
      .attr('width', containerWidth)
      .attr('height', height);

    const g = svg.append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Add grid lines
    const yTicks = yScale.ticks(5);
    g.selectAll('.grid-line')
      .data(yTicks)
      .enter()
      .append('line')
      .attr('class', 'grid-line')
      .attr('x1', 0)
      .attr('x2', chartWidth)
      .attr('y1', d => yScale(d))
      .attr('y2', d => yScale(d))
      .attr('stroke', '#e2e8f0')
      .attr('stroke-width', 1);

    // Create line generator
    const line = d3.line()
      .x(d => xScale(d.label || d.date) + xScale.bandwidth() / 2)
      .y(d => yScale(d.count || d.revenue || 0))
      .curve(d3.curveMonotoneX);

    // Add area under line
    const area = d3.area()
      .x(d => xScale(d.label || d.date) + xScale.bandwidth() / 2)
      .y0(chartHeight)
      .y1(d => yScale(d.count || d.revenue || 0))
      .curve(d3.curveMonotoneX);

    g.append('path')
      .datum(data)
      .attr('class', 'area')
      .attr('d', area)
      .attr('fill', color)
      .attr('opacity', 0.1);

    // Add line
    g.append('path')
      .datum(data)
      .attr('class', 'line')
      .attr('d', line)
      .attr('fill', 'none')
      .attr('stroke', color)
      .attr('stroke-width', 2.5)
      .attr('stroke-linecap', 'round')
      .attr('stroke-linejoin', 'round');

    // Add dots
    const dots = g.selectAll('.dot')
      .data(data)
      .enter()
      .append('circle')
      .attr('class', 'dot')
      .attr('cx', d => xScale(d.label || d.date) + xScale.bandwidth() / 2)
      .attr('cy', d => yScale(d.count || d.revenue || 0))
      .attr('r', 4)
      .attr('fill', color)
      .attr('stroke', '#fff')
      .attr('stroke-width', 2)
      .on('mouseover', function(event, d) {
        d3.select(this).attr('r', 6);
        
        // Show tooltip
        const value = d.count || d.revenue || 0;
        const formattedValue = formatNumber(value);
        const textWidth = formattedValue.length * 7; // Approximate width
        const tooltipWidth = Math.max(textWidth + 16, 60);
        
        const tooltip = g.append('g')
          .attr('class', 'tooltip')
          .attr('transform', `translate(${xScale(d.label || d.date) + xScale.bandwidth() / 2},${yScale(value) - 10})`);
        
        tooltip.append('rect')
          .attr('x', -tooltipWidth / 2)
          .attr('y', -20)
          .attr('width', tooltipWidth)
          .attr('height', 20)
          .attr('fill', '#1f2937')
          .attr('rx', 4);
        
        tooltip.append('text')
          .attr('text-anchor', 'middle')
          .attr('y', -5)
          .attr('fill', '#fff')
          .attr('font-size', '12px')
          .text(formattedValue);
      })
      .on('mouseout', function() {
        d3.select(this).attr('r', 4);
        g.selectAll('.tooltip').remove();
      });

    // Add x-axis
    const xAxis = d3.axisBottom(xScale)
      .tickFormat((d) => {
        // For weekly data, show all labels
        return d;
      });

    const xAxisGroup = g.append('g')
      .attr('class', 'x-axis')
      .attr('transform', `translate(0,${chartHeight})`)
      .call(xAxis);
    
    xAxisGroup.selectAll('text')
      .attr('font-size', '11px')
      .attr('fill', '#64748b')
      .attr('transform', 'rotate(-45)')
      .attr('text-anchor', 'end')
      .attr('dx', '-0.5em')
      .attr('dy', '0.5em');

    // Add y-axis
    const yAxis = d3.axisLeft(yScale)
      .ticks(5)
      .tickFormat((d) => {
        if (isRevenue) {
          // Format as Rupiah, show in thousands if large
          if (d >= 1000) {
            return 'Rp ' + (d / 1000).toFixed(0) + 'k';
          }
          return 'Rp ' + d.toFixed(0);
        }
        return d3.format('d')(d);
      });

    g.append('g')
      .attr('class', 'y-axis')
      .call(yAxis)
      .selectAll('text')
      .attr('font-size', '11px')
      .attr('fill', '#64748b');

    // Style axes
    g.selectAll('.x-axis line, .y-axis line')
      .attr('stroke', '#e2e8f0');
    
    g.selectAll('.x-axis path, .y-axis path')
      .attr('stroke', '#e2e8f0');

    // Add legend if legendLabel is provided
    if (legendLabel) {
      const legend = g.append('g')
        .attr('class', 'legend')
        .attr('transform', `translate(${chartWidth - 100}, 10)`);

      // Legend line
      legend.append('line')
        .attr('x1', 0)
        .attr('x2', 20)
        .attr('y1', 0)
        .attr('y2', 0)
        .attr('stroke', color)
        .attr('stroke-width', 2.5);

      // Legend dot
      legend.append('circle')
        .attr('cx', 10)
        .attr('cy', 0)
        .attr('r', 4)
        .attr('fill', color)
        .attr('stroke', '#fff')
        .attr('stroke-width', 2);

      // Legend text
      legend.append('text')
        .attr('x', 25)
        .attr('y', 4)
        .attr('fill', '#64748b')
        .attr('font-size', '12px')
        .attr('font-weight', '500')
        .text(legendLabel);
    }
  }

  onMount(() => {
    if (svgElement) {
      const resizeObserver = new ResizeObserver(() => {
        if (data && data.length > 0) {
          drawChart();
        }
      });
      resizeObserver.observe(svgElement.parentElement);
      
      return () => resizeObserver.disconnect();
    }
  });
</script>

<div class="chart-container">
  {#if title}
    <h3 class="chart-title">{title}</h3>
  {/if}
  <div class="chart-wrapper">
    <svg bind:this={svgElement}></svg>
  </div>
</div>

<style>
  .chart-container {
    width: 100%;
  }

  .chart-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 1rem;
    letter-spacing: -0.01em;
  }

  .chart-wrapper {
    width: 100%;
    overflow: visible;
  }

  :global(.dot) {
    transition: r 0.2s ease;
    cursor: pointer;
  }

  @media (max-width: 768px) {
    .chart-title {
      font-size: 1rem;
    }
  }
</style>

