import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { adminApi, type AdminUser, type AdminClub, type AdminPost, type AdminStats } from '../api/admin'
import Spinner from '../components/Spinner'
import { Users, BookOpen, ImageIcon, BarChart3, ShieldAlert, Trash2 } from 'lucide-react'

type Tab = 'stats' | 'users' | 'clubs' | 'posts'

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ── Stats Panel ───────────────────────────────────────────────────────────────

function StatsPanel() {
  const [stats, setStats] = useState<AdminStats | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    adminApi.stats().then(setStats).finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="loading-state"><Spinner size={32} /></div>
  if (!stats) return null

  const cards = [
    { label: 'Usuarios registrados', value: stats.users, icon: <Users size={22} />, gradient: 'var(--gradient-purple)', shadow: 'var(--shadow-colored)' },
    { label: 'Clubs creados',        value: stats.clubs, icon: <BookOpen size={22} />, gradient: 'var(--gradient-cyan)', shadow: 'var(--shadow-cyan)' },
    { label: 'Publicaciones',        value: stats.posts, icon: <ImageIcon size={22} />, gradient: 'var(--gradient-rose)', shadow: 'var(--shadow-rose)' },
  ]

  return (
    <div className="admin-stats-grid">
      {cards.map(c => (
        <div key={c.label} className="admin-stat-card">
          <div className="admin-stat-card__icon" style={{ background: c.gradient, boxShadow: c.shadow }}>
            {c.icon}
          </div>
          <div className="admin-stat-card__num">{c.value}</div>
          <div className="admin-stat-card__label">{c.label}</div>
        </div>
      ))}
    </div>
  )
}

// ── Users Panel ───────────────────────────────────────────────────────────────

