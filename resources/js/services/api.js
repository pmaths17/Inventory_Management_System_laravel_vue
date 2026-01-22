import axios from 'axios';

const api = axios.create({//variable holding the axios instance
    // baseURL: '/api',//property of api var
    baseURL: 'http://127.0.0.1:8000/api',//property of api var
     withCredentials: true, // for cookies
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});
// // Add token if logged in
// api.interceptors.request.use(config => { //interceptor:Axios object that allows running function before requests or after responses
//     //request: to execute before each request 
//     //use: method that registers a function to run before each request
//     //config: parameter that registers parameter request configuration
//     const token = localStorage.getItem('token');
//     if (token) config.headers.Authorization = `Bearer ${token}`;
//     return config;
// }
// );
await axios.get('http://127.0.0.1:8000/sanctum/csrf-cookie', { withCredentials: true });

export default api;