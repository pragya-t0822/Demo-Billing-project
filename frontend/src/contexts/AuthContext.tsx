import {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  type ReactNode,
} from 'react';
import { useNavigate } from 'react-router-dom';
import authService, { type AuthUser } from '../services/authService';
import { tokenStorage } from '../services/axiosInstance';

interface AuthContextType {
  user: AuthUser | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const navigate = useNavigate();

  // On mount: if a token exists, fetch the current user to restore session
  useEffect(() => {
    const restoreSession = async () => {
      const token = tokenStorage.get();
      if (!token) { setIsLoading(false); return; }
      try {
        const me = await authService.me();
        setUser(me);
      } catch {
        tokenStorage.clear();
      } finally {
        setIsLoading(false);
      }
    };
    restoreSession();
  }, []);

  // Listen for the session-expired event dispatched by the Axios interceptor
  useEffect(() => {
    const handleExpired = () => {
      setUser(null);
      navigate('/login', { replace: true });
    };
    window.addEventListener('auth:session-expired', handleExpired);
    return () => window.removeEventListener('auth:session-expired', handleExpired);
  }, [navigate]);

  const login = useCallback(async (email: string, password: string) => {
    const { user: me } = await authService.login(email, password);
    setUser(me);
  }, []);

  const logout = useCallback(async () => {
    await authService.logout();
    setUser(null);
    navigate('/login', { replace: true });
  }, [navigate]);

  return (
    <AuthContext.Provider value={{ user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
