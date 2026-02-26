<template>
  <MainLayout>
    <div class="purchases-page">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="dashboard-title">Purchases <br> Management</h2>
          <p class="text-muted small">You have {{ pagination.total }} purchase records</p>
        </div>
        <button v-if="canCreatePurchases" class="btn btn-dark btn-lg rounded-pill px-4 shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>New Purchase
        </button>
      </div>

      <!-- Search and Filter Section -->
      <div class="bento-item p-3 mb-4 bg-white shadow-sm border-0">
        <div class="row g-3 align-items-center">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="fas fa-search text-muted"></i>
              <input type="text" class="form-control border-0 bg-light rounded-pill" placeholder="Search by supplier..."
                v-model="searchQuery" @input="debouncedSearch" />
            </div>
          </div>
          <div class="col-md-7 d-flex gap-2 justify-content-md-end">
            <select class="form-select border-0 bg-light rounded-pill" v-model="supplierFilter" @change="applyFilters">
              <option value="">All Suppliers</option>
              <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                {{ supplier.name }}
              </option>
            </select>
            <select class="form-select border-0 bg-light rounded-pill" v-model="sortBy" @change="applyFilters">
              <option value="id">Sort by ID</option>
              <option value="date">Sort by Date</option>
              <option value="amount">Sort by Amount</option>
              <option value="supplier">Sort by Supplier</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Purchases Table -->
      <div class="bento-item p-0 bg-white shadow-sm border-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-grow text-dark" role="status"></div>
          <p class="mt-3 text-muted">Loading purchases...</p>
        </div>

        <div v-else-if="purchases.length === 0" class="text-center py-5">
          <i class="fas fa-shopping-cart mb-3 text-muted" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No purchases found</h5>
          <p class="text-muted">Start by recording your first purchase</p>
          <button v-if="canCreatePurchases" class="btn btn-dark rounded-pill px-4 mt-3" @click="openAddModal">
            <i class="fas fa-plus mr-2"></i>New Purchase
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Purchase Details</th>
                <th class="border-0 text-uppercase small text-muted">Supplier</th>
                <th class="border-0 text-uppercase small text-muted">Date</th>
                <th class="border-0 text-uppercase small text-muted text-end">Total Amount</th>
                <th class="border-0 text-uppercase small text-muted text-center">Items</th>
                <th class="pe-4 border-0 text-uppercase small text-muted text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="purchase in filteredPurchases" :key="purchase.id" class="purchase-row">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="purchase-icon-mini me-3">
                      <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                      <div class="font-weight-bold text-dark">#{{ purchase.id }}</div>
                      <small class="text-muted">Purchase Order</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="font-weight-bold">{{ purchase.supplier ? purchase.supplier.name : 'N/A' }}</div>
                  <small class="text-muted">{{ purchase.supplier ? purchase.supplier.email : '' }}</small>
                </td>
                <td>
                  <span class="text-dark">{{ formatDate(purchase.purchase_date) }}</span>
                </td>
                <td class="text-end font-weight-bold text-success">{{ formatCurrency(purchase.total_amount) }}</td>
                <td class="text-center">
                  <span class="status-dot badge-success">{{ purchase.items_count || 0 }} items</span>
                </td>
                <td class="pe-4 text-end">
                  <div class="action-buttons">
                    <button class="btn-icon view" @click="viewPurchase(purchase)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="p-3 bg-light d-flex justify-content-between align-items-center">
          <span class="small text-muted ps-2">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} purchases
          </span>
          <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link rounded-pill px-3 me-1" @click="changePage(pagination.current_page - 1)">
                  <i class="fas fa-chevron-left small"></i>
                </button>
              </li>

              <li v-for="page in visiblePages" :key="page" class="page-item"
                :class="{ active: page === pagination.current_page }">
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

      <!-- Add Purchase Modal -->
      <div class="modal fade" id="purchaseModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
              <h5 class="modal-title font-weight-bold">
                <i class="fas fa-shopping-cart mr-2"></i>New Purchase
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeModal"></button>
            </div>
            <div class="modal-body p-4">
              <form @submit.prevent="savePurchase">
                <!-- Purchase Header Info -->
                <div class="row mb-4 g-3">
                  <div class="col-md-6">
                    <label class="small text-muted mb-1">Supplier <span class="text-danger">*</span></label>
                    <select class="form-select bg-light border-0 rounded-pill px-3" v-model="form.supplier_id" required
                      :disabled="!canCreatePurchases"
                      style="height: 45px;">
                      <option value="">Select Supplier</option>
                      <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                        {{ supplier.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="small text-muted mb-1">Purchase Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control bg-light border-0 rounded-pill px-3"
                      :disabled="!canCreatePurchases"
                      v-model="form.purchase_date" required style="height: 45px;" />
                  </div>
                </div>

                <!-- Purchase Items -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Purchase Items</h6>
                    <button v-if="canCreatePurchases" type="button" class="btn btn-sm btn-dark rounded-pill px-3" @click="addItem">
                      <i class="fas fa-plus mr-1"></i>Add Item
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                      <thead class="bg-light">
                        <tr>
                          <th style="width: 40%" class="text-uppercase small">Product</th>
                          <th style="width: 15%" class="text-uppercase small">Quantity</th>
                          <th style="width: 20%" class="text-uppercase small">Unit Price</th>
                          <th style="width: 20%" class="text-uppercase small">Subtotal</th>
                          <th style="width: 5%"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                          <td>
                            <select class="form-select form-select-sm bg-light border-0 rounded-pill"
                              :disabled="!canCreatePurchases"
                              v-model="item.product_id" @change="updateProductPrice(index)" required>
                              <option value="">Select Product</option>
                              <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.sku }})
                              </option>
                            </select>
                          </td>
                          <td>
                            <input type="number" class="form-control form-control-sm bg-light border-0 rounded-pill"
                              :disabled="!canCreatePurchases"
                              v-model.number="item.quantity" @input="calculateSubtotal(index)" min="1" required />
                          </td>
                          <td>
                            <div class="form-control form-control-sm bg-light border-0 rounded-pill text-end">
                              {{ formatCurrency(item.unit_price) }}
                            </div>
                          </td>
                          <td>
                            <div class="font-weight-bold pt-2 text-end">
                              {{ formatCurrency(item.subtotal || 0) }}
                            </div>
                          </td>
                          <td class="text-center">
                            <button v-if="canCreatePurchases" type="button" class="btn btn-sm btn-icon delete" @click="removeItem(index)"
                              :disabled="form.items.length === 1">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="bg-light">
                        <tr>
                          <td colspan="3" class="text-end font-weight-bold">Total Amount:</td>
                          <td colspan="2" class="font-weight-bold text-success text-end">
                            {{ formatCurrency(calculateTotal()) }}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <div v-if="canCreatePurchases" class="d-flex justify-content-end gap-2 mt-4">
                  <button type="submit" class="btn btn-dark rounded-pill px-4"
                    :disabled="saving || form.items.length === 0">
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
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
              <h5 class="modal-title font-weight-bold">
                <i class="fas fa-info-circle mr-2"></i>Purchase Details
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeViewModal"></button>
            </div>
            <div class="modal-body p-4" v-if="selectedPurchase">
              <!-- Purchase Header -->
              <div class="row mb-4 g-3">
                <div class="col-md-4">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Purchase ID</label>
                    <p class="font-weight-bold mb-0">#{{ selectedPurchase.id }}</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Supplier</label>
                    <p class="font-weight-bold mb-0">{{ selectedPurchase.supplier ? selectedPurchase.supplier.name :
                      'N/A' }}</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Purchase Date</label>
                    <p class="font-weight-bold mb-0">{{ formatDate(selectedPurchase.purchase_date) }}</p>
                  </div>
                </div>
              </div>

              <!-- Purchase Items -->
              <div class="mb-4">
                <h6 class="font-weight-bold mb-3">Items Purchased</h6>
                <div class="table-responsive">
                  <table class="table table-bordered align-middle">
                    <thead class="bg-light">
                      <tr>
                        <th class="text-uppercase small">Product</th>
                        <th class="text-uppercase small">SKU</th>
                        <th class="text-uppercase small text-end">Quantity</th>
                        <th class="text-uppercase small text-end">Unit Price</th>
                        <th class="text-uppercase small text-end">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in selectedPurchase.items" :key="item.id">
                        <td>{{ item.product ? item.product.name : 'N/A' }}</td>
                        <td><span class="sku-badge">{{ item.product ? item.product.sku : 'N/A' }}</span></td>
                        <td class="text-end">{{ item.quantity }}</td>
                        <td class="text-end">{{ formatCurrency(item.price) }}</td>
                        <td class="text-end font-weight-bold">{{ formatCurrency(item.quantity * item.price) }}</td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-light">
                      <tr>
                        <td colspan="4" class="text-end font-weight-bold">Total Amount:</td>
                        <td class="text-end font-weight-bold text-success">
                          {{ formatCurrency(selectedPurchase.total_amount) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <!-- Timestamps -->
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Created At</label>
                    <p class="mb-0">{{ formatDateTime(selectedPurchase.created_at) }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Last Updated</label>
                    <p class="mb-0">{{ formatDateTime(selectedPurchase.updated_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-dark rounded-pill px-4" @click="closeViewModal">Close</button>
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
      // sortBy: 'date',
      sortBy: 'id',
      pagination: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      },
      form: {
        supplier_id: '',
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
    currentUser() {
      return getStoredUser() || {};
    },
    isAdmin() {
      return isAdminUser(this.currentUser);
    },
    canCreatePurchases() {
      return hasPermission(this.currentUser, 'purchases.create');
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

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(p =>
          (p.invoice_no && p.invoice_no.toLowerCase().includes(query)) ||
          (p.supplier && p.supplier.name.toLowerCase().includes(query))
        );
      }

      if (this.supplierFilter) {
        filtered = filtered.filter(p => p.supplier_id == this.supplierFilter);
      }

      if (this.sortBy === 'id') {
        filtered.sort((a, b) => b.id - a.id); // latest ID first
      } else if (this.sortBy === 'amount') {
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
        if (!this.canCreatePurchases) return;
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
        if (!this.canCreatePurchases) return;
        this.form.items.push({
          product_id: '',
          quantity: 1,
          unit_price: 0,
          subtotal: 0
        });
      },

      removeItem(index) {
        if (!this.canCreatePurchases) return;
        if (this.form.items.length > 1) {
          this.form.items.splice(index, 1);
        }
      },

      updateProductPrice(index) {
        if (!this.canCreatePurchases) return;
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
        if (!this.canCreatePurchases) return;
        this.saving = true;
        try {
          const purchaseData = {
            supplier_id: this.form.supplier_id,
            purchase_date: this.form.purchase_date,
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

.purchase-row {
  transition: all 0.3s ease;
}

.purchase-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

/* .purchase-icon-mini {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
} */
.purchase-icon-mini {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1a1a1a;
  font-size: 1rem;

}

/* Ensure the row hover still feels consistent */
.purchase-row:hover .purchase-icon-mini {
  background: #e9ecef;
  transition: background 0.2s ease;
}

.sku-badge {
  background: #e9ecef;
  padding: 4px 10px;
  border-radius: 6px;
  font-family: monospace;
  font-size: 0.85rem;
  color: #495057;
}

/* Status Badges */
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

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: flex-end;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  transition: 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-icon.view:hover {
  background: #f0f7ff;
  color: #007bff;
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

/* Modern Pagination */
.pagination .page-link {
  border: none;
  background-color: transparent;
  color: #6c757d;
  font-weight: 600;
  transition: all 0.2s ease;
}

.pagination .page-item.active .page-link {
  background-color: #1a1a1a;
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

.modal-content {
  border-radius: 20px;
}

.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
  border: 1px solid #dee2e6;
}
</style>
