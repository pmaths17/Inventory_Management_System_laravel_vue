<template>
  <MainLayout>
    <div class="sales-page">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1 font-weight-bold">Sales Management</h2>
          <p class="text-muted mb-0">Manage your product sales</p>
        </div>
        <button class="btn btn-primary btn-lg shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>New Sale
        </button>
      </div>

      <!-- Search and Filter Section -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                  <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0" placeholder="Search by customer ..."
                  v-model="searchQuery" @input="debouncedSearch" />
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="customerFilter" @change="applyFilters">
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="sortBy" @change="applyFilters">
                <option value="date">Sort by Date</option>
                <option value="amount">Sort by Amount</option>
                <option value="customer">Sort by Customer</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Sales Table -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading sales...</p>
          </div>

          <div v-else-if="sales.length === 0" class="text-center py-5">
            <i class="fas fa-cash-register fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No sales found</h5>
            <p class="text-muted">Start by recording your first sale</p>
            <button class="btn btn-primary mt-3" @click="openAddModal">
              <i class="fas fa-plus mr-2"></i>New Sale
            </button>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="border-0">Sale ID</th>
                  <th class="border-0">Customer</th>
                  <th class="border-0">Date</th>
                  <th class="border-0">Total Amount</th>
                  <th class="border-0">Items</th>
                  <th class="border-0">Status</th>
                  <th class="border-0">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sale in filteredSales" :key="sale.id" class="sale-row">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="sale-icon mr-3">
                        <i class="fas fa-cash-register"></i>
                      </div>
                      <span class="font-weight-bold" style="margin-left: 9px;">#{{ sale.id }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="font-weight-bold">{{ sale.customer ? sale.customer.name : 'N/A' }}</div>
                    <small class="text-muted">{{ sale.customer ? sale.customer.email : '' }}</small>
                  </td>
                  <td>{{ formatDate(sale.sale_date) }}</td>
                  <td class="font-weight-bold text-success">{{ formatCurrency(sale.total_amount) }}</td>
                  <td>
                    <span class="badge badge-info">{{ sale.items_count || 0 }} items</span>
                  </td>
                  <td>
                    <span class="badge" :class="getStatusClass(sale.status)">
                      {{ sale.status }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button class="btn btn-sm btn-outline-primary" @click="viewSale(sale)"
                        title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.total > 0" class="p-3 border-top">
            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} sales
              </div>
              <nav>
                <ul class="pagination mb-0">
                  <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                    <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                      Previous
                    </a>
                  </li>
                  <li v-for="page in visiblePages" :key="page" class="page-item"
                    :class="{ active: page === pagination.current_page }">
                    <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                  </li>
                  <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                    <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
                      Next
                    </a>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Sale Modal -->
      <div class="modal fade" id="saleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title">
                <i class="fas fa-cash-register mr-2"></i>New Sale
              </h5>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveSale">
                <!-- Sale Header Info -->
                <div class="row mb-4">
                  <div class="col-md-4 mb-3">
                    <label class="font-weight-bold">Customer <span class="text-danger">*</span></label>
                    <select class="form-control" v-model="form.customer_id" required>
                      <option value="">Select Customer</option>
                      <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="font-weight-bold">Sale Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" v-model="form.sale_date" required />
                  </div>
                </div>

                <!-- Sale Items -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Sale Items</h6>
                    <button type="button" class="btn btn-sm btn-success" @click="addItem">
                      <i class="fas fa-plus mr-1"></i>Add Item
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="bg-light">
                        <tr>
                          <th style="width: 35%">Product</th>
                          <th style="width: 15%">Available Stock</th>
                          <th style="width: 15%">Quantity</th>
                          <th style="width: 20%">Unit Price</th>
                          <th style="width: 20%">Subtotal</th>
                          <th style="width: 5%"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                          <td>
                            <select class="form-control form-control-sm" v-model="item.product_id"
                              @change="updateProductInfo(index)" required>
                              <option value="">Select Product</option>
                              <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.sku }})
                              </option>
                            </select>
                          </td>
                          <td>
                            <div class="form-control form-control-sm bg-light text-center" 
                              :class="{'text-danger': item.available_stock < item.quantity}">
                              {{ item.available_stock !== null ? item.available_stock : '-' }}
                            </div>
                          </td>
                          <td>
                            <input type="number" class="form-control form-control-sm" v-model.number="item.quantity"
                              @input="calculateSubtotal(index)" 
                              :max="item.available_stock"
                              min="1" required />
                            <small v-if="item.quantity > item.available_stock" class="text-danger">
                              Exceeds stock!
                            </small>
                          </td>
                          <td>
                            <div class="form-control form-control-sm bg-light text-right">
                              {{ formatCurrency(item.price) }}
                            </div>
                          </td>
                          <td>
                            <div class="font-weight-bold pt-2">
                              {{ formatCurrency(item.subtotal || 0) }}
                            </div>
                          </td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(index)"
                              :disabled="form.items.length === 1">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="bg-light">
                        <tr>
                          <td colspan="4" class="text-right font-weight-bold">Total Amount:</td>
                          <td colspan="2" class="font-weight-bold text-success">
                            {{ formatCurrency(calculateTotal()) }}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                  <button type="button" class="btn btn-secondary mr-2" @click="closeModal">
                    Cancel
                  </button>
                  <button type="submit" class="btn btn-success" :disabled="saving || form.items.length === 0 || hasInsufficientStock">
                    <span v-if="saving">
                      <span class="spinner-border spinner-border-sm mr-2"></span>
                      Saving...
                    </span>
                    <span v-else>
                      <i class="fas fa-save mr-2"></i>
                      Complete Sale
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- View Sale Modal -->
      <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">
                <i class="fas fa-info-circle mr-2"></i>Sale Details
              </h5>
            </div>
            <div class="modal-body" v-if="selectedSale">
              <!-- Sale Header -->
              <div class="row mb-4">
                <div class="col-md-3">
                  <label class="text-muted small">Sale ID</label>
                  <p class="font-weight-bold">#{{ selectedSale.id }}</p>
                </div>
                <div class="col-md-3">
                  <label class="text-muted small">Customer</label>
                  <p class="font-weight-bold">{{ selectedSale.customer ? selectedSale.customer.name : 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                  <label class="text-muted small">Sale Date</label>
                  <p class="font-weight-bold">{{ formatDate(selectedSale.sale_date) }}</p>
                </div>
                <div class="col-md-3">
                  <label class="text-muted small">Status</label>
                  <p>
                    <span class="badge" :class="getStatusClass(selectedSale.status)">
                      {{ selectedSale.status }}
                    </span>
                  </p>
                </div>
              </div>

              <!-- Sale Items -->
              <div class="mb-4">
                <h6 class="font-weight-bold mb-3">Items Sold</h6>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead class="bg-light">
                      <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in selectedSale.items" :key="item.id">
                        <td>{{ item.product ? item.product.name : 'N/A' }}</td>
                        <td><span class="badge badge-secondary">{{ item.product ? item.product.sku : 'N/A' }}</span></td>
                        <td class="text-right">{{ item.quantity }}</td>
                        <td class="text-right">{{ formatCurrency(item.price) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrency(item.subtotal) }}</td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-light">
                      <tr>
                        <td colspan="4" class="text-right font-weight-bold">Total Amount:</td>
                        <td class="text-right font-weight-bold text-success">
                          {{ formatCurrency(selectedSale.total_amount) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <!-- Timestamps -->
              <div class="row">
                <div class="col-md-6">
                  <label class="text-muted small">Created At</label>
                  <p>{{ formatDateTime(selectedSale.created_at) }}</p>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small">Last Updated</label>
                  <p>{{ formatDateTime(selectedSale.updated_at) }}</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeViewModal">Close</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import api from '@/services/api.js';
import { Modal } from 'bootstrap';

export default {
  name: 'Sales',
  components: { MainLayout },
  data() {
    return {
      sales: [],
      filteredSales: [],
      products: [],
      customers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      customerFilter: '',
      sortBy: 'date',
      pagination: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      },
      form: {
        customer_id: '',
        sale_date: new Date().toISOString().split('T')[0],
        items: [
          {
            product_id: '',
            quantity: 1,
            price: 0,
            subtotal: 0,
            available_stock: null
          }
        ]
      },
      selectedSale: null,
      debounceTimer: null
    };
  },
  computed: {
    visiblePages() {
      const pages = [];
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;

      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
      }
      return pages;
    },
    hasInsufficientStock() {
      return this.form.items.some(item => 
        item.product_id && item.quantity > item.available_stock
      );
    }
  },
  mounted() {
    this.fetchSales();
    this.fetchProducts();
    this.fetchCustomers();
  },
  methods: {
    async fetchSales(page = 1) {
      this.loading = true;
      try {
        const params = { page };
        const response = await api.get('/sales', { params });

        this.sales = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          from: response.data.from,
          to: response.data.to,
          total: response.data.total
        };

        this.applyFilters();
      } catch (error) {
        console.error('Error fetching sales:', error);
        alert('Failed to load sales');
      } finally {
        this.loading = false;
      }
    },

    async fetchProducts() {
      try {
        const response = await api.get('/products?per_page=1000');
        this.products = response.data.data;
      } catch (error) {
        console.error('Error fetching products:', error);
      }
    },

    async fetchCustomers() {
      try {
        const response = await api.get('/customers?per_page=1000');
        this.customers = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },

    applyFilters() {
      let filtered = [...this.sales];

      // Search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(s =>
          (s.customer && s.customer.name.toLowerCase().includes(query)) ||
          (s.id && s.id.toString().includes(query))
        );
      }

      // Customer filter
      if (this.customerFilter) {
        filtered = filtered.filter(s => s.customer_id == this.customerFilter);
      }

      // Sorting
      if (this.sortBy === 'amount') {
        filtered.sort((a, b) => b.total_amount - a.total_amount);
      } else if (this.sortBy === 'customer') {
        filtered.sort((a, b) => {
          const nameA = a.customer ? a.customer.name : '';
          const nameB = b.customer ? b.customer.name : '';
          return nameA.localeCompare(nameB);
        });
      } else {
        filtered.sort((a, b) => new Date(b.sale_date) - new Date(a.sale_date));
      }

      this.filteredSales = filtered;
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.applyFilters();
      }, 300);
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchSales(page);
      }
    },

    openAddModal() {
      this.resetForm();
      const modalEl = document.getElementById('saleModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    async viewSale(sale) {
      try {
        const response = await api.get(`/sales/${sale.id}`);
        this.selectedSale = response.data;
        const modalEl = document.getElementById('viewModal');
        const modal = new Modal(modalEl);
        modal.show();
      } catch (error) {
        console.error('Error fetching sale details:', error);
        alert('Failed to load sale details');
      }
    },

    addItem() {
      this.form.items.push({
        product_id: '',
        quantity: 1,
        price: 0,
        subtotal: 0,
        available_stock: null
      });
    },

    removeItem(index) {
      if (this.form.items.length > 1) {
        this.form.items.splice(index, 1);
      }
    },

    updateProductInfo(index) {
      const item = this.form.items[index];
      const product = this.products.find(p => p.id == item.product_id);
      if (product) {
        item.price = parseFloat(product.sale_price) || 0;
        item.available_stock = product.current_stock;
        this.calculateSubtotal(index);
      }
    },

    calculateSubtotal(index) {
      const item = this.form.items[index];
      item.subtotal = (item.quantity || 0) * (item.price || 0);
    },

    calculateTotal() {
      return this.form.items.reduce((total, item) => total + (item.subtotal || 0), 0);
    },

    async saveSale() {
      // Validate stock before saving
      if (this.hasInsufficientStock) {
        alert('Some items exceed available stock. Please adjust quantities.');
        return;
      }

      this.saving = true;
      try {
        const saleData = {
          customer_id: this.form.customer_id,
          sale_date: this.form.sale_date,
          items: this.form.items.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price
          }))
        };

        await api.post('/sales', saleData);
        alert('Sale completed successfully!');
        this.closeModal();
        this.fetchSales(this.pagination.current_page);
      } catch (error) {
        console.error('Error saving sale:', error);
        const errorMsg = error.response?.data?.message || 'Failed to save sale. Please check all fields.';
        alert(errorMsg);
      } finally {
        this.saving = false;
      }
    },

    closeModal() {
      const modalEl = document.getElementById('saleModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.resetForm();
    },

    closeViewModal() {
      const modalEl = document.getElementById('viewModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.selectedSale = null;
    },

    resetForm() {
      this.form = {
        customer_id: '',
        sale_date: new Date().toISOString().split('T')[0],
        items: [
          {
            product_id: '',
            quantity: 1,
            price: 0,
            subtotal: 0,
            available_stock: null
          }
        ]
      };
    },

    getStatusClass(status) {
      const statusClasses = {
        completed: 'badge-success',
        pending: 'badge-warning',
        cancelled: 'badge-danger'
      };
      return statusClasses[status] || 'badge-secondary';
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
    },

    formatDateTime(datetime) {
      return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
.sales-page {
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

.sale-row {
  transition: all 0.3s ease;
}

.sale-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

.sale-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.btn-group .btn {
  border-radius: 6px;
  margin: 0 2px;
}

.input-group-text {
  border-right: 0;
}

.form-control:focus {
  box-shadow: 0 0 0 0.2rem rgba(230, 233, 235, 0.25);
}

.badge {
  padding: 6px 12px;
  font-weight: 500;
}

.badge-secondary {
  background-color: #6c757d !important;
  color: #fff !important;
}

.badge-info {
  background-color: #17a2b8 !important;
  color: #fff !important;
}

.badge-success {
  background-color: #28a745 !important;
  color: #fff !important;
}

.badge-warning {
  background-color: #ffc107 !important;
  color: #212529 !important;
}

.badge-danger {
  background-color: #dc3545 !important;
  color: #fff !important;
}

.modal-content {
  border-radius: 12px;
}

.pagination .page-link {
  border-radius: 6px;
  margin: 0 3px;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
  border: 1px solid #dee2e6;
}
</style>