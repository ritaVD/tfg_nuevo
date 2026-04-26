# 33 — Accesibilidad WAI-A y Comunicación Asíncrona

Este documento acredita el cumplimiento de dos requisitos técnicos obligatorios del ciclo DAW: el nivel mínimo de accesibilidad web **WAI-A** (WCAG 2.1, nivel A) y el uso de **comunicación asíncrona** entre cliente y servidor.

---

## 1. Accesibilidad web — Nivel WAI-A (WCAG 2.1)

Las Pautas de Accesibilidad para el Contenido Web (WCAG 2.1), publicadas por el W3C, organizan los criterios de accesibilidad en tres niveles: A (mínimo), AA (recomendado) y AAA (avanzado). El nivel A es el umbral mínimo exigido en el ciclo DAW y constituye el objetivo de este proyecto.

A continuación se detallan los criterios de nivel A aplicados en TFGdaw, organizados por principio WCAG.

---

### 1.1 Principio: Perceptible

Los contenidos deben poder ser percibidos por todos los usuarios, independientemente de sus capacidades sensoriales.

**Criterio 1.1.1 — Contenido no textual (Nivel A)**

Todas las imágenes de la aplicación incluyen texto alternativo (`alt`) descriptivo:

```html
<!-- Portada de libro en BookDetailPage.tsx -->
<img
  src={coverUrl}
  alt={`Portada de ${book.title}`}
  className="book-cover"
/>

<!-- Avatar de usuario en PostCard.tsx -->
<img
  src={avatarUrl}
  alt={`Avatar de ${user.displayName}`}
  className="avatar"
/>
```

Las imágenes puramente decorativas (fondos, separadores) se implementan mediante CSS (`background-image`) o con `alt=""` vacío, para que los lectores de pantalla las ignoren.

**Criterio 1.3.1 — Información y relaciones (Nivel A)**

La estructura de la interfaz se comunica mediante marcado semántico HTML5:

- `<header>` para la barra de navegación principal.
- `<main>` para el contenido principal de cada página.
- `<nav>` para los menús de navegación.
- `<section>` y `<article>` para agrupar contenido relacionado.
- `<h1>`, `<h2>`, `<h3>` con jerarquía coherente en cada página.
- `<ul>` y `<li>` para listas de resultados, miembros y notificaciones.
- `<form>`, `<label>`, `<input>` con asociación explícita mediante `htmlFor` / `id`.

Ejemplo de formulario correctamente etiquetado en `LoginPage.tsx`:

```tsx
<form onSubmit={handleSubmit}>
  <div className="form-group">
    <label htmlFor="email" className="form-label">
      Correo electrónico
    </label>
    <input
      id="email"
      type="email"
      className="form-control"
      value={email}
      onChange={e => setEmail(e.target.value)}
      required
      autoComplete="email"
    />
  </div>
  <div className="form-group">
    <label htmlFor="password" className="form-label">
      Contraseña
    </label>
    <input
      id="password"
      type="password"
      className="form-control"
      value={password}
      onChange={e => setPassword(e.target.value)}
      required
      autoComplete="current-password"
    />
  </div>
  <button type="submit" className="btn btn-primary">
    Entrar
  </button>
</form>
```

**Criterio 1.4.1 — Uso del color (Nivel A)**

El color nunca es el único medio para transmitir información. Los estados de lectura (`want_to_read`, `reading`, `read`) se distinguen tanto por color como por etiqueta de texto. Los errores de validación se indican con un icono y un texto descriptivo, no solo con borde rojo.

---

### 1.2 Principio: Operable

Los componentes de la interfaz deben poder ser operados por todos los usuarios.

**Criterio 2.1.1 — Teclado (Nivel A)**

Todos los elementos interactivos (botones, enlaces, campos de formulario, selects, checkboxes y toggles) son accesibles mediante teclado. El orden de tabulación (`:focus`) sigue el flujo visual de la página de arriba a abajo y de izquierda a derecha, sin trampas de teclado.

Los modales (lista de seguidores, formulario de nuevo club, etc.) gestionan el foco correctamente: al abrirse, el foco se mueve al interior del modal; al cerrarse, vuelve al elemento que lo abrió.

**Criterio 2.4.2 — Título de página (Nivel A)**

Cada página tiene un título descriptivo único en el `<title>` del documento HTML:

| Ruta | Título |
|------|--------|
| `/` | TFGdaw — Plataforma de Clubes de Lectura |
| `/login` | Iniciar sesión — TFGdaw |
| `/register` | Crear cuenta — TFGdaw |
| `/books` | Buscar libros — TFGdaw |
| `/clubs` | Clubs de lectura — TFGdaw |
| `/shelves` | Mis estanterías — TFGdaw |
| `/profile` | Mi perfil — TFGdaw |

