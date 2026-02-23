<template>
    <main-layout>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="dashboard-title">System <br> Access Control</h2>
            <button v-if="canCreateUsers" class="btn btn-dark rounded-pill px-4 shadow-sm" @click="openCreateModal">
                <i class="fas fa-user-plus me-2"></i> Add New User
            </button>
        </div>

        <div class="bento-item p-4 bg-white shadow-sm">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box bg-dark text-white">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <h5 class="m-0 font-weight-bold">All Users</h5>
                        <small class="text-muted">{{ filteredUsers.length }} shown of {{ users.length }}</small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <input v-model.trim="searchQuery" type="text" class="form-control rounded-pill bg-light border-light"
                        placeholder="Search name or email" style="min-width: 220px;">
                    <select v-model="selectedRole" class="form-select rounded-pill bg-light border-light">
                        <option value="">All Roles</option>
                        <option v-for="role in roleOptions" :key="role" :value="role">{{ role }}</option>
                    </select>
                </div>
            </div>

            <div v-if="filteredUsers.length === 0" class="text-muted small py-3">
                No users match the current filters.
            </div>

            <div v-for="user in filteredUsers" :key="user.id"
                class="user-card p-3 mb-3 border rounded-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3">{{ getInitials(user.name) }}</div>
                    <div>
                        <div class="fw-bold">{{ user.name }}</div>
                        <div class="text-muted small">{{ user.email }}</div>
                        <div class="mt-1">
                            <span class="badge rounded-pill me-1"
                                :class="roleSlug(user) === 'admin' ? 'bg-dark' : 'bg-secondary'">
                                {{ roleLabel(user) }}
                            </span>
                            <span class="badge bg-light text-dark border rounded-pill">User ID: {{ user.id }}</span>
                        </div>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="btn-icon view" @click="viewUser(user)"><i class="fas fa-eye"></i></button>
                    <button v-if="canUpdateUsers" class="btn-icon edit" @click="editUser(user)"><i class="fas fa-edit"></i></button>
                    <button v-if="canDeleteUsers" class="btn-icon delete" @click="confirmDelete(user)"><i class="fas fa-trash"></i></button>
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
                        <select v-model="form.role_id" class="form-select rounded-pill border-light bg-light">
                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.name }}
                            </option>
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
                <div class="badge bg-dark rounded-pill px-3 py-2 mb-4">{{ roleLabel(selectedUser || {}) }}</div>
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
            roles: [],
            searchQuery: '',
            selectedRole: '',
            showAddModal: false,
            showViewModal: false,
            isEditing: false,
            loading: false,
            selectedUser: null,
            form: { name: '', email: '', password: '', role_id: null }
        }
    },
    computed: {
        permissionSlugs() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (!Array.isArray(user.roles)) return [];
            return user.roles.flatMap(role =>
                Array.isArray(role.permissions) ? role.permissions.map(permission => permission.slug) : []
            );
        },
        isAdmin() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            const roleSlugs = Array.isArray(user.roles) ? user.roles.map(role => role.slug) : [];
            return roleSlugs.includes('admin') || user.role === 'admin' || user.is_admin;
        },
        canCreateUsers() {
            return this.isAdmin || this.permissionSlugs.includes('users.create');
        },
        canUpdateUsers() {
            return this.isAdmin || this.permissionSlugs.includes('users.update');
        },
        canDeleteUsers() {
            return this.isAdmin || this.permissionSlugs.includes('users.delete');
        },
        roleOptions() {
            const roleNames = this.users.map(user => this.roleLabel(user));
            return [...new Set(roleNames)].sort();
        },
        filteredUsers() {
            const query = this.searchQuery.toLowerCase();
            return this.users.filter(user => {
                const role = this.roleLabel(user);
                const matchesRole = !this.selectedRole || role === this.selectedRole;
                const matchesText =
                    !query ||
                    user.name.toLowerCase().includes(query) ||
                    user.email.toLowerCase().includes(query);
                return matchesRole && matchesText;
            });
        }
    },
    mounted() {
        this.fetchUsers();
        this.fetchRoles();
    },
    methods: {
        async syncCurrentUserPermissions() {
            try {
                const response = await this.$axios.get('/user');
                localStorage.setItem('user', JSON.stringify(response.data));
            } catch (err) {
                console.error("Unable to refresh session user permissions", err);
            }
        },
        roleSlug(user) {
            if (Array.isArray(user.roles) && user.roles.length > 0) {
                return user.roles[0].slug;
            }
            return user.role || 'staff';
        },
        roleLabel(user) {
            if (Array.isArray(user.roles) && user.roles.length > 0) {
                return user.roles[0].name;
            }
            return user.role || 'Staff';
        },
        async fetchRoles() {
            try {
                const res = await this.$axios.get('/roles');
                this.roles = res.data.filter(role => role.is_active);
                if (!this.form.role_id && this.roles.length > 0) {
                    const staffRole = this.roles.find(role => role.slug === 'staff');
                    this.form.role_id = staffRole ? staffRole.id : this.roles[0].id;
                }
            } catch (err) {
                console.error("Roles fetch failed", err);
            }
        },
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
            const staffRole = this.roles.find(role => role.slug === 'staff');
            this.form = { name: '', email: '', password: '', role_id: staffRole ? staffRole.id : (this.roles[0]?.id || null) };
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
            this.form = {
                name: user.name,
                email: user.email,
                password: '',
                role_id: user.roles?.[0]?.id || null,
            };
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
                await this.syncCurrentUserPermissions();
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
                try {
                    await this.$axios.delete(`/users/${user.id}`);
                    await this.syncCurrentUserPermissions();
                    this.fetchUsers();
                } catch (err) {
                    alert(err.response?.data?.message || 'Unable to delete user');
                }
            }
        },
        closeModal() {
            this.showAddModal = false;
            this.isEditing = false;
            const staffRole = this.roles.find(role => role.slug === 'staff');
            this.form = { name: '', email: '', password: '', role_id: staffRole ? staffRole.id : (this.roles[0]?.id || null) };
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
