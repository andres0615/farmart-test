import { Outlet } from 'react-router-dom'
import Sidebar from '../sidebar/Sidebar'
import { useLayoutContext } from '../../contexts/LayoutContext'

export default function LayoutAdmin() {
  const { maxWidth } = useLayoutContext()

  return (
    <>
      <div className="flex h-screen">
        {/* Sidebar */}
        <Sidebar />

        {/* Main Content */}
        <main className="flex-1 overflow-auto">
            <div className={`${maxWidth} mx-auto py-8`}>
                {/* Content */}
                <Outlet />
            </div>
        </main>
    </div>
    </>
  )
}