**Criterio 2.4.3 — Orden del foco (Nivel A)**

El orden de los elementos en el DOM coincide con el orden visual, por lo que el recorrido con tabulador es predecible y coherente en todas las páginas.

---

### 1.3 Principio: Comprensible

El contenido y el funcionamiento de la interfaz deben ser comprensibles.

**Criterio 3.1.1 — Idioma de la página (Nivel A)**

El atributo `lang` del elemento `<html>` está definido como `es` (español):

```html
<!-- index.html -->
<html lang="es">
```

**Criterio 3.3.1 — Identificación de errores (Nivel A)**

Cuando un formulario se envía con datos inválidos, se muestra un mensaje de error descriptivo junto al campo afectado. Los mensajes indican qué campo tiene el error y por qué, no solo que hay un error genérico.

Ejemplo en `RegisterPage.tsx`:

```tsx
{errors.password && (
  <span className="field-error" role="alert">
    {errors.password}
  </span>
)}
```

El atributo `role="alert"` hace que los lectores de pantalla anuncien el error automáticamente al aparecer en el DOM.

**Criterio 3.3.2 — Etiquetas o instrucciones (Nivel A)**

Todos los campos de formulario tienen una etiqueta (`<label>`) visible y asociada. Los campos con restricciones de formato incluyen texto de ayuda o placeholder que indica el formato esperado (por ejemplo, "mínimo 6 caracteres" en el campo de contraseña).

---

### 1.4 Principio: Robusto

El contenido debe poder ser interpretado de forma fiable por una amplia variedad de agentes de usuario.

**Criterio 4.1.1 — Análisis sintáctico (Nivel A)**

El HTML generado por React no contiene errores de anidamiento ni atributos duplicados. Los elementos interactivos personalizados (botones de like, toggles de privacidad) son implementados con elementos nativos HTML (`<button>`, `<input type="checkbox">`) en lugar de `<div>` con `onClick`, lo que garantiza la compatibilidad con tecnologías de asistencia.

**Criterio 4.1.2 — Nombre, función, valor (Nivel A)**

Los elementos interactivos cuentan con nombres accesibles. Los iconos SVG sin texto visible incluyen `aria-label` o están acompañados de un `<span className="sr-only">` (visualmente oculto pero leído por lectores de pantalla):

```tsx
<!-- Botón de like con icono SVG -->
<button
  className="like-btn"
  onClick={handleLike}
  aria-label={liked ? 'Quitar me gusta' : 'Dar me gusta'}
  aria-pressed={liked}
>
  <HeartIcon />
</button>
```

---

### 1.5 Diseño responsive como complemento a la accesibilidad

El diseño responsive no es en sí mismo un criterio de accesibilidad WCAG, pero contribuye significativamente a garantizar que los usuarios con baja visión que aumentan el zoom del navegador o usan dispositivos de pantalla pequeña puedan acceder al contenido sin pérdida de información.

La aplicación se ha probado en los siguientes tamaños de pantalla:

| Dispositivo | Ancho | Comportamiento |
|-------------|-------|----------------|
| Móvil pequeño | 320px | Una columna, texto legible, sin scroll horizontal |
| Móvil estándar | 375px | Una columna, navbar colapsada |
| Tablet | 768px | Dos columnas en listados, sidebar visible |
| Desktop | 1280px | Layout completo con sidebar y panel principal |
| Desktop wide | 1920px | Max-width aplicado para evitar líneas de texto excesivamente largas |

---

## 2. Comunicación asíncrona con el servidor

El criterio de *Entornos Cliente* del ciclo DAW exige el uso de mecanismos de comunicación asíncrona con el servidor. TFGdaw implementa esta comunicación mediante la **Fetch API** de JavaScript de forma extensiva en todo el frontend.

---

### 2.1 Patrón general: Fetch API con async/await

Todas las peticiones al backend se realizan de forma asíncrona usando la Fetch API nativa del navegador, envueltas en funciones con `async/await` para simplificar el manejo de promesas y errores:

```typescript
// src/api/client.ts — función base de todas las peticiones
export async function apiFetch<T>(
  path: string,
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' = 'GET',
  body?: unknown
): Promise<T> {
  const options: RequestInit = {
    method,
    credentials: 'include',           // enviar cookie de sesión
    headers: { 'Content-Type': 'application/json' },
  }
  if (body !== undefined) {
    options.body = JSON.stringify(body)
  }
  const res = await fetch(`/api${path}`, options)
  if (res.status === 204) return undefined as T
  const data = await res.json()
  if (!res.ok) throw new Error(data.error ?? 'Error desconocido')
  return data as T
}
```

