import { createContext, useContext, useState, type ReactNode } from 'react'

interface LayoutContextType {
  maxWidth: string
  setMaxWidth: (value: string) => void
}

// Crear el contexto
const LayoutContext = createContext<LayoutContextType | undefined>(undefined)

// Provider que expone el contexto
export function LayoutProvider({ children }: { children: ReactNode }) {
  const [maxWidth, setMaxWidth] = useState('max-w-4xl')

  return (
    <LayoutContext.Provider value={{ maxWidth, setMaxWidth }}>
      {children}
    </LayoutContext.Provider>
  )
}

// Hook para acceder al contexto
export function useLayoutContext(): LayoutContextType {
  const context = useContext(LayoutContext)
  if (context === undefined) {
    throw new Error('useLayoutContext debe usarse dentro de un <LayoutProvider>')
  }
  return context
}
