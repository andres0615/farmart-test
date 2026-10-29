import { createContext, useContext, useState, useCallback, type ReactNode } from 'react'

// Tipos de toast disponibles: éxito, error, información o advertencia
export type ToastType = 'success' | 'error' | 'info' | 'warning'

export interface Toast {
  id: number
  message: string
  type: ToastType
}

interface ToastContextType {
  toasts: Toast[]
  showToast: (message: string, type?: ToastType) => void
  removeToast: (id: number) => void
}

// Contexto que almacena la lista de toasts activos
const ToastContext = createContext<ToastContextType | undefined>(undefined)

// Contador incremental para IDs únicos de cada toast
let nextId = 0

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  // Elimina un toast por su ID (útil para cierre manual o auto-remoción)
  const removeToast = useCallback((id: number) => {
    setToasts((prev) => prev.filter((t) => t.id !== id))
  }, [])

  // Agrega un toast a la lista y lo elimina automáticamente tras 3.5 segundos
  const showToast = useCallback((message: string, type: ToastType = 'info') => {
    const id = nextId++
    setToasts((prev) => [...prev, { id, message, type }])
    setTimeout(() => removeToast(id), 6000)
  }, [removeToast])

  return (
    <ToastContext.Provider value={{ toasts, showToast, removeToast }}>
      {children}
    </ToastContext.Provider>
  )
}

// Hook para acceder al contexto desde cualquier componente hijo
export function useToast(): ToastContextType {
  const context = useContext(ToastContext)
  if (context === undefined) {
    throw new Error('useToast debe usarse dentro de un <ToastProvider>')
  }
  return context
}
