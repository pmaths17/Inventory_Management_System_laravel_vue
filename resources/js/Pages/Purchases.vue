<template>
  <MainLayout>
    <div class="purchases-page">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1 font-weight-bold">Purchases Management</h2>
          <p class="text-muted mb-0">Manage your inventory purchases</p>
        </div>
        <button class="btn btn-primary btn-lg shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>New Purchase
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
                <input type="text" class="form-control border-start-0" placeholder="Search by supplier ..."
                  v-model="searchQuery" @input="debouncedSearch" />
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="supplierFilter" @change="applyFilters">
                <option value="">All Suppliers</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplier.name }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="sortBy" @change="applyFilters">
                <option value="date">Sort by Date</option>
                <option value="amount">Sort by Amount</option>
                <option value="supplier">Sort by Supplier</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Purchases Table -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading purchases...</p>
          </div>

          <div v-else-if="purchases.length === 0" class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No purchases found</h5>
            <p class="text-muted">Start by recording your first purchase</p>
            <button class="btn btn-primary mt-3" @click="openAddModal">
              <i class="fas fa-plus mr-2"></i>New Purchase
            </button>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="border-0">Purchase ID</th>
                  <th class="border-0">Supplier</th>
                  <!-- <th class="border-0">Invoice No</th> -->
                  <th class="border-0">Date</th>
                  <th class="border-0">Total Amount</th>
                  <th class="border-0">Items</th>
                  <th class="border-0">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="purchase in filteredPurchases" :key="purchase.id" class="purchase-row">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="purchase-icon mr-3">
                        <i class="fas fa-shopping-cart"></i>
                      </div>
                      <span class="font-weight-bold" style="margin-left: 9px;">#{{ purchase.id }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="font-weight-bold">{{ purchase.supplier ? purchase.supplier.name : 'N/A' }}</div>
                    <small class="text-muted">{{ purchase.supplier ? purchase.supplier.email : '' }}</small>
                  </td>
                  <!-- <td>
                    <span class="badge badge-secondary">{{ purchase.invoice_no || 'N/A' }}</span>
                  </td> -->
                  <td>{{ formatDate(purchase.purchase_date) }}</td>
                  <td class="font-weight-bold text-primary">{{ formatCurrency(purchase.total_amount) }}</td>
                  <td>
                    <!-- <span class="badge badge-info">{{ purchase.purchase_items_count || 0 }} items</span> -->
                    <span class="badge badge-info">{{ purchase.items_count || 0 }} items</span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button class="btn btn-sm btn-outline-primary" @click="viewPurchase(purchase)"
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
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} purchases
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

      <!-- Add Purchase Modal -->
      <div class="modal fade" id="purchaseModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">
                <i class="fas fa-shopping-cart mr-2"></i>New Purchase
              </h5>
            </div>
            <div class="modal-body">
              <form @submit.prevent="savePurchase">
                <!-- Purchase Header Info -->
                <div class="row mb-4">
                  <div class="col-md-4 mb-3">
                    <label class="font-weight-bold">Supplier <span class="text-danger">*</span></label>
                    <select class="form-control" v-model="form.supplier_id" required>
                      <option value="">Select Supplier</option>
                      <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                        {{ supplier.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="font-weight-bold">Purchase Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" v-model="form.purchase_date" required />
                  </div>
                </div>

                <!-- Purchase Items -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Purchase Items</h6>
                    <button type="button" class="btn btn-sm btn-success" @click="addItem">
                      <i class="fas fa-plus mr-1"></i>Add Item
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="bg-light">
                        <tr>
                          <th style="width: 40%">Product</th>
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
                              @change="updateProductPrice(index)" required>
                              <option value="">Select Product</option>
                              <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.sku }})
                              </option>
                            </select>
                          </td>
                          <td>
                            <input type="number" class="form-control form-control-sm" v-model.number="item.quantity"
                              @input="calculateSubtotal(index)" min="1" required />
                          </td>
                          <td>
                            <!-- <input
                              type="number"
                              step="0.01"
                              class="form-control form-control-sm"
                              v-model.number="item.unit_price"
                              @input="calculateSubtotal(index)"
                              min="0"
                              required
                            /> -->
                            <div class="form-control form-control-sm bg-light text-right">
                              {{ formatCurrency(item.unit_price) }}
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
                          <td colspan="3" class="text-right font-weight-bold">Total Amount:</td>
                          <td colspan="2" class="font-weight-bold text-primary">
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
                  <button type="submit" class="btn btn-primary" :disabled="saving || form.items.length === 0">
                    <span v-if="saving">
                      <span class="spinner-border spinner-border-sm mr-2"></span>
                      Saving...
                    </span>
                    <span v-else>
                      <i class="fas fa-save mr-2"></i>
                      Save Purchase
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- View Purchase Modal -->
      <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">
                <i class="fas fa-info-circle mr-2"></i>Purchase Details
              </h5>
            </div>
            <div class="modal-body" v-if="selectedPurchase">
              <!-- Purchase Header -->
              <div class="row mb-4">
                <div class="col-md-3">
                  <label class="text-muted small">Purchase ID</label>
                  <p class="font-weight-bold">#{{ selectedPurchase.id }}</p>
                </div>
                <div class="col-md-3">
                  <label class="text-muted small">Supplier</label>
                  <p class="font-weight-bold">{{ selectedPurchase.supplier ? selectedPurchase.supplier.name : 'N/A' }}
                  </p>
                </div>
                <!-- <div class="col-md-3">
                  <label class="text-muted small">Invoice No</label>
                  <p><span class="badge badge-secondary">{{ selectedPurchase.invoice_no || 'N/A' }}</span></p>
                </div> -->
                <div class="col-md-3">
                  <label class="text-muted small">Purchase Date</label>
                  <p class="font-weight-bold">{{ formatDate(selectedPurchase.purchase_date) }}</p>
                </div>
              </div>

              <!-- Purchase Items -->
              <div class="mb-4">
                <h6 class="font-weight-bold mb-3">Items Purchased</h6>
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
                      <tr v-for="item in selectedPurchase.items" :key="item.id">
                        <td>{{ item.product ? item.product.name : 'N/A' }}</td>
                        <td><span class="badge badge-secondary">{{ item.product ? item.product.sku : 'N/A' }}</span>
                        </td>
                        <td class="text-right">{{ item.quantity }}</td>
                        <td class="text-right">{{ formatCurrency(item.price) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrency(item.quantity * item.price) }}
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-light">
                      <tr>
                        <td colspan="4" class="text-right font-weight-bold">Total Amount:</td>
                        <td class="text-right font-weight-bold text-primary">
                          {{ formatCurrency(selectedPurchase.total_amount) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <!-- Notes -->
              <!-- <div v-if="selectedPurchase.notes" class="mb-3">
                <label class="text-muted small">Notes</label>
                <p>{{ selectedPurchase.notes }}</p>
              </div> -->

              <!-- Timestamps -->
              <div class="row">
                <div class="col-md-6">
                  <label class="text-muted small">Created At</label>
                  <p>{{ formatDateTime(selectedPurchase.created_at) }}</p>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small">Last Updated</label>
                  <p>{{ formatDateTime(selectedPurchase.updated_at) }}</p>
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
  name: 'Purchases',
  components: { MainLayout },
  data() {
    return {
      purchases: [],
      filteredPurchases: [],
      products: [],
      suppliers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      supplierFilter: '',
      sortBy: 'date',
      pagination: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      },
      form: {
        supplier_id: '',
        //invoice_no: '',
        purchase_date: new Date().toISOString().split('T')[0],
        items: [
          {
            product_id: '',
            quantity: 1,
            unit_price: 0,
            subtotal: 0
          }
        ]
      },
      selectedPurchase: null,
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
    this.fetchPurchases();
    this.fetchProducts();
    this.fetchSuppliers();
  },
  methods: {
    async fetchPurchases(page = 1) {
      this.loading = true;
      try {
        const params = { page };
        const response = await api.get('/purchases', { params });

        this.purchases = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          from: response.data.from,
          to: response.data.to,
          total: response.data.total
        };

        this.applyFilters();
      } catch (error) {
        console.error('Error fetching purchases:', error);
        alert('Failed to load purchases');
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

    async fetchSuppliers() {
      try {
        const response = await api.get('/suppliers?per_page=1000');
        this.suppliers = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching suppliers:', error);
      }
    },

    applyFilters() {
      let filtered = [...this.purchases];

      // Search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(p =>
          (p.invoice_no && p.invoice_no.toLowerCase().includes(query)) ||
          (p.supplier && p.supplier.name.toLowerCase().includes(query))
        );
      }

      // Supplier filter
      if (this.supplierFilter) {
        filtered = filtered.filter(p => p.supplier_id == this.supplierFilter);
      }

      // Sorting
      if (this.sortBy === 'amount') {
        filtered.sort((a, b) => b.total_amount - a.total_amount);
      } else if (this.sortBy === 'supplier') {
        filtered.sort((a, b) => {
          const nameA = a.supplier ? a.supplier.name : '';
          const nameB = b.supplier ? b.supplier.name : '';
          return nameA.localeCompare(nameB);
        });
      } else {
        filtered.sort((a, b) => new Date(b.purchase_date) - new Date(a.purchase_date));
      }

      // this.purchases = filtered;
      this.filteredPurchases = filtered;
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.applyFilters();
      }, 300);
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchPurchases(page);
      }
    },

    openAddModal() {
      this.resetForm();
      const modalEl = document.getElementById('purchaseModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    async viewPurchase(purchase) {
      try {
        const response = await api.get(`/purchases/${purchase.id}`);
        this.selectedPurchase = response.data;
        const modalEl = document.getElementById('viewModal');
        const modal = new Modal(modalEl);
        modal.show();
      } catch (error) {
        console.error('Error fetching purchase details:', error);
        alert('Failed to load purchase details');
      }
    },

    addItem() {
      this.form.items.push({
        product_id: '',
        quantity: 1,
        unit_price: 0,
        subtotal: 0
      });
    },

    removeItem(index) {
      if (this.form.items.length > 1) {
        this.form.items.splice(index, 1);
      }
    },

    updateProductPrice(index) {
      const item = this.form.items[index];
      const product = this.products.find(p => p.id == item.product_id);
      if (product) {
        item.unit_price = parseFloat(product.purchase_price) || 0;
        this.calculateSubtotal(index);
      }
    },

    calculateSubtotal(index) {
      const item = this.form.items[index];
      item.subtotal = (item.quantity || 0) * (item.unit_price || 0);
    },

    calculateTotal() {
      return this.form.items.reduce((total, item) => total + (item.subtotal || 0), 0);
    },

    async savePurchase() {
      this.saving = true;
      try {
        const purchaseData = {
          supplier_id: this.form.supplier_id,
          // invoice_no: this.form.invoice_no,
          purchase_date: this.form.purchase_date,
          // notes: this.form.notes,
          items: this.form.items.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.unit_price
          }))
        };

        await api.post('/purchases', purchaseData);
        alert('Purchase created successfully!');
        this.closeModal();
        this.fetchPurchases(this.pagination.current_page);
      } catch (error) {
        console.error('Error saving purchase:', error);
        alert('Failed to save purchase. Please check all fields.');
      } finally {
        this.saving = false;
      }
    },

    closeModal() {
      const modalEl = document.getElementById('purchaseModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.resetForm();
    },

    closeViewModal() {
      const modalEl = document.getElementById('viewModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.selectedPurchase = null;
    },

    resetForm() {
      this.form = {
        supplier_id: '',
        invoice_no: '',
        purchase_date: new Date().toISOString().split('T')[0],
        items: [
          {
            product_id: '',
            quantity: 1,
            unit_price: 0,
            subtotal: 0
          }
        ]
      };
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
.purchases-page {
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

.purchase-row {
  transition: all 0.3s ease;
}

.purchase-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

.purchase-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
  color: #212529 !important;
}

.badge-secondary {
  background-color: #6c757d !important;
  color: #fff !important;
}

.badge-info {
  background-color: #17a2b8 !important;
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