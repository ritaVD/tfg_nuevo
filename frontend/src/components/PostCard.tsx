import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { postsApi, type Post, type PostComment } from '../api/posts'
import Spinner from './Spinner'
import ConfirmDialog from './ConfirmDialog'
import { Heart, MessageCircle, Trash2, X } from 'lucide-react'

function dicebear(seed: string) {
  return `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(seed)}&radius=50`
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

export default function PostCard({
  post: initial,
  meId,
  onDelete,
  hideAuthor = false,
  isAdmin = false,
}: {
  post: Post
  meId: number | null
  onDelete?: (id: number) => void
  hideAuthor?: boolean
  isAdmin?: boolean
}) {
  const [post, setPost] = useState(initial)
  const [showComments, setShowComments] = useState(false)
  const [comments, setComments] = useState<PostComment[]>([])
  const [commentsLoaded, setCommentsLoaded] = useState(false)
  const [commentsLoading, setCommentsLoading] = useState(false)
  const [commentText, setCommentText] = useState('')
  const [sendingComment, setSendingComment] = useState(false)
  const [liking, setLiking] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)

  async function handleLike() {
    if (!meId || liking) return
    setLiking(true)
    try {
      const res = await postsApi.like(post.id)
      setPost(p => ({ ...p, liked: res.liked, likes: res.likes }))
    } catch { /* ignore */ }
    finally { setLiking(false) }
  }

  async function handleToggleComments() {
    if (!showComments && !commentsLoaded) {
      setCommentsLoading(true)
      try {
        setComments(await postsApi.comments(post.id))
        setCommentsLoaded(true)
      } catch { /* ignore */ }
      finally { setCommentsLoading(false) }
    }
    setShowComments(v => !v)
  }

  async function handleSendComment(e: FormEvent) {
    e.preventDefault()
    if (!commentText.trim()) return
    setSendingComment(true)
    try {
      const c = await postsApi.addComment(post.id, commentText.trim())
      setComments(prev => [...prev, c])
      setPost(p => ({ ...p, commentCount: p.commentCount + 1 }))
      setCommentText('')
    } catch { /* ignore */ }
    finally { setSendingComment(false) }
  }

  async function handleDeleteComment(commentId: number) {
    try {
      await postsApi.deleteComment(post.id, commentId)
      setComments(prev => prev.filter(c => c.id !== commentId))
      setPost(p => ({ ...p, commentCount: Math.max(0, p.commentCount - 1) }))
    } catch { /* ignore */ }
  }

  async function handleDeletePost() {
    try {
      await postsApi.delete(post.id)
      onDelete?.(post.id)
    } catch { /* ignore */ }
  }

  const canDelete = onDelete && (meId === post.user.id || isAdmin)

  return (
    <>
    <div className="post-card">
      {/* Image */}
      <div className="post-card__img-wrap">
        <img src={`/uploads/posts/${post.imagePath}`} alt="Post" className="post-card__img" />
        {canDelete && (
          <button className="post-card__delete-btn" onClick={() => setConfirmDelete(true)} title="Eliminar post">
            <Trash2 size={14} />
          </button>
        )}
      </div>

      {/* Body */}
      <div className="post-card__body">
        {/* Author row */}
        {!hideAuthor && (
          <Link to={`/users/${post.user.id}`} className="post-card__author">
            <img
              src={post.user.avatar
                ? (post.user.avatar.startsWith('http') ? post.user.avatar : `/uploads/avatars/${post.user.avatar}`)
                : dicebear(post.user.displayName)}
              alt={post.user.displayName}
              className="post-card__author-avatar"
            />
            <span className="post-card__author-name">{post.user.displayName}</span>
          </Link>
        )}

        {post.description && <p className="post-card__desc">{post.description}</p>}

        {/* Actions */}
        <div className="post-card__actions">
          <button
            className={`post-card__action-btn${post.liked ? ' post-card__action-btn--liked' : ''}`}
            onClick={handleLike}
            disabled={liking || !meId}
            title={!meId ? 'Inicia sesión para dar like' : undefined}
          >
            <Heart size={16} fill={post.liked ? 'currentColor' : 'none'} />
            <span>{post.likes}</span>
          </button>
          <button className="post-card__action-btn" onClick={handleToggleComments}>
            <MessageCircle size={16} />
            <span>{post.commentCount}</span>
          </button>
          <span className="post-card__date">{fmtDate(post.createdAt)}</span>
        </div>

        {/* Comments */}
        {showComments && (
          <div className="post-card__comments">
            {commentsLoading ? (
              <div style={{ padding: '0.5rem', textAlign: 'center' }}><Spinner size={16} /></div>
            ) : (
              <>
                {comments.map(c => (
                  <div key={c.id} className="post-comment">
                    <Link to={`/users/${c.user.id}`} className="post-comment__avatar-link">
                      <img
                        src={c.user.avatar
                          ? (c.user.avatar.startsWith('http') ? c.user.avatar : `/uploads/avatars/${c.user.avatar}`)
                          : dicebear(c.user.displayName)}
                        alt={c.user.displayName}
                        className="post-comment__avatar"
                      />
                    </Link>
                    <div className="post-comment__content">
                      <Link to={`/users/${c.user.id}`} className="post-comment__name post-comment__name--link">
                        {c.user.displayName}
                      </Link>
                      <span className="post-comment__text">{c.content}</span>
                    </div>
                    {meId && (c.user.id === meId || post.user.id === meId || isAdmin) && (
                      <button className="post-comment__delete" onClick={() => handleDeleteComment(c.id)} title="Eliminar">
                        <X size={11} />
                      </button>
                    )}
                  </div>
                ))}
                {comments.length === 0 && (
                  <p style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)', padding: '0.25rem 0' }}>Sin comentarios aún.</p>
                )}
                {meId && (
                  <form onSubmit={handleSendComment} className="post-comment-form">
                    <input
                      className="form-control form-control-sm"
                      placeholder="Escribe un comentario…"
                      value={commentText}
                      onChange={e => setCommentText(e.target.value)}
                      style={{ flex: 1 }}
                    />
                    <button type="submit" className="btn btn-primary btn-sm" disabled={sendingComment || !commentText.trim()}>
                      {sendingComment ? <Spinner size={12} /> : 'Enviar'}
                    </button>
                  </form>
                )}
              </>
            )}
          </div>
        )}
      </div>
    </div>

    <ConfirmDialog
      open={confirmDelete}
      title="Eliminar post"
      message="¿Seguro que quieres eliminar este post? Esta acción no se puede deshacer."
      confirmLabel="Eliminar"
      onConfirm={() => { setConfirmDelete(false); handleDeletePost() }}
      onCancel={() => setConfirmDelete(false)}
    />
    </>
  )
}
