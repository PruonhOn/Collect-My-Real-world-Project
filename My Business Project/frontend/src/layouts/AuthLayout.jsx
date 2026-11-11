import { Link, Outlet } from 'react-router-dom'
import logo from '../assets/logo.svg'
import { APP_NAME } from '../utils/constants'

export function AuthLayout() {
  return (
    <div className="flex min-h-screen flex-col bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4">
          <Link to="/login" className="flex items-center gap-2">
            <img src={logo} alt="" className="size-9 rounded-lg" />
            <span className="text-lg font-semibold tracking-tight text-slate-900">{APP_NAME}</span>
          </Link>
        </div>
      </header>

      <main className="flex flex-1 items-center justify-center px-4 py-10">
        <div className="w-full max-w-md">
          <Outlet />
        </div>
      </main>

      <footer className="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-400">
        {APP_NAME} - business management platform
      </footer>
    </div>
  )
}

export default AuthLayout
