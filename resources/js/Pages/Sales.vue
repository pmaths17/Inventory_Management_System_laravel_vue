<template>
  <MainLayout>
    <div class="sales-page">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="dashboard-title">Sales <br> Management</h2>
          <p class="text-muted small">You have {{ pagination.total }} sales records</p>
        </div>
        <button class="btn btn-dark btn-lg rounded-pill px-4 shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>New Sale
        </button>
      </div>

      <!-- Search and Filter Section -->
      <div class="bento-item p-3 mb-4 bg-white shadow-sm border-0">
        <div class="row g-3 align-items-center">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="fas fa-search text-muted"></i>
              <input type="text" class="form-control border-0 bg-light rounded-pill" placeholder="Search by customer..."
                v-model="searchQuery" @input="debouncedSearch" />
            </div>
          </div>
          <div class="col-md-7 d-flex gap-2 justify-content-md-end">
            <select class="form-select border-0 bg-light rounded-pill" v-model="customerFilter" @change="applyFilters">
              <option value="">All Customers</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.name }}
              </option>
            </select>
            <select class="form-select border-0 bg-light rounded-pill" v-model="sortBy" @change="applyFilters">
              <option value="id">Sort by Sale ID</option>
              <option value="date">Sort by Date</option>
              <option value="amount">Sort by Amount</option>
              <option value="customer">Sort by Customer</option>
              <option value="status">Sort by Status</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Sales Table -->
      <div class="bento-item p-0 bg-white shadow-sm border-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-grow text-dark" role="status"></div>
          <p class="mt-3 text-muted">Loading sales...</p>
        </div>

        <div v-else-if="sales.length === 0" class="text-center py-5">
          <i class="fas fa-cash-register mb-3 text-muted" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No sales found</h5>
          <p class="text-muted">Start by recording your first sale</p>
          <button class="btn btn-dark rounded-pill px-4 mt-3" @click="openAddModal">
            <i class="fas fa-plus mr-2"></i>New Sale
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Sale Details</th>
                <th class="border-0 text-uppercase small text-muted">Customer</th>
                <th class="border-0 text-uppercase small text-muted">Date</th>
                <th class="border-0 text-uppercase small text-muted text-end">Total Amount</th>
                <th class="border-0 text-uppercase small text-muted text-center">Items</th>
                <th class="border-0 text-uppercase small text-muted text-center">Status</th>
                <th class="pe-4 border-0 text-uppercase small text-muted text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sale in filteredSales" :key="sale.id" class="sale-row">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="sale-icon-mini me-3">
                      <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                      <div class="font-weight-bold text-dark">#{{ sale.id }}</div>
                      <small class="text-muted">Sales Order</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="font-weight-bold">{{ sale.customer ? sale.customer.name : 'N/A' }}</div>
                  <small class="text-muted">{{ sale.customer ? sale.customer.email : '' }}</small>
                </td>
                <td>
                  <span class="text-dark">{{ formatDate(sale.sale_date) }}</span>
                </td>
                <td class="text-end font-weight-bold text-success">{{ formatCurrency(sale.total_amount) }}</td>
                <td class="text-center">
                  <span class="status-dot badge-info">{{ sale.items_count || 0 }} items</span>
                </td>
                <td class="text-center">
                  <span class="status-dot" :class="getStatusBadgeClass(sale.status)">
                    {{ sale.status }}
                  </span>
                </td>
                <td class="pe-4 text-end">
                  <div class="action-buttons">
                    <button class="btn-icon view" @click="viewSale(sale)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <!-- EDIT DRAFT -->
                    <button v-if="sale.status === 'draft'" class="btn-icon edit" @click="editDraftSale(sale)"
                      title="Edit Draft">
                      <i class="fas fa-edit"></i>
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
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} sales
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

      <!-- Add Sale Modal -->
      <div class="modal fade" id="saleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
              <!-- <h5 class="modal-title font-weight-bold">
                <i class="fas fa-cash-register mr-2"></i>New Sale
              </h5> -->
              <h5 class="modal-title font-weight-bold">
                <i class="fas fa-cash-register mr-2"></i>
                {{ isEditingDraft ? 'Edit Draft Sale' : 'New Sale' }}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeModal"></button>
            </div>
            <div class="modal-body p-4">
              <form @submit.prevent="saveSale">
                <!-- Sale Header Info -->
                <div class="row mb-4 g-3">
                  <div class="col-md-6">
                    <label class="small text-muted mb-1">Customer <span class="text-danger">*</span></label>
                    <select class="form-select bg-light border-0 rounded-pill px-3" v-model="form.customer_id" required
                      style="height: 45px;">
                      <option value="">Select Customer</option>
                      <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="small text-muted mb-1">Sale Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control bg-light border-0 rounded-pill px-3" v-model="form.sale_date"
                      required style="height: 45px;" />
                  </div>
                </div>

                <!-- Sale Items -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Sale Items</h6>
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" @click="addItem">
                      <i class="fas fa-plus mr-1"></i>Add Item
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                      <thead class="bg-light">
                        <tr>
                          <th style="width: 30%" class="text-uppercase small">Product</th>
                          <th style="width: 15%" class="text-uppercase small">Available Stock</th>
                          <th style="width: 15%" class="text-uppercase small">Quantity</th>
                          <th style="width: 15%" class="text-uppercase small">Unit Price</th>
                          <th style="width: 20%" class="text-uppercase small">Subtotal</th>
                          <th style="width: 5%"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                          <td>
                            <select class="form-select form-select-sm bg-light border-0 rounded-pill"
                              v-model="item.product_id" @change="updateProductInfo(index)" required>
                              <option value="">Select Product</option>
                              <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.sku }})
                              </option>
                            </select>
                          </td>
                          <td>
                            <!-- <div class="form-control form-control-sm bg-light border-0 rounded-pill text-center"
                              :class="{ 'text-danger': item.available_stock < item.quantity }">
                              {{ item.available_stock !== null ? item.available_stock : '-' }}
                            </div> -->
                            <div class="form-control form-control-sm bg-light border-0 rounded-pill text-center">
                              {{ item.available_stock !== null ? item.available_stock : '-' }}
                            </div>
                          </td>
                          <!-- <td>
                            <input type="number" class="form-control form-control-sm bg-light border-0 rounded-pill"
                              v-model.number="item.quantity" @input="calculateSubtotal(index)"
                              :max="item.available_stock" min="1" required />
                            <small v-if="item.quantity > item.available_stock" class="text-danger">
                              Exceeds stock!
                            </small>
                          </td> -->
                          <td>
                            <input type="number" class="form-control form-control-sm bg-light border-0 rounded-pill"
                              v-model.number="item.quantity" @input="calculateSubtotal(index)" min="1" required />
                          </td>

                          <td>
                            <div class="form-control form-control-sm bg-light border-0 rounded-pill text-end">
                              {{ formatCurrency(item.price) }}
                            </div>
                          </td>
                          <td>
                            <div class="font-weight-bold pt-2 text-end">
                              {{ formatCurrency(item.subtotal || 0) }}
                            </div>
                          </td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon delete" @click="removeItem(index)"
                              :disabled="form.items.length === 1">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="bg-light">
                        <tr>
                          <td colspan="4" class="text-end font-weight-bold">Total Amount:</td>
                          <td colspan="2" class="font-weight-bold text-success text-end">
                            {{ formatCurrency(calculateTotal()) }}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                  <!-- <button type="submit" class="btn btn-dark rounded-pill px-4" 
                    :disabled="saving || form.items.length === 0 || hasInsufficientStock">
                    <span v-if="saving">
                      <span class="spinner-border spinner-border-sm mr-2"></span>
                      Saving...
                    </span>
                    <span v-else>
                      <i class="fas fa-save mr-2"></i>
                      Complete Sale
                    </span>
                  </button> -->
                  <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                      :disabled="saving || form.items.length === 0 || hasInsufficientStock" @click="saveSale('draft')">
                      <span v-if="saving && saveType === 'draft'">
                        <span class="spinner-border spinner-border-sm mr-2"></span>
                        Saving Draft...
                      </span>
                      <span v-else>
                        <i class="fas fa-file-alt mr-2"></i> Save as Draft
                      </span>
                    </button>

                    <button type="button" class="btn btn-dark rounded-pill px-4"
                      :disabled="saving || form.items.length === 0 || hasInsufficientStock"
                      @click="saveSale('completed')">
                      <span v-if="saving && saveType === 'completed'">
                        <span class="spinner-border spinner-border-sm mr-2"></span>
                        Completing...
                      </span>
                      <span v-else>
                        <i class="fas fa-check mr-2"></i> Complete Sale
                      </span>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- View Sale Modal -->
      <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
              <h5 class="modal-title font-weight-bold">
                <i class="fas fa-info-circle mr-2"></i>Sale Details
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeViewModal"></button>
            </div>
            <div class="modal-body p-4" v-if="selectedSale">
              <!-- Sale Header -->
              <div class="row mb-4 g-3">
                <div class="col-md-3">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Sale ID</label>
                    <p class="font-weight-bold mb-0">#{{ selectedSale.id }}</p>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Customer</label>
                    <p class="font-weight-bold mb-0">{{ selectedSale.customer ? selectedSale.customer.name : 'N/A' }}
                    </p>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Sale Date</label>
                    <p class="font-weight-bold mb-0">{{ formatDate(selectedSale.sale_date) }}</p>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Status</label>
                    <span class="status-dot" :class="getStatusBadgeClass(selectedSale.status)">
                      {{ selectedSale.status }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Sale Items -->
              <div class="mb-4">
                <h6 class="font-weight-bold mb-3">Items Sold</h6>
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
                      <tr v-for="item in selectedSale.items" :key="item.id">
                        <td>{{ item.product ? item.product.name : 'N/A' }}</td>
                        <td><span class="sku-badge">{{ item.product ? item.product.sku : 'N/A' }}</span></td>
                        <td class="text-end">{{ item.quantity }}</td>
                        <td class="text-end">{{ formatCurrency(item.price) }}</td>
                        <td class="text-end font-weight-bold">{{ formatCurrency(item.subtotal) }}</td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-light">
                      <tr>
                        <td colspan="4" class="text-end font-weight-bold">Total Amount:</td>
                        <td class="text-end font-weight-bold text-success">
                          {{ formatCurrency(selectedSale.total_amount) }}
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
                    <p class="mb-0">{{ formatDateTime(selectedSale.created_at) }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="p-3 bg-light rounded">
                    <label class="text-muted small d-block mb-1">Last Updated</label>
                    <p class="mb-0">{{ formatDateTime(selectedSale.updated_at) }}</p>
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

export default {
  name: 'Sales',
  components: { MainLayout },
  data() {
    return {
      isEditingDraft: false,
      editingSaleId: null,
      saveType: null,
      sales: [],
      filteredSales: [],
      products: [],
      customers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      customerFilter: '',
      sortBy: 'id',
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
    // async editDraftSale(sale) {
    //   try {
    //     this.isEditingDraft = true;
    //     this.editingSaleId = sale.id;

    //     // fetch full sale with items
    //     const response = await api.get(`/sales/${sale.id}`);
    //     const draft = response.data;

    //     // map backend sale → form
    //     this.form = {
    //       customer_id: draft.customer_id,
    //       sale_date: draft.sale_date,
    //       items: draft.items.map(item => ({
    //         product_name: item.product?.name ?? '',
    //         product_id: item.product_id,
    //         quantity: item.quantity,
    //         price: item.price,
    //         subtotal: item.quantity * item.price,
    //         // available_stock: item.product?.current_stock ?? null
    //         available_stock: item.available_stock ?? 0
    //       }))
    //     };

    //     // open SAME modal as New Sale
    //     new Modal(document.getElementById('saleModal')).show();

    //   } catch (error) {
    //     console.error('Failed to load draft sale', error);
    //     alert('Failed to open draft sale');
    //   }
    // },
    async editDraftSale(sale) {
      try {
        this.isEditingDraft = true;
        this.editingSaleId = sale.id;

        // fetch full sale with items
        const response = await api.get(`/sales/${sale.id}`);
        const draft = response.data;

        // map backend sale → form
        this.form = {
          customer_id: draft.customer_id,
          sale_date: draft.sale_date,
          items: draft.items.map(item => ({
            product_id: item.product_id,
            product_name: item.product?.name ?? '', // <- product name included here
            quantity: item.quantity,
            price: item.price,
            subtotal: item.quantity * item.price,
            available_stock: item.available_stock ?? 0
          }))
        };

        // open SAME modal as New Sale
        new Modal(document.getElementById('saleModal')).show();

      } catch (error) {
        console.error('Failed to load draft sale', error);
        alert('Failed to open draft sale');
      }
    },


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

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(s =>
          (s.customer && s.customer.name.toLowerCase().includes(query)) ||
          (s.id && s.id.toString().includes(query))
        );
      }

      if (this.customerFilter) {
        filtered = filtered.filter(s => s.customer_id == this.customerFilter);
      }

      if (this.sortBy === 'id') {
        filtered.sort((a, b) => b.id - a.id); // descending order
      } else if (this.sortBy === 'amount') {
        filtered.sort((a, b) => b.total_amount - a.total_amount);
      } else if (this.sortBy === 'customer') {
        filtered.sort((a, b) => {
          const nameA = a.customer ? a.customer.name : '';
          const nameB = b.customer ? b.customer.name : '';
          return nameA.localeCompare(nameB);
        });
      }
      else if (this.sortBy === 'status') {
        filtered.sort((a, b) => {
          const statusA = a.status || '';
          const statusB = b.status || '';
          return statusA.localeCompare(statusB);
        });
      }
      else {
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
    async saveSale(saveType) {
      // if (this.hasInsufficientStock) {
      //   alert('Some items exceed available stock. Please adjust quantities.');
      //   return;
      // }
      if (saveType !== 'draft' && this.hasInsufficientStock) {
        alert('Some items exceed available stock. Please adjust quantities.');
        return;
      }


      this.saving = true;
      this.saveType = saveType; // track which button is clicked

      try {
        const saleData = {
          customer_id: this.form.customer_id,
          sale_date: this.form.sale_date,
          // status: saveType === 'draft' ? 'pending' : 'completed',
          // status: saveType === 'draft' ? 'draft' : 'completed',
          action: saveType,
          items: this.form.items.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price
          }))
        };

        // await api.post('/sales', saleData);
        if (this.isEditingDraft) {
          await api.put(`/sales/${this.editingSaleId}`, saleData);
        } else {
          await api.post('/sales', saleData);
        }


        alert(saveType === 'draft' ? 'Draft saved successfully!' : 'Sale completed successfully!');

        this.closeModal(); // close modal
        this.fetchSales(this.pagination.current_page); // refresh sales list
      } catch (error) {
        console.error('Error saving sale:', error);
        const errorMsg = error.response?.data?.message || 'Failed to save sale. Please check all fields.';
        alert(errorMsg);
      } finally {
        this.saving = false;
        this.saveType = null;
      }
    },


    // async saveSale() {
    //   if (this.hasInsufficientStock) {
    //     alert('Some items exceed available stock. Please adjust quantities.');
    //     return;
    //   }

    //   this.saving = true;
    //   try {
    //     const saleData = {
    //       customer_id: this.form.customer_id,
    //       sale_date: this.form.sale_date,
    //       items: this.form.items.map(item => ({
    //         product_id: item.product_id,
    //         quantity: item.quantity,
    //         price: item.price
    //       }))
    //     };

    //     await api.post('/sales', saleData);
    //     alert('Sale completed successfully!');
    //     this.closeModal();
    //     this.fetchSales(this.pagination.current_page);
    //   } catch (error) {
    //     console.error('Error saving sale:', error);
    //     const errorMsg = error.response?.data?.message || 'Failed to save sale. Please check all fields.';
    //     alert(errorMsg);
    //   } finally {
    //     this.saving = false;
    //   }
    // },

    // closeModal() {
    //   const modalEl = document.getElementById('saleModal');
    //   const modal = Modal.getInstance(modalEl);
    //   if (modal) modal.hide();
    //   this.resetForm();
    // },
    closeModal() {
      const modalEl = document.getElementById('saleModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();

      this.resetForm();
      this.isEditingDraft = false;
      this.editingSaleId = null;
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

    // getStatusClass(status) {
    //   const statusClasses = {
    //     completed: 'badge-success',
    //     pending: 'badge-warning',
    //     cancelled: 'badge-danger'
    //   };
    //   return statusClasses[status] || 'badge-secondary';
    // },

    getStatusBadgeClass(status) {
      return {
        completed: 'badge-success',
        draft: 'badge-warning',
      }[status] || 'badge-secondary';
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

.sale-row {
  transition: all 0.3s ease;
}

.btn-icon.edit:hover {
  background: #fff9db;
  color: #f08c00;
}

.sale-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

.sale-icon-mini {
  width: 40px;
  height: 40px;
  /* border-radius: 10px; */
  /* background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); */
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1a1a1a;
  font-size: 1rem;
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

.badge-warning {
  background: #fff9db;
  color: #f08c00 !important;
}

.badge-danger {
  background: #fff5f5;
  color: #fa5252 !important;
}

.badge-info {
  background: #e7f5ff;
  color: #228be6 !important;
}

/* Sales Page - Action Buttons */
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

.btn-icon:hover {
  transform: translateY(-2px);
}

.btn-icon.delete:hover {
  background: #fa5252;
  color: white;
}

.pagination .page-link {
  border: none;
  color: #495057;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.2s;
}

.pagination .page-item.active .page-link {
  background-color: #212529;
  color: white;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.pagination .page-item.disabled .page-link {
  background-color: transparent;
  color: #adb5bd;
}

/* Modal Enhancements */
.modal-content {
  overflow: hidden;
}

.form-control:focus,
.form-select:focus {
  box-shadow: none;
  background-color: #fff !important;
  border: 1px solid #dee2e6 !important;
}

/* Quantity input specific */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  cursor: pointer;
}
</style>