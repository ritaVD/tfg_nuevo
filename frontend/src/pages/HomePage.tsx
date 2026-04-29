import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { postsApi, type Post } from '../api/posts'
import PostCard from '../components/PostCard'
import Spinner from '../components/Spinner'
import { Search, BookMarked, Users, ArrowRight, Sparkles, ImageIcon } from 'lucide-react'

export default function HomePage() {
  const { user } = useAuth()

  const [feed, setFeed] = useState<Post[]>([])
  const [feedLoading, setFeedLoading] = useState(false)
  const [feedLoaded, setFeedLoaded] = useState(false)

  useEffect(() => {
    if (!user) return
    setFeedLoading(true)
    postsApi.feed()
      .then(posts => { setFeed(posts); setFeedLoaded(true) })
      .catch(() => {})
      .finally(() => setFeedLoading(false))
  }, [user])

  return (
    <>
      <section className="hero">
        <div className="hero__inner">
          <span className="hero__eyebrow">
            <Sparkles size={13} />
            Plataforma de lectura
          </span>
          <h1 className="hero__title">
            Lee más.<br />
            Comparte <span>tus lecturas</span>.
          </h1>
          <p className="hero__desc">
            Organiza tus libros en estanterías personales, únete a clubs de lectura
            y comenta cada capítulo con otros lectores.
          </p>
          <div className="hero__actions">
            {user ? (
              <>
                <Link to="/clubs" className="btn btn-primary btn-lg">
                  Explorar clubs <ArrowRight size={16} />
                </Link>
                <Link to="/shelves" className="btn btn-secondary btn-lg">
                  Mis estanterías
                </Link>
              </>
            ) : (
              <>
                <Link to="/register" className="btn btn-primary btn-lg">
                  Crear cuenta gratis <ArrowRight size={16} />
                </Link>
                <Link to="/clubs" className="btn btn-secondary btn-lg">
                  Explorar clubs
                </Link>
              </>
            )}
          </div>

          <div className="hero__stats">
            <div className="hero__stat">
              <div className="hero__stat-number">10M+</div>
              <div className="hero__stat-label">Libros disponibles</div>
            </div>
            <div className="hero__stat">
              <div className="hero__stat-number">3</div>
              <div className="hero__stat-label">Modos de búsqueda</div>
            </div>
            <div className="hero__stat">
              <div className="hero__stat-number">∞</div>
              <div className="hero__stat-label">Clubs por crear</div>
            </div>
          </div>
        </div>
      </section>

      {/* Feed — solo para usuarios autenticados */}
      {user && (
        <section className="home-feed">
          <div className="home-feed__header">
            <div className="home-feed__icon"><ImageIcon size={18} /></div>
            <h2 className="home-feed__title">Últimas publicaciones</h2>
            <span className="home-feed__subtitle">De las personas que sigues</span>
          </div>

          {feedLoading ? (
            <div className="loading-state">
              <Spinner size={32} />
            </div>
          ) : feedLoaded && feed.length === 0 ? (
            <div className="empty-state">
              <div className="empty-state__icon"><ImageIcon size={40} /></div>
              <p className="empty-state__title">Tu feed está vacío</p>
              <p className="empty-state__desc">
                Sigue a otros lectores para ver sus publicaciones aquí.
              </p>
              <Link to="/users" className="btn btn-primary btn-sm">
                Descubrir lectores <ArrowRight size={14} />
              </Link>
            </div>
          ) : (
            <div className="posts-grid">
              {feed.map(post => (
                <PostCard
                  key={post.id}
                  post={post}
                  meId={user.id}
                  isAdmin={user.roles?.includes('ROLE_ADMIN')}
                  onDelete={(post.user.id === user.id || user.roles?.includes('ROLE_ADMIN'))
                    ? (id) => setFeed(prev => prev.filter(p => p.id !== id))
                    : undefined
                  }
                />
              ))}
            </div>
          )}
        </section>
      )}

      <section className="features">
        <div className="features__header">
          <h2 className="features__title">Todo lo que necesitas para leer mejor</h2>
          <p className="features__subtitle">Herramientas pensadas para lectores activos</p>
        </div>
        <div className="features__grid">
          <div className="feature-card">
            <div className="feature-card__icon-wrap">
              <Search size={24} />
            </div>
            <h3 className="feature-card__title">Busca cualquier libro</h3>
            <p className="feature-card__desc">
              Accede a millones de títulos a través de Google Books. Busca por título, autor o texto libre.
            </p>
            <Link to="/books" className="btn btn-secondary btn-sm">
              Buscar libros <ArrowRight size={14} />
            </Link>
          </div>
          <div className="feature-card">
            <div className="feature-card__icon-wrap">
              <BookMarked size={24} />
            </div>
            <h3 className="feature-card__title">Estanterías personales</h3>
            <p className="feature-card__desc">
              Crea estanterías y marca cada libro como quiero leer, leyendo o leído.
            </p>
            {user ? (
              <Link to="/shelves" className="btn btn-secondary btn-sm">
                Mis estanterías <ArrowRight size={14} />
              </Link>
            ) : (
              <Link to="/register" className="btn btn-secondary btn-sm">
                Empezar <ArrowRight size={14} />
              </Link>
            )}
          </div>
          <div className="feature-card">
            <div className="feature-card__icon-wrap">
              <Users size={24} />
            </div>
            <h3 className="feature-card__title">Clubs de lectura</h3>
            <p className="feature-card__desc">
              Únete a clubs públicos o crea el tuyo con chats por capítulos y libro del mes.
            </p>
            <Link to="/clubs" className="btn btn-secondary btn-sm">
              Ver clubs <ArrowRight size={14} />
            </Link>
          </div>
        </div>
      </section>
    </>
  )
}
