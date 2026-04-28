import { useState, useEffect, useRef, type FormEvent, type ChangeEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { profileApi, type ProfileData } from '../api/profile'
import { apiFetch } from '../api/client'
import { postsApi, type Post } from '../api/posts'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/Spinner'
import PostCard from '../components/PostCard'
import {
  User, Mail, Lock, Shield, LogOut, Camera,
  Eye, CheckCircle, AlertCircle,
  Plus, X, ImageIcon,
} from 'lucide-react'

interface FollowUser {
  id: number
  displayName: string
  avatar: string | null
  email: string
}

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

// ── Main Page ─────────────────────────────────────────────────────────────────

export default function ProfilePage() {
  const { user, logout, refresh } = useAuth()
  const navigate = useNavigate()

  const [profile, setProfile] = useState<ProfileData | null>(null)
  const [loading, setLoading] = useState(true)

  const [displayName, setDisplayName] = useState('')
  const [bio, setBio] = useState('')
  const [infoLoading, setInfoLoading] = useState(false)
  const [infoSuccess, setInfoSuccess] = useState(false)
  const [infoError, setInfoError] = useState('')

  const [avatarFile, setAvatarFile] = useState<File | null>(null)
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null)
  const [avatarLoading, setAvatarLoading] = useState(false)
  const [avatarError, setAvatarError] = useState('')
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [shelvesPublic, setShelvesPublic] = useState(false)
  const [clubsPublic, setClubsPublic] = useState(false)
  const [isPrivate, setIsPrivate] = useState(false)
  const [privacyLoading, setPrivacyLoading] = useState(false)
  const [privacySuccess, setPrivacySuccess] = useState(false)

  const [modal, setModal] = useState<'followers' | 'following' | null>(null)
  const [modalUsers, setModalUsers] = useState<FollowUser[]>([])
  const [modalLoading, setModalLoading] = useState(false)
  const [removingFollower, setRemovingFollower] = useState<number | null>(null)

  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [pwdLoading, setPwdLoading] = useState(false)
  const [pwdSuccess, setPwdSuccess] = useState(false)
  const [pwdError, setPwdError] = useState('')

  // Posts
  const [posts, setPosts] = useState<Post[]>([])
  const [postsLoading, setPostsLoading] = useState(false)
  const [showCreatePost, setShowCreatePost] = useState(false)
  const [postImage, setPostImage] = useState<File | null>(null)
  const [postImagePreview, setPostImagePreview] = useState<string | null>(null)
  const [postDesc, setPostDesc] = useState('')
  const [creatingPost, setCreatingPost] = useState(false)
  const [postError, setPostError] = useState('')
  const postFileRef = useRef<HTMLInputElement>(null)

  async function loadProfile() {
    setLoading(true)
    try {
      const data = await profileApi.get()
      setProfile(data)
      setDisplayName(data.displayName ?? '')
      setBio(data.bio ?? '')
      setShelvesPublic(data.shelvesPublic)
      setClubsPublic(data.clubsPublic)
      setIsPrivate(data.isPrivate)
    } catch { /* ignore */ }
    finally { setLoading(false) }
  }

  async function loadPosts() {
    if (!user) return
    setPostsLoading(true)
    try { setPosts(await postsApi.byUser(user.id)) }
    catch { /* ignore */ }
    finally { setPostsLoading(false) }
  }

  useEffect(() => { loadProfile() }, [])
  useEffect(() => { if (user) loadPosts() }, [user])

  async function handleInfoSubmit(e: FormEvent) {
    e.preventDefault()
    setInfoLoading(true); setInfoError(''); setInfoSuccess(false)
    try {
      const updated = await profileApi.update({ displayName: displayName || undefined, bio: bio || undefined })
      setProfile(updated)
      await refresh()
      setInfoSuccess(true)
      setTimeout(() => setInfoSuccess(false), 3000)
    } catch (err) {
      setInfoError(err instanceof Error ? err.message : 'Error al actualizar')
    } finally { setInfoLoading(false) }
  }

  function handleAvatarChange(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setAvatarFile(file)
    const reader = new FileReader()
    reader.onload = ev => setAvatarPreview(ev.target?.result as string)
    reader.readAsDataURL(file)
  }

  async function handleAvatarUpload(e: FormEvent) {
    e.preventDefault()
    if (!avatarFile) return
    setAvatarLoading(true); setAvatarError('')
    try {
      const updated = await profileApi.uploadAvatar(avatarFile)
      setProfile(updated)
      await refresh()
      setAvatarFile(null); setAvatarPreview(null)
      if (fileInputRef.current) fileInputRef.current.value = ''
    } catch (err) {
      setAvatarError(err instanceof Error ? err.message : 'Error al subir avatar')
    } finally { setAvatarLoading(false) }
  }

  async function handlePrivacyChange(field: 'shelvesPublic' | 'clubsPublic' | 'isPrivate', value: boolean) {
    const newShelves = field === 'shelvesPublic' ? value : shelvesPublic
    const newClubs   = field === 'clubsPublic'   ? value : clubsPublic
    const newPrivate = field === 'isPrivate'      ? value : isPrivate
    if (field === 'shelvesPublic') setShelvesPublic(value)
    else if (field === 'clubsPublic') setClubsPublic(value)
    else setIsPrivate(value)
    setPrivacyLoading(true); setPrivacySuccess(false)
    try {
      const updated = await profileApi.updatePrivacy({ shelvesPublic: newShelves, clubsPublic: newClubs, isPrivate: newPrivate })
      setProfile(updated); setIsPrivate(updated.isPrivate)
      setPrivacySuccess(true)
      setTimeout(() => setPrivacySuccess(false), 2000)
    } catch {
      if (field === 'shelvesPublic') setShelvesPublic(!value)
      else if (field === 'clubsPublic') setClubsPublic(!value)
      else setIsPrivate(!value)
    } finally { setPrivacyLoading(false) }
  }

  async function openModal(type: 'followers' | 'following') {
    if (!profile) return
    setModal(type); setModalLoading(true); setModalUsers([])
    try { setModalUsers(await apiFetch<FollowUser[]>(`/users/${profile.id}/${type}`)) }
    catch { /* ignore */ }
    finally { setModalLoading(false) }
  }

  async function handleRemoveFollower(followerId: number) {
    setRemovingFollower(followerId)
    try {
      await apiFetch(`/users/${followerId}/followers`, 'DELETE')
      setModalUsers(prev => prev.filter(u => u.id !== followerId))
      setProfile(prev => prev ? { ...prev, followers: (prev.followers ?? 1) - 1 } : prev)
    } catch { /* ignore */ }
    finally { setRemovingFollower(null) }
  }

  async function handlePasswordSubmit(e: FormEvent) {
    e.preventDefault()
    setPwdError(''); setPwdSuccess(false)
    if (newPassword.length < 6) { setPwdError('La nueva contraseña debe tener al menos 6 caracteres'); return }
    if (newPassword !== confirmPassword) { setPwdError('Las contraseñas no coinciden'); return }
    setPwdLoading(true)
    try {
      await profileApi.changePassword(currentPassword, newPassword)
      setPwdSuccess(true)
      setCurrentPassword(''); setNewPassword(''); setConfirmPassword('')
      setTimeout(() => setPwdSuccess(false), 3000)
    } catch (err) {
      setPwdError(err instanceof Error ? err.message : 'Error al cambiar contraseña')
    } finally { setPwdLoading(false) }
  }

  async function handleLogout() { await logout(); navigate('/') }

  function handlePostImageChange(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setPostImage(file)
    const reader = new FileReader()
    reader.onload = ev => setPostImagePreview(ev.target?.result as string)
    reader.readAsDataURL(file)
  }

  async function handleCreatePost(e: FormEvent) {
    e.preventDefault()
    if (!postImage) return
    setCreatingPost(true); setPostError('')
    try {
      const created = await postsApi.create(postImage, postDesc)
      setPosts(prev => [created, ...prev])
      setPostImage(null); setPostImagePreview(null); setPostDesc('')
      setShowCreatePost(false)
      if (postFileRef.current) postFileRef.current.value = ''
    } catch (err) {
      setPostError(err instanceof Error ? err.message : 'Error al publicar')
    } finally { setCreatingPost(false) }
  }

  if (loading) return <div className="loading-state loading-state--page"><Spinner size={36} /></div>

  const avatarSrc = profile?.avatar
    ? (profile.avatar.startsWith('http') ? profile.avatar : `/uploads/avatars/${profile.avatar}`)
    : dicebear(profile?.displayName || profile?.email || 'user')

  return (
    <>
    <div className="profile-layout">
      {/* Sidebar */}
      <aside>
        <div className="profile-avatar-card">
          <div className="profile-avatar-wrap">
            <img src={avatarSrc} alt="Avatar" className="profile-avatar" />
            <button className="profile-avatar-change" onClick={() => fileInputRef.current?.click()} title="Cambiar foto">
              <Camera size={14} />
            </button>
          </div>
          <div className="profile-name">{profile?.displayName || profile?.email}</div>
          <div className="profile-email"><Mail size={13} />{profile?.email}</div>
          {profile?.bio && <p className="profile-bio">{profile.bio}</p>}

          <div className="profile-stats">
            <button className="profile-stat" onClick={() => openModal('followers')}>
              <div className="profile-stat__num">{profile?.followers ?? 0}</div>
              <div className="profile-stat__label">seguidores</div>
            </button>
            <button className="profile-stat" onClick={() => openModal('following')}>
              <div className="profile-stat__num">{profile?.following ?? 0}</div>
              <div className="profile-stat__label">siguiendo</div>
            </button>
          </div>
        </div>
      </aside>

      {/* Main sections */}
      <div className="profile-sections">

        {/* Posts */}
        <section className="profile-section">
          <div className="profile-section__header" style={{ justifyContent: 'space-between' }}>
            <span style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <ImageIcon size={16} /> Mis publicaciones
            </span>
            <button className="btn btn-primary btn-sm" onClick={() => setShowCreatePost(v => !v)}>
              {showCreatePost ? <><X size={14} /> Cancelar</> : <><Plus size={14} /> Nueva publicación</>}
            </button>
          </div>
          <div className="profile-section__body">
            {/* Create form */}
            {showCreatePost && (
              <form onSubmit={handleCreatePost} className="post-create-form">
                <div
                  className="post-create-form__dropzone"
                  onClick={() => postFileRef.current?.click()}
                >
                  {postImagePreview
                    ? <img src={postImagePreview} alt="Preview" style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: 'var(--radius-md)' }} />
                    : <><Camera size={32} style={{ color: 'var(--color-text-muted)' }} /><span style={{ fontSize: '0.82rem', color: 'var(--color-text-muted)', marginTop: '0.5rem' }}>Haz clic para elegir imagen</span></>
                  }
                  <input ref={postFileRef} type="file" accept="image/*" onChange={handlePostImageChange} style={{ display: 'none' }} />
                </div>
                <textarea
                  className="form-control"
                  placeholder="Descripción (opcional)…"
                  rows={3}
                  value={postDesc}
                  onChange={e => setPostDesc(e.target.value)}
                  style={{ resize: 'vertical' }}
                />
                {postError && <p style={{ color: 'var(--color-danger)', fontSize: '0.82rem' }}>{postError}</p>}
                <button type="submit" className="btn btn-primary" disabled={creatingPost || !postImage}>
                  {creatingPost ? <Spinner size={15} /> : 'Publicar'}
                </button>
              </form>
            )}

            {/* Posts grid */}
            {postsLoading ? (
              <div className="loading-state" style={{ padding: '2rem 0' }}><Spinner size={28} /></div>
            ) : posts.length === 0 && !showCreatePost ? (
              <div className="empty-state" style={{ padding: '2rem 0' }}>
                <div className="empty-state__icon"><ImageIcon size={36} /></div>
                <p className="empty-state__title">Aún no tienes publicaciones</p>
                <p className="empty-state__desc">Comparte fotos con tus seguidores</p>
              </div>
            ) : (
              <div className="posts-grid">
                {posts.map(post => (
                  <PostCard
                    key={post.id}
                    post={post}
                    meId={user!.id}
                    hideAuthor
                    onDelete={id => setPosts(prev => prev.filter(p => p.id !== id))}
                  />
                ))}
              </div>
            )}
          </div>
        </section>

        {/* Personal info */}
        <section className="profile-section">
          <div className="profile-section__header"><User size={16} />Información personal</div>
          <div className="profile-section__body">
            {infoError && <div className="alert alert-danger"><AlertCircle size={15} /> {infoError}</div>}
            {infoSuccess && <div className="alert alert-success"><CheckCircle size={15} /> ¡Perfil actualizado correctamente!</div>}
            <form onSubmit={handleInfoSubmit}>
              <div className="form-group">
                <label className="form-label">Nombre visible</label>
                <input type="text" className="form-control" value={displayName} onChange={e => setDisplayName(e.target.value)} placeholder="Tu nombre público" />
              </div>
              <div className="form-group">
                <label className="form-label">Bio</label>
                <textarea className="form-control" value={bio} onChange={e => setBio(e.target.value)} placeholder="Cuéntanos algo sobre ti…" rows={3} />
              </div>
              <button type="submit" className="btn btn-primary" disabled={infoLoading}>
                {infoLoading ? <Spinner size={16} /> : 'Guardar cambios'}
              </button>
            </form>
            <hr style={{ margin: '1.5rem 0', border: 'none', borderTop: '1px solid var(--color-border)' }} />
            <div>
              <p className="form-label" style={{ marginBottom: '0.5rem' }}><Camera size={14} /> Foto de perfil</p>
              {avatarError && <div className="alert alert-danger">{avatarError}</div>}
              <form onSubmit={handleAvatarUpload}>
                <input ref={fileInputRef} type="file" accept="image/*" onChange={handleAvatarChange} className="form-control" style={{ marginBottom: '0.75rem' }} />
                {avatarPreview && <div className="avatar-preview"><img src={avatarPreview} alt="Preview" /><span className="text-sm text-muted">Vista previa</span></div>}
                {avatarFile && (
                  <button type="submit" className="btn btn-accent" disabled={avatarLoading} style={{ marginTop: '0.75rem' }}>
                    {avatarLoading ? <Spinner size={16} /> : <><Camera size={14} /> Subir avatar</>}
                  </button>
                )}
              </form>
            </div>
          </div>
        </section>

        {/* Privacy */}
        <section className="profile-section">
          <div className="profile-section__header">
            <Shield size={16} />Privacidad
            {privacyLoading && <Spinner size={14} />}
            {privacySuccess && <span className="badge badge-success" style={{ marginLeft: '0.5rem' }}><CheckCircle size={11} /> Guardado</span>}
          </div>
          <div className="profile-section__body">
            {[
              { field: 'isPrivate' as const, icon: <Lock size={14} />, title: 'Perfil privado', desc: 'Solo tus seguidores aprobados pueden ver tu actividad', val: isPrivate },
              { field: 'shelvesPublic' as const, icon: <Eye size={14} />, title: 'Estanterías públicas', desc: 'Permite que otros usuarios vean tus estanterías', val: shelvesPublic },
              { field: 'clubsPublic' as const, icon: <Eye size={14} />, title: 'Clubs públicos', desc: 'Muestra a qué clubs perteneces en tu perfil público', val: clubsPublic },
            ].map(({ field, icon, title, desc, val }) => (
              <div key={field} className="toggle-row">
                <div className="toggle-row__label">
                  <div className="toggle-row__title">{icon} {title}</div>
                  <div className="toggle-row__desc">{desc}</div>
                </div>
                <label className="toggle">
                  <input type="checkbox" checked={val} onChange={e => handlePrivacyChange(field, e.target.checked)} disabled={privacyLoading} />
                  <span className="toggle__slider" />
                </label>
              </div>
            ))}
          </div>
        </section>

        {/* Change password */}
        <section className="profile-section">
          <div className="profile-section__header"><Lock size={16} />Cambiar contraseña</div>
          <div className="profile-section__body">
            {pwdError && <div className="alert alert-danger"><AlertCircle size={15} /> {pwdError}</div>}
            {pwdSuccess && <div className="alert alert-success"><CheckCircle size={15} /> Contraseña actualizada correctamente</div>}
            <form onSubmit={handlePasswordSubmit}>
              {[
                { label: 'Contraseña actual', val: currentPassword, set: setCurrentPassword, auto: 'current-password' },
                { label: 'Nueva contraseña', val: newPassword, set: setNewPassword, auto: 'new-password' },
                { label: 'Confirmar nueva contraseña', val: confirmPassword, set: setConfirmPassword, auto: 'new-password' },
              ].map(({ label, val, set, auto }) => (
                <div key={label} className="form-group">
                  <label className="form-label">{label}</label>
                  <input type="password" className="form-control" value={val} onChange={e => set(e.target.value)} required autoComplete={auto} />
                </div>
              ))}
              <button type="submit" className="btn btn-primary" disabled={pwdLoading}>
                {pwdLoading ? <Spinner size={16} /> : <><Lock size={14} /> Cambiar contraseña</>}
              </button>
            </form>
          </div>
        </section>

        {/* Session */}
        <section className="profile-section">
          <div className="profile-section__header"><LogOut size={16} />Sesión</div>
          <div className="profile-section__body">
            <p className="text-sm text-muted" style={{ marginBottom: '1rem' }}>Conectado como <strong>{user?.email}</strong></p>
            <button className="btn btn-danger" onClick={handleLogout}><LogOut size={14} /> Cerrar sesión</button>
          </div>
        </section>
      </div>
    </div>

    {/* Modal seguidores / siguiendo */}
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
              <div style={{ display: 'flex', justifyContent: 'center', padding: '2rem' }}><Spinner size={28} /></div>
            ) : modalUsers.length === 0 ? (
              <p style={{ textAlign: 'center', color: 'var(--color-text-muted)', fontSize: '0.875rem', padding: '2rem 0' }}>
                {modal === 'followers' ? 'Nadie te sigue todavía.' : 'No sigues a nadie todavía.'}
              </p>
            ) : (
              modalUsers.map(u => (
                <div key={u.id} className="follow-modal-user" style={{ cursor: 'default' }}>
                  <Link
                    to={`/users/${u.id}`}
                    onClick={() => setModal(null)}
                    style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', textDecoration: 'none', flex: 1, minWidth: 0, color: 'inherit' }}
                  >
                    <img
                      src={u.avatar ? (u.avatar.startsWith('http') ? u.avatar : `/uploads/avatars/${u.avatar}`) : dicebear(u.displayName || u.email)}
                      alt={u.displayName}
                      className="follow-modal-user__avatar"
                    />
                    <span className="follow-modal-user__name" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {u.displayName || u.email}
                    </span>
                  </Link>
                  {modal === 'followers' && (
                    <button
                      className="btn btn-ghost btn-sm btn-icon"
                      style={{ color: 'var(--color-danger)', flexShrink: 0 }}
                      onClick={() => handleRemoveFollower(u.id)}
                      disabled={removingFollower === u.id}
                      title="Eliminar seguidor"
                    >
                      {removingFollower === u.id ? <Spinner size={13} /> : <X size={14} />}
                    </button>
                  )}
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    )}
    </>
  )
}
