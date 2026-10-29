import { Routes, Route, Navigate } from 'react-router-dom'
import LayoutAdmin from './components/layout/LayoutAdmin'
import { LayoutProvider } from './contexts/LayoutContext'
import { ToastProvider } from './components/toast/ToastContext'
import Toast from './components/toast/Toast'
import './App.css'
import { AuthProvider } from '@/contexts/AuthContext';
import AuthGuard from '@/components/guards/AuthGuard';
import GuestGuard from '@/components/guards/GuestGuard';
import LoginPage from '@/modules/auth/pages/LoginPage';

function App() {
  return (
    <AuthProvider>
      <LayoutProvider>
        <ToastProvider>
          <Routes>
            {/* Rutas protegidas por auth */}
            <Route element={<AuthGuard />}>
              {/* Paginas con el layout de admin */}
              <Route element={<LayoutAdmin />}>
                <Route path="/" element={<Navigate to="/products" replace />} />
                {/* <Route path="/patient" element={<PatientListPage />} /> */}
              </Route>
            </Route>

            {/* Rutas públicas */}
            <Route element={<GuestGuard />}>
              <Route path="/login" element={<LoginPage />} />
            </Route>
          </Routes>
          {/* Componente que muestra los notificaciones en la esquina inferior derecha */}
          <Toast />
        </ToastProvider>
      </LayoutProvider>
    </AuthProvider>
  )
}

export default App