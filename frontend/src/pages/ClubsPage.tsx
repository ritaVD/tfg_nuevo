import { useState, useEffect, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { clubsApi, type Club } from '../api/clubs'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/Spinner'
import ConfirmDialog from '../components/ConfirmDialog'
import {
  Users, Plus, X, Globe, Lock, Shield,
  UserCheck, UserMinus, BookOpen, ArrowRight, Search, Sparkles, Clock,
} from 'lucide-react'

export default function ClubsPage() {
  const { user } = useAuth()

  const [clubs, setClubs] = useState<Club[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [showCreate, setShowCreate] = useState(false)

  const [createName, setCreateName] = useState('')
  const [createDesc, setCreateDesc] = useState('')
  const [createVisibility, setCreateVisibility] = useState<'public' | 'private'>('public')
  const [createLoading, setCreateLoading] = useState(false)
  const [createError, setCreateError] = useState('')

  async function loadClubs() {
    setLoading(true)
    setError('')
    try {
      const data = await clubsApi.list()
      setClubs(data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al cargar clubs')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { loadClubs() }, [])

  async function handleCreate(e: FormEvent) {
    e.preventDefault()
    if (!createName.trim()) return
    setCreateLoading(true)
    setCreateError('')
    try {
      const club = await clubsApi.create({
        name: createName.trim(),
        description: createDesc.trim() || undefined,
        visibility: createVisibility,
      })
      setClubs(prev => [club, ...prev])
      setShowCreate(false)
      setCreateName('')
      setCreateDesc('')
      setCreateVisibility('public')
    } catch (err) {
      setCreateError(err instanceof Error ? err.message : 'Error al crear club')
    } finally {
      setCreateLoading(false)
    }
  }

  const filtered = clubs.filter(c =>
    c.name.toLowerCase().includes(search.toLowerCase())
  )

  return (
    <>
    <div className="page-banner page-banner--clubs">
      <div className="page-banner__inner">
        <div className="page-banner__text">
          <span className="page-banner__eyebrow">
            <Sparkles size={12} /> Comunidades lectoras
          </span>
          <h1 className="page-banner__title">Clubs de lectura</h1>
          <p className="page-banner__desc">
            Únete a comunidades de lectores, debate cada capítulo y descubre el libro del mes.
          </p>
          {user && (
            <div className="page-banner__actions">
              <button className="btn btn-primary" onClick={() => setShowCreate(v => !v)}>
                {showCreate ? <><X size={15} /> Cancelar</> : <><Plus size={15} /> Nuevo club</>}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
    <div className="page-content">

      {showCreate && user && (
        <div className="create-club-panel">
          <h2 className="create-club-panel__title">Crear nuevo club</h2>
          {createError && <div className="alert alert-danger">{createError}</div>}
          <form onSubmit={handleCreate}>
            <div className="form-group">
              <label className="form-label">Nombre del club *</label>
              <input
                type="text"
                className="form-control"
                value={createName}
                onChange={e => setCreateName(e.target.value)}
                placeholder="Ej: Club de ciencia ficción"
                required
                autoFocus
              />
            </div>
            <div className="form-group">
              <label className="form-label">Descripción</label>
              <textarea
                className="form-control"
                value={createDesc}
                onChange={e => setCreateDesc(e.target.value)}
                placeholder="Describe el club…"
                rows={3}
              />
            </div>
            <div className="form-group">
              <label className="form-label">Visibilidad</label>
              <select
                className="form-control"
                value={createVisibility}
                onChange={e => setCreateVisibility(e.target.value as 'public' | 'private')}
              >
                <option value="public">Público — cualquiera puede unirse</option>
                <option value="private">Privado — requiere aprobación</option>
              </select>
            </div>
            <div className="create-club-panel__actions">
              <button type="submit" className="btn btn-primary" disabled={createLoading}>
                {createLoading ? <Spinner size={16} /> : <><Plus size={16} /> Crear club</>}
              </button>
              <button type="button" className="btn btn-secondary" onClick={() => setShowCreate(false)}>
                Cancelar
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Toolbar */}
      <div className="clubs-toolbar">
        <div className="clubs-search-wrap">
          <span className="clubs-search-icon"><Search size={16} /></span>
          <input
            type="text"
            className="form-control clubs-search-input"
            placeholder="Filtrar clubs por nombre…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {loading ? (
        <div className="loading-state"><Spinner size={32} /></div>
      ) : filtered.length === 0 ? (
        <div className="empty-state">
          <div className="empty-state__icon"><Users size={40} /></div>
          <p className="empty-state__title">
            {search ? 'No hay clubs con ese nombre' : 'Aún no hay clubs'}
          </p>
          <p className="empty-state__desc">
            {user ? '¡Sé el primero en crear uno!' : 'Inicia sesión para crear un club.'}
          </p>
        </div>
      ) : (
        <div className="clubs-grid">
          {filtered.map(club => (
            <ClubCard key={club.id} club={club} onRefresh={loadClubs} />
          ))}
        </div>
      )}
    </div>
    </>
  )
}

function ClubCard({ club, onRefresh }: { club: Club; onRefresh: () => void }) {
  const { user } = useAuth()
  const [actionLoading, setActionLoading] = useState(false)
  const [actionError, setActionError] = useState('')
  const [confirmLeave, setConfirmLeave] = useState(false)

  async function handleJoin() {
    setActionLoading(true)
    setActionError('')
    try {
      await clubsApi.join(club.id)
      onRefresh()
    } catch (err) {
      setActionError(err instanceof Error ? err.message : 'Error')
    } finally {
      setActionLoading(false)
    }
  }

  async function doLeave() {
    setActionLoading(true)
    setActionError('')
    try {
      await clubsApi.leave(club.id)
      setConfirmLeave(false)
      onRefresh()
    } catch (err) {
      setActionError(err instanceof Error ? err.message : 'Error')
    } finally {
      setActionLoading(false)
    }
  }

  const cover = club.currentBook?.thumbnail || club.currentBook?.coverUrl

  return (
    <div className="club-card">
      <div className="club-card__header">
        <h3 className="club-card__title">{club.name}</h3>
        <div className="club-card__badges">
          <span className={`badge ${club.visibility === 'public' ? 'badge-accent' : 'badge-neutral'}`}>
            {club.visibility === 'public'
              ? <><Globe size={11} /> Público</>
              : <><Lock size={11} /> Privado</>}
          </span>
          {club.userRole === 'admin' && (
            <span className="badge badge-primary"><Shield size={11} /> Admin</span>
          )}
          {club.userRole === 'member' && (
            <span className="badge badge-success"><UserCheck size={11} /> Miembro</span>
          )}
        </div>
      </div>

      <div className="club-card__body">
        {club.description && (
          <p className="club-card__desc">{club.description}</p>
        )}
        <div className="club-card__meta">
          {club.memberCount != null && (
            <span><Users size={13} /> {club.memberCount} miembro{club.memberCount !== 1 ? 's' : ''}</span>
          )}
        </div>
        {club.currentBook && (
          <div className="club-card__book">
            {cover && <img src={cover} alt={club.currentBook.title} className="club-card__book-thumb" />}
            <span>
              <BookOpen size={13} /> <strong>Libro del mes:</strong> {club.currentBook.title}
            </span>
          </div>
        )}
        {actionError && (
          <div className="alert alert-danger" style={{ marginTop: '0.5rem', padding: '0.4rem 0.6rem', fontSize: '0.8rem' }}>
            {actionError}
          </div>
        )}
      </div>

      <div className="club-card__footer">
        <Link to={`/clubs/${club.id}`} className="btn btn-secondary btn-sm">
          Ver club <ArrowRight size={14} />
        </Link>
        {user && !club.userRole && !club.hasPendingRequest && (
          <button
            className="btn btn-primary btn-sm"
            onClick={handleJoin}
            disabled={actionLoading}
          >
            {actionLoading ? <Spinner size={14} /> : club.visibility === 'private' ? 'Solicitar unirse' : 'Unirse'}
          </button>
        )}
        {user && !club.userRole && club.hasPendingRequest && (
          <span className="btn btn-ghost btn-sm" style={{ cursor: 'default', opacity: 0.8 }}>
            <Clock size={14} /> Solicitud enviada
          </span>
        )}
        {user && club.userRole === 'member' && (
          <button
            className="btn btn-ghost btn-sm"
            onClick={() => setConfirmLeave(true)}
            disabled={actionLoading}
          >
            <UserMinus size={14} /> Abandonar
          </button>
        )}
      </div>

      <ConfirmDialog
        open={confirmLeave}
        title="¿Abandonar el club?"
        message={<>Dejarás de ser miembro de <strong>{club.name}</strong>. Podrás volver a unirte si el club es público.</>}
        confirmLabel="Sí, abandonar"
        variant="danger"
        loading={actionLoading}
        onConfirm={doLeave}
        onCancel={() => setConfirmLeave(false)}
      />
    </div>
  )
}
