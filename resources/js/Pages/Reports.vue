<template>
  <MainLayout>
    <div class="reports-page">
      <div class="mb-4">
        <h2 class="dashboard-title">Analytics & <br> Reports</h2>
        <p class="text-muted small">Comprehensive business performance metrics</p>
      </div>

      <div class="bento-item p-2 mb-4 bg-white shadow-sm d-flex gap-2 flex-wrap">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          class="btn rounded-pill px-4 transition-all"
          :class="activeTab === tab.id ? 'btn-dark' : 'btn-light text-muted'"
        >
          <i :class="tab.icon" class="me-2"></i>{{ tab.label }}
        </button>
      </div>

      <div class="bento-item p-3 mb-4 bg-white shadow-sm border-0">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="small text-muted mb-1">From Date</label>
            <input type="date" v-model="filters.from_date" class="form-control border-0 bg-light rounded-pill">
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">To Date</label>
            <input type="date" v-model="filters.to_date" class="form-control border-0 bg-light rounded-pill">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button @click="fetchData" class="btn btn-dark rounded-pill px-4 flex-fill">
              Generate
            </button>
            <button @click="resetFilters" class="btn btn-light rounded-pill px-3">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-grow text-dark"></div>
        <p class="mt-3 text-muted">Analyzing data...</p>
      </div>

      <div v-else class="bento-item bg-white shadow-sm overflow-hidden">
        
        <div v-if="activeTab === 'stock'" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Product</th>
                <th class="border-0 text-uppercase small text-muted">SKU</th>
                <th class="border-0 text-uppercase small text-muted">Current Stock</th>
                <th class="pe-4 border-0 text-uppercase small text-muted text-end">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in reportData" :key="item.id">
                <td class="ps-4 font-weight-bold">{{ item.name }}</td>
                <td><span class="badge bg-light text-dark border">{{ item.sku }}</span></td>
                <td>{{ item.current_stock }}</td>
                <td class="pe-4 text-end">
                  <span :class="item.current_stock < 10 ? 'text-danger' : 'text-success'">
                    <i class="fas fa-circle small me-1"></i>
                    {{ item.current_stock < 10 ? 'Low Stock' : 'Healthy' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="activeTab === 'sales' || activeTab === 'purchases'" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Date</th>
                <th class="border-0 text-uppercase small text-muted">Entity</th>
                <th class="border-0 text-uppercase small text-muted text-end">Total Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in reportData.data" :key="item.id">
                <td class="ps-4">{{ item.sale_date || item.purchase_date }}</td>
                <td>{{ item.customer ? item.customer.name : (item.supplier ? item.supplier.name : 'N/A') }}</td>
                <td class="pe-4 text-end font-weight-bold">PKR {{ parseFloat(item.total_amount).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import api from '@/services/api.js';

export default {
  components: { MainLayout },
  data() {
    return {
      activeTab: 'stock',
      loading: false,
      reportData: [],
      tabs: [
        { id: 'stock', label: 'Stock Summary', icon: 'fas fa-boxes' },
        { id: 'sales', label: 'Sales Report', icon: 'fas fa-chart-bar' },
        { id: 'purchases', label: 'Purchase Report', icon: 'fas fa-shopping-bag' }
      ],
      filters: {
        from_date: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().substr(0, 10),
        to_date: new Date().toISOString().substr(0, 10)
      }
    };
  },
  watch: {
    activeTab() {
      this.fetchData();
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        let endpoint = '';
        if (this.activeTab === 'stock') endpoint = '/reports/stock-summary';
        if (this.activeTab === 'sales') endpoint = '/reports/sales';
        if (this.activeTab === 'purchases') endpoint = '/reports/purchases';

        const response = await api.get(endpoint, { params: this.filters });
        this.reportData = response.data;
      } catch (error) {
        console.error("Report Fetch Error:", error);
      } finally {
        this.loading = false;
      }
    },
    resetFilters() {
      this.filters.from_date = '';
      this.filters.to_date = '';
      this.fetchData();
    }
  }
};
</script>

<style scoped>
.dashboard-title { font-weight: 800; letter-spacing: -1px; line-height: 1.1; color: #1a1a1a; }
.bento-item { background: white; border-radius: 16px; transition: all 0.2s ease; }
.transition-all { transition: all 0.3s ease; }
</style>