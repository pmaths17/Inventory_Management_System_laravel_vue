<template>
  <MainLayout>
    <div class="products-page">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="dashboard-title">Inventory <br> Management</h2>
          <p class="text-muted small">You have {{ pagination.total }} products in your catalog</p>
        </div>
        <button v-if="canCreateProducts" class="btn btn-dark btn-lg rounded-pill px-4 shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>Add Product
        </button>
      </div>

      <div class="bento-item p-3 mb-4 bg-white shadow-sm border-0">
        <div class="row g-3 align-items-center">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="fas fa-search text-muted"></i>
              <input type="text" class="form-control border-0 bg-light rounded-pill"
                placeholder="Search products or SKU..." v-model="searchQuery" @input="debouncedSearch" />
            </div>
          </div>
          <div class="col-md-7 d-flex gap-2 justify-content-md-end">
            <select class="form-select border-0 bg-light rounded-pill" v-model="stockFilter" @change="fetchProducts">
              <option value="all">All Levels</option>
              <option value="low">Low Stock</option>
              <option value="out">Out of Stock</option>
            </select>
            <select class="form-select border-0 bg-light rounded-pill" v-model="sortBy" @change="fetchProducts">
              <option value="id">Sort: ID</option>
              <option value="name">Sort: Name</option>
              <option value="stock">Sort: Stock</option>
              <option value="price">Sort: Price</option>
            </select>
          </div>
        </div>
      </div>

      <div class="bento-item p-0 bg-white shadow-sm border-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-grow text-dark" role="status"></div>
          <p class="mt-3 text-muted">Refreshing catalog...</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Product Details</th>
                <th class="border-0 text-uppercase small text-muted">SKU</th>
                <th class="border-0 text-uppercase small text-muted text-center">Stock Status</th>
                <th class="border-0 text-uppercase small text-muted text-end">Purchase Price</th>
                <th class="border-0 text-uppercase small text-muted text-end">Sale Price</th>
                <th class="border-0 text-uppercase small text-muted text-center">Locked Stock</th>
                <th v-if="canUpdateProducts || canDeleteProducts" class="pe-4 border-0 text-uppercase small text-muted text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products" :key="product.id" class="product-row">
                <!-- Product Details -->
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="product-icon-mini me-3">
                      <i class="fas fa-box"></i>
                    </div>
                    <div>
                      <div class="font-weight-bold text-dark">{{ product.name }}</div>
                      <small class="text-muted">ID: #{{ product.id }}</small>
                    </div>
                  </div>
                </td>
                <!-- SKU -->
                <td><span class="sku-badge">{{ product.sku }}</span></td>
                <!-- Stock Status -->
                <td class="text-center">
                  <span :class="getStockBadgeClass(product.current_stock)" class="status-dot">
                    {{ product.current_stock }} units
                  </span>
                </td>
                <!-- Purchase Price -->
                <td class="text-end">{{ formatCurrency(product.purchase_price) }}</td>
                <!-- Sale Price -->
                <td class="text-end font-weight-bold">{{ formatCurrency(product.sale_price) }}</td>
                <!-- Locked Stock -->
                <td class="text-center">{{ product.locked_stock }}</td>
                <!-- Action Buttons -->
                <td v-if="canUpdateProducts || canDeleteProducts" class="pe-4 text-end">
                  <div class="action-buttons">
                    <button class="btn-icon view" @click="viewProduct(product)"><i class="fas fa-eye"></i></button>
                    <button v-if="canUpdateProducts" class="btn-icon edit" @click="editProduct(product)"><i class="fas fa-edit"></i></button>
                    <button v-if="canDeleteProducts" class="btn-icon delete" @click="confirmDelete(product)"><i
                        class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="products.length === 0 && !loading" class="text-center py-5">
            <i class="fas fa-search mb-3 text-muted" style="font-size: 3rem;"></i>
            <h5>No products found</h5>
            <p class="text-muted">Try adjusting your filters or search term.</p>
            <button class="btn btn-outline-dark rounded-pill mt-2"
              @click="searchQuery = ''; stockFilter = 'all'; fetchProducts(1);">
              Clear All Filters
            </button>
          </div>
        </div>


        <div class="p-3 bg-light d-flex justify-content-between align-items-center">
          <span class="small text-muted ps-2">Total: {{ pagination.total }} items</span>
          <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link rounded-pill px-3 me-1" @click="changePage(pagination.current_page - 1)">
                  <i class="fas fa-chevron-left small"></i>
                </button>
              </li>

              <li v-for="page in visiblePages" :key="page" class="page-item"
                :class="{ active: pagination.current_page === page }">
                <button class="page-link rounded-circle d-flex align-items-center justify-content-center"
                  style="width: 32px; height: 32px;" @click="changePage(page)">
                  {{ page }}
                </button>
              </li>

              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link rounded-pill px-3 ms-1" @click="changePage(pagination.current_page + 1)">
                  <i class="fas fa-chevron-right small"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
          <div class="modal-header border-0 pb-0 pt-4 px-4">
            <h5 class="modal-title font-weight-bold">
              {{ isEditing ? 'Edit Product' : 'New Product' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
              @click="closeModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveProduct">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="small text-muted mb-1">Product Name</label>
                  <input type="text" class="form-control bg-light border-0 rounded-pill px-3" v-model="form.name"
                    required />
                </div>
                <div class="col-md-6">
                  <label class="small text-muted mb-1">SKU</label>
                  <input type="text" class="form-control bg-light border-0 rounded-pill px-3" v-model="form.sku"
                    placeholder="ABC-12345" @input="form.sku = form.sku.toUpperCase()" required />
                </div>
                <div class="col-md-6">
                  <label class="small text-muted mb-1">Purchase Price (PKR)</label>
                  <input type="number" step="0.01" class="form-control bg-light border-0 rounded-pill px-3"
                    v-model="form.purchase_price" required />
                </div>
                <div class="col-md-6">
                  <label class="small text-muted mb-1">Sale Price (PKR)</label>
                  <input type="number" step="0.01" class="form-control bg-light border-0 rounded-pill px-3"
                    v-model="form.sale_price" required />
                </div>
              </div>

              <div v-if="form.sale_price && form.purchase_price"
                class="mt-3 p-2 px-3 bg-light rounded-pill d-inline-block small">
                Profit Margin: <span class="text-success font-weight-bold">{{ calculateProfitMargin() }}%</span>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-dark rounded-pill px-4" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEditing ? 'Update' : 'Create Product' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
          <div class="modal-body p-4" v-if="selectedProduct">
            <div class="d-flex justify-content-between mb-4">
              <h5 class="font-weight-bold">Product Summary</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                @click="closeViewModal"></button>
            </div>

            <div class="text-center mb-4">
              <div class="product-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                <i class="fas fa-box"></i>
              </div>
              <h4>{{ selectedProduct.name }}</h4>
              <span class="sku-badge">{{ selectedProduct.sku }}</span>
            </div>

            <div class="row g-3 text-center">
              <div class="col-6 p-3 bg-light rounded-start border-end">
                <small class="text-muted d-block">Cost</small>
                <strong>{{ formatCurrency(selectedProduct.purchase_price) }}</strong>
              </div>
              <div class="col-6 p-3 bg-light rounded-end">
                <small class="text-muted d-block">Price</small>
                <strong class="text-success">{{ formatCurrency(selectedProduct.sale_price) }}</strong>
              </div>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center">
              <span :class="getStockBadgeClass(selectedProduct.current_stock)" class="status-dot">
                {{ selectedProduct.current_stock }} units in stock
              </span>
              <button class="btn btn-dark rounded-pill px-4" @click="editFromView">Edit</button>
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
import { getStoredUser, hasPermission, isAdminUser } from '@/utils/authz.js';

export default {
  name: 'Products',
  components: { MainLayout },
  data() {
    return {
      products: [],
      loading: false,
      saving: false,
      searchQuery: '',
      stockFilter: 'all',
      sortBy: 'name',
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0
      },
      form: {
        name: '',
        sku: '',
        purchase_price: '',
        sale_price: ''
      },
      isEditing: false,
      editingId: null,
      selectedProduct: null,
      debounceTimer: null
    };
  },
  computed: {
    currentUser() {
      return getStoredUser() || {};
    },
    isAdmin() {
      return isAdminUser(this.currentUser);
    },
    canCreateProducts() {
      return hasPermission(this.currentUser, 'products.create');
    },
    canUpdateProducts() {
      return hasPermission(this.currentUser, 'products.update');
    },
    canDeleteProducts() {
      return hasPermission(this.currentUser, 'products.delete');
    },
    visiblePages() {
      const pages = [];
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    async fetchProducts(page = 1) {
      if (typeof page !== 'number') {
        page = 1;
      }
      this.loading = true;
      try {
        // We send all current filter states to the Laravel Controller
        const params = {
          page,
          search: this.searchQuery,
          stock_status: this.stockFilter,
          sort_by: this.sortBy
        };

        const response = await api.get('/products', { params });

        // Laravel now returns ONLY the products that match the filter
        this.products = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          from: response.data.from,
          to: response.data.to,
          total: response.data.total
        };
      } catch (error) {
        console.error('Error fetching products:', error);
        alert('Failed to load products');
      } finally {
        this.loading = false;
      }
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.fetchProducts(1); // Reset to page 1 when searching
      }, 300);
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchProducts(page);
      }
    },

    // Modal & Form Logic
    openAddModal() {
      this.isEditing = false;
      this.resetForm();
      new Modal(document.getElementById('productModal')).show();
    },

    editProduct(product) {
      this.isEditing = true;
      this.editingId = product.id;
      this.form = { ...product };
      new Modal(document.getElementById('productModal')).show();
    },

    viewProduct(product) {
      this.selectedProduct = product;
      new Modal(document.getElementById('viewModal')).show();
    },

    async saveProduct() {
      this.saving = true;
      try {
        if (this.isEditing) {
          await api.put(`/products/${this.editingId}`, this.form);
        } else {
          await api.post('/products', this.form);
        }
        this.closeModal('productModal');
        this.fetchProducts(this.pagination.current_page);
      } catch (error) {
        alert('Error saving product');
      } finally {
        this.saving = false;
      }
    },

    async confirmDelete(product) {
      if (confirm(`Delete "${product.name}"?`)) {
        try {
          await api.delete(`/products/${product.id}`);
          this.fetchProducts(this.pagination.current_page);
        } catch (error) {
          alert('Delete failed');
        }
      }
    },

    // Helpers
    closeModal(id) {
      const modalEl = document.getElementById(id);
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      if (id === 'productModal') this.resetForm();
    },

    resetForm() {
      this.form = { name: '', sku: '', purchase_price: '', sale_price: '' };
      this.editingId = null;
    },

    calculateProfitMargin() {
      const p = parseFloat(this.form.purchase_price);
      const s = parseFloat(this.form.sale_price);
      if (!p || p <= 0) return '0.00';
      return (((s - p) / p) * 100).toFixed(2);
    },

    getStockBadgeClass(stock) {
      if (stock === 0) return 'badge-danger';
      return stock <= 10 ? 'badge-warning' : 'badge-success';
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR'
      }).format(amount);
    },

    editFromView() {
      const product = { ...this.selectedProduct };
      this.closeModal('viewModal');
      this.editProduct(product);
    }
  }
};
</script>


