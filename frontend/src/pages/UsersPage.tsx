import { useState, useEffect, useRef } from 'react'
import { Link } from 'react-router-dom'
import { Search, Users, UserCheck, UserPlus, Clock, Sparkles } from 'lucide-react'
import { useAuth } from '../context/AuthContext'
import { usersApi, type UserResult } from '../api/users'
import { apiFetch } from '../api/client'
import Spinner from '../components/Spinner'

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

export default function UsersPage() {
  const { user: me } = useAuth()
  const [query, setQuery]       = useState('')
  const [results, setResults]   = useState<UserResult[]>([])
  const [loading, setLoading]   = useState(false)
  const [searched, setSearched] = useState(false)
  const [following, setFollowing] = useState<Record<number, boolean>>({})
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current)

    if (query.trim().length < 2) {
      setResults([])
      setSearched(false)
      return
    }

    debounceRef.current = setTimeout(async () => {
      setLoading(true)
      try {
        const data = await usersApi.search(query.trim())
        setResults(data)
        setSearched(true)
      } catch {
        setResults([])
      } finally {
        setLoading(false)
      }
    }, 350)

    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current)
    }
  }, [query])

  async function handleFollow(u: UserResult) {
    if (!me || following[u.id]) return
    setFollowing(prev => ({ ...prev, [u.id]: true }))
    try {
      if (u.followStatus === 'none') {
        const res = await apiFetch<{ status: string; followers: number }>(`/users/${u.id}/follow`, 'POST')
        setResults(prev => prev.map(r =>
          r.id === u.id
            ? { ...r, followStatus: res.status as 'pending' | 'accepted', followers: res.followers }
            : r
        ))
      } else {
        const res = await apiFetch<{ followers: number }>(`/users/${u.id}/follow`, 'DELETE')
        setResults(prev => prev.map(r =>
          r.id === u.id ? { ...r, followStatus: 'none', followers: res.followers } : r
        ))
      }
    } catch { /* ignore */ }
    finally { setFollowing(prev => ({ ...prev, [u.id]: false })) }
  }

  return (
    <>
    <div className="page-banner page-banner--users">
      <div className="page-banner__inner">
        <div className="page-banner__text">
          <span className="page-banner__eyebrow">
            <Sparkles size={12} /> Comunidad lectora
          </span>
          <h1 className="page-banner__title">Buscar lectores</h1>
          <p className="page-banner__desc">
            Encuentra otros lectores, sigue su actividad y descubre qué están leyendo.
          </p>
        </div>
      </div>
    </div>
    <div className="page-content" style={{ maxWidth: 700 }}>

      {/* Search input */}
      <div className="users-search-wrap">
        <span className="users-search-icon"><Search size={18} /></span>
        <input
          className="form-control users-search-input"
          placeholder="Busca por nombre de usuario…"
          value={query}
          onChange={e => setQuery(e.target.value)}
          autoFocus
        />
        {loading && (
          <span className="users-search-spinner"><Spinner size={16} /></span>
        )}
      </div>

      {/* Results */}
      {!searched && !loading && (
        <div className="empty-state">
          <div className="empty-state__icon"><Users size={40} /></div>
          <p className="empty-state__title">Busca lectores</p>
          <p className="empty-state__desc">Escribe al menos 2 caracteres para empezar.</p>
        </div>
      )}

      {searched && results.length === 0 && !loading && (
        <div className="empty-state">
          <div className="empty-state__icon"><Search size={40} /></div>
          <p className="empty-state__title">Sin resultados</p>
          <p className="empty-state__desc">Nadie coincide con "{query}". Prueba con otro nombre.</p>
        </div>
      )}

      {results.length > 0 && (
        <div className="users-results">
          {results.map(u => (
            <div key={u.id} className="user-search-card">
              <Link to={`/users/${u.id}`} className="user-search-card__avatar-link">
                <img
                  src={u.avatar
                    ? (u.avatar.startsWith('http') ? u.avatar : `/uploads/avatars/${u.avatar}`)
                    : dicebear(u.displayName ?? 'U')}
                  alt={u.displayName ?? ''}
                  className="user-search-card__avatar"
                />
              </Link>

              <div className="user-search-card__info">
                <Link to={`/users/${u.id}`} className="user-search-card__name">
                  {u.displayName}
                </Link>
                {u.bio && (
                  <p className="user-search-card__bio">{u.bio}</p>
                )}
                <span className="user-search-card__followers">
                  {u.followers} {u.followers === 1 ? 'seguidor' : 'seguidores'}
                </span>
              </div>

              {me && !u.isMe && (
                <button
                  className={`btn btn-sm user-search-card__action${u.followStatus === 'accepted' ? ' btn-secondary' : u.followStatus === 'pending' ? ' btn-ghost' : ' btn-primary'}`}
                  onClick={() => handleFollow(u)}
                  disabled={!!following[u.id]}
                >
                  {following[u.id] ? (
                    <Spinner size={12} />
                  ) : u.followStatus === 'accepted' ? (
                    <><UserCheck size={14} /> Siguiendo</>
                  ) : u.followStatus === 'pending' ? (
                    <><Clock size={14} /> Pendiente</>
                  ) : (
                    <><UserPlus size={14} /> Seguir</>
                  )}
                </button>
              )}
              {u.isMe && (
                <Link to="/profile" className="btn btn-ghost btn-sm user-search-card__action">
                  Mi perfil
                </Link>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
    </>
  )
}
