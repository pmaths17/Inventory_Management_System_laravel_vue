<template>
  <div class="d-flex vh-100">
    <!-- Sidebar -->
    <nav class="sidebar bg-dark text-white" style="width: 250px; overflow-y: auto;">
      <div class="sidebar-header p-4 border-bottom border-secondary">
        <h3 class="mb-0 font-weight-bold">
          <i class="fas fa-boxes mr-2"></i>IMS
        </h3>
        <small class="text-white">Inventory Management</small>
      </div>

      <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
          <router-link to="/dashboard" class="nav-link text-white" exact-active-class="active">
            <i class="fas fa-chart-line mr-2"></i>Dashboard
          </router-link>
        </li>

        <li class="nav-item mb-2">
          <router-link to="/products" class="nav-link text-white" active-class="active">
            <i class="fas fa-box mr-2"></i>Products
          </router-link>
        </li>

        <li class="nav-item mb-2">
          <router-link to="/purchases" class="nav-link text-white" active-class="active">
            <i class="fas fa-shopping-cart mr-2"></i>Purchases
          </router-link>
        </li>

        <li class="nav-item mb-2">
          <router-link to="/sales" class="nav-link text-white" active-class="active">
            <i class="fas fa-cash-register mr-2"></i>Sales
          </router-link>
        </li>

        <li class="nav-item mb-2">
          <router-link to="/customers" class="nav-link text-white" active-class="active">
            <i class="fas fa-users mr-2"></i>Customers
          </router-link>
        </li>

        <li class="nav-item mb-2">
          <router-link to="/suppliers" class="nav-link text-white" active-class="active">
            <i class="fas fa-truck mr-2"></i>Suppliers
          </router-link>
        </li>
      </ul>
    </nav>

    <!-- Main Content Area -->
    <div class="flex-fill d-flex flex-column">
      <!-- Top Navbar -->
      <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
          <span class="navbar-text font-weight-bold">
            {{ $route.name }}
          </span>
          <div class="ml-auto d-flex align-items-center">
            <span class="mr-3">
              <i class="fas fa-user-circle fa-lg"></i>
              <span class="ml-2">{{ userName }}</span>
            </span>
            <button class="btn btn-outline-danger btn-sm ml-3" @click="handleLogout" :disabled="loggingOut"
              style="margin-left:7px">
              <span v-if="loggingOut">
                <span class="spinner-border spinner-border-sm mr-1"></span>
                Logging out...
              </span>
              <span v-else>
                <i class="fas fa-sign-out-alt mr-1"></i>Logout
              </span>
            </button>
          </div>
        </div>
      </nav>

      <!-- Page Content -->
      <div class="flex-fill p-4 bg-light overflow-auto">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/services/api.js';

export default {
  name: 'MainLayout',
  data() {
    return {
      loggingOut: false
    };
  },
  computed: {
    userName() {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.name || '';
    }
  },
  methods: {
    async handleLogout() {
      this.loggingOut = true;

      // try {
      //   // Call logout API to revoke token on server
      //   await api.post('/logout');
      //   console.log('Logout successful');
      // } catch (error) {
      //   console.error('Logout error:', error);
      //   // Continue with logout even if API call fails
      // } finally {
      //   // Clear localStorage
      //   localStorage.removeItem('token');
      //   localStorage.removeItem('user');

      //   // Remove axios authorization header
      //   delete api.defaults.headers.common['Authorization'];

      //   // Redirect to login
      //   this.$router.replace('/login');

      //   this.loggingOut = false;
      // }
      try {
        await api.post('/logout');
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        // ONLY clear user (no tokens!)
        localStorage.removeItem('user');
        // Remove axios headers if you set any
        delete api.defaults.headers.common['Authorization'];

        // Redirect
        this.$router.replace('/login');

        this.loggingOut = false;
      }
    }
  }
};
</script>

<style scoped>
.sidebar {
  min-height: 100vh;
}

.sidebar .nav-link {
  border-radius: 8px;
  padding: 12px 16px;
  transition: all 0.3s ease;
  font-weight: 500;
}

.sidebar .nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link.active {
  background-color: #007bff;
  color: white !important;
}

.navbar {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>