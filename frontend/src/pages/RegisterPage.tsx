import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { authApi } from '../api/auth'
import { BookOpen, Mail, Lock, UserPlus } from 'lucide-react'
import Spinner from '../components/Spinner'

export default function RegisterPage() {
  const { login } = useAuth()
  const navigate = useNavigate()

  const [displayName, setDisplayName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    if (password.length < 6) {
      setError('La contraseña debe tener al menos 6 caracteres')
      return
    }
    if (password !== confirm) {
      setError('Las contraseñas no coinciden')
      return
    }
    setLoading(true)
    try {
      await authApi.register(email, password, displayName)
      await login(email, password)
      navigate('/')
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al crear la cuenta')
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
          <h1 className="auth-card__title">Crear cuenta</h1>
          <p className="auth-card__subtitle">Únete a la comunidad de lectores</p>

          {error && <div className="alert alert-danger">{error}</div>}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label className="form-label">Nombre visible</label>
              <input
                type="text"
                className="form-control"
                value={displayName}
                onChange={e => setDisplayName(e.target.value)}
                placeholder="Tu nombre público"
                autoFocus
              />
            </div>
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
                placeholder="Mínimo 6 caracteres"
                autoComplete="new-password"
              />
            </div>
            <div className="form-group">
              <label className="form-label">
                <Lock size={14} /> Confirmar contraseña
              </label>
              <input
                type="password"
                className="form-control"
                value={confirm}
                onChange={e => setConfirm(e.target.value)}
                required
                placeholder="Repite tu contraseña"
                autoComplete="new-password"
              />
            </div>
            <button
              type="submit"
              className="btn btn-primary btn-block btn-lg"
              disabled={loading}
            >
              {loading ? <Spinner size={18} /> : <><UserPlus size={16} /> Crear cuenta</>}
            </button>
          </form>

          <div className="auth-card__footer">
            ¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link>
          </div>
        </div>
      </div>
    </div>
  )
}
