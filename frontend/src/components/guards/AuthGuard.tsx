import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';
import LoadingPage from '@/components/ui/LoadingPage';

export default function AuthGuard() {
  const auth = useAuth();
  if (!auth) return null;

  const { user, loading } = auth;

  if (loading) return <LoadingPage />;
  if (!user) return <Navigate to="/login" replace />;

  return <Outlet />;
}