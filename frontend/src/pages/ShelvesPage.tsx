import { useState, useEffect, useRef, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { shelvesApi, type Shelf, type ShelfBook } from '../api/shelves'
import { readingProgressApi, type ReadingProgressItem } from '../api/readingProgress'
import Spinner from '../components/Spinner'
import { useToast } from '../components/Toast'
import {
  BookOpen, Plus, Trash2, Search,
  BookMarked, BookOpenCheck,
  ChevronDown, Check, X, Layers,
} from 'lucide-react'

// ── Reading Tracker ──────────────────────────────────────────────────────────

function ProgressBar({ pct }: { pct: number }) {
  const clamped = Math.min(100, Math.max(0, pct))
  return (
    <div className="prog-bar">
      <div className="prog-bar__fill" style={{ width: `${clamped}%` }} />
    </div>
  )
}

function ReadingCard({
  item,
  onChange,
  onDelete,
}: {
  item: ReadingProgressItem
  onChange: (updated: ReadingProgressItem) => void
  onDelete: (id: number) => void
}) {
  const { toast } = useToast()
  const [mode, setMode] = useState<'pages' | 'percent'>(item.mode)
  const [pages, setPages] = useState<string>(item.currentPage?.toString() ?? '')
  const [totalPages, setTotalPages] = useState<string>(item.totalPages?.toString() ?? '')
  const [pct, setPct] = useState<string>(item.percent?.toString() ?? '')
  const [saving, setSaving] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [showTotalInput, setShowTotalInput] = useState(!item.totalPages && !item.book.pageCount && mode === 'pages')

  const effectiveTotal = item.totalPages ?? item.book.pageCount
  const cover = item.book.coverUrl

  async function handleSave() {
    setSaving(true)
    try {
      let patch: Parameters<typeof readingProgressApi.update>[1] = { mode }
      if (mode === 'pages') {
        patch.currentPage = pages !== '' ? parseInt(pages) : null
        if (totalPages !== '') patch.totalPages = parseInt(totalPages)
      } else {
        patch.percent = pct !== '' ? parseInt(pct) : null
      }
      const updated = await readingProgressApi.update(item.id, patch)
      onChange(updated)
      setMode(updated.mode)
      setPages(updated.currentPage?.toString() ?? '')
      setTotalPages(updated.totalPages?.toString() ?? '')
      setPct(updated.percent?.toString() ?? '')
    } catch { /* ignore */ }
    finally { setSaving(false) }
  }

  async function handleDelete() {
    setDeleting(true)
    try {
      await readingProgressApi.delete(item.id)
      onDelete(item.id)
      toast(`"${item.book.title}" quitado del seguimiento`, 'success')
    } catch {
      toast('Error al quitar el libro', 'error')
      setConfirmDelete(false)
    } finally {
      setDeleting(false)
    }
  }

  const computed = item.computed

  return (
    <div className="reading-card">
      <div className="reading-card__cover">
        {cover
          ? <img src={cover} alt={item.book.title} />
          : <div className="reading-card__cover-empty"><BookOpen size={22} /></div>
        }
      </div>

      <div className="reading-card__main">
        <Link to={`/books/${encodeURIComponent(item.book.externalId)}`} className="reading-card__title">
          {item.book.title}
        </Link>
        {item.book.authors.length > 0 && (
          <div className="reading-card__authors">{item.book.authors.join(', ')}</div>
        )}

        <div className="reading-card__progress-row">
          <ProgressBar pct={computed} />
          <span className="reading-card__pct">{computed}%</span>
        </div>

        <div className="reading-card__controls">
          {/* Mode toggle */}
          <div className="reading-card__mode-toggle">
            <button
              className={`reading-card__mode-btn${mode === 'percent' ? ' active' : ''}`}
              onClick={() => setMode('percent')}
              type="button"
            >%</button>
            <button
              className={`reading-card__mode-btn${mode === 'pages' ? ' active' : ''}`}
              onClick={() => { setMode('pages'); if (!effectiveTotal) setShowTotalInput(true) }}
              type="button"
            >Págs.</button>
          </div>

          {mode === 'percent' ? (
            <div className="reading-card__input-wrap">
              <input
                type="number"
                min={0} max={100}
                className="form-control form-control-sm"
                                placeholder="0–100"
                value={pct}
                onChange={e => setPct(e.target.value)}
              />
              <span className="reading-card__unit">%</span>
            </div>
          ) : (
            <div className="reading-card__input-wrap">
              <input
                type="number"
                min={0}
                className="form-control form-control-sm"
                                placeholder="Página"
                value={pages}
                onChange={e => setPages(e.target.value)}
              />
              <span className="reading-card__unit">/</span>
              {effectiveTotal && !showTotalInput ? (
                <span
                  className="reading-card__total"
                  onClick={() => setShowTotalInput(true)}
                  title="Clic para editar"
                >{effectiveTotal}</span>
              ) : (
                <input
                  type="number"
                  min={1}
                  className="form-control form-control-sm"
                                    placeholder="Total págs."
                  value={totalPages}
                  onChange={e => setTotalPages(e.target.value)}
                />
              )}
            </div>
          )}

          <button
            className="btn btn-primary btn-sm"
            onClick={handleSave}
            disabled={saving}
          >
            {saving ? <Spinner size={13} /> : <Check size={14} />}
          </button>

          {confirmDelete ? (
            <div className="inline-confirm">
              <span className="inline-confirm__label">¿Quitar?</span>
              <button
                className="btn btn-danger btn-sm btn-icon"
                onClick={handleDelete}
                disabled={deleting}
                title="Confirmar"
              >
                {deleting ? <Spinner size={13} /> : <Check size={13} />}
              </button>
              <button
                className="btn btn-ghost btn-sm btn-icon"
                onClick={() => setConfirmDelete(false)}
                title="Cancelar"
              >
                <X size={13} />
              </button>
            </div>
          ) : (
            <button
              className="btn btn-ghost btn-sm btn-icon btn-ghost--danger"
              onClick={() => setConfirmDelete(true)}
              title="Quitar seguimiento"
            >
              <Trash2 size={14} />
            </button>
          )}
        </div>
      </div>
    </div>
  )
}

function ReadingTracker() {
  const [items, setItems] = useState<ReadingProgressItem[]>([])
  const [loading, setLoading] = useState(true)
  const [open, setOpen] = useState(true)

  useEffect(() => {
    readingProgressApi.list()
      .then(setItems)
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [])

  if (loading) return null
  if (items.length === 0) return null

  return (
    <div className="reading-tracker">
      <button
        className="reading-tracker__header"
        onClick={() => setOpen(v => !v)}
        type="button"
      >
        <BookOpenCheck size={16} />
        <span>Estoy leyendo</span>
        <span className="reading-tracker__count">{items.length}</span>
        <ChevronDown size={16} className={`reading-tracker__chevron${open ? ' open' : ''}`} />
      </button>

      {open && (
        <div className="reading-tracker__body">
          {items.map(item => (
            <ReadingCard
              key={item.id}
              item={item}
              onChange={updated => setItems(prev => prev.map(i => i.id === updated.id ? updated : i))}
              onDelete={id => setItems(prev => prev.filter(i => i.id !== id))}
            />
          ))}
        </div>
      )}
    </div>
  )
}

// ── Shelves Page ─────────────────────────────────────────────────────────────

export default function ShelvesPage() {
  const { toast } = useToast()
  const [shelves, setShelves] = useState<Shelf[]>([])
  const [shelvesLoading, setShelvesLoading] = useState(true)
  const [activeShelf, setActiveShelf] = useState<number | null>(null)

  const [books, setBooks] = useState<ShelfBook[]>([])
  const [booksLoading, setBooksLoading] = useState(false)

  const [newShelfName, setNewShelfName] = useState('')
  const [addingShelf, setAddingShelf] = useState(false)
  const [error, setError] = useState('')

  // Inline confirm states
  const [confirmShelfId, setConfirmShelfId] = useState<number | null>(null)
  const [deletingShelfId, setDeletingShelfId] = useState<number | null>(null)
  const [confirmBookId, setConfirmBookId] = useState<number | null>(null)
  const [removingBookId, setRemovingBookId] = useState<number | null>(null)

  async function loadShelves() {
    setShelvesLoading(true)
    try {
      const data = await shelvesApi.list()
      setShelves(data)
      if (data.length > 0 && activeShelf === null) {
        setActiveShelf(data[0].id)
      }
    } catch {
      setError('Error al cargar estanterías')
    } finally {
      setShelvesLoading(false)
    }
  }

  async function loadBooks(shelfId: number) {
    setBooksLoading(true)
    try {
      const data = await shelvesApi.books(shelfId)
      setBooks(data)
    } catch {
      setBooks([])
    } finally {
      setBooksLoading(false)
    }
  }

  useEffect(() => { loadShelves() }, [])
  useEffect(() => { if (activeShelf != null) loadBooks(activeShelf) }, [activeShelf])

  async function handleAddShelf(e: FormEvent) {
    e.preventDefault()
    if (!newShelfName.trim()) return
    setAddingShelf(true)
    try {
      const shelf = await shelvesApi.create(newShelfName.trim())
      setShelves(prev => [...prev, shelf])
      setNewShelfName('')
      setActiveShelf(shelf.id)
      toast(`Estantería "${shelf.name}" creada`, 'success')
    } catch {
      toast('Error al crear la estantería', 'error')
    } finally { setAddingShelf(false) }
  }

  async function handleDeleteShelf(shelfId: number, e: React.MouseEvent) {
    e.stopPropagation()
    setDeletingShelfId(shelfId)
    try {
      await shelvesApi.delete(shelfId)
      const deleted = shelves.find(s => s.id === shelfId)
      setShelves(prev => prev.filter(s => s.id !== shelfId))
      if (activeShelf === shelfId) {
        const remaining = shelves.filter(s => s.id !== shelfId)
        setActiveShelf(remaining.length > 0 ? remaining[0].id : null)
      }
      toast(`Estantería "${deleted?.name}" eliminada`, 'success')
    } catch {
      toast('Error al eliminar la estantería', 'error')
    } finally {
      setDeletingShelfId(null)
      setConfirmShelfId(null)
    }
  }

  async function handleRemoveBook(shelfId: number, bookId: number) {
    setRemovingBookId(bookId)
    try {
      await shelvesApi.removeBook(shelfId, bookId)
      const removed = books.find(b => b.id === bookId)
      setBooks(prev => prev.filter(b => b.id !== bookId))
      toast(`"${removed?.book.title}" quitado de la estantería`, 'success')
    } catch {
      toast('Error al quitar el libro', 'error')
    } finally {
      setRemovingBookId(null)
      setConfirmBookId(null)
    }
  }


  const activeShelfData = shelves.find(s => s.id === activeShelf)

  if (shelvesLoading) {
    return <div className="loading-state loading-state--page"><Spinner size={36} /></div>
  }

  return (
    <>
    <div className="page-banner page-banner--shelves">
      <div className="page-banner__inner">
        <div className="page-banner__text">
          <span className="page-banner__eyebrow">
            <Layers size={12} /> Biblioteca personal
          </span>
          <h1 className="page-banner__title">Mis estanterías</h1>
          <p className="page-banner__desc">
            Organiza tus lecturas, sigue tu progreso y lleva un registro de todos tus libros.
          </p>
        </div>
      </div>
    </div>
    <div className="shelves-outer">

      {/* ── Reading Tracker (above shelves) ── */}
      <ReadingTracker />

      {/* ── Shelves layout ── */}
      <div className="shelves-layout">
        {/* Sidebar */}
        <aside className="shelf-sidebar">
          <div className="shelf-sidebar__header">
            <BookMarked size={16} />
            Mis estanterías
          </div>

          {error && <div className="alert alert-danger">{error}</div>}

          <nav className="shelf-nav">
            {shelves.length === 0 ? (
              <p className="shelf-nav-empty">
                No tienes estanterías aún
              </p>
            ) : (
              shelves.map((shelf) => (
                <div
                  key={shelf.id}
                  className={`shelf-nav-item${activeShelf === shelf.id ? ' shelf-nav-item--active' : ''}`}
                  onClick={() => { setConfirmShelfId(null); setActiveShelf(shelf.id) }}
                  role="button"
                  tabIndex={0}
                  onKeyDown={e => e.key === 'Enter' && setActiveShelf(shelf.id)}
                >
                  <BookOpen size={14} className="shelf-nav-item__icon" />
                  <span className="shelf-nav-item__name">{shelf.name}</span>
                  {confirmShelfId === shelf.id ? (
                      <div className="shelf-confirm" onClick={e => e.stopPropagation()}>
                        <span className="shelf-confirm__label">¿Borrar?</span>
                        <button
                          className="shelf-nav-item__delete"
                          onClick={e => handleDeleteShelf(shelf.id, e)}
                          disabled={deletingShelfId === shelf.id}
                          title="Confirmar"
                        >
                          {deletingShelfId === shelf.id ? <Spinner size={11} /> : <Check size={12} />}
                        </button>
                        <button
                          className="shelf-nav-item__delete"
                          onClick={e => { e.stopPropagation(); setConfirmShelfId(null) }}
                          title="Cancelar"
                        >
                          <X size={12} />
                        </button>
                      </div>
                    ) : (
                      <button
                        className="shelf-nav-item__delete"
                        onClick={e => { e.stopPropagation(); setConfirmShelfId(shelf.id) }}
                        title="Eliminar estantería"
                      >
                        <Trash2 size={13} />
                      </button>
                    )
                  }
                </div>
              ))
            )}
          </nav>

          <div className="shelf-sidebar__add">
            <form onSubmit={handleAddShelf}>
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="Nueva estantería…"
                value={newShelfName}
                onChange={e => setNewShelfName(e.target.value)}
              />
              <button
                type="submit"
                className="btn btn-primary btn-sm"
                disabled={addingShelf || !newShelfName.trim()}
              >
                {addingShelf ? <Spinner size={12} /> : <Plus size={14} />}
              </button>
            </form>
          </div>
        </aside>

        {/* Panel */}
        <main className="shelf-panel">
          {activeShelf == null ? (
            <div className="empty-state" style={{ padding: '4rem 2rem' }}>
              <div className="empty-state__icon"><BookMarked size={40} /></div>
              <p className="empty-state__title">Sin estanterías</p>
              <p className="empty-state__desc">Crea tu primera estantería usando el formulario de la izquierda</p>
            </div>
          ) : (
            <>
              <div className="shelf-panel__header">
                <h2 className="shelf-panel__title">{activeShelfData?.name ?? 'Estantería'}</h2>
                <Link to="/books" className="btn btn-secondary btn-sm">
                  <Search size={14} /> Buscar libros
                </Link>
              </div>

              <div className="shelf-panel__body">
                {booksLoading ? (
                  <div className="loading-state"><Spinner size={28} /></div>
                ) : books.length === 0 ? (
                  <div className="empty-state" style={{ padding: '3rem 1rem' }}>
                    <div className="empty-state__icon"><BookOpen size={36} /></div>
                    <p className="empty-state__title">Esta estantería está vacía</p>
                    <p className="empty-state__desc">Busca libros y añádelos a esta estantería</p>
                    <Link to="/books" className="btn btn-primary btn-sm" style={{ marginTop: '0.5rem' }}>
                      <Search size={14} /> Buscar libros
                    </Link>
                  </div>
                ) : (() => {
                  return books.map(shelfBook => {
                    const cover = shelfBook.book.thumbnail || shelfBook.book.coverUrl
                    return (
                      <div key={shelfBook.id} className="shelf-book">
                        <Link to={`/books/${encodeURIComponent(shelfBook.book.externalId)}`}>
                          {cover
                            ? <img src={cover} alt={shelfBook.book.title} className="shelf-book__cover" />
                            : <div className="shelf-book__cover-placeholder"><BookOpen size={24} /></div>
                          }
                        </Link>
                        <div className="shelf-book__info">
                          <Link to={`/books/${encodeURIComponent(shelfBook.book.externalId)}`} className="shelf-book__title" style={{ textDecoration: 'none', color: 'inherit' }}>
                            {shelfBook.book.title}
                          </Link>
                          {shelfBook.book.authors?.length > 0 && (
                            <div className="shelf-book__authors">{shelfBook.book.authors.join(', ')}</div>
                          )}
                        </div>
                        <div className="shelf-book__actions">
                          {confirmBookId === shelfBook.id ? (
                            <div className="inline-confirm">
                              <span className="inline-confirm__label">¿Quitar?</span>
                              <button
                                className="btn btn-danger btn-sm btn-icon"
                                onClick={() => handleRemoveBook(activeShelf, shelfBook.id)}
                                disabled={removingBookId === shelfBook.id}
                                title="Confirmar"
                              >
                                {removingBookId === shelfBook.id ? <Spinner size={13} /> : <Check size={13} />}
                              </button>
                              <button
                                className="btn btn-ghost btn-sm btn-icon"
                                onClick={() => setConfirmBookId(null)}
                                title="Cancelar"
                              >
                                <X size={13} />
                              </button>
                            </div>
                          ) : (
                            <button
                              className="btn btn-danger btn-sm btn-icon"
                              onClick={() => setConfirmBookId(shelfBook.id)}
                              title="Quitar de la estantería"
                            >
                              <Trash2 size={14} />
                            </button>
                          )}
                        </div>
                      </div>
                    )
                  })
                })()}
              </div>
            </>
          )}
        </main>
      </div>
    </div>
    </>
  )
}
