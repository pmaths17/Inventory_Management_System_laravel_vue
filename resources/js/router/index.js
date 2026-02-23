import Vue from 'vue';
import VueRouter from 'vue-router';

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

router.beforeEach((to, from, next) => {
  const userItem = localStorage.getItem('user');
  let user = null;

  if (userItem && userItem !== 'undefined') {
    try {
      user = JSON.parse(userItem);
    } catch (e) {
      console.error('User data corrupted, clearing storage');
      localStorage.removeItem('user');
    }
  }

  const isAuthenticated = !!user;
  const roleSlugs = Array.isArray(user?.roles) ? user.roles.map(r => r.slug) : [];
  const isAdmin = user && (roleSlugs.includes('admin') || user.role === 'admin' || user.is_admin);
  const permissionSlugs = Array.isArray(user?.roles)
    ? user.roles.flatMap(role => Array.isArray(role.permissions) ? role.permissions.map(permission => permission.slug) : [])
    : [];
  const hasPermission = (permission) => isAdmin || permissionSlugs.includes(permission);

  if (to.matched.some(record => record.meta.adminOnly)) {

    if (isAuthenticated && isAdmin) {
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
      const allowed = requiredAll.every(permission => hasPermission(permission));
      if (allowed) {
        next();
      } else {
        next('/dashboard');
      }
    } else if (to.matched.some(record => record.meta.permission)) {
      const required = to.matched.map(record => record.meta.permission).filter(Boolean);
      const allowed = required.every(permission => hasPermission(permission));
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
