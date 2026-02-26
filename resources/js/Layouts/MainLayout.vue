<template>
  <div class="d-flex vh-100">
    <nav class="sidebar" :class="{ collapsed: isSidebarCollapsed }">
      <div class="sidebar-header">
        <button class="collapse-btn" @click="toggleSidebar">
          <i class="fas fa-bars"></i>
        </button>
        <h3 v-if="!isSidebarCollapsed" class="logo">IMS</h3>
      </div>

      <ul class="nav flex-column">
        <li class="nav-item" v-for="item in menu" :key="item.label">
          <router-link :to="item.to" class="nav-link" active-class="active">
            <i :class="item.icon"></i>
            <span v-if="!isSidebarCollapsed">{{ item.label }}</span>
          </router-link>
        </li>
      </ul>

      <div class="p-3 mt-auto">
        <button class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center"
          @click="handleLogout" :disabled="loggingOut" style="min-height: 45px;">
          <span v-if="loggingOut">
            <span class="spinner-border spinner-border-sm"></span>
          </span>
          <span v-else class="d-flex align-items-center gap-2">
            <i class="fas fa-sign-out-alt"></i>
            <span v-if="!isSidebarCollapsed">Logout</span>
          </span>
        </button>
      </div>
    </nav>

    <!-- <div class="flex-fill d-flex flex-column">
      <div class="flex-fill overflow-auto" style="background-color: #181818; padding: 1rem;">
        <div class="page-container">
          <slot></slot>
        </div>
      </div>
    </div> -->
    <div class="main-content">
      <div class="content-wrapper">
        <div v-if="hasNoActiveRoles" class="alert alert-warning py-2 small mb-3">
          Your account has no active role. Access is limited. Contact an administrator.
        </div>
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/services/api.js';
import { getPermissionSlugs, getStoredUser, hasPermission, isAdminUser } from '@/utils/authz.js';

export default {
  name: 'MainLayout',
  data() {
    return {
      loggingOut: false,
      isSidebarCollapsed: localStorage.getItem('sidebarStatus') === 'true',
      user: getStoredUser() || {}
    };
  },
  computed: {
    isAdmin() {
      return isAdminUser(this.user);
    },
    permissionSlugs() {
      return getPermissionSlugs(this.user);
    },
    hasNoActiveRoles() {
      return Array.isArray(this.user?.roles) && this.user.roles.length === 0;
    },
    menu() {
      const can = (permission) => hasPermission(this.user, permission);
      const items = [
        { to: '/dashboard', label: 'Dashboard', icon: 'fas fa-chart-line' },
        { to: '/products', label: 'Products', icon: 'fas fa-box', permission: 'products.view' },
        { to: '/purchases', label: 'Purchases', icon: 'fas fa-shopping-cart', permission: 'purchases.view' },
        { to: '/sales', label: 'Sales', icon: 'fas fa-cash-register', permission: 'sales.view' },
        { to: '/customers', label: 'Customers', icon: 'fas fa-users', permission: 'customers.view' },
        { to: '/suppliers', label: 'Suppliers', icon: 'fas fa-truck', permission: 'suppliers.view' },
      ];

      if (can('reports.view')) {
        items.push({ to: '/reports', label: 'Reports', icon: 'fas fa-file-invoice-dollar' });
      }
      if (can('users.view')) {
        items.push({ to: '/users', label: 'Manage Staff', icon: 'fas fa-user-shield' });
      }
      if (can('roles.view') && can('permissions.view')) {
        items.push({ to: '/roles-permissions', label: 'Access Rules', icon: 'fas fa-key' });
      }

      return items.filter(item => !item.permission || can(item.permission));
    }
  },
  // EVERYTHING BELOW MUST BE INSIDE METHODS
  methods: {
    toggleSidebar() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed;
      localStorage.setItem('sidebarStatus', this.isSidebarCollapsed);
    },
    async handleLogout() {
      this.loggingOut = true;
      try {
        await api.post('/logout');
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        localStorage.removeItem('user');
        this.$router.replace('/login');
        this.loggingOut = false;
      }
    }
  } // End of methods
};
</script>

<style scoped>
.sidebar {
  background-color: #181818;
  /* #121212;  modern dark */
  color: #fff;
  width: 250px;
  min-height: 100vh;
  transition: width 0.25s ease;
  overflow: hidden;
   position: sticky;
}

.sidebar.collapsed {
  width: 72px;
}

.sidebar-header {
  display: flex;
  align-items: center;
  padding: 16px;
}

.collapse-btn {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
}

.logo {
  margin-left: 12px;
  font-weight: 700;
}

.nav-link {
  position: relative;
  color: #cfcfcf;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  border-radius: 4px;
  /* Slightly sharper edges to match image */
  margin: 4px 0px;
  /* Removed side margins to allow indicator to touch the edge */
  transition: all 0.2s ease;
  text-decoration: none;
}

.nav-link:hover {
  color: white;
  background: rgba(255, 255, 255, 0.05);
}

/* The Active State Styling */
.nav-link.active {
  color: #ffffff;
  /* Soft white gradient background for the whole tab */
  background: linear-gradient(to right,
      rgba(255, 255, 255, 0.12) 0%,
      rgba(255, 255, 255, 0.05) 50%,
      transparent 100%);
}

/* The White Vertical Indicator */
.nav-link.active::before {
  content: "";
  position: absolute;
  left: 0;
  top: 15%;
  bottom: 15%;
  width: 4px;
  background: #ffffff;
  border-radius: 0 4px 4px 0;
  /* Glow effect */
  box-shadow: 2px 0px 10px rgba(255, 255, 255, 0.5);
}

.nav-link i {
  color: #ffffff;
  font-size: 18px;
  opacity: 0.8;
  /* Dimmed slightly when not active */
}

.nav-link.active i {
  opacity: 1;
  text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}


/* .page-container {
  background-color: #f3f5ed;
  border-radius: 20px;
  padding: 2rem;

  width: calc(100% - 10px);
  max-width: 100%;

  min-height: calc(100vh - 2rem);
  margin-left: 0;

  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  transition: all 0.25s ease;
} */


/* .flex-fill.overflow-auto {
  padding: 1rem 1rem 1rem 0.5rem !important;
  transition: all 0.25s ease;
} */


.sidebar.collapsed .btn-outline-light {
  padding: 10px 0;
  border: none;
}

.sidebar.collapsed .btn-outline-light i {
  margin: 0;
  font-size: 20px;
}

.flex-fill {
  transition: all 0.25s ease;
}

.sidebar.collapsed .nav-link {
  justify-content: center;
  padding: 12px 0;
}

.sidebar.collapsed .nav-link i {
  margin: 0;
}
/* ---------------------------- */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  background-color: #181818;
  padding: 1rem 1rem 1rem 0.5rem;
  overflow: hidden; /* KEY: Prevents the main area from scrolling */
  height: 100vh;
}

.content-wrapper {
  background-color: #f3f5ed;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
  overflow-y: auto; /* KEY: Makes ONLY the content scrollable */
  overflow-x: hidden;
  height: 100%;
  /* Custom scrollbar styling */
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
}

.content-wrapper::-webkit-scrollbar {
  width: 8px;
}

.content-wrapper::-webkit-scrollbar-track {
  background: transparent;
}

.content-wrapper::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.3);
  border-radius: 10px;
}
</style>
