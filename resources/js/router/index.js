import Vue from 'vue';
import VueRouter from 'vue-router';

import Dashboard from '@/Pages/Dashboard.vue';
import Products from '@/Pages/Products.vue';
import Sales from '@/Pages/Sales.vue';
import Purchases from '@/Pages/Purchases.vue';
import Customers from '@/Pages/Customers.vue';
import Suppliers from '@/Pages/Suppliers.vue';
import Register from '@/Pages/Register.vue';
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
  }
];
const router = new VueRouter({
  mode: 'history',
  routes
});
router.beforeEach((to, from, next) => {
  next();
});
export default router;