import axios from "axios";
import { invalidateRecordCache } from "./recordCache";



export const getApiOrigin = (): string => {
    const envUrl = (import.meta as any).env?.VITE_API_URL;
    if (envUrl && /^https?:\/\//i.test(envUrl)) {
        try {
            return new URL(envUrl).origin;
        } catch {
            // ignore malformed URLs
        }
    }

    if (typeof window !== "undefined" && window.location?.origin) {
        return window.location.origin;
    }

    return "http://127.0.0.1:8000";
};

export const getApiBaseUrl = (): string => {
    const envUrl = (import.meta as any).env?.VITE_API_URL;
    if (envUrl) {
        return envUrl.replace(/\/+$/, "");
    }
    return `${getApiOrigin()}/api`;
};

export const resolveImageUrl = (path: string | null | undefined): string | null => {
    if (!path) return null;
    const trimmed = String(path).trim();
    if (!trimmed) return null;

    if (
        trimmed.startsWith("http://") ||
        trimmed.startsWith("https://") ||
        trimmed.startsWith("data:") ||
        trimmed.startsWith("blob:")
    ) {
        return trimmed;
    }

    const origin = getApiOrigin();
    const cleanPath = trimmed.startsWith("/") ? trimmed : `/${trimmed}`;
    return `${origin}${cleanPath}`;
};

const api = axios.create({
    baseURL: getApiBaseUrl(),
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("token");

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        return config;
    },
    (error) => Promise.reject(error)
);

api.interceptors.response.use(
    (response) => {
        if (!['get', 'head', 'options'].includes(response.config.method?.toLowerCase() || 'get')) {
            invalidateRecordCache();
        }
        return response;
    },
    (error) => {
        if (error?.response?.status === 401) {
            invalidateRecordCache();
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            if (
                typeof window !== "undefined" &&
                window.location &&
                !window.location.pathname.startsWith("/login")
            ) {
                window.location.href = "/login";
            }
        }
        return Promise.reject(error);
    }
);

export default api;
