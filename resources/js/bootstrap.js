import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Global error intercepter for generic Axios requests 
// (Inertia handles its own, but we catch stragglers like AI Chat requests)
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && (error.response.status === 401 || error.response.status === 419)) {
            // Force a full page reload on session expiry.
            // The backend auth middleware will naturally catch it and redirect to the correct login path.
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
