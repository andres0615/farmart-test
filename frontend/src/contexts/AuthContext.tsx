import { createContext, useContext, useState, useEffect, type ReactNode } from 'react';
import type { User } from '@/types/user';
import apiClient from '@/api/apiClient';

type LoginCredentials = { email: string; password: string };

interface AuthContextValue {
  user: User | null;
  login: (credentials: LoginCredentials) => Promise<{ access_token: string }>;
  logout: () => Promise<void>;
  loading: boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('access_token');
    if (token) {
      fetchUser();
    } else {
      setLoading(false);
    }
  }, []);

  const fetchUser = async () => {
    try {
      const data = await apiClient<User>('/me',{
        method: 'POST',
      });
      setUser(data);
    } catch {
      localStorage.removeItem('access_token');
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (credentials: LoginCredentials) => {
    const response = await apiClient('/login', {
      method: 'POST',
      body: credentials,
    });
    if (!response) throw new Error('Respuesta inválida del servidor');

    const { access_token, expires_in, token_type } = await response.json();
    
    localStorage.setItem('access_token', access_token);
    await fetchUser();
    return response;
  };

  const logout = async () => {
    try {
      await apiClient('/logout', { method: 'POST' });
    } finally {
      localStorage.removeItem('access_token');
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);