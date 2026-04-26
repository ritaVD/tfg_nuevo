# Documentación Backend — TFGdaw

Bienvenido a la documentación técnica del backend de **TFGdaw**, una plataforma social de clubes de lectura construida con Symfony 7 y una API REST que consume un frontend en React.

---

## Índice de documentos

| # | Documento | Descripción |
|---|-----------|-------------|
| 1 | [Arquitectura general](01-arquitectura.md) | Stack tecnológico, estructura de carpetas y flujo de una petición |
| 2 | [Configuración](02-configuracion.md) | Archivos `.env`, bundles, paquetes y seguridad |
| 3 | [Modelo de datos — Entidades](03-entidades.md) | Todas las entidades Doctrine y sus relaciones |
| 4 | [Controladores API](04-controladores.md) | Endpoints REST: rutas, parámetros y respuestas |
| 5 | [Seguridad y autenticación](05-seguridad.md) | Login JSON, handlers, roles y autorización |
| 6 | [Repositorios](06-repositorios.md) | Consultas personalizadas a la base de datos |
| 7 | [Migraciones](07-migraciones.md) | Historial de cambios en el esquema de la base de datos |
| 8 | [Flujos principales](08-flujos-principales.md) | Casos de uso end-to-end: registro, posts, follows, clubes |
| 9 | [Integración Google Books](09-google-books.md) | Búsqueda, importación automática y re-ranking de libros |
| 10 | [Sistema de notificaciones](10-sistema-notificaciones.md) | Ciclo de vida completo de las notificaciones |
| 11 | [Gestión de imágenes](11-gestion-imagenes.md) | Subida, almacenamiento y eliminación de archivos |
| 12 | [Instalación y despliegue](12-despliegue.md) | Guía paso a paso para desarrollo y producción |
| 13 | [Módulo de Clubes](13-modulo-clubs.md) | Membresía, solicitudes, libro del mes y hilos de debate |
| 14 | [Módulo Social](14-modulo-social.md) | Posts, likes, comentarios y sistema de follows |
| 15 | [Módulo de Libros](15-modulo-libros.md) | Estanterías, progreso de lectura y reseñas |
| 16 | [Referencia JSON](16-referencia-json.md) | Estructura completa de todas las respuestas de la API |
| 17 | [Patrones de código](17-patrones-codigo.md) | Convenciones recurrentes: constructores, serialización, autorización, validación |
| 18 | [Optimización de consultas](18-optimizacion-consultas.md) | Problema N+1, batch queries, eager loading, paginación con Doctrine |
| 19 | [Modelo de privacidad](19-modelo-privacidad.md) | Flags `isPrivate`, `shelvesPublic`, `clubsPublic` y visibilidad de clubes |
| 20 | [Panel de administración](20-panel-administracion.md) | Endpoints admin: estadísticas, gestión de usuarios, clubes y posts |
| 21 | [Módulo de perfil de usuario](21-modulo-perfil-usuario.md) | Editar perfil, avatar, contraseña, privacidad, búsqueda y perfil público |
| 22 | [Registro, sesión y SPA](22-registro-y-sesion.md) | Registro con displayName único, login/logout con handlers, SpaController |
| 23 | [Repositorios — detalle de consultas](23-repositorios-detalle.md) | NotificationRepository, FollowRepository, UserRepository y patrones DQL avanzados |
| 24 | [Controlador de Posts](24-controlador-posts.md) | Feed, creación multipart, toggle like, comentarios, permisos de borrado |
| 25 | [Controlador de Follows](25-controlador-follows.md) | Seguir, dejar de seguir, expulsar, solicitudes para cuentas privadas |
| 26 | [Controlador de Clubes](26-controlador-clubs.md) | CRUD, membresía, solicitudes de ingreso, libro del mes con rango de fechas |
| 27 | [Controlador de Chat de Clubes](27-controlador-clubchat.md) | Hilos de debate, mensajes paginados, permisos por rol, helper resolveChat() |
| 28 | [Controlador de Reseñas](28-controlador-resenas.md) | Patrón upsert, reseña única por libro, estadísticas actualizadas en tiempo real |
| 29 | [Controlador de Progreso de Lectura](29-controlador-progreso-lectura.md) | Modos pages/percent, idempotencia al crear, computed percent, array_key_exists vs isset |
| 30 | [Controlador de Estanterías](30-controlador-estanterias.md) | CRUD estanterías, añadir/mover/quitar libros, auto-importación, ruta /full |
| 31 | [Manual de usuario](31-manual-usuario.md) | Guía paso a paso de todas las funcionalidades desde el punto de vista del usuario final |
| 32 | [Diagrama E/R y paso a tablas](32-paso-a-tablas.md) | Modelo Entidad-Relación completo y traducción a tablas relacionales con tipos, PKs, FKs y restricciones |
| 33 | [Accesibilidad WAI-A y comunicación asíncrona](33-accesibilidad-y-comunicacion.md) | Criterios WCAG 2.1 nivel A aplicados y documentación de la Fetch API asíncrona con validación cliente |
| 34 | [Usabilidad — Heurísticas de Nielsen](34-usabilidad.md) | Las 10 heurísticas de usabilidad de Jakob Nielsen aplicadas a TFGdaw con ejemplos de código |
| 35 | [Figuras para el TFG.docx](35-figuras-para-tfg-docx.md) | Pies de figura y párrafos de referencia listos para insertar en el documento Word del TFG |

---

## Resumen rápido

- **Framework:** Symfony 7
- **Base de datos:** MySQL / PostgreSQL a través de Doctrine ORM
- **Autenticación:** Sesiones PHP con login JSON
- **API externa:** Google Books API
- **Frontend:** React SPA servida desde `public/app/`
- **Almacenamiento de imágenes:** `public/uploads/posts/`

---

> Consulta cada documento en orden para obtener una comprensión completa del sistema.
