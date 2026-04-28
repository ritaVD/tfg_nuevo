import { useEffect, type ReactNode } from 'react'
import { X } from 'lucide-react'

interface ConfirmDialogProps {
  open: boolean
  title: string
  message: ReactNode
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'danger' | 'primary'
  loading?: boolean
  onConfirm: () => void
  onCancel: () => void
}

export default function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Confirmar',
  cancelLabel = 'Cancelar',
  variant = 'danger',
  loading = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onCancel()
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onCancel])

  if (!open) return null

  return (
    <div className="cdialog-backdrop" onClick={onCancel} aria-modal="true" role="dialog">
      <div className="cdialog" onClick={e => e.stopPropagation()}>
        <div className="cdialog__header">
          <h2 className="cdialog__title">{title}</h2>
          <button className="cdialog__close" onClick={onCancel} aria-label="Cerrar">
            <X size={16} />
          </button>
        </div>

        <p className="cdialog__message">{message}</p>

        <div className="cdialog__actions">
          <button className="btn btn-ghost" onClick={onCancel} disabled={loading}>
            {cancelLabel}
          </button>
          <button
            className={`btn ${variant === 'danger' ? 'btn-danger' : 'btn-primary'}`}
            onClick={onConfirm}
            disabled={loading}
          >
            {loading ? <span className="cdialog__spinner" /> : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}
