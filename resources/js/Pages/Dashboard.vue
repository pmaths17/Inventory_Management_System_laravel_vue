<template>
  <main-layout>
    <div class="dashboard">
      <h2 class="mb-4 font-weight-bold">Dashboard Overview</h2>

      <!-- Stats Cards Row -->
      <div class="row mb-4">
        <div class="col-md-3 mb-3">
          <stats-card title="Total Products" :value="stats.totalProducts" icon="fas fa-boxes" color="primary"/>
        </div>

        <div class="col-md-3 mb-3">
          <stats-card title="Total Sales" :value="formatCurrency(stats.totalSales)" icon="fas fa-chart-line"
            color="success"/>
        </div>

        <div class="col-md-3 mb-3">
          <stats-card title="Total Purchases" :value="formatCurrency(stats.totalPurchases)" icon="fas fa-shopping-cart"
            color="warning"/>
        </div>

        <div class="col-md-3 mb-3">
          <stats-card title="Low Stock Items" :value="stats.lowStockItems" icon="fas fa-exclamation-triangle"
            color="danger"/>
        </div>
      </div>

      <!-- Recent Activity Row -->
      <div class="row">
        <div class="col-md-8 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
              <h5 class="mb-0 font-weight-bold">Recent Sales</h5>
            </div>
            <div class="card-body">
              <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="sr-only visually-hidden">Loading...</span>
                </div>
              </div>
              <div v-else-if="recentSales.length === 0" class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No recent sales</p>
              </div>
              <div v-else class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Customer</th>
                      <th>Date</th>
                      <th>Amount</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sale in recentSales" :key="sale.id">
                      <td>#{{ sale.id }}</td>
                      <td>{{ sale.customer ? sale.customer.name : 'N/A' }}</td>
                      <td>{{ formatDate(sale.sale_date) }}</td>
                      <td class="font-weight-bold">{{ formatCurrency(sale.total_amount) }}</td>
                      <td class="sale-status">
                        <span class="badge badge-success">{{ sale.status }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
              <h5 class="mb-0 font-weight-bold">Low Stock Alert</h5>
            </div>
            <div class="card-body">
              <div v-if="loadingStock" class="text-center py-5">
                <div class="spinner-border text-danger" role="status">
                  <span class="sr-only visually-hidden">Loading...</span>
                </div>
              </div>
              <div v-else-if="lowStockProducts.length === 0" class="text-center py-5 text-muted">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <p>All products well stocked!</p>
              </div>
              <ul v-else class="list-group list-group-flush">
                <li v-for="product in lowStockProducts" :key="product.id"
                  class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="font-weight-bold">{{ product.name }}</div>
                    <small class="text-muted">SKU: {{ product.sku }}</small>
                  </div>
                  <span class="badge badge-danger badge-pill">{{ product.current_stock }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main-layout>
</template>

<script>
import MainLayout from '../Layouts/MainLayout.vue';
import StatsCard from '../Components/StatsCard.vue';
import api from '@/services/api.js';
import authApi from '@/services/authApi.js';

export default {
  name: 'Dashboard',
  components: {
    MainLayout,
    StatsCard
  },
  data() {
    return {
      loading: false,
      loadingStock: false,
      stats: {
        totalProducts: 0,
        totalSales: 0,
        totalPurchases: 0,
        lowStockItems: 0
      },
      recentSales: [],
      lowStockProducts: []
    };
  },
  mounted() {
    this.fetchDashboardData();
  },
  methods: {
    async fetchDashboardData() {
      this.loading = true;
      this.loadingStock = true;

      try {
        // Fetch stats
        await authApi.get('/sanctum/csrf-cookie');
        const [productsRes, salesRes, lowStockRes] = await Promise.all([
          this.$axios.get('/products'),
          this.$axios.get('/sales'),
          this.$axios.get('/reports/low-stock?threshold=10')
        ]);

        this.stats.totalProducts = productsRes.data.total || 0;
        this.recentSales = salesRes.data.data.slice(0, 5) || [];

        // Calculate total sales
        this.stats.totalSales = salesRes.data.data.reduce((sum, sale) => {
          return sum + parseFloat(sale.total_amount);
        }, 0);

        this.lowStockProducts = lowStockRes.data.slice(0, 5) || [];
        this.stats.lowStockItems = lowStockRes.data.length || 0;

        // Fetch purchases for stats
        const purchasesRes = await this.$axios.get('/purchases');
        this.stats.totalPurchases = purchasesRes.data.data.reduce((sum, purchase) => {
          return sum + parseFloat(purchase.total_amount);
        }, 0);

      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      } finally {
        this.loading = false;
        this.loadingStock = false;
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR'
      }).format(amount);
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }
  }
};
</script>

<style scoped>
.dashboard {
  animation: fadeIn 0.5s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card {
  border-radius: 12px;
}

.table {
  margin-bottom: 0;
}

.badge {
  padding: 6px 12px;
  border-radius: 6px;
}

.sale-status .badge {
  color:black;
  font-weight: 500;
}
</style>