import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import { apiFetch } from '../api/client'
import { postsApi, type Post } from '../api/posts'
import { useAuth } from '../context/AuthContext'
import PostCard from '../components/PostCard'
import Spinner from '../components/Spinner'
import {
  User, BookMarked, Users, Lock, UserPlus, UserMinus,
  BookOpen, ImageIcon, Clock, X, ArrowLeft,
} from 'lucide-react'

interface ShelfBook {
  id: number
  title: string
  authors: string[]
  coverUrl: string | null
  thumbnail: string | null
}

interface PublicShelf {
  id: number
  name: string
  books: ShelfBook[]
}

interface PublicProfile {
  id: number
  displayName: string | null
  bio: string | null
  avatar: string | null
  followers: number
  following: number
  followStatus: 'none' | 'pending' | 'accepted'
  shelves: PublicShelf[] | null
  clubs: { id: number; name: string; visibility: string; role: string }[] | null
}

interface FollowUser {
  id: number
  displayName: string
  avatar: string | null
  email: string
}

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

export default function PublicProfilePage() {
  const { id } = useParams<{ id: string }>()
  const { user: me } = useAuth()

  const [profile, setProfile]       = useState<PublicProfile | null>(null)
  const [loading, setLoading]       = useState(true)
  const [error, setError]           = useState('')
  const [followLoading, setFollowLoading] = useState(false)
  const [modal, setModal]           = useState<'followers' | 'following' | null>(null)
  const [modalUsers, setModalUsers] = useState<FollowUser[]>([])
  const [modalLoading, setModalLoading] = useState(false)

  const [posts, setPosts]           = useState<Post[]>([])
  const [postsLoading, setPostsLoading] = useState(false)

  useEffect(() => {
    setLoading(true)
    setError('')
    apiFetch<PublicProfile>(`/users/${id}`)
      .then(setProfile)
      .catch(err => setError(err instanceof Error ? err.message : 'Usuario no encontrado'))
      .finally(() => setLoading(false))

    setPostsLoading(true)
    postsApi.byUser(Number(id))
      .then(setPosts)
      .catch(() => {})
      .finally(() => setPostsLoading(false))
  }, [id])

  async function handleFollow() {
    if (!profile) return
    setFollowLoading(true)
    try {
      if (profile.followStatus === 'none') {
        const res = await apiFetch<{ status: string; followers: number }>(
          `/users/${profile.id}/follow`, 'POST'
        )
        setProfile(p => p ? { ...p, followStatus: res.status as 'pending' | 'accepted', followers: res.followers } : p)
      } else {
        const res = await apiFetch<{ followers: number }>(
          `/users/${profile.id}/follow`, 'DELETE'
        )
        setProfile(p => p ? { ...p, followStatus: 'none', followers: res.followers } : p)
      }
    } catch { /* ignore */ }
    finally { setFollowLoading(false) }
  }

  async function openModal(type: 'followers' | 'following') {
    if (!profile) return
    setModal(type)
    setModalLoading(true)
    setModalUsers([])
    try {
      const users = await apiFetch<FollowUser[]>(`/users/${profile.id}/${type}`)
      setModalUsers(users)
    } catch { /* ignore */ }
    finally { setModalLoading(false) }
  }

  if (loading) {
    return <div className="loading-state loading-state--page"><Spinner size={36} /></div>
  }

  if (error || !profile) {
    return (
      <div className="page-content profile-error-page">
        <div className="empty-state__icon"><User size={48} /></div>
        <p className="empty-state__title">{error || 'Usuario no encontrado'}</p>
        <Link to="/clubs" className="btn btn-secondary"><ArrowLeft size={14} /> Volver a clubs</Link>
      </div>
    )
  }

  const isOwnProfile = me?.id === profile.id
  const name = profile.displayName || `Usuario #${profile.id}`
  const avatarSrc = profile.avatar
    ? (profile.avatar.startsWith('http') ? profile.avatar : `/uploads/avatars/${profile.avatar}`)
    : dicebear(name)

  return (
    <>
    <div className="profile-layout">

      {/* ── Sidebar ── */}
      <aside>
        <div className="profile-avatar-card">
          <div className="profile-avatar-wrap">
            <img src={avatarSrc} alt={name} className="profile-avatar" />
          </div>

          <div className="profile-name">{name}</div>
          {profile.bio && <p className="profile-bio">{profile.bio}</p>}

          <div className="profile-stats">
            <button className="profile-stat" onClick={() => openModal('followers')}>
              <div className="profile-stat__num">{profile.followers}</div>
              <div className="profile-stat__label">seguidores</div>
            </button>
            <button className="profile-stat" onClick={() => openModal('following')}>
              <div className="profile-stat__num">{profile.following}</div>
              <div className="profile-stat__label">siguiendo</div>
            </button>
          </div>

          {me && !isOwnProfile && (
            <button
              className={`btn btn-sm profile-follow-btn${
                profile.followStatus === 'accepted' ? ' btn-secondary'
                : profile.followStatus === 'pending'  ? ' btn-ghost'
                : ' btn-primary'
              }`}
              onClick={handleFollow}
              disabled={followLoading}
            >
              {followLoading ? (
                <Spinner size={14} />
              ) : profile.followStatus === 'accepted' ? (
                <><UserMinus size={14} /> Siguiendo</>
              ) : profile.followStatus === 'pending' ? (
                <><Clock size={14} /> Solicitud enviada</>
              ) : (
                <><UserPlus size={14} /> Seguir</>
              )}
            </button>
          )}

          {isOwnProfile && (
            <Link to="/profile" className="btn btn-ghost btn-sm profile-follow-btn">
              Editar perfil
            </Link>
          )}
        </div>
      </aside>

      {/* ── Main sections ── */}
      <div className="profile-sections">

        {/* Posts */}
        <section className="profile-section">
          <div className="profile-section__header">
            <ImageIcon size={15} /> Publicaciones
          </div>
          <div className="profile-section__body">
            {postsLoading ? (
              <div className="loading-state loading-state--sm">
                <Spinner size={28} />
              </div>
            ) : posts.length === 0 ? (
              <div className="empty-state empty-state--compact">
                <div className="empty-state__icon"><ImageIcon size={28} /></div>
                <p className="empty-state__title">Sin publicaciones</p>
                <p className="empty-state__desc">Este usuario aún no ha publicado nada.</p>
              </div>
            ) : (
              <div className="posts-grid">
                {posts.map(post => (
                  <PostCard
                    key={post.id}
                    post={post}
                    meId={me?.id ?? null}
                    isAdmin={me?.roles?.includes('ROLE_ADMIN')}
                    onDelete={me?.roles?.includes('ROLE_ADMIN')
                      ? (id) => setPosts((prev: Post[]) => prev.filter((p: Post) => p.id !== id))
                      : undefined
                    }
                  />
                ))}
              </div>
            )}
          </div>
        </section>

        {/* Shelves */}
        <section className="profile-section">
          <div className="profile-section__header">
            <BookMarked size={15} /> Estanterías
          </div>
          <div className="profile-section__body">
            {profile.shelves === null ? (
              <p className="profile-private-msg">
                <Lock size={14} /> Este usuario mantiene sus estanterías en privado.
              </p>
            ) : profile.shelves.length === 0 ? (
              <p className="text-sm text-muted">No tiene estanterías públicas.</p>
            ) : (
              <div className="pub-shelves-list">
                {profile.shelves.map(shelf => (
                  <div key={shelf.id}>
                    <div className="pub-shelf-header">
                      <span className="pub-shelf-header__icon"><BookMarked size={14} /></span>
                      <span className="pub-shelf-header__name">{shelf.name}</span>
                      <span className="pub-shelf-header__count">{shelf.books.length}</span>
                    </div>
                    {shelf.books.length === 0 ? (
                      <p className="pub-shelf-empty">Vacía</p>
                    ) : (
                      <div className="pub-shelf-books">
                        {shelf.books.map(book => {
                          const cover = book.thumbnail || book.coverUrl
                          return (
                            <div
                              key={book.id}
                              className="pub-shelf-book"
                              title={`${book.title}${book.authors?.length ? ' — ' + book.authors[0] : ''}`}
                            >
                              {cover
                                ? <img src={cover} alt={book.title} className="pub-shelf-book__cover" />
                                : <div className="pub-shelf-book__cover pub-shelf-book__cover--empty"><BookOpen size={14} /></div>
                              }
                              <div className="pub-shelf-book__title">{book.title}</div>
                            </div>
                          )
                        })}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
        </section>

        {/* Clubs */}
        <section className="profile-section">
          <div className="profile-section__header">
            <Users size={15} /> Clubs de lectura
          </div>
          <div className="profile-section__body">
            {profile.clubs === null ? (
              <p className="profile-private-msg">
                <Lock size={14} /> Este usuario mantiene sus clubs en privado.
              </p>
            ) : profile.clubs.length === 0 ? (
              <p className="text-sm text-muted">No pertenece a ningún club público.</p>
            ) : (
              <div className="pub-clubs-list">
                {profile.clubs.map(club => (
                  <Link key={club.id} to={`/clubs/${club.id}`} className="profile-club-link">
                    <Users size={14} />
                    <span className="profile-club-link__name">{club.name}</span>
                    {club.role === 'admin' && (
                      <span className="badge badge-primary">Admin</span>
                    )}
                  </Link>
                ))}
              </div>
            )}
          </div>
        </section>

      </div>
    </div>

    {/* ── Followers / Following modal ── */}
    {modal && (
      <div className="follow-modal-backdrop" onClick={e => { if (e.target === e.currentTarget) setModal(null) }}>
        <div className="follow-modal">
          <div className="follow-modal__head">
            <span className="follow-modal__title">
              {modal === 'followers' ? 'Seguidores' : 'Siguiendo'}
            </span>
            <button className="follow-modal__close" onClick={() => setModal(null)}><X size={16} /></button>
          </div>
          <div className="follow-modal__body">
            {modalLoading ? (
              <div className="follow-modal__loading"><Spinner size={28} /></div>
            ) : modalUsers.length === 0 ? (
              <p className="follow-modal__empty">
                {modal === 'followers' ? 'Nadie sigue a este usuario aún.' : 'No sigue a nadie aún.'}
              </p>
            ) : (
              modalUsers.map(u => (
                <Link
                  key={u.id}
                  to={`/users/${u.id}`}
                  onClick={() => setModal(null)}
                  className="follow-modal-user"
                >
                  <img
                    src={u.avatar
                      ? (u.avatar.startsWith('http') ? u.avatar : `/uploads/avatars/${u.avatar}`)
                      : dicebear(u.displayName || u.email)}
                    alt={u.displayName}
                    className="follow-modal-user__avatar"
                  />
                  <span className="follow-modal-user__name">{u.displayName || u.email}</span>
                </Link>
              ))
            )}
          </div>
        </div>
      </div>
    )}
    </>
  )
}
