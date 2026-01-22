<template>
  <MainLayout>
    <div class="products-page">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1 font-weight-bold">Products Management</h2>
          <p class="text-muted mb-0">Manage your inventory products</p>
        </div>
        <button class="btn btn-primary btn-lg shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>Add Product
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
                <input
                  type="text"
                  class="form-control border-left-0"
                  placeholder="Search by name or SKU..."
                  v-model="searchQuery"
                  @input="debouncedSearch"
                />
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="stockFilter" @change="fetchProducts">
                <option value="all">All Stock Levels</option>
                <option value="low">Low Stock Only</option>
                <option value="out">Out of Stock</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="sortBy" @change="fetchProducts">
                <option value="name">Sort by Name</option>
                <option value="stock">Sort by Stock</option>
                <option value="price">Sort by Price</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Products Grid/Table -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading products...</p>
          </div>

          <div v-else-if="products.length === 0" class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No products found</h5>
            <p class="text-muted">Start by adding your first product</p>
            <button class="btn btn-primary mt-3" @click="openAddModal">
              <i class="fas fa-plus mr-2"></i>Add Product
            </button>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="border-0">Product</th>
                  <th class="border-0">SKU</th>
                  <th class="border-0">Purchase Price</th>
                  <th class="border-0">Sale Price</th>
                  <th class="border-0">Stock</th>
                  <th class="border-0">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in products" :key="product.id" class="product-row">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="product-icon mr-3">
                        <i class="fas fa-box"></i>
                      </div>
                      <div>
                        <div class="font-weight-bold">{{ product.name }}</div>
                        <small class="text-muted">ID: #{{ product.id }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge-secondary">{{ product.sku }}</span>
                  </td>
                  <td class="font-weight-bold">{{ formatCurrency(product.purchase_price) }}</td>
                  <td class="font-weight-bold text-success">{{ formatCurrency(product.sale_price) }}</td>
                  <td>
                    <span 
                      class="badge badge-pill"
                      :class="getStockBadgeClass(product.current_stock)"
                    >
                      {{ product.current_stock }} units
                    </span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button 
                        class="btn btn-sm btn-outline-primary" 
                        @click="viewProduct(product)"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-outline-warning" 
                        @click="editProduct(product)"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-outline-danger" 
                        @click="confirmDelete(product)"
                        title="Delete"
                      >
                        <i class="fas fa-trash"></i>
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
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} products
              </div>
              <nav>
                <ul class="pagination mb-0">
                  <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                    <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                      Previous
                    </a>
                  </li>
                  <li 
                    v-for="page in visiblePages" 
                    :key="page"
                    class="page-item"
                    :class="{ active: page === pagination.current_page }"
                  >
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

      <!-- Add/Edit Product Modal -->
      <div class="modal fade" id="productModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">
                <i class="fas fa-box mr-2"></i>
                {{ isEditing ? 'Edit Product' : 'Add New Product' }}
              </h5>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveProduct">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">Product Name <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.name"
                      placeholder="Enter product name"
                      required
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">SKU <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.sku"
                      placeholder="Enter SKU"
                      required
                    />
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">Purchase Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text py-3">PKR</span>
                      </div>
                      <input
                        type="number"
                        step="0.01"
                        class="form-control"
                        v-model="form.purchase_price"
                        placeholder="0.00"
                        required
                      />
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">Sale Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text py-3">PKR</span>
                      </div>
                      <input
                        type="number"
                        step="0.01"
                        class="form-control"
                        v-model="form.sale_price"
                        placeholder="0.00"
                        required
                      />
                    </div>
                  </div>
                </div>

                <div v-if="form.sale_price && form.purchase_price" class="alert alert-info">
                  <i class="fas fa-info-circle mr-2"></i>
                  Profit Margin: <strong>{{ calculateProfitMargin() }}%</strong>
                </div>

                <div class="d-flex justify-content-end mt-4">
                  <button type="button" class="btn btn-secondary mr-2" @click="closeModal">
                    Cancel
                  </button>
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving">
                      <span class="spinner-border spinner-border-sm mr-2"></span>
                      Saving...
                    </span>
                    <span v-else>
                      <i class="fas fa-save mr-2"></i>
                      {{ isEditing ? 'Update Product' : 'Create Product' }}
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- View Product Modal -->
      <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">
                <i class="fas fa-info-circle mr-2"></i>Product Details
              </h5>
            </div>
            <div class="modal-body" v-if="selectedProduct">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">Product Name</label>
                  <p class="font-weight-bold">{{ selectedProduct.name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">SKU</label>
                  <p><span class="badge badge-secondary">{{ selectedProduct.sku }}</span></p>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="text-muted small">Purchase Price</label>
                  <p class="font-weight-bold">{{ formatCurrency(selectedProduct.purchase_price) }}</p>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="text-muted small">Sale Price</label>
                  <p class="font-weight-bold text-success">{{ formatCurrency(selectedProduct.sale_price) }}</p>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="text-muted small">Current Stock</label>
                  <p>
                    <span 
                      class="badge badge-pill badge-lg"
                      :class="getStockBadgeClass(selectedProduct.current_stock)"
                    >
                      {{ selectedProduct.current_stock }} units
                    </span>
                  </p>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">Total Purchases</label>
                  <p class="font-weight-bold">{{ selectedProduct.purchase_items_count || 0 }} transactions</p>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">Total Sales</label>
                  <p class="font-weight-bold">{{ selectedProduct.sale_items_count || 0 }} transactions</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeViewModal">Close</button>
              <button type="button" class="btn btn-warning" @click="editFromView">
                <i class="fas fa-edit mr-2"></i>Edit Product
              </button>
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
        from: 0,
        to: 0,
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
      this.loading = true;
      try {
        const params = { page };
        const response = await api.get('/products', { params });

        this.products = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          from: response.data.from,
          to: response.data.to,
          total: response.data.total
        };

        // Apply filters
        this.applyFilters();
      } catch (error) {
        console.error('Error fetching products:', error);
        alert('Failed to load products');
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      let filtered = [...this.products];

      // Search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(p =>
          p.name.toLowerCase().includes(query) ||
          p.sku.toLowerCase().includes(query)
        );
      }

      // Stock filter
      if (this.stockFilter === 'low') {
        filtered = filtered.filter(p => p.current_stock > 0 && p.current_stock <= 10);
      } else if (this.stockFilter === 'out') {
        filtered = filtered.filter(p => p.current_stock === 0);
      }

      // Sorting
      if (this.sortBy === 'stock') {
        filtered.sort((a, b) => a.current_stock - b.current_stock);
      } else if (this.sortBy === 'price') {
        filtered.sort((a, b) => b.sale_price - a.sale_price);
      }

      this.products = filtered;
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.applyFilters();
      }, 300);
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchProducts(page);
      }
    },

    openAddModal() {
      this.isEditing = false;
      this.resetForm();
      const modalEl = document.getElementById('productModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    editProduct(product) {
      this.isEditing = true;
      this.editingId = product.id;
      this.form = {
        name: product.name,
        sku: product.sku,
        purchase_price: product.purchase_price,
        sale_price: product.sale_price
      };
      const modalEl = document.getElementById('productModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    viewProduct(product) {
      this.selectedProduct = product;
      const modalEl = document.getElementById('viewModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    editFromView() {
      const modalEl = document.getElementById('viewModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.editProduct(this.selectedProduct);
    },

    async saveProduct() {
      this.saving = true;
      try {
        if (this.isEditing) {
          await api.put(`/products/${this.editingId}`, this.form);
          alert('Product updated successfully!');
        } else {
          await api.post('/products', this.form);
          alert('Product created successfully!');
        }
        this.closeModal();
        this.fetchProducts(this.pagination.current_page);
      } catch (error) {
        console.error('Error saving product:', error);
        alert('Failed to save product');
      } finally {
        this.saving = false;
      }
    },

    async confirmDelete(product) {
      if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
        try {
          await api.delete(`/products/${product.id}`);
          alert('Product deleted successfully!');
          this.fetchProducts(this.pagination.current_page);
        } catch (error) {
          console.error('Error deleting product:', error);
          alert('Failed to delete product');
        }
      }
    },

    closeModal() {
      const modalEl = document.getElementById('productModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.resetForm();
    },

    closeViewModal() {
      const modalEl = document.getElementById('viewModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.selectedProduct = null;
    },

    resetForm() {
      this.form = {
        name: '',
        sku: '',
        purchase_price: '',
        sale_price: ''
      };
      this.editingId = null;
    },

    calculateProfitMargin() {
      const profit = this.form.sale_price - this.form.purchase_price;
      const margin = (profit / this.form.purchase_price) * 100;
      return margin.toFixed(2);
    },

    getStockBadgeClass(stock) {
      if (stock === 0) return 'badge-danger';
      if (stock <= 10) return 'badge-warning';
      return 'badge-success';
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR'
      }).format(amount);
    }
  }
};
</script>


<style scoped>
  /* fixed */
  /* Force text color for badges to be visible */
.badge {
  color: #212529 !important; /* dark text for visibility */
}

/* Optionally, make SKU and stock badges more readable */
.badge-secondary {
  background-color: #6c757d !important;
  color: #fff !important; /* white text on gray background */
}

.badge-pill.badge-lg {
  color: #212529 !important; /* dark text for large badges in view modal */
  background-color: #f1f3f5 !important; /* light gray background */
}

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

.card {
  border-radius: 12px;
}

.product-row {
  transition: all 0.3s ease;
}

.product-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

.product-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2441c0 0%, #1924e3 100%);
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

.badge-lg {
  font-size: 1rem;
  padding: 8px 16px;
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

 
</style>