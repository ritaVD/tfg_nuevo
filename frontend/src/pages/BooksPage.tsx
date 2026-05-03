import { useState, useEffect, useRef, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { booksApi, type Book, type SearchParams } from '../api/books'
import { shelvesApi, type Shelf } from '../api/shelves'
import { readingProgressApi } from '../api/readingProgress'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/Spinner'
import { Search, BookMarked, Star, X, CheckCircle, BookOpen, Plus, BookOpenCheck, Sparkles } from 'lucide-react'

function renderStars(rating: number): string {
  const full = Math.floor(rating)
  const half = rating - full >= 0.5 ? 1 : 0
  const empty = 5 - full - half
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty)
}

function fmtCount(n: number): string {
  if (n >= 1000) return (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'k'
  return String(n)
}

const SUGGESTIONS = [
  'Harry Potter',
  'El señor de los anillos',
  'Cien años de soledad',
  '1984',
  'Don Quijote',
  'El principito',
]

// ── Shelf Drawer ────────────────────────────────────────────────────────────

function ShelfDrawer({
  book,
  shelves,
  onShelvesChange,
  onClose,
}: {
  book: Book
  shelves: Shelf[]
  onShelvesChange: (shelves: Shelf[]) => void
  onClose: () => void
}) {
  const [adding, setAdding] = useState<Record<number, boolean>>({})
  const [done, setDone] = useState<Record<number, boolean>>({})
  const [error, setError] = useState('')
  const [newName, setNewName] = useState('')
  const [creating, setCreating] = useState(false)
  const [readingDone, setReadingDone] = useState(false)
  const [readingLoading, setReadingLoading] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  async function handleAddReading() {
    setReadingLoading(true)
    setError('')
    try {
      await readingProgressApi.add(book.externalId, 'percent')
      setReadingDone(true)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error')
    } finally {
      setReadingLoading(false)
    }
  }

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
    } finally {
      setCreating(false)
    }
  }

  const cover = book.thumbnail || book.coverUrl

  return (
    <div className="shelf-drawer-backdrop" onClick={e => e.target === e.currentTarget && onClose()}>
      <div className="shelf-drawer">
        <div className="shelf-drawer__header">
          <div className="shelf-drawer__book-info">
            {cover
              ? <img src={cover} alt={book.title} className="shelf-drawer__thumb" />
              : <div className="shelf-drawer__thumb shelf-drawer__thumb--empty"><BookOpen size={18} /></div>
            }
            <div>
              <div className="shelf-drawer__book-title">{book.title}</div>
              {book.authors?.length > 0 && (
                <div className="shelf-drawer__book-authors">{book.authors[0]}</div>
              )}
            </div>
          </div>
          <button className="shelf-drawer__close" onClick={onClose} aria-label="Cerrar">
            <X size={18} />
          </button>
        </div>

        <div className="shelf-drawer__body">
          {/* Reading tracker shortcut */}
          <button
            className={`shelf-drawer__shelf-btn${readingDone ? ' shelf-drawer__shelf-btn--done' : ''}`}
            style={{ marginBottom: '0.75rem', borderColor: readingDone ? undefined : 'rgba(124,58,237,0.3)', background: readingDone ? undefined : 'rgba(124,58,237,0.05)' }}
            onClick={handleAddReading}
            disabled={readingLoading || readingDone}
          >
            <BookOpenCheck size={15} />
            <span>Estoy leyendo este libro</span>
            {readingLoading && <Spinner size={14} />}
            {readingDone && <CheckCircle size={14} className="shelf-drawer__done-icon" />}
          </button>

          <p className="shelf-drawer__label">Añadir a estantería</p>
          {error && <div className="alert alert-danger" style={{ padding: '0.4rem 0.7rem', fontSize: '0.82rem', marginBottom: '0.75rem' }}>{error}</div>}

          {shelves.length === 0 ? (
            <p className="shelf-drawer__empty">
              Aún no tienes estanterías. Crea una abajo.
            </p>
          ) : (
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
          )}

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
            <button
              type="submit"
              className="btn btn-primary btn-sm"
              disabled={creating || !newName.trim()}
            >
              {creating ? <Spinner size={13} /> : <Plus size={14} />}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}

// ── Main Page ────────────────────────────────────────────────────────────────

