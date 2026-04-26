import { useState, useEffect, useRef, type FormEvent } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { booksApi, type Book } from '../api/books'
import { shelvesApi, type Shelf } from '../api/shelves'
import { reviewsApi, type Review, type ReviewStats, type ReviewsResponse } from '../api/reviews'
import { readingProgressApi } from '../api/readingProgress'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/Spinner'
import {
  ArrowLeft, BookOpen, Star, Building, Calendar, Globe,
  Layers, Hash, BookMarked, Plus, CheckCircle, X, Pencil, Trash2, BookOpenCheck,
} from 'lucide-react'

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

function fmtCount(n: number): string {
  if (n >= 1000) return (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'k'
  return String(n)
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
}

// ── Estrellas ────────────────────────────────────────────────────────────────

function Stars({ rating, size = 16, interactive = false, onSelect }: {
  rating: number
  size?: number
  interactive?: boolean
  onSelect?: (r: number) => void
}) {
  const [hover, setHover] = useState(0)
  const display = interactive ? (hover || rating) : rating

  return (
    <span style={{ display: 'inline-flex', gap: 2 }}>
      {[1, 2, 3, 4, 5].map(i => (
        <Star
          key={i}
          size={size}
          style={{
            color: i <= display ? '#f59e0b' : 'var(--color-border)',
            fill:  i <= display ? '#f59e0b' : 'transparent',
            cursor: interactive ? 'pointer' : 'default',
            transition: 'color 120ms, fill 120ms',
          }}
          onMouseEnter={() => interactive && setHover(i)}
          onMouseLeave={() => interactive && setHover(0)}
          onClick={() => interactive && onSelect?.(i)}
        />
      ))}
    </span>
  )
}

// ── Valoración global estilo Amazon ──────────────────────────────────────────

function GlobalRating({ stats }: { stats: ReviewStats }) {
  if (stats.count === 0) {
    return (
      <p className="text-muted text-sm" style={{ padding: '1.5rem 0', textAlign: 'center' }}>
        Sin valoraciones todavía. ¡Sé el primero!
      </p>
    )
  }

  const avg = stats.average ?? 0

  return (
    <div className="book-global-rating">
      <div className="book-global-rating__score-block">
        <div className="book-global-rating__score">{avg.toFixed(1)}</div>
        <Stars rating={avg} size={18} />
        <div className="book-global-rating__count">
          {fmtCount(stats.count)} {stats.count === 1 ? 'valoración' : 'valoraciones'}
        </div>
      </div>
    </div>
  )
}

// ── Formulario de reseña ─────────────────────────────────────────────────────

function ReviewForm({
  externalId,
  existing,
  onSaved,
  onDeleted,
}: {
  externalId: string
  existing: { id: number; rating: number; content: string | null } | null
  onSaved: (review: Review, stats: ReviewStats) => void
  onDeleted: (stats: ReviewStats) => void
}) {
  const [rating, setRating] = useState(existing?.rating ?? 0)
  const [content, setContent] = useState(existing?.content ?? '')
  const [loading, setLoading] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [error, setError] = useState('')
  const [editing, setEditing] = useState(!existing)

  useEffect(() => {
    setRating(existing?.rating ?? 0)
    setContent(existing?.content ?? '')
    setEditing(!existing)
  }, [existing])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (rating === 0) { setError('Selecciona una puntuación'); return }
    setLoading(true)
    setError('')
    try {
      const res = await reviewsApi.upsert(externalId, rating, content)
      onSaved(res.review, res.stats)
      setEditing(false)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al guardar')
    } finally { setLoading(false) }
  }

  async function handleDelete() {
    if (!confirm('¿Eliminar tu reseña?')) return
    setDeleting(true)
    try {
      const res = await reviewsApi.delete(externalId)
      onDeleted(res.stats)
      setRating(0)
      setContent('')
      setEditing(true)
    } catch { /* ignore */ }
    finally { setDeleting(false) }
  }

  if (existing && !editing) {
    return (
      <div className="review-mine">
        <div className="review-mine__head">
          <div className="review-mine__head-left">
            <Stars rating={existing.rating} size={16} />
            <span className="review-mine__label">Tu reseña</span>
          </div>
          <div className="review-mine__actions">
            <button className="btn btn-ghost btn-sm" onClick={() => setEditing(true)} title="Editar">
              <Pencil size={13} />
            </button>
            <button className="btn btn-danger btn-sm" onClick={handleDelete} disabled={deleting} title="Eliminar">
              {deleting ? <Spinner size={13} /> : <Trash2 size={13} />}
            </button>
          </div>
        </div>
        {existing.content && (
          <p className="review-mine__text">{existing.content}</p>
        )}
      </div>
    )
  }

  return (
    <form onSubmit={handleSubmit} className="review-form">
      <div className="review-form__rating-row">
        <p className="review-form__label">
          {existing ? 'Edita tu valoración:' : 'Deja tu valoración:'}
        </p>
        <Stars rating={rating} size={22} interactive onSelect={setRating} />
      </div>
      <textarea
        className="form-control review-form__textarea"
        placeholder="Escribe tu reseña (opcional)…"
        rows={3}
        value={content}
        onChange={e => setContent(e.target.value)}
      />
      {error && <p className="review-form__error">{error}</p>}
      <div className="review-form__actions">
        <button type="submit" className="btn btn-primary btn-sm" disabled={loading || rating === 0}>
          {loading ? <Spinner size={14} /> : existing ? 'Actualizar reseña' : 'Publicar reseña'}
        </button>
        {existing && (
          <button type="button" className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>
            Cancelar
          </button>
        )}
      </div>
    </form>
  )
}

