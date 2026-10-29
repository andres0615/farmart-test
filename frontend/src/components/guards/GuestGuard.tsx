import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';

export default function GuestGuard() {
  const auth = useAuth();
  if (!auth) return null;

  const { user, loading } = auth;

  if (loading) return <div>Cargando...</div>;
  if (user) return <Navigate to="/products" replace />;

  return <Outlet />;
}