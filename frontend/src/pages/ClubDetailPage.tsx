import { useState, useEffect, useRef, type FormEvent } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { clubsApi, type Club, type ClubMember, type JoinRequest } from '../api/clubs'
import { chatsApi, type Chat, type ChatMessage } from '../api/chats'
import { booksApi, type Book } from '../api/books'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/Spinner'
import ConfirmDialog from '../components/ConfirmDialog'
import {
  Globe, Lock, MessageSquare, Users, ClipboardList,
  Settings, UserCheck, User, BookOpen, Calendar,
  Trash2, X, Check, Plus, ChevronDown, ChevronRight, ArrowLeft,
} from 'lucide-react'

type Tab = 'chats' | 'members' | 'requests'

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtShort(ymd: string) {
  return new Date(ymd + 'T00:00:00').toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

function todayStr() { return new Date().toISOString().slice(0, 10) }
function nextMonthStr() {
  const d = new Date(); d.setMonth(d.getMonth() + 1); return d.toISOString().slice(0, 10)
}

// ─── Book Month Modal ─────────────────────────────────────────────────────────

function BookMonthModal({
  clubId,
  onSaved,
  onClose,
}: {
  clubId: number
  onSaved: (book: NonNullable<Club['currentBook']>) => void
  onClose: () => void
}) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<Book[]>([])
  const [searching, setSearching] = useState(false)
  const [selected, setSelected] = useState<Book | null>(null)
  const [dateFrom, setDateFrom] = useState(todayStr())
  const [dateUntil, setDateUntil] = useState(nextMonthStr())
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function handleSearch(e: FormEvent) {
    e.preventDefault()
    if (!query.trim()) return
    setSearching(true)
    setError('')
    try {
      const res = await booksApi.search({ q: query.trim(), limit: 8 })
      setResults(res.results)
    } catch {
      setError('Error al buscar')
    } finally {
      setSearching(false)
    }
  }

  async function handleSave() {
    if (!selected) return
    if (!dateFrom || !dateUntil) { setError('Selecciona las fechas'); return }
    if (dateUntil <= dateFrom) { setError('La fecha de fin debe ser posterior'); return }
    setSaving(true)
    setError('')
    try {
      const book = await clubsApi.setCurrentBook(clubId, selected.externalId, dateFrom, dateUntil)
      onSaved(book!)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al guardar')
      setSaving(false)
    }
  }

  return (
    <div className="bm-backdrop" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="bm-box" role="dialog" aria-modal="true">
        <div className="bm-head">
          <span className="bm-title"><BookOpen size={16} /> Libro del mes</span>
          <button className="bm-close" onClick={onClose} aria-label="Cerrar"><X size={16} /></button>
        </div>

        <div className="bm-body">
          {/* Search */}
          <form onSubmit={handleSearch} style={{ display: 'flex', gap: '0.5rem' }}>
            <input
              className="form-control form-control-sm"
              placeholder="Buscar por título o autor…"
              value={query}
              onChange={e => setQuery(e.target.value)}
              style={{ flex: 1 }}
            />
            <button type="submit" className="btn btn-primary btn-sm" disabled={searching}>
              {searching ? <Spinner size={13} /> : 'Buscar'}
            </button>
          </form>

          {/* Results */}
          {results.length > 0 && (
            <div className="bm-results">
              {results.map(b => (
                <div
                  key={b.externalId}
                  className={`bm-result-row${selected?.externalId === b.externalId ? ' bm-result-row--selected' : ''}`}
                  onClick={() => setSelected(b)}
                  role="button"
                  tabIndex={0}
                  onKeyDown={e => e.key === 'Enter' && setSelected(b)}
                >
                  {(b.thumbnail || b.coverUrl)
                    ? <img src={b.thumbnail || b.coverUrl} alt={b.title} className="bm-result-thumb" />
                    : <div className="bm-result-thumb bm-result-thumb--empty" />}
                  <div style={{ overflow: 'hidden' }}>
                    <div className="bm-result-title">{b.title}</div>
                    <div className="bm-result-author">{(b.authors || []).join(', ')}</div>
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Selected preview */}
          {selected && (
            <div className="bm-selected">
              <span className="bm-selected-label">Seleccionado:</span>
              <strong style={{ fontSize: '0.88rem' }}>{selected.title}</strong>
              {selected.authors?.length > 0 && (
                <span style={{ fontSize: '0.78rem', color: 'var(--color-text-muted)' }}>
                  {selected.authors[0]}
                </span>
              )}
            </div>
          )}

          {/* Date range */}
          <div className="bm-date-range">
            <div className="form-group" style={{ margin: 0 }}>
              <label className="form-label" style={{ fontSize: '0.8rem' }}>Inicio</label>
              <input
                type="date"
                className="form-control form-control-sm"
                value={dateFrom}
                min={todayStr()}
                onChange={e => setDateFrom(e.target.value)}
              />
            </div>
            <div className="form-group" style={{ margin: 0 }}>
              <label className="form-label" style={{ fontSize: '0.8rem' }}>Fin</label>
              <input
                type="date"
                className="form-control form-control-sm"
                value={dateUntil}
                min={dateFrom || todayStr()}
                onChange={e => setDateUntil(e.target.value)}
              />
            </div>
          </div>

          {error && <p className="text-danger" style={{ fontSize: '0.82rem' }}>{error}</p>}
        </div>

        <div className="bm-footer">
          <button
            className="btn btn-primary btn-sm"
            onClick={handleSave}
            disabled={saving || !selected}
          >
            {saving ? <Spinner size={14} /> : <><Check size={14} /> Confirmar</>}
          </button>
          <button className="btn btn-secondary btn-sm" onClick={onClose}>Cancelar</button>
        </div>
      </div>
    </div>
  )
}

// ─── Chat Thread ─────────────────────────────────────────────────────────────

function ChatThread({
  chat,
  clubId,
  isAdmin,
  isMember,
  userId,
  onDelete,
}: {
  chat: Chat
  clubId: number
  isAdmin: boolean
  isMember: boolean
  userId: number | null
  onDelete: (id: number) => void
}) {
  const [expanded, setExpanded] = useState(false)
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [msgLoading, setMsgLoading] = useState(false)
  const [msgText, setMsgText] = useState('')
  const [sending, setSending] = useState(false)
  const [sendError, setSendError] = useState('')
  const messagesContainerRef = useRef<HTMLDivElement>(null)
  const messagesEndRef = useRef<HTMLDivElement>(null)

  async function loadMessages() {
    setMsgLoading(true)
    try {
      setMessages(await chatsApi.messages(clubId, chat.id))
    } finally {
      setMsgLoading(false)
    }
  }

  useEffect(() => { if (expanded) loadMessages() }, [expanded])

  useEffect(() => {
    if (expanded && messagesContainerRef.current) {
      messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight
    }
  }, [messages, expanded])

  async function handleSend(e: FormEvent) {
    e.preventDefault()
    if (!msgText.trim()) return
    setSending(true)
    setSendError('')
    try {
      const msg = await chatsApi.sendMessage(clubId, chat.id, msgText.trim())
      setMessages(prev => [...prev, msg])
      setMsgText('')
    } catch (err) {
      setSendError(err instanceof Error ? err.message : 'Error al enviar')
    } finally {
      setSending(false)
    }
  }

  async function handleDeleteMsg(msgId: number) {
    try {
      await chatsApi.deleteMessage(clubId, chat.id, msgId)
      setMessages(prev => prev.filter(m => m.id !== msgId))
    } catch { /* ignore */ }
  }

  async function handleDeleteThread() {
    if (!confirm(`¿Eliminar el hilo "${chat.title}"?`)) return
    try { await chatsApi.delete(clubId, chat.id); onDelete(chat.id) } catch { /* ignore */ }
  }

  return (
    <div className="thread-card">
      {/* Header — click to expand */}
      <div
        className="thread-card__header"
        onClick={() => setExpanded(v => !v)}
        role="button"
        tabIndex={0}
        onKeyDown={e => e.key === 'Enter' && setExpanded(v => !v)}
      >
        <div className="thread-card__icon"><MessageSquare size={16} /></div>
        <div className="thread-card__body">
          <div className="thread-card__title">{chat.title}</div>
          <div className="thread-card__meta">
            {chat.messageCount ?? 0} mensaje{(chat.messageCount ?? 0) !== 1 ? 's' : ''} · {fmtDate(chat.createdAt)}
          </div>
        </div>
        <div className="thread-card__right">
          <span className={`badge ${chat.isOpen ? 'badge-accent' : 'badge-neutral'}`} style={{ fontSize: '0.72rem' }}>
            {chat.isOpen ? 'Abierto' : 'Cerrado'}
          </span>
          {isAdmin && (
            <button
              className="btn btn-ghost btn-sm btn-icon btn-ghost--danger"
              onClick={e => { e.stopPropagation(); handleDeleteThread() }}
              title="Eliminar hilo"
            >
              <Trash2 size={14} />
            </button>
          )}
          {expanded ? <ChevronDown size={16} style={{ color: 'var(--color-text-muted)' }} /> : <ChevronRight size={16} style={{ color: 'var(--color-text-muted)' }} />}
        </div>
      </div>

      {/* Expanded messages */}
      {expanded && (
        <div className="thread-card__messages">
          {msgLoading ? (
            <div style={{ textAlign: 'center', padding: '2rem' }}><Spinner size={24} /></div>
          ) : messages.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '2rem', color: 'var(--color-text-muted)', fontSize: '0.88rem' }}>
              Sin mensajes aún. ¡Sé el primero!
            </div>
          ) : (
            <div className="wapp-messages" ref={messagesContainerRef}>
              {messages.map(msg => {
                const isMe = msg.user.id === userId
                const name = msg.user.displayName || msg.user.email
                const avatar = msg.user.avatar
                  ? (msg.user.avatar.startsWith('http') ? msg.user.avatar : `/uploads/avatars/${msg.user.avatar}`)
                  : dicebear(name)
                const canDelete = isAdmin || msg.user.id === userId
                return (
                  <div key={msg.id} className={`wapp-row${isMe ? ' wapp-row--me' : ''}`}>
                    {!isMe && <img src={avatar} alt={name} className="wapp-avatar" />}
                    <div className="wapp-content">
                      {!isMe && (
                        <Link to={`/users/${msg.user.id}`} className="wapp-name wapp-name--link">
                          {name}
                        </Link>
                      )}
                      <div className={`wapp-bubble${isMe ? ' wapp-bubble--me' : ' wapp-bubble--other'}`}>
                        {msg.content}
                      </div>
                      {isMe && canDelete && (
                        <button className="wapp-delete" onClick={() => handleDeleteMsg(msg.id)} title="Eliminar"><Trash2 size={13} /></button>
                      )}
                      {!isMe && canDelete && (
                        <button className="wapp-delete wapp-delete--left" onClick={() => handleDeleteMsg(msg.id)} title="Eliminar"><Trash2 size={13} /></button>
                      )}
                    </div>
                    {isMe && <img src={avatar} alt={name} className="wapp-avatar" />}
                  </div>
                )
              })}
              <div ref={messagesEndRef} />
            </div>
          )}

          {isMember && chat.isOpen && (
            <form onSubmit={handleSend} className="wapp-send">
              <input
                className="form-control form-control-sm"
                placeholder="Escribe un mensaje… (Enter para enviar)"
                value={msgText}
                onChange={e => setMsgText(e.target.value)}
                onKeyDown={e => {
                  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(e as unknown as FormEvent) }
                }}
                style={{ flex: 1 }}
              />
              <button type="submit" className="btn btn-primary btn-sm" disabled={sending || !msgText.trim()}>
                {sending ? <Spinner size={14} /> : 'Enviar'}
              </button>
            </form>
          )}
          {sendError && <p className="text-danger" style={{ fontSize: '0.8rem', marginTop: '0.4rem' }}>{sendError}</p>}
          {isMember && !chat.isOpen && (
            <p style={{ fontSize: '0.82rem', color: 'var(--color-text-muted)', padding: '0.5rem 0' }}>
              Este hilo está cerrado.
            </p>
          )}
        </div>
      )}
    </div>
  )
}

