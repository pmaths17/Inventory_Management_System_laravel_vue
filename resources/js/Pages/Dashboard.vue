<template>
  <main-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="dashboard-title">Hi, here's what's happening <br> in your stores</h2>
      <div class="d-flex gap-2 filter-tabs">
        <div class="btn-group bg-white rounded-pill p-1 shadow-sm">
          <button @click="updateChartDays(1)" :class="{ 'active': activeDays === 1 }"
            class="btn btn-sm rounded-pill">Today</button>
          <button @click="updateChartDays(7)" :class="{ 'active': activeDays === 7 }"
            class="btn btn-sm rounded-pill">This
            Week</button>
          <button @click="updateChartDays(30)" :class="{ 'active': activeDays === 30 }"
            class="btn btn-sm rounded-pill">This Month</button>
        </div>
      </div>
    </div>

    <div class="bento-grid">
      <div class="bento-item main-stats-card p-4 mb-4">
        <div class="row align-items-center">
          <div class="col-md-4">
            <p class="text-muted mb-1">
              In the last {{ activeDays === 1 ? '24 hours' : activeDays + ' days' }} your stores sold
            </p>
            <h1 class="display-5 font-weight-bold">{{ formatCurrency(stats.totalSales) }}</h1>
            <p class="text-success small">Revenue performance active</p>
          </div>
          <div class="col-md-5">
            <apexchart type="area" height="160" :options="sparklineOptions" :series="chartSeries">
            </apexchart>
          </div>
          <div class="col-md-3 border-start">
            <div class="mb-4">
              <p class="text-muted small mb-0">Low Stock Alerts</p>
              <h3>{{ stats.lowStockItems }}</h3>
            </div>
            <div>
              <p class="text-muted small mb-0">Total Products</p>
              <h3>{{ stats.totalProducts }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-7">
          <div class="bento-item p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-4">
              <h5 class="font-weight-bold">Daily Sales Volume</h5>
            </div>
            <apexchart type="bar" height="250" :options="barChartOptions" :series="chartSeries">
            </apexchart>
          </div>
        </div>

        <div class="col-md-5 d-flex flex-column gap-4">
          <div class="bento-item p-4 flex-fill alert-card">
            <h5 class="font-weight-bold">Inventory Status</h5>
            <p class="text-muted small">You have {{ stats.lowStockItems }} items below threshold.</p>
            <!-- <router-link to="/reports" class="btn btn-dark btn-sm rounded-pill px-3">View Report</router-link> -->
            <router-link to="/reports" class="btn btn-dark btn-sm rounded-pill px-3" :class="{ disabled: !isAdmin }"
              :aria-disabled="!isAdmin" @click.prevent="!isAdmin">
              View Report
            </router-link>

            <p v-if="!isAdmin" class="text-muted small mt-2 mb-0">
              Reports are available to administrators only.
            </p>

          </div>

          <div class="bento-item p-4 flex-fill info-card">
            <h5 class="font-weight-bold">Purchase Summary</h5>
            <p class="text-muted small">Total Cost: {{ formatCurrency(stats.totalPurchases) }}</p>
          </div>
        </div>
      </div>
    </div>
    <div v-if="!isAdmin" class="bento-item p-4 text-center">
  <i class="fas fa-lock fa-2x text-muted mb-3"></i>
  <h5 class="fw-bold">Limited Access</h5>
  <p class="text-muted mb-0">
    This dashboard is available for administrators only.<br>
    Your account currently has limited access.
  </p>
</div>
<p class="text-danger small">
   Name: {{ user.name }} | Role: {{ user.role }}
</p>
  </main-layout>
</template>

<script>
import MainLayout from '../Layouts/MainLayout.vue';
import VueApexCharts from 'vue-apexcharts';

export default {
  name: 'Dashboard',
  components: {
    MainLayout,
    apexchart: VueApexCharts, // Register the component
  },
  data() {
    return {
      user: null,
      activeDays: 30,
      stats: { totalProducts: 0, totalSales: 0, totalPurchases: 0, lowStockItems: 0 },
      chartSeries: [{ name: 'Sales', data: [] }],

      // Sparkline (Top Area Chart) Options
      sparklineOptions: {
        chart: { sparkline: { enabled: true }, animations: { enabled: true } },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
        colors: ['#1a1a1a'],
        tooltip: { x: { show: false }, y: { title: { formatter: (val) => 'Total PKR' } } }
      },

      // Bar Chart Options
      barChartOptions: {
        chart: { toolbar: { show: false } },
        colors: ['#1a1a1a'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '35%' } },
        xaxis: { categories: [], axisBorder: { show: false } },
        dataLabels: { enabled: false },
        grid: { show: false }
      }
    };
  },
  mounted() {
    this.fetchDashboardData();
    this.fetchChartData(30);
    this.fetchCurrentUser();
  },
  computed: {
    isAdmin() {
    return this.user?.role === 'admin'; // <-- use fetched user
  }
  },

  methods: {
    async fetchCurrentUser() {
    try {
      const res = await this.$axios.get('/user'); // <-- Laravel endpoint for current user
      this.user = res.data;
    } catch (err) {
      console.error('Error fetching user:', err);
    }
  },
    async updateChartDays(days) {
      this.activeDays = days;
      await this.fetchChartData(days);
    },

    async fetchChartData(days) {
      try {
        const res = await this.$axios.get(`/reports/sales-chart?days=${days}`);
        const labels = res.data.map(item => item.date);
        const values = res.data.map(item => parseFloat(item.total));

        this.chartSeries = [{ name: 'Revenue', data: values }];

        // Update bar chart categories
        this.barChartOptions = {
          ...this.barChartOptions,
          xaxis: { categories: labels }
        };
      } catch (error) {
        console.error("Error fetching chart:", error);
      }
    },

    async updateChartDays(days) {
      this.activeDays = days;
      // Fetch both simultaneously
      await Promise.all([
        this.fetchChartData(days),
        this.fetchDashboardData(days)
      ]);
    },

    async fetchDashboardData(days = 30) {
      try {
        // We hit the new summary endpoint with the 'days' filter
        const res = await this.$axios.get(`/reports/dashboard-summary?days=${days}`);

        // Update the stats object
        this.stats = {
          totalProducts: res.data.totalProducts,
          totalSales: res.data.totalSales,
          totalPurchases: res.data.totalPurchases,
          lowStockItems: res.data.lowStockItems,
          profit: res.data.profit // You can now display this too!
        };
      } catch (error) {
        console.error('Error fetching stats:', error);
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR' }).format(amount);
    }
  }
};
</script>

<style scoped>
/* Inherit your existing styles */
.active {
  background-color: #1a1a1a !important;
  color: white !important;
}

.alert-card {
  background: #fffcf0;
  border: 1px solid #ffeeba;
}

.info-card {
  background: #f0f7ff;
}
</style>