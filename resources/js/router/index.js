import Vue from 'vue';
import VueRouter from 'vue-router';
import api from '@/services/api.js';
import {
  clearStoredUser,
  getStoredUser,
  hasAllPermissions,
  isAdminUser,
} from '@/utils/authz.js';

import Dashboard from '@/Pages/Dashboard.vue';
import Products from '@/Pages/Products.vue';
import Sales from '@/Pages/Sales.vue';
import Purchases from '@/Pages/Purchases.vue';
import Customers from '@/Pages/Customers.vue';
import Suppliers from '@/Pages/Suppliers.vue';
import Reports from '@/Pages/Reports.vue';

Vue.use(VueRouter);

const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/products',
    name: 'Products',
    component: Products,
    meta: { requiresAuth: true, permission: 'products.view' }
  },
  {
    path: '/sales',
    name: 'Sales',
    component: Sales,
    meta: { requiresAuth: true, permission: 'sales.view' }
  },
  {
    path: '/purchases',
    name: 'Purchases',
    component: Purchases,
    meta: { requiresAuth: true, permission: 'purchases.view' }
  },
  {
    path: '/customers',
    name: 'Customers',
    component: Customers,
    meta: { requiresAuth: true, permission: 'customers.view' }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/Pages/login.vue'),
    meta: { guest: true }
  },
  {
    path: '/suppliers',
    name: 'Suppliers',
    component: Suppliers,
    meta: { requiresAuth: true, permission: 'suppliers.view' }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: Reports,
    meta: { requiresAuth: true, permission: 'reports.view' }
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../Pages/Users.vue'),
    meta: { requiresAuth: true, permission: 'users.view' }
  },
  {
    path: '/roles-permissions',
    name: 'RolesPermissions',
    component: () => import('../Pages/RolesPermissions.vue'),
    meta: { requiresAuth: true, permissionsAll: ['roles.view', 'permissions.view'] }
  },
];

const router = new VueRouter({
  mode: 'history',
  routes
});

async function refreshCurrentUser() {
  try {
    const response = await api.get('/user');
    localStorage.setItem('user', JSON.stringify(response.data));
    return response.data;
  } catch (error) {
    clearStoredUser();
    return null;
  }
}

router.beforeEach(async (to, from, next) => {
  const needsAuthCheck = to.matched.some(record =>
    record.meta.requiresAuth || record.meta.adminOnly || record.meta.permission || record.meta.permissionsAll
  );

  let user = getStoredUser();
  if (needsAuthCheck || user) {
    user = await refreshCurrentUser();
  }

  const isAuthenticated = Boolean(user);

  if (to.matched.some(record => record.meta.guest) && isAuthenticated) {
    next('/dashboard');
  } else if (to.matched.some(record => record.meta.adminOnly)) {

    if (isAuthenticated && isAdminUser(user)) {
      next();
    } else if (isAuthenticated) {
      next('/dashboard');
    } else {
      next('/login');
    }
  } else if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      next('/login');
    } else if (to.matched.some(record => record.meta.permissionsAll)) {
      const requiredAll = to.matched.flatMap(record => record.meta.permissionsAll || []);
      const allowed = hasAllPermissions(user, requiredAll);
      if (allowed) {
        next();
      } else {
        next('/dashboard');
      }
    } else if (to.matched.some(record => record.meta.permission)) {
      const required = to.matched.map(record => record.meta.permission).filter(Boolean);
      const allowed = hasAllPermissions(user, required);
      if (allowed) {
        next();
      } else {
        next('/dashboard');
      }
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