export default function BooksPage() {
  const { user } = useAuth()

  const [query, setQuery] = useState('')
  const [searchMode, setSearchMode] = useState<'q' | 'title' | 'author'>('q')
  const [books, setBooks] = useState<Book[]>([])
  const [page, setPage] = useState(1)
  const [total, setTotal] = useState<number | undefined>()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [fallback, setFallback] = useState(false)
  const [searched, setSearched] = useState(false)

  const [shelves, setShelves] = useState<Shelf[]>([])
  const [drawerBook, setDrawerBook] = useState<Book | null>(null)

  useEffect(() => {
    if (user) {
      shelvesApi.list().then(setShelves).catch(() => {})
    }
  }, [user])

  async function doSearch(p = 1, qOverride?: string, modeOverride?: 'q' | 'title' | 'author') {
    const q = qOverride ?? query
    const mode = modeOverride ?? searchMode
    if (!q.trim()) return
    setLoading(true)
    setError('')
    setFallback(false)
    setSearched(true)
    try {
      const params: SearchParams = { page: p, limit: 12 }
      if (mode === 'q') params.q = q
      else if (mode === 'title') params.title = q
      else params.author = q
      const res = await booksApi.search(params)
      setBooks(res.results)
      setPage(res.page)
      setTotal(res.total)
      setFallback(res.fallback ?? false)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al buscar libros')
    } finally {
      setLoading(false)
    }
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault()
    doSearch(1)
  }

  function handleSuggestion(s: string) {
    setQuery(s)
    setSearchMode('q')
    doSearch(1, s, 'q')
  }

  const totalPages = total != null ? Math.ceil(total / 12) : undefined

  return (
    <>
    <div className="page-banner page-banner--books">
      <div className="page-banner__inner">
        <div className="page-banner__text">
          <span className="page-banner__eyebrow">
            <Sparkles size={12} /> Más de 10 millones de títulos
          </span>
          <h1 className="page-banner__title">Buscar libros</h1>
          <p className="page-banner__desc">
            Explora millones de títulos, descubre nuevos autores y añade libros a tus estanterías personales.
          </p>
        </div>
      </div>
    </div>
    <div className="page-content">

      <form onSubmit={handleSubmit} className="search-bar">
        <select
          className="form-control"
          style={{ width: 'auto', flex: '0 0 auto' }}
          value={searchMode}
          onChange={e => setSearchMode(e.target.value as 'q' | 'title' | 'author')}
        >
          <option value="q">Texto libre</option>
          <option value="title">Por título</option>
          <option value="author">Por autor</option>
        </select>
        <input
          type="text"
          className="form-control"
          placeholder="Busca un libro, autor o tema…"
          value={query}
          onChange={e => setQuery(e.target.value)}
          autoFocus
        />
        <button type="submit" className="btn btn-primary" disabled={loading || !query.trim()}>
          {loading ? <Spinner size={16} /> : <><Search size={15} /> Buscar</>}
        </button>
      </form>

      {!searched && (
        <div className="books-suggestions">
          <p className="books-suggestions__label">Sugerencias:</p>
          <div className="books-suggestions__chips">
            {SUGGESTIONS.map(s => (
              <button
                key={s}
                className="btn btn-secondary btn-sm"
                onClick={() => handleSuggestion(s)}
              >
                {s}
              </button>
            ))}
          </div>
        </div>
      )}

      {error && <div className="alert alert-danger" style={{ marginTop: '1rem' }}>{error}</div>}

      {fallback && (
        <div className="alert alert-warning" style={{ marginTop: '1rem' }}>
          No se pueden cargar más libros en este momento. Vuelve a intentarlo más tarde.
          {books.length > 0 && ' Mostrando resultados guardados localmente.'}
        </div>
      )}

      {loading && (
        <div className="loading-state"><Spinner size={32} /></div>
      )}

      {!loading && searched && books.length === 0 && (
        <div className="empty-state">
          <div className="empty-state__icon"><BookOpen size={40} /></div>
          <p className="empty-state__title">No se encontraron resultados</p>
          <p className="empty-state__desc">Prueba con otros términos de búsqueda</p>
        </div>
      )}

      {!loading && books.length > 0 && (
        <>
          <div className="book-grid">
            {books.map(book => {
              const cover = book.thumbnail || book.coverUrl
              return (
                <div key={book.externalId} className="book-card">
                  <Link to={`/books/${encodeURIComponent(book.externalId)}`} className="book-card__cover" style={{ display: 'block', textDecoration: 'none' }}>
                    {cover ? (
                      <img src={cover} alt={book.title} loading="lazy" />
                    ) : (
                      <div className="book-card__cover-placeholder">
                        <BookOpen size={28} />
                      </div>
                    )}
                  </Link>

                  <div className="book-card__body">
                    <Link to={`/books/${encodeURIComponent(book.externalId)}`} className="book-card__title" style={{ textDecoration: 'none', color: 'inherit' }}>
                      {book.title}
                    </Link>
                    {book.authors?.length > 0 && (
                      <div className="book-card__authors">{book.authors.join(', ')}</div>
                    )}
                    {book.averageRating != null && (
                      <div className="book-card__rating">
                        <Star size={12} style={{ color: '#f59e0b', fill: '#f59e0b' }} />
                        <span className="book-card__stars">{renderStars(book.averageRating)}</span>
                        <span className="book-card__rating-text">
                          {book.averageRating.toFixed(1)}
                          {book.ratingsCount != null && ` (${fmtCount(book.ratingsCount)})`}
                        </span>
                      </div>
                    )}
                    {book.description && (
                      <div className="book-card__desc">{book.description}</div>
                    )}

                    <div className="book-card__links">
                      {user && (
                        <button
                          className="btn btn-primary btn-sm"
                          onClick={() => setDrawerBook(book)}
                        >
                          <BookMarked size={13} /> + Estantería
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              )
            })}
          </div>

          <div className="pager">
            <button
              className="btn btn-secondary"
              onClick={() => doSearch(page - 1)}
              disabled={page <= 1 || loading}
            >
              ← Anterior
            </button>
            <span className="pager__info">Página {page}{totalPages != null ? ` de ${totalPages}` : ''}</span>
            <button
              className="btn btn-secondary"
              onClick={() => doSearch(page + 1)}
              disabled={loading || (totalPages != null && page >= totalPages)}
            >
              Siguiente →
            </button>
          </div>
        </>
      )}

      {drawerBook && (
        <ShelfDrawer
          book={drawerBook}
          shelves={shelves}
          onShelvesChange={setShelves}
          onClose={() => setDrawerBook(null)}
        />
      )}
    </div>
    </>
  )
}
