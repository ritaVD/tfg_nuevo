import { BookOpen } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Footer() {
  const { user } = useAuth()
  const year = new Date().getFullYear()

  return (
    <footer className="footer">
      <div className="footer__inner">

        {/* Brand */}
        <div>
          <div className="footer__brand-name">
            <BookOpen size={18} />
            Books&amp;Gossip
          </div>
          <p className="footer__brand-desc">
            Tu plataforma para descubrir libros, gestionar estanterías y conectar con otros lectores en clubs de lectura.
          </p>
          <div className="footer__social">
            <a href="#" className="footer__social-link" aria-label="Instagram">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
            <a href="#" className="footer__social-link" aria-label="Twitter / X">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
          </div>
        </div>

        {/* Nav */}
        <div>
          <p className="footer__col-title">Navegación</p>
          <ul className="footer__links">
            <li><Link to="/">Inicio</Link></li>
            <li><Link to="/books">Buscar libros</Link></li>
            <li><Link to="/clubs">Clubs de lectura</Link></li>
            {user && (
              <>
                <li><Link to="/shelves">Mis estanterías</Link></li>
                <li><Link to="/profile">Mi perfil</Link></li>
              </>
            )}
          </ul>
        </div>

        {/* Account */}
        <div>
          <p className="footer__col-title">Cuenta</p>
          <ul className="footer__links">
            {user ? (
              <>
                <li><Link to="/profile">Configuración</Link></li>
              </>
            ) : (
              <>
                <li><Link to="/login">Iniciar sesión</Link></li>
                <li><Link to="/register">Crear cuenta</Link></li>
              </>
            )}
          </ul>
        </div>

      </div>

      <div className="footer__bottom">
        <span className="footer__copy">&copy; {year} Books&amp;Gossip — Todos los derechos reservados.</span>
        <span className="footer__tagline">Hecho con ♥ para lectores</span>
      </div>
    </footer>
  )
}
