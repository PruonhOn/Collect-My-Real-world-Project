import { Navigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

export default function AdminRoute({ children }) {
  const { user, isAuthenticated } = useAuth()
  const isAdmin = user?.roles?.includes('admin')

  if (!isAuthenticated || !isAdmin) {
    return <Navigate to={isAuthenticated ? '/dashboard' : '/login'} replace />
  }
  return children
}