// ─── Sidebar: book widget ─────────────────────────────────────────────────────

function isBookExpired(until: string | undefined | null): boolean {
  if (!until) return false
  return until < todayStr()
}

function BookWidget({
  club,
  isAdmin,
  onBookChange,
}: {
  club: Club
  isAdmin: boolean
  onBookChange: (book: NonNullable<Club['currentBook']> | null) => void
}) {
  const [showModal, setShowModal] = useState(false)
  const [removing, setRemoving] = useState(false)
  const book = club.currentBook
  const expired = book ? isBookExpired(book.until) : false

  async function handleRemove() {
    setRemoving(true)
    try {
      await clubsApi.removeCurrentBook(club.id)
      onBookChange(null)
    } finally {
      setRemoving(false)
    }
  }

  return (
    <>
      <div className="sw-widget">
        <div className="sw-head">
          <span style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
            <BookOpen size={14} />
            {expired ? 'Libro anterior' : 'Libro del mes'}
          </span>
          {isAdmin && book && !expired && (
            <button className="btn btn-primary btn-sm" style={{ fontSize: '0.72rem', padding: '2px 8px' }}
              onClick={() => setShowModal(true)}>
              Cambiar
            </button>
          )}
        </div>
        <div className="sw-body">
          {book ? (
            <div className="bw-book" style={expired ? { opacity: 0.6 } : undefined}>
              {(book.thumbnail || book.coverUrl) ? (
                <img src={book.thumbnail || book.coverUrl} alt={book.title} className="bw-cover" />
              ) : (
                <div className="bw-cover bw-cover--empty"><BookOpen size={22} /></div>
              )}
              <div className="bw-info">
                <div className="bw-title">{book.title}</div>
                {book.authors?.length > 0 && (
                  <div className="bw-author">{book.authors.join(', ')}</div>
                )}
                {(book.since || book.until) && (
                  <div className="bw-dates">
                    <Calendar size={12} /> {book.since ? fmtShort(book.since) : '?'} — {book.until ? fmtShort(book.until) : '···'}
                  </div>
                )}
                {expired && (
                  <div style={{ marginTop: '0.35rem', fontSize: '0.75rem', color: 'var(--color-text-muted)', fontStyle: 'italic' }}>
                    Periodo finalizado
                  </div>
                )}
              </div>
            </div>
          ) : (
            <p style={{ fontSize: '0.82rem', color: 'var(--color-text-muted)' }}>
              Sin libro del mes asignado.
            </p>
          )}

          {isAdmin && (
            <div style={{ marginTop: '0.75rem', display: 'flex', flexDirection: 'column', gap: '0.4rem' }}>
              {(!book || expired) && (
                <button className="btn btn-primary btn-sm" style={{ width: '100%' }} onClick={() => setShowModal(true)}>
                  <Plus size={14} /> {expired ? 'Nuevo libro del mes' : 'Establecer libro del mes'}
                </button>
              )}
              {book && (
                <button className="btn btn-secondary btn-sm" style={{ width: '100%', fontSize: '0.78rem' }}
                  onClick={handleRemove} disabled={removing}>
                  {removing ? <Spinner size={13} /> : <><X size={13} /> Quitar libro del mes</>}
                </button>
              )}
            </div>
          )}
        </div>
      </div>

      {showModal && (
        <BookMonthModal
          clubId={club.id}
          onSaved={b => { onBookChange(b); setShowModal(false) }}
          onClose={() => setShowModal(false)}
        />
      )}
    </>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function ClubDetailPage() {
  const { id } = useParams<{ id: string }>()
  const clubId = Number(id)
  const navigate = useNavigate()
  const { user } = useAuth()

  const [club, setClub] = useState<Club | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [tab, setTab] = useState<Tab>('chats')
  const [chats, setChats] = useState<Chat[]>([])
  const [chatsLoading, setChatsLoading] = useState(false)
  const [newChatTitle, setNewChatTitle] = useState('')
  const [creatingChat, setCreatingChat] = useState(false)
  const [showNewChat, setShowNewChat] = useState(false)

  const [members, setMembers] = useState<ClubMember[]>([])
  const [membersLoading, setMembersLoading] = useState(false)
  const [membersLoaded, setMembersLoaded] = useState(false)

  const [requests, setRequests] = useState<JoinRequest[]>([])
  const [requestsLoading, setRequestsLoading] = useState(false)
  const [requestsLoaded, setRequestsLoaded] = useState(false)

  const [actionLoading, setActionLoading] = useState(false)
  const [confirmLeave, setConfirmLeave] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)

  async function loadClub() {
    setLoading(true); setError('')
    try { setClub(await clubsApi.get(clubId)) }
    catch { setError('No se pudo cargar el club') }
    finally { setLoading(false) }
  }

  async function loadChats() {
    setChatsLoading(true)
    try { setChats(await chatsApi.list(clubId)) }
    catch { /* ignore */ }
    finally { setChatsLoading(false) }
  }

  async function loadMembers() {
    if (membersLoaded) return
    setMembersLoading(true)
    try { setMembers(await clubsApi.members(clubId)); setMembersLoaded(true) }
    catch { /* ignore */ }
    finally { setMembersLoading(false) }
  }

  async function loadRequests() {
    if (!club || club.visibility !== 'private' || club.userRole !== 'admin') return
    if (requestsLoaded) return
    setRequestsLoading(true)
    try { setRequests((await clubsApi.requests(clubId)).filter(r => r.status === 'pending')); setRequestsLoaded(true) }
    catch { /* ignore */ }
    finally { setRequestsLoading(false) }
  }

  useEffect(() => { setMembersLoaded(false); setRequestsLoaded(false); loadClub() }, [clubId])
  useEffect(() => { if (club) loadChats() }, [club?.id])
  useEffect(() => {
    if (!club) return
    window.scrollTo({ top: 0 })
    if (tab === 'members') loadMembers()
    if (tab === 'requests') loadRequests()
  }, [tab, club])

  const isAdmin = !!user && club?.userRole === 'admin'
  const isMember = !!user && (club?.userRole === 'admin' || club?.userRole === 'member')
  const isGlobalAdmin = user?.roles?.includes('ROLE_ADMIN')

  async function handleJoin() {
    setActionLoading(true)
    try { await clubsApi.join(clubId); await loadClub() }
    catch { /* ignore */ }
    finally { setActionLoading(false) }
  }

  async function doLeave() {
    setActionLoading(true)
    try { await clubsApi.leave(clubId); navigate('/clubs') }
    catch { setActionLoading(false) }
  }

  async function doDelete() {
    try { await clubsApi.delete(clubId); navigate('/clubs') }
    catch { /* ignore */ }
  }

  async function handleCreateChat(e: FormEvent) {
    e.preventDefault()
    if (!newChatTitle.trim()) return
    setCreatingChat(true)
    try {
      const chat = await chatsApi.create(clubId, newChatTitle.trim())
      setChats(prev => [chat, ...prev])
      setNewChatTitle(''); setShowNewChat(false)
    } catch { /* ignore */ }
    finally { setCreatingChat(false) }
  }

  async function handleKick(memberId: number) {
    if (!confirm('¿Expulsar a este miembro?')) return
    try {
      await clubsApi.kickMember(clubId, memberId)
      setMembers(prev => prev.filter(m => m.id !== memberId))
      setMembersLoaded(false)
    }
    catch { /* ignore */ }
  }

  async function handleApprove(requestId: number) {
    try {
      await clubsApi.approveRequest(clubId, requestId)
      setRequests(prev => prev.filter(r => r.id !== requestId))
      setRequestsLoaded(false)
      setMembersLoaded(false)
      loadClub()
    }
    catch { /* ignore */ }
  }

  async function handleReject(requestId: number) {
    try {
      await clubsApi.rejectRequest(clubId, requestId)
      setRequests(prev => prev.filter(r => r.id !== requestId))
      setRequestsLoaded(false)
    }
    catch { /* ignore */ }
  }

  if (loading) return <div className="loading-state loading-state--page"><Spinner size={40} /></div>

  if (error || !club) {
    return (
      <div style={{ maxWidth: 600, margin: '3rem auto', padding: '0 1rem' }}>
        <div className="alert alert-danger">{error || 'Club no encontrado'}</div>
        <Link to="/clubs" className="btn btn-secondary">← Volver a clubs</Link>
      </div>
    )
  }

  const showRequestsTab = isAdmin && club.visibility === 'private'

  return (
    <>
      {/* ── Hero ── */}
      <div className="ch-hero">
        <div className="ch-hero__inner">
          <Link to="/clubs" className="ch-hero__back"><ArrowLeft size={14} /> Volver a clubs</Link>
          <div className="ch-hero__top">
            <div className="ch-hero__left">
              <h1 className="ch-hero__name">{club.name}</h1>
              {club.description && <p className="ch-hero__desc">{club.description}</p>}
              <div className="ch-hero__meta">
                <span className="ch-hero__badge">
                  {club.visibility === 'public'
                    ? <><Globe size={12} /> Público</>
                    : <><Lock size={12} /> Privado</>}
                </span>
                {(isAdmin || isMember) && (
                  <span className="ch-hero__badge ch-hero__badge--role">
                    {isAdmin ? <><Settings size={12} /> Administrador</> : <><UserCheck size={12} /> Miembro</>}
                  </span>
                )}
                {club.memberCount != null && (
                  <span className="ch-hero__stat">
                    <User size={12} /> {club.memberCount} miembro{club.memberCount !== 1 ? 's' : ''}
                  </span>
                )}
              </div>
            </div>
            <div className="ch-hero__actions">
              {user && !club.userRole && !club.hasPendingRequest && (
                <button className="btn ch-btn-primary" onClick={handleJoin} disabled={actionLoading}>
                  {actionLoading ? <Spinner size={15} /> : club.visibility === 'private' ? 'Solicitar entrada' : 'Unirse'}
                </button>
              )}
              {user && !club.userRole && club.hasPendingRequest && (
                <span className="btn ch-btn-ghost" style={{ cursor: 'default', opacity: 0.8 }}>
                  ⏳ Solicitud enviada
                </span>
              )}
              {isMember && !isAdmin && (
                <button className="btn ch-btn-ghost" onClick={() => setConfirmLeave(true)} disabled={actionLoading}>
                  Abandonar club
                </button>
              )}
              {isAdmin && (
                <>
                  <button className="btn ch-btn-ghost" onClick={() => setConfirmLeave(true)} disabled={actionLoading}>
                    Abandonar
                  </button>
                  <button className="btn ch-btn-danger" onClick={() => setConfirmDelete(true)}>
                    Eliminar club
                  </button>
                </>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* ── Body: main + sidebar ── */}
      <div className="ch-body">

        {/* Main */}
        <div className="ch-main">
          {/* Tabs */}
          <div className="ch-tabs">
            {(['chats', 'members'] as Tab[]).map(t => (
              <button
                key={t}
                className={`ch-tab${tab === t ? ' ch-tab--active' : ''}`}
                onClick={() => setTab(t)}
              >
                {t === 'chats'
                  ? <><MessageSquare size={14} /> Debates</>
                  : <><Users size={14} /> Miembros</>}
              </button>
            ))}
            {showRequestsTab && (
              <button
                className={`ch-tab${tab === 'requests' ? ' ch-tab--active' : ''}`}
                onClick={() => setTab('requests')}
              >
                <ClipboardList size={14} /> Solicitudes
                {requests.length > 0 && (
                  <span className="badge badge-danger" style={{ marginLeft: '0.3rem', fontSize: '0.68rem' }}>
                    {requests.length}
                  </span>
                )}
              </button>
            )}
          </div>

          {/* Chats */}
          {tab === 'chats' && club.visibility === 'private' && !isMember && !isGlobalAdmin && (
            <div className="empty-state">
              <div className="empty-state__icon"><Lock size={40} /></div>
              <p className="empty-state__title">Hilos solo disponibles para miembros del club</p>
            </div>
          )}
          {tab === 'chats' && (club.visibility !== 'private' || isMember || isGlobalAdmin) && (
            <div>
              {(isAdmin || isGlobalAdmin) && (
                <div style={{ marginBottom: '1rem' }}>
                  {!showNewChat ? (
                    <button className="btn btn-primary btn-sm" onClick={() => setShowNewChat(true)}>
                      + Nuevo hilo
                    </button>
                  ) : (
                    <form onSubmit={handleCreateChat} className="new-thread-form">
                      <input
                        type="text"
                        className="form-control form-control-sm"
                        placeholder="Título del hilo…"
                        value={newChatTitle}
                        onChange={e => setNewChatTitle(e.target.value)}
                        autoFocus
                        style={{ flex: 1 }}
                      />
                      <button type="submit" className="btn btn-primary btn-sm" disabled={creatingChat || !newChatTitle.trim()}>
                        {creatingChat ? <Spinner size={14} /> : 'Crear'}
                      </button>
                      <button type="button" className="btn btn-secondary btn-sm"
                        onClick={() => { setShowNewChat(false); setNewChatTitle('') }}>
                        Cancelar
                      </button>
                    </form>
                  )}
                </div>
              )}

              {chatsLoading ? (
                <div className="loading-state"><Spinner size={28} /></div>
              ) : chats.length === 0 ? (
                <div className="empty-state">
                  <div className="empty-state__icon"><MessageSquare size={40} /></div>
                  <p className="empty-state__title">No hay hilos de debate</p>
                  {(isAdmin || isGlobalAdmin) && (
                    <p className="empty-state__desc">Crea el primer hilo para empezar la conversación</p>
                  )}
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                  {chats.map(chat => (
                    <ChatThread
                      key={chat.id}
                      chat={chat}
                      clubId={clubId}
                      isAdmin={isAdmin || !!isGlobalAdmin}
                      isMember={isMember || !!isGlobalAdmin}
                      userId={user?.id ?? null}
                      onDelete={chatId => setChats(prev => prev.filter(c => c.id !== chatId))}
                    />
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Members */}
          {tab === 'members' && club.visibility === 'private' && !isMember && !isGlobalAdmin && (
            <div className="empty-state">
              <div className="empty-state__icon"><Lock size={40} /></div>
              <p className="empty-state__title">Club privado</p>
              <p className="empty-state__desc">Los miembros solo son visibles para los integrantes del club</p>
            </div>
          )}
          {tab === 'members' && (club.visibility !== 'private' || isMember || isGlobalAdmin) && (
            <div>
              {membersLoading ? (
                <div className="loading-state"><Spinner size={28} /></div>
              ) : members.length === 0 ? (
                <div className="empty-state">
                  <div className="empty-state__icon"><Users size={40} /></div>
                  <p className="empty-state__title">Sin miembros</p>
                </div>
              ) : (
                <div className="members-list">
                  {members.map(member => {
                    const name = member.user.displayName || member.user.email
                    const avatar = member.user.avatar
                      ? (member.user.avatar.startsWith('http') ? member.user.avatar : `/uploads/avatars/${member.user.avatar}`)
                      : dicebear(name)
                    const canKick = isAdmin && member.user.id !== user?.id
                    return (
                      <div key={member.id} className="member-item">
                        <Link to={`/users/${member.user.id}`} style={{ flexShrink: 0 }}>
                          <img src={avatar} alt={name} className="member-item__avatar" style={{ display: 'block' }} />
                        </Link>
                        <div className="member-item__info">
                          <Link to={`/users/${member.user.id}`} className="member-item__name" style={{ textDecoration: 'none', color: 'inherit' }}>{name}</Link>
                          <div className="member-item__email">{member.user.email}</div>
                          <div className="text-xs text-muted">Unido {fmtDate(member.joinedAt)}</div>
                        </div>
                        <div className="member-item__meta">
                          <span className={`badge ${member.role === 'admin' ? 'badge-primary' : 'badge-neutral'}`}>
                            {member.role === 'admin' ? 'Admin' : 'Miembro'}
                          </span>
                          {canKick && (
                            <button className="btn btn-danger btn-sm" onClick={() => handleKick(member.id)}>
                              Expulsar
                            </button>
                          )}
                        </div>
                      </div>
                    )
                  })}
                </div>
              )}
            </div>
          )}

          {/* Requests */}
          {tab === 'requests' && showRequestsTab && (
            <div>
              {requestsLoading ? (
                <div className="loading-state"><Spinner size={28} /></div>
              ) : requests.length === 0 ? (
                <div className="empty-state">
                  <div className="empty-state__icon"><ClipboardList size={40} /></div>
                  <p className="empty-state__title">No hay solicitudes pendientes</p>
                </div>
              ) : (
                <div className="requests-list">
                  {requests.map(req => (
                    <div key={req.id} className="request-item">
                      <div className="request-item__info">
                        <Link to={`/users/${req.user.id}`} className="request-item__name request-item__name--link">
                          {req.user.displayName || req.user.email}
                        </Link>
                        <div className="request-item__date">
                          {req.user.email} · {fmtDate(req.requestedAt)}
                        </div>
                      </div>
                      <div className="request-item__actions">
                        <button className="btn btn-accent btn-sm" onClick={() => handleApprove(req.id)}>Aprobar</button>
                        <button className="btn btn-danger btn-sm" onClick={() => handleReject(req.id)}>Rechazar</button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>

        {/* Sidebar */}
        <aside className="ch-sidebar">
          {/* Info widget */}
          <div className="sw-widget">
            <div className="sw-head"><span style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}><Settings size={14} /> Información</span></div>
            <div className="sw-body">
              <div className="sw-row">
                <span className="sw-label">Visibilidad</span>
                <span className={`badge ${club.visibility === 'public' ? 'badge-accent' : 'badge-neutral'}`} style={{ fontSize: '0.75rem' }}>
                  {club.visibility === 'public'
                    ? <><Globe size={11} /> Público</>
                    : <><Lock size={11} /> Privado</>}
                </span>
              </div>
              <div className="sw-row">
                <span className="sw-label">Miembros</span>
                <strong style={{ fontSize: '0.9rem' }}>{club.memberCount ?? 0}</strong>
              </div>
              {club.owner && (
                <div className="sw-row">
                  <span className="sw-label">Creado por</span>
                  <Link to={`/users/${club.owner.id}`} style={{ fontSize: '0.82rem', color: 'var(--color-accent)', textDecoration: 'none', fontWeight: 500 }}>
                    {club.owner.displayName || club.owner.email}
                  </Link>
                </div>
              )}
            </div>
          </div>

          {/* Book widget */}
          <BookWidget
            club={club}
            isAdmin={isAdmin}
            onBookChange={book => setClub(prev => prev ? { ...prev, currentBook: book } : prev)}
          />
        </aside>
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
      <ConfirmDialog
        open={confirmDelete}
        title="¿Eliminar el club?"
        message={<>Esta acción es permanente. El club <strong>{club.name}</strong>, sus debates y todos sus datos se eliminarán para siempre.</>}
        confirmLabel="Sí, eliminar"
        variant="danger"
        onConfirm={doDelete}
        onCancel={() => setConfirmDelete(false)}
      />
    </>
  )
}
