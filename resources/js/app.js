import Vue from 'vue';
import App from './app.vue';
import router from './router';
// import axios from 'axios';
import api from '@/services/api';

// Import Bootstrap and FontAwesome CSS
import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
// import '../css/app.css';
// import '@/css/app.css';
import '../css/app.css';

// Axios configuration
//axios.defaults.baseURL = '/api';
//axios.defaults.headers.common['Accept'] = 'application/json';

// Get token from localStorage if it exists
// const token = localStorage.getItem('token');
//if (token) {
//  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
//}

Vue.prototype.$axios = api;

new Vue({
  router,
  render: h => h(App),
}).$mount('#app');