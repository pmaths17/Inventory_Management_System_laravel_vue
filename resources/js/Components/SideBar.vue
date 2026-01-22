<template>
  <nav class="sidebar bg-dark text-white d-flex flex-column">
    <div class="sidebar-header p-3 border-bottom border-secondary">
      <div class="d-flex align-items-center">
        <i class="fas fa-chart-line me-2"></i>
        <h4 class="mb-0">ERP System</h4>
      </div>
    </div>
    
    <ul class="nav flex-column p-3">
      <li class="nav-item mb-2">
        <router-link to="/" class="nav-link text-white" active-class="active">
          <i class="fas fa-home me-2"></i>
          Dashboard
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/products" class="nav-link text-white" active-class="active">
          <i class="fas fa-box me-2"></i>
          Products
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/sales" class="nav-link text-white" active-class="active">
          <i class="fas fa-shopping-cart me-2"></i>
          Sales
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/purchases" class="nav-link text-white" active-class="active">
          <i class="fas fa-truck me-2"></i>
          Purchases
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/customers" class="nav-link text-white" active-class="active">
          <i class="fas fa-users me-2"></i>
          Customers
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/suppliers" class="nav-link text-white" active-class="active">
          <i class="fas fa-industry me-2"></i>
          Suppliers
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/reports" class="nav-link text-white" active-class="active">
          <i class="fas fa-chart-bar me-2"></i>
          Reports
        </router-link>
      </li>
      <li class="nav-item mb-2">
        <router-link to="/settings" class="nav-link text-white" active-class="active">
          <i class="fas fa-cog me-2"></i>
          Settings
        </router-link>
      </li>
    </ul>

    <div class="mt-auto p-3 border-top border-secondary">
      <div class="d-flex align-items-center">
        <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="User">
        <div class="flex-grow-1">
          <div class="small fw-bold">Admin User</div>
          <div class="x-small text-muted">admin@erp.com</div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Sidebar'
}
</script>

<style scoped>
.sidebar {
  width: 250px;
  min-height: 100vh;
  background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
}

.sidebar-header h4 {
  font-size: 1.25rem;
  font-weight: 600;
}

.nav-link {
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  transition: all 0.3s ease;
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
  background-color: #3498db;
  font-weight: 500;
}

.x-small {
  font-size: 0.75rem;
}
</style>