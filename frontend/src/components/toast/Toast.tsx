import { useToast } from './ToastContext'
import type { ToastType } from './ToastContext'

// Mapeo de tipo a clase de color de daisyUI (strings literales para que Tailwind las detecte)
const colorClass: Record<ToastType, string> = {
  success: 'alert-success',
  error: 'alert-error',
  info: 'alert-info',
  warning: 'alert-warning',
}

const btnColorClass: Record<ToastType, string> = {
  success: 'btn-success',
  error: 'btn-error',
  info: 'btn-info',
  warning: 'btn-warning',
}

// Componente visual que renderiza la lista de toasts activos en la esquina inferior derecha
export default function Toast() {
  const { toasts, removeToast } = useToast()

  // No renderiza nada si no hay notificaciones pendientes
  if (toasts.length === 0) return null

  return (
    // Contenedor anclado al viewport con z-index alto para superponerse al contenido
    <div className="toast toast-top toast-end z-50">
      {toasts.map((t) => (
        // Cada toast usa daisyUI alert con el color según su tipo
        <div key={t.id} className={`alert ${colorClass[t.type]} shadow-lg font-light color-black`}>
          <span>{t.message}</span>
          {/* Botón para cerrar el toast manualmente */}
          <button
            className={`btn ${btnColorClass[t.type]} btn-xs text-[#03100c]`}
            onClick={() => removeToast(t.id)}
          >
            ✕
          </button>
        </div>
      ))}
    </div>
  )
}