function UsersPanel({ currentUserId }: { currentUserId: number }) {
  const [users, setUsers] = useState<AdminUser[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')

  useEffect(() => {
    adminApi.users().then(setUsers).finally(() => setLoading(false))
  }, [])

  async function handleToggleAdmin(user: AdminUser) {
    if (!confirm(`${user.isAdmin ? 'Quitar' : 'Dar'} rol de administrador a ${user.displayName || user.email}?`)) return
    try {
      await adminApi.setRole(user.id, !user.isAdmin)
      setUsers(prev => prev.map(u => u.id === user.id ? { ...u, isAdmin: !u.isAdmin } : u))
    } catch { /* ignore */ }
  }

  async function handleToggleBan(user: AdminUser) {
    if (!confirm(`${user.isBanned ? 'Desbanear' : 'Banear'} a ${user.displayName || user.email}?`)) return
    try {
      await adminApi.setBan(user.id, !user.isBanned)
      setUsers(prev => prev.map(u => u.id === user.id ? { ...u, isBanned: !u.isBanned } : u))
    } catch { /* ignore */ }
  }

  async function handleDelete(user: AdminUser) {
    if (!confirm(`¿Eliminar la cuenta de ${user.displayName || user.email}? Esta acción no se puede deshacer.`)) return
    try {
      await adminApi.deleteUser(user.id)
      setUsers(prev => prev.filter(u => u.id !== user.id))
    } catch { /* ignore */ }
  }

  const filtered = users.filter(u => {
    const q = search.toLowerCase()
    return !q || u.email.toLowerCase().includes(q) || (u.displayName || '').toLowerCase().includes(q)
  })

  if (loading) return <div className="loading-state"><Spinner size={32} /></div>

  return (
    <div>
      <input
        className="form-control form-control-sm"
        placeholder="Buscar por email o nombre…"
        value={search}
        onChange={e => setSearch(e.target.value)}
        style={{ maxWidth: 320, marginBottom: '1rem' }}
      />
      <div className="admin-table-wrap">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(user => (
              <tr key={user.id}>
                <td>{user.id}</td>
                <td><span style={{ fontWeight: 600 }}>{user.displayName || '—'}</span></td>
                <td>{user.email}</td>
                <td>
                  {user.isAdmin
                    ? <span className="badge badge-primary">Admin</span>
                    : <span className="badge badge-neutral">Usuario</span>}
                </td>
                <td>
                  {user.isBanned
                    ? <span className="badge badge-danger">Baneado</span>
                    : <span className="badge badge-success">Activo</span>}
                </td>
                <td>
                  {user.id !== currentUserId ? (
                    <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap' }}>
                      <button
                        className={`btn btn-sm ${user.isAdmin ? 'btn-secondary' : 'btn-primary'}`}
                        onClick={() => handleToggleAdmin(user)}
                      >
                        {user.isAdmin ? 'Quitar admin' : 'Hacer admin'}
                      </button>
                      <button
                        className={`btn btn-sm ${user.isBanned ? 'btn-secondary' : 'btn-warning'}`}
                        onClick={() => handleToggleBan(user)}
                      >
                        {user.isBanned ? 'Desbanear' : 'Banear'}
                      </button>
                      <button className="btn btn-danger btn-sm btn-icon" onClick={() => handleDelete(user)} title="Eliminar usuario">
                        <Trash2 size={13} />
                      </button>
                    </div>
                  ) : (
                    <span className="badge badge-success">Tú</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {filtered.length === 0 && (
          <div className="empty-state" style={{ padding: '2rem' }}>
            <p className="empty-state__title">Sin resultados</p>
          </div>
        )}
      </div>
    </div>
  )
}

// ── Clubs Panel ───────────────────────────────────────────────────────────────

function ClubsPanel() {
  const [clubs, setClubs] = useState<AdminClub[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')

  useEffect(() => {
    adminApi.clubs().then(setClubs).finally(() => setLoading(false))
  }, [])

  async function handleDelete(club: AdminClub) {
    if (!confirm(`¿Eliminar el club "${club.name}"? Esta acción no se puede deshacer.`)) return
    try {
      await adminApi.deleteClub(club.id)
      setClubs(prev => prev.filter(c => c.id !== club.id))
    } catch { /* ignore */ }
  }

  const filtered = clubs.filter(c => {
    const q = search.toLowerCase()
    return !q || c.name.toLowerCase().includes(q)
  })

  if (loading) return <div className="loading-state"><Spinner size={32} /></div>

  return (
    <div>
      <input
        className="form-control form-control-sm"
        placeholder="Buscar club…"
        value={search}
        onChange={e => setSearch(e.target.value)}
        style={{ maxWidth: 320, marginBottom: '1rem' }}
      />
      <div className="admin-table-wrap">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Propietario</th>
              <th>Visibilidad</th>
              <th>Miembros</th>
              <th>Creado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(club => (
              <tr key={club.id}>
                <td>{club.id}</td>
                <td><span style={{ fontWeight: 600 }}>{club.name}</span></td>
                <td>{club.owner?.displayName || club.owner?.email || '—'}</td>
                <td>
                  {club.visibility === 'public'
                    ? <span className="badge badge-accent">Público</span>
                    : <span className="badge badge-neutral">Privado</span>}
                </td>
                <td>{club.memberCount}</td>
                <td>{fmtDate(club.createdAt)}</td>
                <td>
                  <button className="btn btn-danger btn-sm btn-icon" onClick={() => handleDelete(club)} title="Eliminar club">
                    <Trash2 size={13} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {filtered.length === 0 && (
          <div className="empty-state" style={{ padding: '2rem' }}>
            <p className="empty-state__title">Sin resultados</p>
          </div>
        )}
      </div>
    </div>
  )
}

// ── Posts Panel ───────────────────────────────────────────────────────────────

function PostsPanel() {
  const [posts, setPosts] = useState<AdminPost[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    adminApi.posts().then(setPosts).finally(() => setLoading(false))
  }, [])

  async function handleDelete(post: AdminPost) {
    if (!confirm('¿Eliminar esta publicación?')) return
    try {
      await adminApi.deletePost(post.id)
      setPosts(prev => prev.filter(p => p.id !== post.id))
    } catch { /* ignore */ }
  }

  if (loading) return <div className="loading-state"><Spinner size={32} /></div>

  if (posts.length === 0) {
    return (
      <div className="empty-state">
        <div className="empty-state__icon"><ImageIcon size={40} /></div>
        <p className="empty-state__title">No hay publicaciones</p>
      </div>
    )
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.625rem' }}>
      {posts.map(post => (
        <div key={post.id} className="admin-post-row">
          <img
            src={`/uploads/posts/${post.imagePath}`}
            alt="post"
            className="admin-post-row__thumb"
          />
          <div className="admin-post-row__info">
            <div className="admin-post-row__user">{post.user.displayName || post.user.email}</div>
            {post.description && (
              <div className="admin-post-row__desc">{post.description}</div>
            )}
            <div className="admin-post-row__date">{fmtDate(post.createdAt)}</div>
          </div>
          <button
            className="btn btn-danger btn-sm btn-icon"
            onClick={() => handleDelete(post)}
            title="Eliminar publicación"
          >
            <Trash2 size={14} />
          </button>
        </div>
      ))}
    </div>
  )
}

// ── Main Page ─────────────────────────────────────────────────────────────────

const TABS: { id: Tab; label: string; icon: React.ReactNode }[] = [
  { id: 'stats', label: 'Estadísticas', icon: <BarChart3 size={14} /> },
  { id: 'users', label: 'Usuarios',     icon: <Users size={14} /> },
  { id: 'clubs', label: 'Clubs',        icon: <BookOpen size={14} /> },
  { id: 'posts', label: 'Publicaciones',icon: <ImageIcon size={14} /> },
]

export default function AdminPage() {
  const { user, loading } = useAuth()
  const navigate = useNavigate()
  const [tab, setTab] = useState<Tab>('stats')

  useEffect(() => {
    if (!loading && (!user || !user.roles?.includes('ROLE_ADMIN'))) {
      navigate('/')
    }
  }, [user, loading])

  if (loading || !user) return <div className="loading-state loading-state--page"><Spinner size={40} /></div>

  return (
    <>
      <div className="page-banner page-banner--admin">
        <div className="page-banner__inner">
          <div className="page-banner__text">
            <span className="page-banner__eyebrow">
              <ShieldAlert size={12} /> Administración
            </span>
            <h1 className="page-banner__title">Panel de administración</h1>
            <p className="page-banner__desc">
              Gestión completa de usuarios, clubs y contenido de la plataforma.
            </p>
          </div>
        </div>
      </div>

      <div className="page-content">
        <div className="ch-tabs" style={{ marginBottom: '1.75rem' }}>
          {TABS.map(t => (
            <button
              key={t.id}
              className={`tab-btn${tab === t.id ? ' tab-btn--active' : ''}`}
              onClick={() => setTab(t.id)}
            >
              {t.icon} {t.label}
            </button>
          ))}
        </div>

        <div className="profile-section">
          <div className="profile-section__header">
            {TABS.find(t => t.id === tab)?.icon}
            {TABS.find(t => t.id === tab)?.label}
          </div>
          <div className="profile-section__body">
            {tab === 'stats' && <StatsPanel />}
            {tab === 'users' && <UsersPanel currentUserId={user.id} />}
            {tab === 'clubs' && <ClubsPanel />}
            {tab === 'posts' && <PostsPanel />}
          </div>
        </div>
      </div>
    </>
  )
}
