<template>
  <MainLayout>
    <div class="suppliers-page">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1 font-weight-bold">Suppliers Management</h2>
          <p class="text-muted mb-0">Manage your suppliers</p>
        </div>
        <button class="btn btn-primary btn-lg shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>New Supplier
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
                <input type="text" class="form-control border-start-0"
                  placeholder="Search by name or phone..."
                  v-model="searchQuery"
                  @input="debouncedSearch" />
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-control" v-model="sortBy" @change="applyFilters">
                <option value="name">Sort by Name</option>
                <option value="date">Sort by Created Date</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Suppliers Table -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3 text-muted">Loading suppliers...</p>
          </div>

          <div v-else-if="suppliers.length === 0" class="text-center py-5">
            <i class="fas fa-truck fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No suppliers found</h5>
            <p class="text-muted">Start by adding a new supplier</p>
            <button class="btn btn-primary mt-3" @click="openAddModal">
              <i class="fas fa-plus mr-2"></i>New Supplier
            </button>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="border-0">Supplier ID</th>
                  <th class="border-0">Name</th>
                  <th class="border-0">Phone</th>
                  <th class="border-0">Address</th>
                  <th class="border-0">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="supplier in filteredSuppliers" :key="supplier.id" class="supplier-row">
                  <td>#{{ supplier.id }}</td>
                  <td class="font-weight-bold">{{ supplier.name }}</td>
                  <td>{{ supplier.phone || 'N/A' }}</td>
                  <td>{{ supplier.address || 'N/A' }}</td>
                  <td>
                    <div class="btn-group" role="group">
                      <button class="btn btn-sm btn-outline-primary" @click="editSupplier(supplier)" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" @click="deleteSupplier(supplier)" title="Delete">
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
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} suppliers
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

      <!-- Add/Edit Supplier Modal -->
      <div class="modal fade" id="supplierModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">
                <i class="fas fa-truck mr-2"></i>{{ form.id ? 'Edit Supplier' : 'New Supplier' }}
              </h5>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveSupplier">
                <div class="mb-3">
                  <label class="font-weight-bold">Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="form.name" required />
                </div>
                <div class="mb-3">
                  <label class="font-weight-bold">Phone</label>
                  <input type="text" class="form-control" v-model="form.phone" />
                </div>
                <div class="mb-3">
                  <label class="font-weight-bold">Address</label>
                  <textarea class="form-control" rows="3" v-model="form.address"></textarea>
                </div>

                <div class="d-flex justify-content-end mt-4">
                  <button type="button" class="btn btn-secondary mr-2" @click="closeModal">Cancel</button>
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving">
                      <span class="spinner-border spinner-border-sm mr-2"></span>Saving...
                    </span>
                    <span v-else>
                      <i class="fas fa-save mr-2"></i>Save
                    </span>
                  </button>
                </div>
              </form>
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
  name: 'Suppliers',
  components: { MainLayout },
  data() {
    return {
      suppliers: [],
      filteredSuppliers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      sortBy: 'name',
      pagination: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      },
      form: {
        id: null,
        name: '',
        phone: '',
        address: ''
      },
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
    this.fetchSuppliers();
  },
  methods: {
    async fetchSuppliers(page = 1) {
      this.loading = true;
      try {
        const params = { page };
        const response = await api.get('/suppliers', { params });
        this.suppliers = response.data.data || response.data;
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          from: response.data.from || 1,
          to: response.data.to || this.suppliers.length,
          total: response.data.total || this.suppliers.length
        };
        this.applyFilters();
      } catch (error) {
        console.error('Error fetching suppliers:', error);
        alert('Failed to load suppliers');
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      let filtered = [...this.suppliers];
      const q = this.searchQuery.toLowerCase();
      if (q) {
        filtered = filtered.filter(s =>
          s.name.toLowerCase().includes(q) || (s.phone || '').includes(q)
        );
      }

      if (this.sortBy === 'name') {
        filtered.sort((a, b) => a.name.localeCompare(b.name));
      } else if (this.sortBy === 'date') {
        filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      }

      this.filteredSuppliers = filtered;
    },

    debouncedSearch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.applyFilters();
      }, 300);
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) this.fetchSuppliers(page);
    },

    openAddModal() {
      this.resetForm();
      const modalEl = document.getElementById('supplierModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    editSupplier(supplier) {
      this.form = { ...supplier };
      const modalEl = document.getElementById('supplierModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    async deleteSupplier(supplier) {
      if (!confirm(`Are you sure you want to delete ${supplier.name}?`)) return;
      try {
        await api.delete(`/suppliers/${supplier.id}`);
        alert('Supplier deleted successfully');
        this.fetchSuppliers(this.pagination.current_page);
      } catch (error) {
        console.error('Delete error:', error);
        alert('Failed to delete supplier');
      }
    },

    async saveSupplier() {
      this.saving = true;
      try {
        if (this.form.id) {
          await api.put(`/suppliers/${this.form.id}`, this.form);
          alert('Supplier updated successfully');
        } else {
          await api.post('/suppliers', this.form);
          alert('Supplier created successfully');
        }
        this.closeModal();
        this.fetchSuppliers(this.pagination.current_page);
      } catch (error) {
        console.error('Save error:', error);
        alert('Failed to save supplier');
      } finally {
        this.saving = false;
      }
    },

    closeModal() {
      const modalEl = document.getElementById('supplierModal');
      const modal = Modal.getInstance(modalEl);
      if (modal) modal.hide();
      this.resetForm();
    },

    resetForm() {
      this.form = { id: null, name: '', phone: '', address: '' };
    }
  }
};
</script>

<style scoped>
.suppliers-page { animation: fadeIn 0.5s; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.card { border-radius: 12px; }
.supplier-row { transition: all 0.3s ease; }
.supplier-row:hover { background-color: #f8f9fa; transform: translateX(5px); }

.btn-group .btn { border-radius: 6px; margin: 0 2px; }
</style>
