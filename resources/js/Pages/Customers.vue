<template>
  <MainLayout>
    <div class="customers-page">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="dashboard-title">Customers <br> Management</h2>
          <p class="text-muted small">You have {{ pagination.total }} customers</p>
        </div>
        <button class="btn btn-dark btn-lg rounded-pill px-4 shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>New Customer
        </button>
      </div>

      <div class="bento-item p-3 mb-4 shadow-sm border-0">
        <div class="row g-3 align-items-center">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="fas fa-search text-muted"></i>
              <input type="text" class="form-control border-0 bg-light rounded-pill"
                placeholder="Search by name or phone..." v-model="searchQuery" @input="debouncedSearch" />
            </div>
          </div>
          <div class="col-md-7 d-flex gap-2 justify-content-md-end">
            <select class="form-select border-0 bg-light rounded-pill" v-model="sortBy" @change="applyFilters">
              <option value="id">Sort by ID</option>
              <option value="name">Sort by Name</option>
            </select>
          </div>
        </div>
      </div>

      <div class="bento-item p-0 shadow-sm border-0 overflow-hidden">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-grow text-dark" role="status"></div>
          <p class="mt-3 text-muted">Loading customers...</p>
        </div>

        <div v-else-if="customers.length === 0" class="text-center py-5">
          <i class="fas fa-users mb-3 text-muted" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No customers found</h5>
          <p class="text-muted">Start by adding your first customer</p>
          <button class="btn btn-dark rounded-pill px-4 mt-3" @click="openAddModal">
            <i class="fas fa-plus mr-2"></i>New Customer
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Customer Name</th>
                <th class="border-0 text-uppercase small text-muted">Phone</th>
                <th class="border-0 text-uppercase small text-muted">Address</th>
                <th class="pe-4 border-0 text-uppercase small text-muted text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="customer in filteredCustomers" :key="customer.id" class="customer-row">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="customer-icon-mini me-3">
                      <i class="fas fa-user"></i>
                    </div>
                    <div>
                      <div class="font-weight-bold text-dark">{{ customer.name }}</div>
                      <small class="text-muted">ID: #{{ customer.id }}</small>
                    </div>
                  </div>
                </td>
                <td>{{ customer.phone }}</td>
                <td>{{ customer.address || 'N/A' }}</td>
                <td class="pe-4 text-end">
                  <div class="action-buttons">
                    <button class="btn-icon view" @click="viewCustomer(customer)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon edit" @click="editCustomer(customer)" title="Edit Customer">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" @click="confirmDelete(customer)" title="Delete Customer">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination.total > 0" class="p-3 bg-light d-flex justify-content-between align-items-center">
          <span class="small text-muted ps-2">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} customers
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

      <div class="modal fade" id="customerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
              <h5 class="modal-title font-weight-bold">
                {{ isEditing ? 'Edit Customer' : 'Add New Customer' }}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeModal"></button>
            </div>
            <div class="modal-body p-4">
              <form @submit.prevent="saveCustomer">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="small text-muted mb-1">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light border-0 rounded-pill px-3" v-model="form.name"
                      required />
                  </div>
                  <div class="col-md-6">
                    <label class="small text-muted mb-1">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light border-0 rounded-pill px-3" v-model="form.phone"
                      required />
                  </div>
                  <div class="col-md-12">
                    <label class="small text-muted mb-1">Address</label>
                    <input type="text" class="form-control bg-light border-0 rounded-pill px-3"
                      v-model="form.address" />
                  </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                  <button type="submit" class="btn btn-dark rounded-pill px-4" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ isEditing ? 'Update Customer' : 'Create Customer' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-body p-4" v-if="selectedCustomer">
              <div class="d-flex justify-content-between mb-4">
                <h5 class="font-weight-bold">Customer Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeViewModal"></button>
              </div>

              <div class="text-center mb-4">
                <div class="customer-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                  <i class="fas fa-user"></i>
                </div>
                <h4>{{ selectedCustomer.name }}</h4>
                <span class="sku-badge">ID: #{{ selectedCustomer.id }}</span>
              </div>

              <div class="row g-3 text-center">
                <div class="col-6 p-3 bg-light rounded-start border-end">
                  <small class="text-muted d-block">Phone</small>
                  <strong>{{ selectedCustomer.phone }}</strong>
                </div>
                <div class="col-6 p-3 bg-light rounded-end">
                  <small class="text-muted d-block">Location</small>
                  <strong class="text-truncate d-block px-2">{{ selectedCustomer.address || 'No Address' }}</strong>
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-danger rounded-pill px-4" @click="deleteFromView">
                  <i class="fas fa-trash me-2"></i>Delete
                </button>
                <button class="btn btn-dark rounded-pill px-4" @click="editFromView">Edit</button>
              </div>
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
  name: 'Customers',
  components: { MainLayout },
  data() {
    return {
      filteredCustomers: [],
      customers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      // sortBy: 'name',
      sortBy: 'id',
      pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
      form: { name: '', phone: '', address: '' },
      isEditing: false,
      editingId: null,
      selectedCustomer: null,
      debounceTimer: null
    };
  },
  computed: {
    visiblePages() {
      const pages = [];
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) pages.push(i);
      return pages;
    }
  },
  mounted() {
    this.fetchCustomers();
  },
  methods: {
    async fetchCustomers(page = 1) {
      this.loading = true;
      try {
        // const params = { page, search: this.searchQuery };
        const params = { page };
        const response = await api.get('/customers', { params });
        // this.customers = response.data.data;
        this.customers = response.data.data || response.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          from: response.data.from,
          to: response.data.to,
          total: response.data.total
        };
        this.applyFilters();
      } catch (error) {
        console.error('Error fetching customers:', error);
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      let filtered = [...this.customers];
      const q = this.searchQuery.toLowerCase();

      if (q) {
        filtered = filtered.filter(c =>
          c.name.toLowerCase().includes(q) ||
          (c.phone || '').includes(q)
        );
      }

      if (this.sortBy === 'name') {
        filtered.sort((a, b) => a.name.localeCompare(b.name));
      } else if (this.sortBy === 'id') {
        filtered.sort((a, b) => a.id - b.id);
      }

      this.filteredCustomers = filtered;
    },


    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      // this.debounceTimer = setTimeout(() => this.fetchCustomers(1), 300);
      this.debounceTimer = setTimeout(() => {
        this.applyFilters();
      }, 300);
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) this.fetchCustomers(page);
    },

    openAddModal() {
      this.isEditing = false;
      this.resetForm();
      new Modal(document.getElementById('customerModal')).show();
    },

    editCustomer(customer) {
      this.isEditing = true;
      this.editingId = customer.id;
      this.form = { ...customer };
      new Modal(document.getElementById('customerModal')).show();
    },

    viewCustomer(customer) {
      this.selectedCustomer = customer;
      new Modal(document.getElementById('viewModal')).show();
    },

    editFromView() {
      const modal = Modal.getInstance(document.getElementById('viewModal'));
      if (modal) modal.hide();
      this.editCustomer(this.selectedCustomer);
    },

    async confirmDelete(customer) {
      if (confirm(`Are you sure you want to delete "${customer.name}"?`)) {
        try {
          await api.delete(`/customers/${customer.id}`);
          this.fetchCustomers(this.pagination.current_page);
        } catch (error) {
          alert('Failed to delete customer');
        }
      }
    },

    deleteFromView() {
      const customerToDelete = this.selectedCustomer;
      const modal = Modal.getInstance(document.getElementById('viewModal'));
      if (modal) modal.hide();
      this.confirmDelete(customerToDelete);
    },

    async saveCustomer() {
      this.saving = true;
      try {
        if (this.isEditing) await api.put(`/customers/${this.editingId}`, this.form);
        else await api.post('/customers', this.form);
        this.closeModal();
        this.fetchCustomers(this.pagination.current_page);
      } catch (error) {
        alert('Failed to save customer');
      } finally {
        this.saving = false;
      }
    },

    closeModal() {
      const modal = Modal.getInstance(document.getElementById('customerModal'));
      if (modal) modal.hide();
      this.resetForm();
    },

    closeViewModal() {
      const modal = Modal.getInstance(document.getElementById('viewModal'));
      if (modal) modal.hide();
      this.selectedCustomer = null;
    },

    resetForm() {
      this.form = { name: '', phone: '', address: '' };
      this.editingId = null;
    }
  }
};
</script>

<style scoped>
/* Inherited from Inventory Aesthetic */
.customers-page {
  animation: fadeIn 0.5s;
}

.bento-item {
  background: white;
  border-radius: 16px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-title {
  font-weight: 800;
  letter-spacing: -1px;
  line-height: 1.1;
  color: #1a1a1a;
}

.customer-icon,
.customer-icon-mini {
  background: #f8f9fa;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.customer-icon-mini {
  width: 35px;
  height: 35px;
}

.sku-badge {
  background: #f0f0f0;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #666;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: 0.2s;
  margin-left: 4px;
}

.btn-icon.view:hover {
  background: #f0f7ff;
  color: #007bff;
}

.btn-icon.edit:hover {
  background: #fff3cd;
  color: #ffc107;
}

.btn-icon.delete:hover {
  background: #ffe5e5;
  color: #dc3545;
}

.customer-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
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
</style>