La función `apiFetch` es el único punto de acceso a la API en todo el frontend. Centraliza el manejo de cookies, los headers, la serialización del body y la propagación de errores, evitando código duplicado en cada módulo.

---

### 2.2 Comunicación asíncrona en los componentes React

Los componentes React realizan peticiones asíncronas al montarse (`useEffect`) o en respuesta a acciones del usuario (handlers). La interfaz permanece responsive durante la carga gracias al estado `loading` y al componente `<Spinner>`:

```typescript
// Ejemplo: carga del feed en HomePage.tsx
useEffect(() => {
  if (!user) return
  setLoading(true)
  postsApi.feed()
    .then(data => setPosts(data))
    .catch(() => setError('No se pudo cargar el feed'))
    .finally(() => setLoading(false))
}, [user])
```

El usuario ve un indicador de carga mientras la petición está en vuelo, y un mensaje de error si falla, sin que la página se congele en ningún momento.

---

### 2.3 Actualización optimista de la interfaz

Para operaciones de alta frecuencia como los likes, la interfaz se actualiza de forma **optimista**: el estado visual cambia inmediatamente al hacer clic, sin esperar la respuesta del servidor. Si el servidor devuelve un error, el estado se revierte:

```typescript
// PostCard.tsx — like optimista
const handleLike = async () => {
  // actualización inmediata en la UI
  setLocalPost(prev => ({
    ...prev,
    liked: !prev.liked,
    likes: prev.liked ? prev.likes - 1 : prev.likes + 1,
  }))
  try {
    const result = await postsApi.like(post.id)
    // confirmar con el valor real del servidor
    setLocalPost(prev => ({ ...prev, liked: result.liked, likes: result.likes }))
  } catch {
    // revertir si falla
    setLocalPost(prev => ({
      ...prev,
      liked: !prev.liked,
      likes: prev.liked ? prev.likes - 1 : prev.likes + 1,
    }))
  }
}
```

---

### 2.4 Carga diferida de contenido

Los comentarios de las publicaciones no se cargan al renderizar la tarjeta, sino únicamente cuando el usuario despliega la sección de comentarios por primera vez. Esto reduce la carga inicial de la página y evita peticiones innecesarias:

```typescript
// PostCard.tsx — carga diferida de comentarios
const handleToggleComments = async () => {
  if (!showComments && !commentsLoaded) {
    const data = await postsApi.comments(post.id)
    setComments(data)
    setCommentsLoaded(true)
  }
  setShowComments(prev => !prev)
}
```

---

### 2.5 Módulos de API organizados por dominio

Para mantener el código organizado y evitar que los componentes React contengan lógica de red, todas las llamadas HTTP se encapsulan en módulos dedicados en `src/api/`:

| Módulo | Peticiones que gestiona |
|--------|------------------------|
| `auth.ts` | Login, logout, registro, `/me` |
| `books.ts` | Búsqueda y detalle de libros |
| `shelves.ts` | CRUD de estanterías y gestión de libros |
| `clubs.ts` | CRUD de clubs, membresías, libro del mes |
| `chats.ts` | Hilos y mensajes de club |
| `profile.ts` | Perfil, avatar, privacidad, contraseña |
| `reviews.ts` | Reseñas por libro |
| `readingProgress.ts` | Tracker de lectura |
| `posts.ts` | Feed, publicaciones, likes, comentarios |
| `follow.ts` | Seguir, dejar de seguir, seguidores, siguiendo |

Esta separación hace que cada componente React importe únicamente el módulo que necesita, manteniendo el código desacoplado y fácil de mantener.

---

### 2.6 Validación en el cliente antes de enviar al servidor

Antes de realizar cualquier petición al backend, los formularios validan los datos en el cliente para proporcionar retroalimentación inmediata y evitar peticiones con datos claramente incorrectos:

```typescript
// RegisterPage.tsx — validación cliente antes del fetch
const handleSubmit = async (e: FormEvent) => {
  e.preventDefault()
  const newErrors: Record<string, string> = {}

  if (!email.includes('@')) {
    newErrors.email = 'Introduce un email válido'
  }
  if (password.length < 6) {
    newErrors.password = 'La contraseña debe tener al menos 6 caracteres'
  }
  if (password !== confirmPassword) {
    newErrors.confirmPassword = 'Las contraseñas no coinciden'
  }
  if (Object.keys(newErrors).length > 0) {
    setErrors(newErrors)
    return   // no se realiza la petición
  }

  // si pasa la validación, enviar al servidor
  await authApi.register(email, password)
}
```

La validación en el cliente **no sustituye** a la validación en el servidor: el backend valida de nuevo todos los datos recibidos. La validación en el cliente sirve únicamente para mejorar la experiencia de usuario al detectar errores antes de la petición de red.
