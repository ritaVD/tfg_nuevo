import { useState, type FormEvent } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { BookOpen, Mail, Lock, LogIn } from 'lucide-react'
import Spinner from '../components/Spinner'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/'

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(email, password)
      navigate(from, { replace: true })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Credenciales incorrectas')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <div className="auth-card__top" />
        <div className="auth-card__content">
          <div className="auth-card__logo">
            <BookOpen size={32} />
          </div>
          <h1 className="auth-card__title">Bienvenido de nuevo</h1>
          <p className="auth-card__subtitle">Inicia sesión en tu cuenta</p>

          {error && <div className="alert alert-danger">{error}</div>}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label className="form-label">
                <Mail size={14} /> Correo electrónico
              </label>
              <input
                type="email"
                className="form-control"
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                autoFocus
                placeholder="tu@email.com"
                autoComplete="email"
              />
            </div>
            <div className="form-group">
              <label className="form-label">
                <Lock size={14} /> Contraseña
              </label>
              <input
                type="password"
                className="form-control"
                value={password}
                onChange={e => setPassword(e.target.value)}
                required
                placeholder="••••••••"
                autoComplete="current-password"
              />
            </div>
            <button
              type="submit"
              className="btn btn-primary btn-block btn-lg"
              disabled={loading}
            >
              {loading ? <Spinner size={18} /> : <><LogIn size={16} /> Iniciar sesión</>}
            </button>
          </form>

          <div className="auth-card__footer">
            ¿No tienes cuenta? <Link to="/register">Regístrate gratis</Link>
          </div>
        </div>
      </div>
    </div>
  )
}
