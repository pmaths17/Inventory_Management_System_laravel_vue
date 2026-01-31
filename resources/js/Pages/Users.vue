<template>
    <main-layout>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="dashboard-title">System <br> Access Control</h2>
            <button class="btn btn-dark rounded-pill px-4 shadow-sm" @click="openCreateModal">
                <i class="fas fa-user-plus me-2"></i> Add New User
            </button>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="bento-item p-4 bg-white h-100 shadow-sm">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-box bg-dark text-white me-3">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="m-0 font-weight-bold">Administrators</h5>
                    </div>

                    <div v-if="admins.length === 0" class="text-muted small py-3">No admins found.</div>

                    <div v-for="user in admins" :key="user.id"
                        class="user-card p-3 mb-3 border rounded-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3">{{ getInitials(user.name) }}</div>
                            <div>
                                <div class="fw-bold">{{ user.name }}</div>
                                <div class="text-muted small">{{ user.email }}</div>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-icon view" @click="viewUser(user)"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon edit" @click="editUser(user)"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon delete" @click="confirmDelete(user)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bento-item p-4 bg-white h-100 shadow-sm">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-box bg-light text-dark me-3 border">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="m-0 font-weight-bold">Staff Members</h5>
                    </div>

                    <div v-if="staff.length === 0" class="text-muted small py-3">No staff members assigned.</div>

                    <div v-for="user in staff" :key="user.id"
                        class="user-card p-3 mb-3 border rounded-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle staff-avatar me-3">{{ getInitials(user.name) }}</div>
                            <div>
                                <div class="fw-bold">{{ user.name }}</div>
                                <div class="text-muted small">{{ user.email }}</div>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-icon view" @click="viewUser(user)"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon edit" @click="editUser(user)"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon delete" @click="confirmDelete(user)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showAddModal" class="modal-overlay">
            <div class="modal-content bento-item p-4">
                <h4 class="mb-4">{{ isEditing ? 'Update User Account' : 'Create New Account' }}</h4>

                <form @submit.prevent="saveUser">
                    <div class="mb-3">
                        <label class="small text-muted">Full Name</label>
                        <input v-model="form.name" type="text" class="form-control rounded-pill border-light bg-light" required>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted">Email Address</label>
                        <input v-model="form.email" type="email" class="form-control rounded-pill border-light bg-light" :disabled="isEditing" required>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted">
                            {{ isEditing ? 'Change Password (leave blank to keep current)' : 'Initial Password' }}
                        </label>
                        <input v-model="form.password" type="password" class="form-control rounded-pill border-light bg-light" :required="!isEditing">
                    </div>

                    <div class="mb-4">
                        <label class="small text-muted">Account Role</label>
                        <select v-model="form.role" class="form-select rounded-pill border-light bg-light">
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark rounded-pill px-4 flex-fill" :disabled="loading">
                            {{ loading ? 'Saving...' : (isEditing ? 'Update User' : 'Create User') }}
                        </button>
                        <button type="button" @click="closeModal" class="btn btn-light rounded-pill px-4">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showViewModal" class="modal-overlay">
            <div class="modal-content bento-item p-4 text-center">
                <div class="avatar-circle mx-auto mb-3" style="width: 70px; height: 70px; font-size: 1.5rem;">
                    {{ getInitials(selectedUser?.name || '') }}
                </div>
                <h4>{{ selectedUser?.name }}</h4>
                <p class="text-muted">{{ selectedUser?.email }}</p>
                <div class="badge bg-dark rounded-pill px-3 py-2 mb-4">{{ selectedUser?.role }}</div>
                <button class="btn btn-light rounded-pill w-100" @click="showViewModal = false">Close</button>
            </div>
        </div>
    </main-layout>
</template>

<script>
import MainLayout from '../Layouts/MainLayout.vue';

export default {
    components: { MainLayout },
    data() {
        return {
            users: [],
            showAddModal: false,
            showViewModal: false,
            isEditing: false,
            loading: false,
            selectedUser: null,
            form: { name: '', email: '', password: '', role: 'staff' }
        }
    },
    computed: {
        admins() { return this.users.filter(u => u.role === 'admin'); },
        staff() { return this.users.filter(u => u.role === 'staff'); }
    },
    mounted() {
        this.fetchUsers();
    },
    methods: {
        async fetchUsers() {
            try {
                const res = await this.$axios.get('/users');
                // Note: Controllers using ->paginate() return data inside .data.data
                this.users = res.data.data || res.data;
            } catch (err) {
                console.error("Fetch failed", err);
            }
        },
        openCreateModal() {
            this.isEditing = false;
            this.form = { name: '', email: '', password: '', role: 'staff' };
            this.showAddModal = true;
        },
        viewUser(user) {
            this.selectedUser = user;
            this.showViewModal = true;
        },
        editUser(user) {
            this.isEditing = true;
            this.selectedUser = user;
            // Clear password so user doesn't think they are editing an encrypted string
            this.form = { ...user, password: '' }; 
            this.showAddModal = true;
        },
        async saveUser() {
            this.loading = true;
            try {
                if (this.isEditing) {
                    await this.$axios.put(`/users/${this.selectedUser.id}`, this.form);
                } else {
                    await this.$axios.post('/users', this.form);
                }
                this.closeModal();
                this.fetchUsers();
            } catch (err) {
                alert(err.response?.data?.message || 'Error saving user');
            } finally {
                this.loading = false;
            }
        },
        async confirmDelete(user) {
            if (confirm(`Remove ${user.name} from the system?`)) {
                await this.$axios.delete(`/users/${user.id}`);
                this.fetchUsers();
            }
        },
        closeModal() {
            this.showAddModal = false;
            this.isEditing = false;
            this.form = { name: '', email: '', password: '', role: 'staff' };
        },
        getInitials(name) {
            if (!name) return '';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }
    }
}
</script>

<style scoped>
.bento-item {
    border-radius: 20px;
    transition: all 0.25s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.icon-box {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-circle {
    width: 48px;
    height: 48px;
    background: #1a1a1a;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.85rem;
}

.staff-avatar {
    background: #e9ecef;
    color: #495057;
}

.user-card {
    transition: transform 0.2s ease;
    cursor: default;
}

.user-card:hover {
    transform: translateX(5px);
    background: #f8f9fa;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    width: 400px;
    background: white;
    border: none;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
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
</style>