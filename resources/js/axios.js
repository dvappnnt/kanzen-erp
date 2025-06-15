import axios from "axios";

const instance = axios.create({
    baseURL: import.meta.env.VITE_APP_URL || "https://127.0.0.1:8000", // Use your app's URL
    headers: {
        "X-Requested-With": "XMLHttpRequest",
    },
});

instance.interceptors.request.use((config) => {
    if (config.data instanceof FormData) {
        config.headers["Content-Type"] = "multipart/form-data";
    }
    return config;
});

export default instance;