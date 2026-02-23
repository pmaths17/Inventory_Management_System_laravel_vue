<template>
    <main-layout>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="dashboard-title">Roles & <br> Permissions</h2>
            <button v-if="canCreateRoles" class="btn btn-dark rounded-pill px-4 shadow-sm" @click="openCreateRoleModal">
                <i class="fas fa-plus me-2"></i> New Role
            </button>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="bento-item p-4 bg-white shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0 font-weight-bold">Role Catalog</h5>
                        <small class="text-muted">{{ roles.length }} roles</small>
                    </div>

                    <div v-if="loading" class="text-muted small">Loading roles...</div>
                    <div v-else-if="errorMessage" class="alert alert-warning py-2 small">{{ errorMessage }}</div>
                    <div v-else-if="roles.length === 0" class="text-muted small">No roles created yet.</div>

                    <div v-for="role in roles" :key="role.id"
                        class="role-card p-3 mb-2 rounded-4 d-flex justify-content-between align-items-center"
                        :class="{ selected: editingRoleId === role.id }">
                        <div>
                            <div class="fw-bold d-flex align-items-center gap-2">
                                {{ role.name }}
                                <span v-if="role.is_system" class="badge rounded-pill text-bg-dark">System</span>
                                <span v-if="!role.is_active" class="badge rounded-pill text-bg-secondary">Archived</span>
                            </div>
                            <small class="text-muted">{{ role.slug }}</small>
                            <div class="small text-muted mt-1">
                                {{ role.permissions_count || rolePermissionsCount(role) }} permissions,
                                {{ role.users_count || 0 }} users
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button v-if="canUpdateRoles" class="btn-icon view" @click="editRole(role)" title="Edit role">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button v-if="canUpdateRoles" class="btn-icon archive" @click="toggleArchive(role)"
                                :title="role.is_active ? 'Archive role' : 'Restore role'">
                                <i :class="role.is_active ? 'fas fa-box-archive' : 'fas fa-rotate-left'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="bento-item p-4 bg-white shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0 font-weight-bold">Permissions</h5>
                        <small class="text-muted">{{ permissions.length }} total permissions</small>
                    </div>

                    <form @submit.prevent="saveRole" class="role-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted">Role Name</label>
                                <input v-model="form.name" type="text" class="form-control rounded-pill border-light bg-light"
                                    placeholder="e.g. Inventory Manager" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Role Slug</label>
                                <input v-model="form.slug" type="text" class="form-control rounded-pill border-light bg-light"
                                    placeholder="inventory-manager" :disabled="form.is_system">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activeRole" v-model="form.is_active">
                                <label class="form-check-label small text-muted" for="activeRole">
                                    Role active
                                </label>
                            </div>
                            <small v-if="form.is_system" class="text-muted">System role: slug is locked.</small>
                        </div>

                        <div class="permission-panel mt-3 p-3 rounded-4">
                            <div v-if="groupedPermissions.length === 0" class="text-muted small">
                                No permissions found.
                            </div>

                            <div v-for="group in groupedPermissions" :key="group.module" class="mb-3">
                                <h6 class="text-uppercase small text-muted mb-2">{{ group.module }}</h6>
                                <div class="permission-grid">
                                    <label v-for="permission in group.items" :key="permission.id" class="permission-chip">
                                        <input type="checkbox" :value="permission.id" v-model="form.permission_ids">
                                        <span>{{ permission.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-dark rounded-pill px-4" :disabled="saving || !canSubmitRole">
                                {{ saving ? 'Saving...' : (editingRoleId ? 'Update Role' : 'Create Role') }}
                            </button>
                            <button type="button" class="btn btn-light rounded-pill px-4" @click="resetForm">
                                Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main-layout>
</template>

<script>
import MainLayout from '../Layouts/MainLayout.vue';

export default {
    name: 'RolesPermissions',
    components: { MainLayout },
    data() {
        return {
            roles: [],
            permissions: [],
            loading: false,
            saving: false,
            errorMessage: '',
            editingRoleId: null,
            form: {
                name: '',
                slug: '',
                is_active: true,
                is_system: false,
                permission_ids: [],
            }
        };
    },
    computed: {
        isAdmin() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            const roleSlugs = Array.isArray(user.roles) ? user.roles.map(role => role.slug) : [];
            return roleSlugs.includes('admin') || user.role === 'admin' || user.is_admin;
        },
        permissionSlugs() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (!Array.isArray(user.roles)) return [];
            return user.roles.flatMap(role =>
                Array.isArray(role.permissions) ? role.permissions.map(permission => permission.slug) : []
            );
        },
        canCreateRoles() {
            return this.isAdmin || this.permissionSlugs.includes('roles.create');
        },
        canUpdateRoles() {
            return this.isAdmin || this.permissionSlugs.includes('roles.update');
        },
        canSubmitRole() {
            if (this.editingRoleId) {
                return this.canUpdateRoles;
            }
            return this.canCreateRoles;
        },
        groupedPermissions() {
            const groups = {};
            const permissionList = Array.isArray(this.permissions) ? this.permissions : [];
            permissionList.forEach(permission => {
                const module = (permission.module || 'general').toUpperCase();
                if (!groups[module]) {
                    groups[module] = [];
                }
                groups[module].push(permission);
            });
            return Object.keys(groups).sort().map(module => ({
                module,
                items: groups[module]
            }));
        }
    },
    mounted() {
        this.fetchAll();
    },
    methods: {
        async syncCurrentUserPermissions() {
            try {
                const response = await this.$axios.get('/user');
                localStorage.setItem('user', JSON.stringify(response.data));
            } catch (error) {
                console.error('Unable to refresh current user permissions:', error);
            }
        },
        rolePermissionsCount(role) {
            if (!role || !Array.isArray(role.permissions)) {
                return 0;
            }
            return role.permissions.length;
        },
        async fetchAll() {
            this.loading = true;
            this.errorMessage = '';
            try {
                const [rolesRes, permissionsRes] = await Promise.all([
                    this.$axios.get('/roles'),
                    this.$axios.get('/permissions'),
                ]);

                const rolesData = Array.isArray(rolesRes.data) ? rolesRes.data : (Array.isArray(rolesRes.data?.data) ? rolesRes.data.data : []);
                const permissionsData = Array.isArray(permissionsRes.data) ? permissionsRes.data : (Array.isArray(permissionsRes.data?.data) ? permissionsRes.data.data : []);

                this.roles = rolesData;
                this.permissions = permissionsData;
            } catch (error) {
                console.error('Failed loading RBAC data:', error);
                this.errorMessage = error.response?.data?.message || 'Failed to load roles and permissions.';
                this.roles = [];
                this.permissions = [];
            } finally {
                this.loading = false;
            }
        },
        openCreateRoleModal() {
            this.resetForm();
        },
        editRole(role) {
            this.editingRoleId = role.id;
            this.form = {
                name: role.name,
                slug: role.slug,
                is_active: !!role.is_active,
                is_system: !!role.is_system,
                permission_ids: (role.permissions || []).map(permission => permission.id),
            };
        },
        async saveRole() {
            if (!this.canSubmitRole) {
                return;
            }
            this.saving = true;
            const payload = {
                name: this.form.name,
                slug: this.form.slug || undefined,
                is_active: this.form.is_active,
                permission_ids: this.form.permission_ids,
            };

            try {
                if (this.editingRoleId) {
                    await this.$axios.put(`/roles/${this.editingRoleId}`, payload);
                } else {
                    await this.$axios.post('/roles', payload);
                }
                await this.fetchAll();
                await this.syncCurrentUserPermissions();
                this.resetForm();
            } catch (error) {
                alert(error.response?.data?.message || 'Unable to save role');
            } finally {
                this.saving = false;
            }
        },
        async toggleArchive(role) {
            const nextActive = !role.is_active;
            try {
                await this.$axios.put(`/roles/${role.id}`, {
                    name: role.name,
                    slug: role.slug,
                    is_active: nextActive,
                    permission_ids: (role.permissions || []).map(permission => permission.id),
                });
                await this.fetchAll();
                await this.syncCurrentUserPermissions();
                if (this.editingRoleId === role.id) {
                    this.form.is_active = nextActive;
                }
            } catch (error) {
                alert(error.response?.data?.message || 'Unable to update role state');
            }
        },
        resetForm() {
            this.editingRoleId = null;
            this.form = {
                name: '',
                slug: '',
                is_active: true,
                is_system: false,
                permission_ids: [],
            };
        }
    }
};
</script>

<style scoped>
.bento-item {
    border-radius: 20px;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.dashboard-title {
    font-weight: 800;
    letter-spacing: -1px;
    line-height: 1.1;
    color: #1a1a1a;
}

.role-card {
    border: 1px solid #ececec;
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.role-card:hover {
    transform: translateX(4px);
    background: #f3f4f6;
}

.role-card.selected {
    border-color: #1a1a1a;
    background: #f1f3f5;
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
    background: #fff9db;
    color: #f08c00;
}

.btn-icon.archive:hover {
    background: #e7f5ff;
    color: #1971c2;
}

.permission-panel {
    max-height: 360px;
    overflow-y: auto;
    background: #f8f9fa;
    border: 1px solid #ececec;
}

.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 8px;
}

.permission-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.9rem;
}
</style>
