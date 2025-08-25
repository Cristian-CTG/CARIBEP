<template>
  <div class="chart-container">
    <div class="empty-state" v-if="!hasData">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="120"
        height="120"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="text-muted"
      >
        <path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
        <path d="M12 8h2a1 1 0 0 1 1 1v2m0 4v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-10" />
        <path d="M15 11v-6a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v12m-1 3h-4a1 1 0 0 1 -1 -1v-4" />
        <path d="M4 20h14" />
        <path d="M3 3l18 18" />
      </svg>
      <p class="text-muted" style="font-weight: normal; font-size: 15px;">{{ emptyText }}</p>
    </div>

    <canvas v-show="hasData" ref="canvas"></canvas>
  </div>
</template>

<script>
import Chart from 'chart.js';

export default {
  props: {
    type: { type: String, required: true },
    allData: {
      type: Object,
      default: () => ({ labels: [], datasets: [] })
    },
    emptyText: { type: String, default: 'Sin datos para mostrar' },
  },
  data () {
    return {
      chart: null,
      options: {
        maintainAspectRatio: false,
        legend: { display: false },
        elements: { line: { tension: 0 } }
      }
    }
  },
  computed: {
    hasData () {
      const labels = this.allData?.labels || [];
      const datasets = this.allData?.datasets || [];
      if (!labels.length || !datasets.length) return false;

      return datasets.some(ds => {
        const arr = Array.isArray(ds.data) ? ds.data : [];
        return arr.some(v => typeof v === 'number' && !isNaN(v) && v !== 0);
      });
    }
  },
  watch: {
    allData: {
      immediate: true,
      deep: true,
      handler () {
        this.refreshChart();
      }
    }
  },
  methods: {
    refreshChart () {
      if (!this.hasData) {
        if (this.chart) {
          this.chart.destroy();
          this.chart = null;
        }
        return;
      }

      if (this.chart) this.chart.destroy();

      const ctx = this.$refs.canvas.getContext('2d');
      this.chart = new Chart(ctx, {
        type: this.type,
        data: {
          labels: this.allData.labels,
          datasets: this.allData.datasets
        },
        options: this.options
      });
    }
  },
  beforeDestroy () {
    if (this.chart) this.chart.destroy();
  }
}
</script>

<style scoped>
.chart-container {
  position: relative;
  margin: auto;
  height: 260px;
  width: 190px;
}
.chart-container .chartjs-render-monitor {
  height: inherit !important;
}

.empty-state {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  text-align: center;
  padding: .75rem;
  opacity: .8;
}
</style>
