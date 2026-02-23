import axios from 'axios';

const backendBaseUrl = import.meta.env.VITE_BACKEND_URL
    || `${window.location.protocol}//${window.location.hostname}:8000`;

const api = axios.create({
    baseURL: backendBaseUrl,
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export default api;
