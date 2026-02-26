<template>
  <MainLayout>
    <div class="suppliers-page">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="dashboard-title">Supplier <br> Management</h2>
          <p class="text-muted small">
            You have {{ pagination.total }} suppliers
          </p>
        </div>
        <button v-if="canCreateSuppliers" class="btn btn-dark btn-lg rounded-pill px-4 shadow-sm" @click="openAddModal">
          <i class="fas fa-plus mr-2"></i>Add Supplier
        </button>
      </div>

      <!-- Search & Sort -->
      <div class="bento-item p-3 mb-4 bg-white shadow-sm border-0">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="search-wrapper">
              <i class="fas fa-search text-muted"></i>
              <input type="text" class="form-control border-0 bg-light rounded-pill" placeholder="Search suppliers..."
                v-model="searchQuery" @input="debouncedSearch" />
            </div>
          </div>

          <div class="col-md-6 d-flex justify-content-md-end">
            <select class="form-select border-0 bg-light rounded-pill" v-model="sortBy" @change="applyFilters">
              <option value="id">Sort: ID</option>
              <option value="name">Sort: Name</option>
              <option value="date">Sort: Created Date</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Suppliers Table -->
      <div class="bento-item p-0 bg-white shadow-sm border-0 overflow-hidden">

        <div v-if="loading" class="text-center py-5">
          <div class="spinner-grow text-dark"></div>
          <p class="mt-3 text-muted">Loading suppliers...</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 border-0 text-uppercase small text-muted">Supplier</th>
                <th class="border-0 text-uppercase small text-muted">Phone</th>
                <th class="border-0 text-uppercase small text-muted">Address</th>
                <th class="pe-4 border-0 text-uppercase small text-muted text-end">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="supplier in filteredSuppliers" :key="supplier.id" class="supplier-row">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="supplier-icon-mini me-3">
                      <i class="fas fa-truck"></i>
                    </div>
                    <div>
                      <div class="font-weight-bold text-dark">
                        {{ supplier.name }}
                      </div>
                      <small class="text-muted">ID: #{{ supplier.id }}</small>
                    </div>
                  </div>
                </td>

                <td>{{ supplier.phone || 'N/A' }}</td>
                <td>{{ supplier.address || 'N/A' }}</td>

                <td class="pe-4 text-end">
                  <div class="action-buttons">
                    <button class="btn-icon view" @click="viewSupplier(supplier)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button v-if="canUpdateSuppliers" class="btn-icon edit" @click="editSupplier(supplier)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button v-if="canDeleteSuppliers" class="btn-icon delete" @click="deleteSupplier(supplier)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="filteredSuppliers.length === 0" class="text-center py-5">
            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
            <h5>No suppliers found</h5>
            <p class="text-muted">Try adjusting your search</p>
          </div>
        </div>

        <!-- Pagination (UNCHANGED) -->
        <div v-if="pagination.total > 0" class="p-3 bg-light d-flex justify-content-between align-items-center">
          <span class="small text-muted ps-2">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }}
          </span>

          <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link rounded-pill px-3" @click="changePage(pagination.current_page - 1)">
                  <i class="fas fa-chevron-left small"></i>
                </button>
              </li>

              <li v-for="page in visiblePages" :key="page" class="page-item"
                :class="{ active: pagination.current_page === page }">
                <button class="page-link rounded-circle d-flex align-items-center justify-content-center"
                  style="width:32px;height:32px" @click="changePage(page)">
                  {{ page }}
                </button>
              </li>

              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link rounded-pill px-3" @click="changePage(pagination.current_page + 1)">
                  <i class="fas fa-chevron-right small"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>

      </div>
    </div>
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
          <div class="modal-header border-0 pt-4 px-4">
            <h5 class="modal-title fw-bold">
              {{ form.id ? 'Edit Supplier' : 'Add New Supplier' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveSupplier">
              <div class="mb-3">
                <label class="small text-muted mb-1">Supplier Name *</label>
                <input type="text" v-model="form.name" class="form-control bg-light border-0 rounded-pill px-3"
                  required />
              </div>
              <div class="mb-3">
                <label class="small text-muted mb-1">Phone Number</label>
                <input type="text" v-model="form.phone" class="form-control bg-light border-0 rounded-pill px-3" />
              </div>
              <div class="mb-3">
                <label class="small text-muted mb-1">Address</label>
                <textarea v-model="form.address" class="form-control bg-light border-0 rounded-3" rows="3"></textarea>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-light rounded-pill px-4" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ form.id ? 'Update' : 'Save' }} Supplier
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
          <div class="modal-body p-4" v-if="selectedSupplier">
            <div class="d-flex justify-content-between mb-4">
              <h5 class="font-weight-bold">Supplier Summary</h5>
              <button type="button" class="btn-close" @click="closeViewModal"></button>
            </div>

            <div class="text-center mb-4">
              <div class="supplier-icon-mini mx-auto mb-3"
                style="width: 60px; height: 60px; font-size: 1.5rem; background: #f8f9fa;">
                <i class="fas fa-truck"></i>
              </div>
              <h4>{{ selectedSupplier.name }}</h4>
              <span class="sku-badge">ID: #{{ selectedSupplier.id }}</span>
            </div>

            <div class="row g-3 text-center">
              <div class="col-6 p-3 bg-light rounded-start border-end">
                <small class="text-muted d-block">Phone</small>
                <strong>{{ selectedSupplier.phone || 'N/A' }}</strong>
              </div>
              <div class="col-6 p-3 bg-light rounded-end">
                <small class="text-muted d-block">Address</small>
                <strong class="text-truncate d-block px-2">{{ selectedSupplier.address || 'No Address' }}</strong>
              </div>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center">
              <button v-if="canDeleteSuppliers" class="btn btn-outline-danger rounded-pill px-4" @click="deleteFromView">
                <i class="fas fa-trash me-2"></i>Delete
              </button>
              <button v-if="canUpdateSuppliers" class="btn btn-dark rounded-pill px-4" @click="editFromView">Edit</button>
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
  name: 'Suppliers',
  components: { MainLayout },
  data() {
    return {
      selectedSupplier: null,
      suppliers: [],
      filteredSuppliers: [],
      loading: false,
      saving: false,
      searchQuery: '',
      sortBy: 'id',
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
    currentUser() {
      return getStoredUser() || {};
    },
    isAdmin() {
      return isAdminUser(this.currentUser);
    },
    canCreateSuppliers() {
      return hasPermission(this.currentUser, 'suppliers.create');
    },
    canUpdateSuppliers() {
      return hasPermission(this.currentUser, 'suppliers.update');
    },
    canDeleteSuppliers() {
      return hasPermission(this.currentUser, 'suppliers.delete');
    },
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
      } else if (this.sortBy === 'id') { // NEW
        filtered.sort((a, b) => a.id - b.id);
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
      if (!this.canCreateSuppliers) return;
      this.resetForm();
      const modalEl = document.getElementById('supplierModal');
      const modal = new Modal(modalEl);
      modal.show();
    },

    editSupplier(supplier) {
      if (!this.canUpdateSuppliers) return;
      this.form = { ...supplier };
      const modalEl = document.getElementById('supplierModal');
      const modal = new Modal(modalEl);
      modal.show();
    },
    viewSupplier(supplier) {
      this.selectedSupplier = supplier;
      new Modal(document.getElementById('viewModal')).show();
    },

    closeViewModal() {
      const modal = Modal.getInstance(document.getElementById('viewModal'));
      if (modal) modal.hide();
      this.selectedSupplier = null;
    },

    editFromView() {
      const supplierToEdit = this.selectedSupplier;
      this.closeViewModal();
      this.editSupplier(supplierToEdit);
    },

    deleteFromView() {
      const supplierToDelete = this.selectedSupplier;
      this.closeViewModal();
      this.deleteSupplier(supplierToDelete);
    },

    async deleteSupplier(supplier) {
      if (!this.canDeleteSuppliers) return;
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
          if (!this.canUpdateSuppliers) return;
          await api.put(`/suppliers/${this.form.id}`, this.form);
          alert('Supplier updated successfully');
        } else {
          if (!this.canCreateSuppliers) return;
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
.suppliers-page {
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

/* Bento container */
.bento-item {
  background: white;
  border-radius: 16px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Page Title */
.dashboard-title {
  font-weight: 800;
  letter-spacing: -1px;
  line-height: 1.1;
  color: #1a1a1a;
}

/* Search */
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

/* Table Row */
.supplier-row {
  transition: all 0.3s ease;
}

.supplier-row:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

/* Supplier Icon */
.supplier-icon-mini {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1f1f1f;
  font-size: 1rem;
}

/* Action Buttons (match products) */
.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  margin-left: 4px;
  transition: 0.2s;
}

.btn-icon.edit:hover {
  background: #fff9db;
  color: #f08c00;
}

.btn-icon.delete:hover {
  background: #fff5f5;
  color: #f03e3e;
}

/* Pagination */
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
  opacity: 0.4;
}

.btn-icon.view:hover {
  background: #f0f7ff;
  color: #007bff;
}

.sku-badge {
  background: #f0f0f0;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #666;
}
</style>
