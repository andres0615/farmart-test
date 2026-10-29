import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';

export default function LoginPage() {
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await login(form);
      navigate('/products');
    } catch {
      setError('Credenciales inválidas');
    }
  };


  return (
    <>
      <div className="bg-base-200 min-h-screen flex items-center justify-center p-4 font-[var(--font-sans)]" >
        <div className="card bg-base-100 shadow-md w-full max-w-sm">
          <div className="card-body gap-6">
            {/* Icono decorativo */}
            <div className="flex justify-center">
              <div className="size-14 rounded-full bg-primary/10 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" className="size-7 text-primary">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
              </div>
            </div>

            {/* Título */}
            <div className="text-center">
              <h2 className="text-2xl font-bold">Iniciar Sesión</h2>
              <p className="text-sm text-base-content/60 mt-1">Ingresa tus credenciales para acceder</p>
            </div>

            <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
              {/* Correo electrónico */}
              <label className="floating-label">
                <span>Correo electrónico</span>
                <input 
                  type="email" 
                  placeholder="Correo electrónico" 
                  className="input input-md w-full" 
                  required 
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                />
              </label>

              {/* Contraseña */}
              <label className="floating-label">
                <span>Contraseña</span>
                <input 
                  type="password" 
                  placeholder="Contraseña" 
                  className="input input-md w-full" 
                  required 
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                />
              </label>

              {/* Recordar + Olvidé contraseña */}
              {/* <div className="flex items-center justify-between">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" className="checkbox checkbox-primary checkbox-sm" />
                  <span className="text-sm">Recordar sesión</span>
                </label>
                <a href="#" className="link link-primary link-hover text-sm">Olvidé mi contraseña</a>
              </div> */}

              {/* Botón submit */}
              <button type="submit" className="btn btn-primary btn-block">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" className="size-5">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                Iniciar Sesión
              </button>
            </form>

            {/* Registro */}
            {/* <p className="text-center text-sm text-base-content/60">
              ¿No tienes cuenta?
              <a href="#" className="link link-primary link-hover font-medium">Registrarse</a>
            </p> */}
          </div>
        </div>
      </div>
    </>
  );
}
