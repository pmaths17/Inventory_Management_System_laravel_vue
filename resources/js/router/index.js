import Vue from 'vue';
import VueRouter from 'vue-router';

import Dashboard from '@/Pages/Dashboard.vue';
import Products from '@/Pages/Products.vue';
import Sales from '@/Pages/Sales.vue';
import Purchases from '@/Pages/Purchases.vue';
import Customers from '@/Pages/Customers.vue';
import Suppliers from '@/Pages/Suppliers.vue';
// import Register from '@/Pages/Register.vue';
import Reports from '@/Pages/Reports.vue';
import Users from '@/Pages/Users.vue';
import api from '@/services/api.js';


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
    meta: { requiresAuth: true }
  },
  {
    path: '/sales',
    name: 'Sales',
    component: Sales,
    meta: { requiresAuth: true }
  },
  {
    path: '/purchases',
    name: 'Purchases',
    component: Purchases,
    meta: { requiresAuth: true }
  },
  {
    path: '/customers',
    name: 'Customers',
    component: Customers,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/Pages/login.vue'),  // lazy load optional
    meta: { guest: true }   // <-- guest route
  },
  // in router/index.js
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/Pages/Register.vue'),
    meta: { guest: true }
  },
  {
    path: '/suppliers',
    name: 'Suppliers',
    component: Suppliers,
    meta: { requiresAuth: true }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: Reports,
    meta: { requiresAuth: true }
  },
  {
  path: '/users',
  name: 'Users',
  component: () => import('../Pages/Users.vue'),
  meta: { requiresAuth: true, adminOnly: true } // 👈 Add this
},
];
const router = new VueRouter({
  mode: 'history',
  routes
});
router.beforeEach((to, from, next) => {
  const userItem = localStorage.getItem('user');
  let user = null;

  // 1. Safe Parsing Logic
  if (userItem && userItem !== "undefined") {
    try {
      user = JSON.parse(userItem);
    } catch (e) {
      console.error("User data corrupted, clearing storage");
      localStorage.removeItem('user');
    }
  }

  const isAuthenticated = !!user;

  // 2. Logic for Admin-only routes
  if (to.matched.some(record => record.meta.adminOnly)) {
    // Check if user exists AND has admin privileges
    const isAdmin = user && (user.role === 'admin' || user.is_admin);
    
    if (isAuthenticated && isAdmin) {
      next(); 
    } else if (isAuthenticated) {
      // Logged in but not an admin? To the dashboard!
      next('/dashboard'); 
    } else {
      // Not logged in at all? To the login!
      next('/login');
    }
  } 
  // 3. Logic for Auth-required routes (General)
  else if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      next('/login');
    } else {
      next();
    }
  }
  else {
    next(); 
  }
});
//----------------------------
// router.beforeEach((to, from, next) => {
//   next();
// });
//--------------
// router.beforeEach((to, from, next) => {
//   const user = JSON.parse(localStorage.getItem('user') || '{}');
//   const isAuthenticated = !!localStorage.getItem('user');

//   // Check if route requires admin
//   if (to.matched.some(record => record.meta.adminOnly)) {
//     if (isAuthenticated && (user.role === 'admin' || user.is_admin)) {
//       next(); // User is admin, allow access
//     } else {
//       next('/dashboard'); // Not admin? Kick them to dashboard
//     }
//   } else {
//     next(); // Not an admin route, carry on
//   }
// });
export default router;