import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import Spinner from './Spinner'

export default function PrivateRoute({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <div className="loading-state"><Spinner /></div>
  if (!user) return <Navigate to="/login" state={{ from: location }} replace />
  return <>{children}</>
}
