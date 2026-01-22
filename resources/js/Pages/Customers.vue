<template>
  <MainLayout>
    <div class="customers-page">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1 font-weight-bold">Customers Management</h2>
          <p class="text-muted mb-0">Manage your customer information</p>
        </div>
        <button class="btn btn-primary btn-lg shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>Add Customer
        </button>
      </div>

      <!-- Search & Sort -->
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
                  class="form-control border-start-0"
                  placeholder="Search by name, or phone..."
                  v-model="searchQuery"
                  @input="debouncedSearch"
                />
              </div>
            </div>
            <div class="col-md-6">
              <select class="form-control" v-model="sortBy" @change="applyFilters">
                <option value="name">Sort by Name</option>
                <option value="phone">Sort by Phone</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Customers Table -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading customers...</p>
          </div>

          <div v-else-if="customers.length === 0" class="text-center py-5">
            <i class="fas fa-users fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No customers found</h5>
            <p class="text-muted">Start by adding your first customer</p>
            <button class="btn btn-primary mt-3" @click="openAddModal">
              <i class="fas fa-plus mr-2"></i>Add Customer
            </button>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="border-0">Customer Name</th>
                  <th class="border-0">Phone</th>
                  <th class="border-0">Address</th>
                  <th class="border-0">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="customer in customers" :key="customer.id" class="customer-row">
                  <td>
                    <div class="font-weight-bold">{{ customer.name }}</div>
                  </td>
                  <td>{{ customer.phone }}</td>
                  <td>{{ customer.address }}</td>
                  <td>
                    <div class="btn-group" role="group">
                      <button
                        class="btn btn-sm btn-outline-primary"
                        @click="viewCustomer(customer)"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-outline-warning"
                        @click="editCustomer(customer)"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
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
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} customers
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

      <!-- Add/Edit Customer Modal -->
      <div class="modal fade" id="customerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">
                <i class="fas fa-user mr-2"></i>
                {{ isEditing ? 'Edit Customer' : 'Add New Customer' }}
              </h5>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveCustomer">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="form.name" required />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="form.phone" required />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">Address</label>
                    <input type="text" class="form-control" v-model="form.address" />
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                  <button type="button" class="btn btn-secondary mr-2" @click="closeModal">Cancel</button>
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving">
                      <span class="spinner-border spinner-border-sm mr-2"></span>Saving...
                    </span>
                    <span v-else>
                      <i class="fas fa-save mr-2"></i>{{ isEditing ? 'Update Customer' : 'Create Customer' }}
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- View Customer Modal -->
      <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">
                <i class="fas fa-info-circle mr-2"></i>Customer Details
              </h5>
            </div>
            <div class="modal-body" v-if="selectedCustomer">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">Name</label>
                  <p class="font-weight-bold">{{ selectedCustomer.name }}</p>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">Phone</label>
                  <p>{{ selectedCustomer.phone }}</p>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="text-muted small">Address</label>
                  <p>{{ selectedCustomer.address }}</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeViewModal">Close</button>
              <button type="button" class="btn btn-warning" @click="editFromView">
                <i class="fas fa-edit mr-2"></i>Edit Customer
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
  name: 'Customers',
  components: { MainLayout },
  data() {
    return {
      customers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      sortBy: 'name',
      pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
      form: { name: '', email: '', phone: '', address: '' },
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
        const params = { page };
        const response = await api.get('/customers', { params });
        this.customers = response.data.data;
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
        alert('Failed to load customers');
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      let filtered = [...this.customers];
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(c =>
          c.name.toLowerCase().includes(query) ||
          c.phone.toLowerCase().includes(query)
        );
      }
      if (this.sortBy === 'email') filtered.sort((a, b) => a.email.localeCompare(b.email));
      else if (this.sortBy === 'phone') filtered.sort((a, b) => a.phone.localeCompare(b.phone));
      else filtered.sort((a, b) => a.name.localeCompare(b.name));
      this.customers = filtered;
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.applyFilters(), 300);
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
      Modal.getInstance(document.getElementById('viewModal')).hide();
      this.editCustomer(this.selectedCustomer);
    },

    async saveCustomer() {
      this.saving = true;
      try {
        if (this.isEditing) await api.put(`/customers/${this.editingId}`, this.form);
        else await api.post('/customers', this.form);
        this.closeModal();
        this.fetchCustomers(this.pagination.current_page);
      } catch (error) {
        console.error('Error saving customer:', error);
        alert('Failed to save customer');
      } finally {
        this.saving = false;
      }
    },

    closeModal() {
      Modal.getInstance(document.getElementById('customerModal')).hide();
      this.resetForm();
    },

    closeViewModal() {
      Modal.getInstance(document.getElementById('viewModal')).hide();
      this.selectedCustomer = null;
    },

    resetForm() {
      this.form = { name: '',  phone: '', address: '' };
      this.editingId = null;
    }
  }
};
</script>

<style scoped>
.customers-page {
  animation: fadeIn 0.5s;
}
@keyframes fadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

.card { border-radius: 12px; }
.customer-row { transition: all 0.3s ease; }
.customer-row:hover { background-color: #f8f9fa; transform: translateX(5px); }

.btn-group .btn { border-radius: 6px; margin: 0 2px; }
.input-group-text { border-right: 0; }
.form-control:focus { box-shadow: 0 0 0 0.2rem rgba(230,233,235,0.25); }
.modal-content { border-radius: 12px; }

.pagination .page-link { border-radius: 6px; margin: 0 3px; }
.pagination .page-item.active .page-link { background-color: #007bff; border-color: #007bff; }
</style>