<style scoped>
/* fixed-end */
.products-page {
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


.product-row {
  transition: all 0.3s ease;
}

.product-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}


.modal-content {
  border-radius: 12px;
}



/* Bento Grid Item Base */
.bento-item {
  background: white;
  border-radius: 16px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Page Title Style */
.dashboard-title {
  font-weight: 800;
  letter-spacing: -1px;
  line-height: 1.1;
  color: #1a1a1a;
}

/* Search Wrapper */
.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-wrapper i {
  position: absolute;
  left: 15px;
  z-index: 5;
}

.search-wrapper .form-control {
  padding-left: 40px;
  height: 45px;
}


.sku-badge {
  background: #e9ecef;
  padding: 4px 10px;
  border-radius: 6px;
  font-family: monospace;
  font-size: 0.85rem;
  color: #495057;
}

/* Status Badges - Dot Style */
.status-dot {
  padding: 5px 12px;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-success {
  background: #e6fcf5;
  color: #0ca678 !important;
}

.badge-warning {
  background: #fff9db;
  color: #f08c00 !important;
}

.badge-danger {
  background: #fff5f5;
  color: #f03e3e !important;
}

/* Custom Action Buttons */
.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  margin-left: 4px;
  transition: 0.2s;
}

.btn-icon.view:hover {
  background: #f0f7ff;
  color: #007bff;
}

.btn-icon.edit:hover {
  background: #fff9db;
  color: #f08c00;
}

.btn-icon.delete:hover {
  background: #fff5f5;
  color: #f03e3e;
}

/* Form Select Styling */
.form-select {
  height: 45px;
  font-size: 0.9rem;
  padding: 0 20px;
  cursor: pointer;
}

/* Modern Pagination Styling */
.pagination .page-link {
  border: none;
  background-color: transparent;
  color: #6c757d;
  font-weight: 600;
  transition: all 0.2s ease;
}

.pagination .page-item.active .page-link {
  background-color: #1a1a1a;
  /* Match your dark theme */
  color: #fff !important;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.pagination .page-item:not(.active):hover .page-link {
  background-color: #e9ecef;
  color: #1a1a1a;
}

.pagination .page-item.disabled .page-link {
  background-color: transparent;
  opacity: 0.4;
}
</style>
