
  ---
  TFG — Checklist General

  ---
  BACKEND (Symfony – API REST)

  Base de Datos / Entidades

  - Tabla user (email, password_hash, roles, is_active,    
  timestamps)
  - Tabla user_profile (display_name, bio, avatar_url,     
  location) — 1:1 con user
  - Tabla book (title, authors, isbn10/13, cover_url,      
  description, publisher, published_date, language,        
  page_count, categories, external_source, external_id,    
  timestamps)
  - Tabla shelf (user_id, name, order_index, timestamps)   
  - Tabla shelf_book (shelf_id, book_id, order_index,      
  status, added_at) — UNIQUE(shelf_id, book_id)
  - Tabla club (name, description, owner_id, visibility    
  public/private, timestamps)
  - Tabla club_member (club_id, user_id, role enum:        
  member/admin, joined_at) — PK compuesta
  - Tabla club_join_request (club_id, user_id, status,     
  requested_at, resolved_at, resolved_by_user_id) —        
  UNIQUE(club_id, user_id)
  - Tabla club_monthly_books (club_id, book_id, month,     
  set_by_user_id, created_at) — UNIQUE(club_id, month)     
  - Tabla club_chat (club_id, title, created_by_user_id,   
  is_open, created_at, closed_at)
  - Tabla club_chat_message (chat_id, user_id, content,    
  created_at) — INDEX(chat_id, created_at)
  - Tabla book_search_cache (opcional) (query, source,     
  response_json, created_at, expires_at)

  Índices y constraints de books

  - UNIQUE(external_source, external_id)
  - UNIQUE(isbn13)
  - UNIQUE(isbn10)
  - INDEX(title)
  - FULLTEXT(title, description, authors) (opcional)       
  - created_at y updated_at automáticos (lifecycle
  callbacks)

  Seguridad / Auth

  - Login desde React → Symfony crea sesión con cookie     
  - Rutas públicas: listado de clubs, libros
  - Rutas protegidas (requieren auth): unirse a club, crear
   club, crear chat (solo admin), enviar mensajes,
  gestionar estanterías

  Endpoints — Usuarios / Perfil

  - GET /api/me — datos del usuario logueado
  - PUT /api/me/profile — actualizar perfil (bio, avatar,  
  display_name, location)
  - POST /api/register — registro
  - POST /api/login — login

  Endpoints — Libros / Buscador

  - GET /api/books?q=... — busca en BD local, si no hay    
  llama a Google Books API y guarda
  - GET /api/books/{id} — detalle de un libro

  Endpoints — Estanterías

  - GET /api/shelves — estanterías del usuario
  - POST /api/shelves — crear estantería
  - PUT /api/shelves/{id} — editar nombre/orden
  - DELETE /api/shelves/{id} — eliminar estantería
  - POST /api/shelves/{id}/books — añadir libro a
  estantería
  - DELETE /api/shelves/{id}/books/{bookId} — quitar libro 
  de estantería

  Endpoints — Clubs

  - GET /api/clubs — listar clubs (público)
  - POST /api/clubs — crear club (logueado)
  - GET /api/clubs/{id} — detalle del club
  - POST /api/clubs/{id}/join — unirse (público→miembro,   
  privado→solicitud)
  - GET /api/clubs/{id}/requests — ver solicitudes (solo   
  admin)
  - POST /api/clubs/{id}/requests/{requestId}/approve —    
  aprobar (solo admin)
  - POST /api/clubs/{id}/requests/{requestId}/reject —     
  rechazar (solo admin)
  - POST /api/clubs/{id}/monthly-book — fijar libro del mes
   (solo admin)
  - GET /api/clubs/{id}/monthly-books — histórico de libros
   mensuales

  Endpoints — Chats (polling)

  - POST /api/clubs/{id}/chats — crear chat (solo admin)   
  - GET /api/chats/{chatId}/messages?after=timestamp —     
  mensajes nuevos (logueado y miembro)
  - POST /api/chats/{chatId}/messages — enviar mensaje     
  (logueado y miembro)

  Integración externa

  - Integración con Google Books API para búsqueda de      
  libros
  - Cache de búsquedas en BD (book_search_cache) (opcional)

  ---
  FRONTEND (React)

  Configuración / Infraestructura

  - Setup proyecto React
  - Configurar axios/fetch con base URL de la API Symfony  
  - Gestión de sesión/auth (cookie) — detectar si está     
  logueado
  - Routing (React Router): /, /clubs, /shelves, /profile  

  Página Principal (Home)

  - Sección de bienvenida — qué es la web y cómo funciona  
  - Sección de libros más leídos / destacados

  Página de Clubes de Lectura

  - Listado de clubs disponibles (público)
  - Botón "Unirse" (club público) / "Solicitar unirse"     
  (club privado)
  - Vista de detalle del club (libro del mes, miembros,    
  chats)
  - Panel admin: gestionar solicitudes de ingreso
  (aprobar/rechazar)
  - Panel admin: crear nuevo chat
  - Panel admin: fijar libro mensual
  - Histórico de libros mensuales del club

  Chat del Club

  - Listado de chats del club
  - Vista de mensajes de un chat
  - Polling cada 2-3 segundos para mensajes nuevos
  - Formulario para enviar mensaje
  - Cache de resultados en localStorage

  Página de Estanterías

  - Listado de estanterías del usuario
  - Crear nueva estantería
  - Renombrar / eliminar estantería
  - Buscador de libros (con cache en localStorage)
  - Añadir libro a una o varias estanterías
  - Mover libro entre estanterías
  - Quitar libro de una estantería

  Página de Perfil de Usuario

  - Ver información del perfil (display_name, bio, avatar, 
  location)
  - Editar foto de perfil
  - Editar bio, nombre y ubicación
  - Ver estanterías propias desde el perfil

  ---
  ▎ Nota: Las tablas marcadas con [x] ya aparecen creadas  
  en la BD según la captura del PDF. El resto está
  pendiente de implementar.