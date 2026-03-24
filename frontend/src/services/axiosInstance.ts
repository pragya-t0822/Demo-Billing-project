import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';

const BASE_URL = import.meta.env.VITE_API_URL as string;

// Token storage keys
const ACCESS_TOKEN_KEY = 'retail_access_token';

export const tokenStorage = {
  get: (): string | null => localStorage.getItem(ACCESS_TOKEN_KEY),
  set: (token: string): void => { localStorage.setItem(ACCESS_TOKEN_KEY, token); },
  clear: (): void => { localStorage.removeItem(ACCESS_TOKEN_KEY); },
};

// Central Axios instance
export const axiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  timeout: 15000,
});

// ── Request interceptor: attach JWT Bearer token ──────────────────────────
axiosInstance.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = tokenStorage.get();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// Track whether a refresh is in progress to avoid multiple concurrent refreshes
let isRefreshing = false;
let pendingQueue: Array<{ resolve: (token: string) => void; reject: (err: unknown) => void }> = [];

const processPendingQueue = (error: unknown, token: string | null) => {
  pendingQueue.forEach(({ resolve, reject }) => {
    if (error) reject(error);
    else if (token) resolve(token);
  });
  pendingQueue = [];
};

// ── Response interceptor: handle 401 (token refresh) and 5xx errors ──────
axiosInstance.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    // 401 → attempt silent token refresh once (skip for auth endpoints to avoid loops)
    const url = originalRequest.url ?? '';
    if (error.response?.status === 401 && !originalRequest._retry && !url.includes('/auth/')) {
      if (isRefreshing) {
        // Queue concurrent requests until refresh completes
        return new Promise((resolve, reject) => {
          pendingQueue.push({
            resolve: (token) => {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              resolve(axiosInstance(originalRequest));
            },
            reject,
          });
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const { data } = await axiosInstance.post<{ success: boolean; data: { token: string } }>('/auth/refresh');
        const newToken = data.data.token; // backend: { success, data: { token } }
        tokenStorage.set(newToken);
        axiosInstance.defaults.headers.common.Authorization = `Bearer ${newToken}`;
        processPendingQueue(null, newToken);
        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return axiosInstance(originalRequest);
      } catch (refreshError) {
        processPendingQueue(refreshError, null);
        tokenStorage.clear();
        // Redirect to login — dispatch a custom event so AuthContext can react
        window.dispatchEvent(new CustomEvent('auth:session-expired'));
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  },
);

export default axiosInstance;