// ── Tarjeta de reseña ────────────────────────────────────────────────────────

function ReviewCard({ review }: { review: Review }) {
  const avatarSrc = review.user.avatar
    ? (review.user.avatar.startsWith('http') ? review.user.avatar : `/uploads/${review.user.avatar}`)
    : dicebear(review.user.displayName)

  return (
    <div className="review-card">
      <div className="review-card__header">
        <Link to={`/users/${review.user.id}`} className="review-card__user-link">
          <img src={avatarSrc} alt={review.user.displayName} className="review-card__avatar" />
          <span className="review-card__name">{review.user.displayName}</span>
        </Link>
        <div className="review-card__right">
          <Stars rating={review.rating} size={14} />
          <span className="review-card__date">{fmtDate(review.createdAt)}</span>
        </div>
      </div>
      {review.content && (
        <p className="review-card__content">{review.content}</p>
      )}
    </div>
  )
}

// ── Shelf Drawer ─────────────────────────────────────────────────────────────

function ShelfDrawer({ book, shelves, onShelvesChange, onClose }: {
  book: Book; shelves: Shelf[]; onShelvesChange: (s: Shelf[]) => void; onClose: () => void
}) {
  const [adding, setAdding] = useState<Record<number, boolean>>({})
  const [done, setDone] = useState<Record<number, boolean>>({})
  const [error, setError] = useState('')
  const [newName, setNewName] = useState('')
  const [creating, setCreating] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  async function handleAdd(shelf: Shelf) {
    setAdding(p => ({ ...p, [shelf.id]: true }))
    setError('')
    try {
      await shelvesApi.addBook(shelf.id, book.externalId, 'want_to_read')
      setDone(p => ({ ...p, [shelf.id]: true }))
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al añadir')
    } finally {
      setAdding(p => ({ ...p, [shelf.id]: false }))
    }
  }

  async function handleCreate(e: FormEvent) {
    e.preventDefault()
    if (!newName.trim()) return
    setCreating(true)
    setError('')
    try {
      const shelf = await shelvesApi.create(newName.trim())
      onShelvesChange([...shelves, shelf])
      setNewName('')
      inputRef.current?.focus()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al crear')
    } finally { setCreating(false) }
  }

  return (
    <div className="shelf-drawer-backdrop" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="shelf-drawer">
        <div className="shelf-drawer__header">
          <div className="shelf-drawer__book-info">
            {(book.thumbnail || book.coverUrl)
              ? <img src={book.thumbnail || book.coverUrl} alt={book.title} className="shelf-drawer__thumb" />
              : <div className="shelf-drawer__thumb shelf-drawer__thumb--empty"><BookOpen size={18} /></div>
            }
            <div>
              <div className="shelf-drawer__book-title">{book.title}</div>
              {book.authors?.length > 0 && <div className="shelf-drawer__book-authors">{book.authors[0]}</div>}
            </div>
          </div>
          <button className="shelf-drawer__close" onClick={onClose} aria-label="Cerrar"><X size={18} /></button>
        </div>
        <div className="shelf-drawer__body">
          <p className="shelf-drawer__label">Añadir a estantería</p>
          {error && <div className="alert alert-danger" style={{ padding: '0.4rem 0.7rem', fontSize: '0.82rem', marginBottom: '0.75rem' }}>{error}</div>}
          {shelves.length === 0
            ? <p className="shelf-drawer__empty">Aún no tienes estanterías. Crea una abajo.</p>
            : (
              <div className="shelf-drawer__shelves">
                {shelves.map(shelf => (
                  <button
                    key={shelf.id}
                    className={`shelf-drawer__shelf-btn${done[shelf.id] ? ' shelf-drawer__shelf-btn--done' : ''}`}
                    onClick={() => !done[shelf.id] && handleAdd(shelf)}
                    disabled={adding[shelf.id]}
                  >
                    <BookMarked size={15} />
                    <span>{shelf.name}</span>
                    {adding[shelf.id] && <Spinner size={14} />}
                    {done[shelf.id] && <CheckCircle size={14} className="shelf-drawer__done-icon" />}
                  </button>
                ))}
              </div>
            )
          }
          <div className="shelf-drawer__divider" />
          <form onSubmit={handleCreate} className="shelf-drawer__new">
            <input
              ref={inputRef}
              type="text"
              className="form-control form-control-sm"
              placeholder="Nueva estantería…"
              value={newName}
              onChange={e => setNewName(e.target.value)}
              style={{ flex: 1 }}
            />
            <button type="submit" className="btn btn-primary btn-sm" disabled={creating || !newName.trim()}>
              {creating ? <Spinner size={13} /> : <Plus size={14} />}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}

// ── Main Page ────────────────────────────────────────────────────────────────

export default function BookDetailPage() {
  const { externalId } = useParams<{ externalId: string }>()
  const navigate = useNavigate()
  const { user } = useAuth()

  const [book, setBook] = useState<Book | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [shelves, setShelves] = useState<Shelf[]>([])
  const [showDrawer, setShowDrawer] = useState(false)

  const [isReading, setIsReading] = useState(false)
  const [addingReading, setAddingReading] = useState(false)

  const [reviewsData, setReviewsData] = useState<ReviewsResponse | null>(null)
  const [reviewsLoading, setReviewsLoading] = useState(true)

  useEffect(() => {
    if (!externalId) return
    setLoading(true)
    booksApi.get(externalId)
      .then(setBook)
      .catch(err => setError(err instanceof Error ? err.message : 'Libro no encontrado'))
      .finally(() => setLoading(false))
  }, [externalId])

  useEffect(() => {
    if (!externalId) return
    setReviewsLoading(true)
    reviewsApi.list(externalId)
      .then(setReviewsData)
      .catch(() => {})
      .finally(() => setReviewsLoading(false))
  }, [externalId])

  useEffect(() => {
    if (!user || !externalId) return
    shelvesApi.list().then(setShelves).catch(() => {})
    readingProgressApi.list()
      .then(items => setIsReading(items.some(i => i.book.externalId === externalId)))
      .catch(() => {})
  }, [user, externalId])

  async function handleAddReading() {
    if (!externalId || isReading) return
    setAddingReading(true)
    try {
      await readingProgressApi.add(externalId, 'percent')
      setIsReading(true)
    } catch { /* ignore */ }
    finally { setAddingReading(false) }
  }

  if (loading) {
    return <div className="loading-state loading-state--page"><Spinner size={40} /></div>
  }

  if (error || !book) {
    return (
      <div className="page-content" style={{ maxWidth: 480, textAlign: 'center', paddingTop: '4rem' }}>
        <div className="empty-state__icon"><BookOpen size={48} /></div>
        <p className="empty-state__title">{error || 'Libro no encontrado'}</p>
        <button className="btn btn-secondary" onClick={() => navigate(-1)} style={{ marginTop: '1rem' }}><ArrowLeft size={14} /> Volver</button>
      </div>
    )
  }

  const cover = book.thumbnail || book.coverUrl
  const langNames: Record<string, string> = { es: 'Español', en: 'Inglés', fr: 'Francés', de: 'Alemán', it: 'Italiano', pt: 'Portugués' }

  return (
    <>
      {/* ── Hero ──────────────────────────────────────────────── */}
      <div className="bd-hero">
        {cover && <div className="bd-hero__bg" style={{ backgroundImage: `url(${cover})` }} />}
        <div className="bd-hero__inner">
          <button className="bd-hero__back btn btn-ghost btn-sm" onClick={() => navigate(-1)}>
            <ArrowLeft size={15} /> Volver
          </button>

          <div className="bd-hero__layout">
            <div className="bd-hero__cover-wrap">
              {cover
                ? <img src={cover} alt={book.title} className="bd-hero__cover" />
                : <div className="bd-hero__cover-placeholder"><BookOpen size={40} /></div>
              }
            </div>

            <div className="bd-hero__info">
              <h1 className="bd-hero__title">{book.title}</h1>
              {book.subtitle && <p className="bd-hero__subtitle">{book.subtitle}</p>}
              {book.authors?.length > 0 && (
                <p className="bd-hero__authors">{book.authors.join(', ')}</p>
              )}

              {book.averageRating != null && (
                <div className="bd-hero__rating">
                  <Star size={14} style={{ color: '#fbbf24', fill: '#fbbf24', flexShrink: 0 }} />
                  <span style={{ fontWeight: 700 }}>{book.averageRating.toFixed(1)}</span>
                  <span>Google Books{book.ratingsCount != null && ` · ${fmtCount(book.ratingsCount)} valoraciones`}</span>
                </div>
              )}

              <div className="bd-hero__meta">
                {book.publisher && <span className="bd-hero__badge"><Building size={11} /> {book.publisher}</span>}
                {book.publishedDate && <span className="bd-hero__badge"><Calendar size={11} /> {book.publishedDate}</span>}
                {book.pageCount && <span className="bd-hero__badge"><Layers size={11} /> {book.pageCount} páginas</span>}
                {book.language && (
                  <span className="bd-hero__badge"><Globe size={11} /> {langNames[book.language] ?? book.language.toUpperCase()}</span>
                )}
                {book.categories?.map(cat => (
                  <span key={cat} className="bd-hero__badge bd-hero__badge--category">{cat}</span>
                ))}
              </div>

              {(book.isbn13 || book.isbn10) && (
                <p className="bd-hero__isbn">
                  <Hash size={12} />
                  {book.isbn13 && <span>ISBN-13: {book.isbn13}</span>}
                  {book.isbn13 && book.isbn10 && <span style={{ margin: '0 0.3rem' }}>·</span>}
                  {book.isbn10 && <span>ISBN-10: {book.isbn10}</span>}
                </p>
              )}

              <div className="bd-hero__actions">
                {user && (
                  <button className="btn btn-primary" onClick={() => setShowDrawer(true)}>
                    <BookMarked size={15} /> + Estantería
                  </button>
                )}
                {user && (
                  <button
                    className={`btn ${isReading ? 'btn-secondary' : 'btn-accent'}`}
                    onClick={handleAddReading}
                    disabled={addingReading || isReading}
                  >
                    {addingReading
                      ? <Spinner size={14} />
                      : isReading
                        ? <><BookOpenCheck size={15} /> Leyendo</>
                        : <><BookOpenCheck size={15} /> Estoy leyendo</>
                    }
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Content ───────────────────────────────────────────── */}
      <div className="page-content bd-content">

        {/* Sinopsis */}
        {book.description && (
          <div className="book-section">
            <div className="book-section__header">
              <BookOpen size={16} /> Sinopsis
            </div>
            <div
              className="book-section__body book-description"
              dangerouslySetInnerHTML={{ __html: book.description }}
            />
          </div>
        )}

        {/* Reseñas */}
        <div className="book-section">
          <div className="book-section__header">
            <Star size={16} /> Valoraciones de la comunidad
          </div>
          <div className="book-section__body">
            {reviewsLoading ? (
              <div className="loading-state" style={{ padding: '2rem 0' }}><Spinner size={28} /></div>
            ) : (
              <>
                <GlobalRating stats={reviewsData?.stats ?? { average: null, count: 0 }} />

                {user && (
                  <div style={{ marginBottom: '1.5rem' }}>
                    <ReviewForm
                      externalId={externalId!}
                      existing={reviewsData?.myRating ?? null}
                      onSaved={(review, stats) => {
                        setReviewsData(prev => {
                          if (!prev) return prev
                          const without = prev.reviews.filter(r => r.id !== review.id)
                          return { ...prev, stats, myRating: { id: review.id, rating: review.rating, content: review.content }, reviews: [review, ...without] }
                        })
                      }}
                      onDeleted={stats => {
                        setReviewsData(prev => prev ? { ...prev, stats, myRating: null, reviews: prev.reviews.filter(r => r.user.id !== user.id) } : prev)
                      }}
                    />
                  </div>
                )}

                {!user && (
                  <p className="text-sm text-muted" style={{ marginBottom: '1rem' }}>
                    <Link to="/login" className="text-accent">Inicia sesión</Link> para dejar una reseña.
                  </p>
                )}

                {(reviewsData?.reviews?.length ?? 0) > 0 && (
                  <>
                    <hr className="review-divider" />
                    <div className="review-list">
                      {reviewsData!.reviews.map(review => (
                        <ReviewCard key={review.id} review={review} />
                      ))}
                    </div>
                  </>
                )}
              </>
            )}
          </div>
        </div>
      </div>

      {showDrawer && (
        <ShelfDrawer
          book={book}
          shelves={shelves}
          onShelvesChange={setShelves}
          onClose={() => setShowDrawer(false)}
        />
      )}
    </>
  )
}
