import axiosInstance, { tokenStorage } from './axiosInstance';

// Backend wraps all responses in { success, message, data: <payload> }
interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

interface LoginData {
  token: string;       // backend returns 'token', not 'access_token'
  token_type: string;
  expires_in: number;
  user: AuthUser;      // user is included in login response — no extra /me call needed
}

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  employee_code: string | null;
  phone: string | null;
  is_active: boolean;
  roles: string[];
  stores: string[];
}

const authService = {
  async login(email: string, password: string): Promise<{ token: string; user: AuthUser }> {
    const { data: res } = await axiosInstance.post<ApiResponse<LoginData>>('/auth/login', { email, password });
    tokenStorage.set(res.data.token);
    return { token: res.data.token, user: res.data.user };
  },

  async me(): Promise<AuthUser> {
    const { data: res } = await axiosInstance.get<ApiResponse<AuthUser>>('/auth/me');
    return res.data;
  },

  async logout(): Promise<void> {
    try {
      await axiosInstance.post('/auth/logout');
    } finally {
      tokenStorage.clear();
    }
  },

  async refresh(): Promise<string> {
    const { data: res } = await axiosInstance.post<ApiResponse<{ token: string }>>('/auth/refresh');
    tokenStorage.set(res.data.token);
    return res.data.token;
  },
};

export default authService;
