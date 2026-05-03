import { useEffect, useState, useRef } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import {
  BookOpen, Search, Users, BookMarked, User,
  LogOut, LogIn, UserPlus, Bell,
  Check, X, ChevronDown, UserSearch, UserCheck,
  ShieldCheck, ShieldX, ShieldAlert, Clock, ArrowLeft,
  Menu,
} from 'lucide-react'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api/client'

// ── Types ────────────────────────────────────────────────────────────────────

interface NotifActor {
  id: number
  displayName: string
  avatar: string | null
}

interface NotifItem {
  id: number
  type: string
  isRead: boolean
  createdAt: string
  refId: number | null
  actor: NotifActor
  club: { id: number; name: string } | null
  post: { id: number } | null
}

interface NotifResponse {
  unread: number
  items: NotifItem[]
}

interface HistoryResponse {
  items: NotifItem[]
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

function timeAgo(iso: string): string {
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000)
  if (diff < 60)    return 'ahora'
  if (diff < 3600)  return `${Math.floor(diff / 60)}m`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h`
  return `${Math.floor(diff / 86400)}d`
}

// ── Notification item ─────────────────────────────────────────────────────────

function NotifRow({ n, onClose }: { n: NotifItem; onClose: () => void }) {
  const [acting, setActing] = useState(false)
  const [actionResult, setActionResult] = useState<{ action: 'accepted' | 'declined'; name: string } | null>(null)

  async function doFollow(action: 'accept' | 'decline') {
    if (!n.refId) return
    setActing(true)
    try {
      if (action === 'accept') {
        await apiFetch(`/notifications/follow-requests/${n.refId}/accept`, 'POST')
      } else {
        await apiFetch(`/notifications/follow-requests/${n.refId}`, 'DELETE')
      }
      setActionResult({ action: action === 'accept' ? 'accepted' : 'declined', name: n.actor.displayName })
    } catch { /* ignore */ }
    finally { setActing(false) }
  }

  async function doClub(clubId: number, reqId: number, action: 'approve' | 'reject') {
    setActing(true)
    try {
      await apiFetch(`/clubs/${clubId}/requests/${reqId}/${action}`, 'POST')
      setActionResult({ action: action === 'approve' ? 'accepted' : 'declined', name: n.actor.displayName })
    } catch { /* ignore */ }
    finally { setActing(false) }
  }

  if (actionResult) {
    return (
      <div className="notif-bell__item notif-bell__item--done">
        <div className="notif-bell__item-icon">
          {actionResult.action === 'accepted'
            ? <Check size={14} style={{ color: 'var(--color-success)' }} />
            : <X size={14} style={{ color: 'var(--color-danger)' }} />}
        </div>
        <div className="notif-bell__item-content">
          <div className="notif-bell__item-title">
            {actionResult.action === 'accepted'
              ? <>Aceptaste a <strong>{actionResult.name}</strong></>
              : <>Rechazaste a <strong>{actionResult.name}</strong></>}
          </div>
        </div>
      </div>
    )
  }

  const { type, actor, club, refId, createdAt, isRead } = n

  const A = (
    <Link to={`/users/${actor.id}`} className="notif-actor-link" onClick={onClose}>
      {actor.displayName}
    </Link>
  )
  const C = club
    ? <Link to={`/clubs/${club.id}`} className="notif-actor-link" onClick={onClose}>{club.name}</Link>
    : null

  let icon: React.ReactNode
  let text: React.ReactNode
  let actions: React.ReactNode = null

  switch (type) {
    case 'follow':
      icon = <UserCheck size={14} style={{ color: 'var(--color-success)' }} />
      text = <>{A} ha empezado a seguirte</>
      break
    case 'follow_request':
      icon = <User size={14} style={{ color: 'var(--color-primary)' }} />
      text = <>{A} quiere seguirte</>
      if (refId) {
        actions = (
          <div className="notif-bell__item-actions">
            <button className="btn btn-primary btn-sm" disabled={acting} onClick={() => doFollow('accept')}>
              <Check size={12} /> Aceptar
            </button>
            <button className="btn btn-secondary btn-sm" disabled={acting} onClick={() => doFollow('decline')}>
              <X size={12} /> Rechazar
            </button>
          </div>
        )
      }
      break
    case 'follow_accepted':
      icon = <UserCheck size={14} style={{ color: 'var(--color-success)' }} />
      text = <>{A} aceptó tu solicitud de seguimiento</>
      break
    case 'club_request':
      icon = <ShieldCheck size={14} style={{ color: 'var(--color-primary)' }} />
      text = <>{A} quiere unirse a {C ?? <strong>{club?.name}</strong>}</>
      if (club && refId) {
        actions = (
          <div className="notif-bell__item-actions">
            <button className="btn btn-primary btn-sm" disabled={acting} onClick={() => doClub(club.id, refId, 'approve')}>
              <Check size={12} /> Aceptar
            </button>
            <button className="btn btn-secondary btn-sm" disabled={acting} onClick={() => doClub(club.id, refId, 'reject')}>
              <X size={12} /> Rechazar
            </button>
          </div>
        )
      }
      break
    case 'club_approved':
      icon = <ShieldCheck size={14} style={{ color: 'var(--color-success)' }} />
      text = <>Te aceptaron en {C ?? <strong>{club?.name}</strong>}</>
      break
    case 'club_rejected':
      icon = <ShieldX size={14} style={{ color: 'var(--color-danger)' }} />
      text = <>Tu solicitud a {C ?? <strong>{club?.name}</strong>} fue rechazada</>
      break
    case 'like':
      icon = <Bell size={14} style={{ color: 'var(--color-rose-light)' }} />
      text = <>{A} dio me gusta a tu publicación</>
      break
    case 'comment':
      icon = <Bell size={14} style={{ color: 'var(--color-accent)' }} />
      text = <>{A} comentó en tu publicación</>
      break
    default:
      icon = <Bell size={14} />
      text = <>{A}</>
  }

  return (
    <div className={`notif-bell__item${isRead ? '' : ' notif-bell__item--unread'}`}>
      <div className="notif-bell__item-icon">{icon}</div>
      <div className="notif-bell__item-content">
        <div className="notif-bell__item-title">{text}</div>
        <div className="notif-bell__item-time">{timeAgo(createdAt)}</div>
        {actions}
      </div>
    </div>
  )
}

// ── Navbar ───────────────────────────────────────────────────────────────────

const NAV_LINKS = (user: { roles?: string[] } | null) => [
  { to: '/',       end: true,  icon: null,               label: 'Inicio' },
  { to: '/books',  end: false, icon: <Search size={14} />, label: 'Libros' },
  { to: '/clubs',  end: false, icon: <Users size={14} />, label: 'Clubs' },
  { to: '/users',  end: false, icon: <UserSearch size={14} />, label: 'Lectores' },
  ...(user ? [{ to: '/shelves', end: false, icon: <BookMarked size={14} />, label: 'Estanterías' }] : []),
  ...(user?.roles?.includes('ROLE_ADMIN')
    ? [{ to: '/admin', end: false, icon: <ShieldAlert size={14} />, label: 'Admin', danger: true }]
    : []),
]

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const [scrolled, setScrolled]         = useState(false)
  const [mobileOpen, setMobileOpen]     = useState(false)
  const [notifOpen, setNotifOpen]       = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [data, setData]                 = useState<NotifResponse>({ unread: 0, items: [] })
  const [loading, setLoading]           = useState(false)
  const [showHistory, setShowHistory]   = useState(false)
  const [historyItems, setHistoryItems] = useState<NotifItem[]>([])
  const [historyLoading, setHistoryLoading] = useState(false)

  const notifRef   = useRef<HTMLDivElement>(null)
  const userMenuRef = useRef<HTMLDivElement>(null)

  // Scroll detection
  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  // Close dropdowns on outside click
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (notifRef.current && !notifRef.current.contains(e.target as Node)) {
        setNotifOpen(false)
        setShowHistory(false)
      }
      if (userMenuRef.current && !userMenuRef.current.contains(e.target as Node)) {
        setUserMenuOpen(false)
      }
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  // Close mobile menu on route change / resize
  useEffect(() => {
    if (mobileOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
    return () => { document.body.style.overflow = '' }
  }, [mobileOpen])

  // Poll notifications every 60s
  useEffect(() => {
    if (!user) return
    const load = () =>
      apiFetch<NotifResponse>('/notifications').then(setData).catch(() => {})
    load()
    const interval = setInterval(load, 60_000)
    return () => clearInterval(interval)
  }, [user])

  async function openNotifications() {
    const opening = !notifOpen
    setNotifOpen(opening)
    setShowHistory(false)
    setUserMenuOpen(false)
    if (opening) {
      setLoading(true)
      try {
        const res = await apiFetch<NotifResponse>('/notifications')
        setData(res)
        await apiFetch('/notifications/read-all', 'POST')
        setData(prev => ({ ...prev, unread: 0 }))
      } finally {
        setLoading(false)
      }
    }
  }

  async function openHistory() {
    setShowHistory(true)
    setHistoryLoading(true)
    try {
      const res = await apiFetch<HistoryResponse>('/notifications/history')
      setHistoryItems(res.items)
    } catch { /* ignore */ }
    finally { setHistoryLoading(false) }
  }

  async function handleLogout() {
    setUserMenuOpen(false)
    setMobileOpen(false)
    await logout()
    navigate('/')
  }

  const avatarSrc = user?.avatar
    ? (user.avatar.startsWith('http') ? user.avatar : `/uploads/avatars/${user.avatar}`)
    : dicebear(user?.displayName || user?.email || 'U')

  const navLinks = NAV_LINKS(user)

  return (
    <>
      <nav className={`navbar${scrolled ? ' navbar--scrolled' : ''}`}>
        <div className="navbar__inner">

          {/* Brand */}
          <Link to="/" className="navbar__brand" onClick={() => setMobileOpen(false)}>
            <div className="navbar__brand-icon">
              <BookOpen size={18} />
            </div>
            <span>Books&amp;Gossip</span>
          </Link>

          {/* Desktop nav links */}
          <div className="navbar__links">
            {navLinks.map(link => (
              <NavLink
                key={link.to}
                to={link.to}
                end={link.end}
                className={({ isActive }) =>
                  'navbar__link' +
                  (isActive ? ' navbar__link--active' : '') +
                  (('danger' in link && link.danger) ? ' navbar__link--danger' : '')
                }
              >
                {link.icon}
                {link.label}
              </NavLink>
            ))}
          </div>

          {/* Right actions */}
          <div className="navbar__actions">
            {user ? (
              <>
                {/* Notification bell */}
                <div className="notif-bell" ref={notifRef}>
                  <button
                    className="notif-bell__btn"
                    onClick={openNotifications}
                    aria-label="Notificaciones"
                  >
                    <Bell size={18} />
                    {data.unread > 0 && (
                      <span className="notif-bell__badge">
                        {data.unread > 99 ? '99+' : data.unread}
                      </span>
                    )}
                  </button>

                  {notifOpen && (
                    <div className="notif-bell__dropdown is-open">
                      <div className="notif-bell__header">
                        {showHistory ? (
                          <>
                            <button className="notif-bell__back-btn" onClick={() => setShowHistory(false)}>
                              <ArrowLeft size={14} />
                            </button>
                            <span>Historial</span>
                          </>
                        ) : (
                          <>
                            <span>Notificaciones</span>
                            <span className="notif-bell__header-sub">Últimas 72h</span>
                          </>
                        )}
                      </div>

                      <div className="notif-bell__body">
                        {showHistory ? (
                          historyLoading
                            ? <div className="notif-bell__empty">Cargando…</div>
                            : historyItems.length === 0
                              ? <div className="notif-bell__empty">Sin notificaciones</div>
                              : historyItems.map(n => <NotifRow key={n.id} n={n} onClose={() => { setNotifOpen(false); setShowHistory(false) }} />)
                        ) : (
                          loading
                            ? <div className="notif-bell__empty">Cargando…</div>
                            : data.items.length === 0
                              ? <div className="notif-bell__empty">Sin notificaciones recientes</div>
                              : data.items.map(n => <NotifRow key={n.id} n={n} onClose={() => setNotifOpen(false)} />)
                        )}
                      </div>

                      {!showHistory && (
                        <div className="notif-bell__footer">
                          <button className="notif-bell__history-btn" onClick={openHistory}>
                            <Clock size={13} />
                            Ver historial completo
                          </button>
                        </div>
                      )}
                    </div>
                  )}
                </div>

                {/* User dropdown */}
                <div className="navbar__user-menu" ref={userMenuRef}>
                  <button
                    className="navbar__user-btn"
                    onClick={() => { setUserMenuOpen(v => !v); setNotifOpen(false) }}
                    aria-label="Menú de usuario"
                  >
                    <img src={avatarSrc} alt={user.displayName || user.email} className="navbar__user-avatar" />
                    <span className="navbar__user-name">{user.displayName || user.email}</span>
                    <ChevronDown
                      size={13}
                      style={{
                        transition: 'transform 180ms ease',
                        transform: userMenuOpen ? 'rotate(180deg)' : 'rotate(0deg)',
                        flexShrink: 0,
                      }}
                    />
                  </button>

                  {userMenuOpen && (
                    <div className="navbar__user-dropdown">
                      <div className="navbar__user-dropdown-header">
                        <img src={avatarSrc} alt="" className="navbar__user-dropdown-avatar" />
                        <div>
                          <div className="navbar__user-dropdown-name">{user.displayName || 'Usuario'}</div>
                        </div>
                      </div>
                      <div className="navbar__user-dropdown-body">
                        <Link
                          to="/profile"
                          className="navbar__user-item"
                          onClick={() => setUserMenuOpen(false)}
                        >
                          <User size={14} /> Mi perfil
                        </Link>
                        <Link
                          to="/shelves"
                          className="navbar__user-item"
                          onClick={() => setUserMenuOpen(false)}
                        >
                          <BookMarked size={14} /> Estanterías
                        </Link>
                      </div>
                      <div className="navbar__user-dropdown-footer">
                        <button className="navbar__user-item navbar__user-item--danger" onClick={handleLogout}>
                          <LogOut size={14} /> Cerrar sesión
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              </>
            ) : (
              <>
                <Link to="/login" className="btn btn-ghost btn-sm">
                  <LogIn size={14} />
                  Entrar
                </Link>
                <Link to="/register" className="btn btn-primary btn-sm">
                  <UserPlus size={14} />
                  Registrarse
                </Link>
              </>
            )}

            {/* Mobile hamburger */}
            <button
              className="navbar__hamburger"
              onClick={() => setMobileOpen(v => !v)}
              aria-label={mobileOpen ? 'Cerrar menú' : 'Abrir menú'}
              aria-expanded={mobileOpen}
            >
              {mobileOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
          </div>
        </div>
      </nav>

      {/* Mobile menu overlay */}
      {mobileOpen && (
        <div className="navbar__mobile-overlay" onClick={() => setMobileOpen(false)} />
      )}
      <div className={`navbar__mobile-menu${mobileOpen ? ' is-open' : ''}`}>
        <div className="navbar__mobile-inner">
          {/* User info on mobile */}
          {user && (
            <div className="navbar__mobile-user">
              <img src={avatarSrc} alt="" className="navbar__mobile-user-avatar" />
              <div>
                <div className="navbar__mobile-user-name">{user.displayName || 'Usuario'}</div>
              </div>
            </div>
          )}

          {/* Nav links */}
          <nav className="navbar__mobile-nav">
            {navLinks.map(link => (
              <NavLink
                key={link.to}
                to={link.to}
                end={link.end}
                className={({ isActive }) =>
                  'navbar__mobile-link' +
                  (isActive ? ' navbar__mobile-link--active' : '') +
                  (('danger' in link && link.danger) ? ' navbar__mobile-link--danger' : '')
                }
                onClick={() => setMobileOpen(false)}
              >
                {link.icon}
                {link.label}
              </NavLink>
            ))}
          </nav>

          {/* Auth buttons on mobile */}
          {!user && (
            <div className="navbar__mobile-auth">
              <Link to="/login" className="btn btn-secondary w-full" onClick={() => setMobileOpen(false)}>
                <LogIn size={16} /> Iniciar sesión
              </Link>
              <Link to="/register" className="btn btn-primary w-full" onClick={() => setMobileOpen(false)}>
                <UserPlus size={16} /> Crear cuenta
              </Link>
            </div>
          )}

          {user && (
            <div className="navbar__mobile-auth">
              <button className="btn btn-secondary w-full" onClick={handleLogout}>
                <LogOut size={16} /> Cerrar sesión
              </button>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
