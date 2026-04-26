# 35 — Figuras para el TFG.docx

Este documento contiene los pies de figura y los párrafos de referencia ya redactados para cada imagen que debe insertarse en el documento TFG.docx. El proceso es el siguiente:

1. Insertar la imagen en el punto indicado dentro del Word.
2. Añadir debajo el pie de figura correspondiente (en negrita o con el estilo de leyenda del Word).
3. Copiar el párrafo de referencia al texto principal donde se indica.

Los números de figura deben ajustarse si el orden de inserción varía respecto al indicado aquí.

---

## FIGURA 1 — Diagrama Entidad-Relación

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Modelo de datos" o "Base de datos".

**Imagen a insertar:** Captura del diagrama E/R creado con draw.io / ERDPlus / DBDiagram con las 16 entidades del sistema y sus relaciones.

**Pie de figura:**
> Figura 1: Diagrama Entidad-Relación del sistema TFGdaw. Se muestran las 16 entidades del modelo de datos y sus relaciones de cardinalidad.

**Párrafo de referencia para el texto:**
> La Figura 1 muestra el diagrama Entidad-Relación completo del sistema. En él se observa cómo la entidad central `User` se relaciona con el resto de entidades del sistema: estanterías (`Shelf`), libros (`Book`), clubs (`Club`), publicaciones (`Post`) y seguimientos (`Follow`). El diagrama completo, junto con el paso a tablas detallado, se documenta en el Anexo técnico correspondiente.

---

## FIGURA 2 — Mapa de navegación

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Mapa de navegación".

**Imagen a insertar:** Diagrama de flujo con la estructura de rutas del frontend: `/`, `/login`, `/register`, `/books`, `/books/:id`, `/clubs`, `/clubs/:id`, `/shelves`, `/profile`, `/users/:id`, `/admin`. Puede hacerse con draw.io, Figma o cualquier herramienta de diagramas. Indicar qué rutas son públicas y cuáles requieren autenticación (con un color diferente o un icono de candado).

**Pie de figura:**
> Figura 2: Mapa de navegación de la aplicación TFGdaw. Las rutas marcadas en gris requieren sesión activa (ROLE_USER); la ruta del panel de administración requiere además ROLE_ADMIN.

**Párrafo de referencia para el texto:**
> Como se puede observar en la Figura 2, la aplicación cuenta con diez rutas principales. Las rutas públicas (página de inicio, buscador de libros, listado de clubs y perfiles de usuario) son accesibles para cualquier visitante sin necesidad de cuenta. Las rutas privadas (estanterías, perfil propio y panel de administración) están protegidas por el componente `PrivateRoute`, que redirige automáticamente al formulario de inicio de sesión si no hay sesión activa.

---

## FIGURA 3 — Página de inicio (HomePage)

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Diseño de pantallas" o "Páginas implementadas".

**Imagen a insertar:** Captura de pantalla de la página de inicio (`/`) con sesión iniciada, mostrando el hero, el feed de publicaciones y la sección de características.

**Pie de figura:**
> Figura 3: Página de inicio de TFGdaw. La sección superior muestra el hero con las llamadas a la acción; el cuerpo central muestra el feed de publicaciones de los usuarios seguidos.

**Párrafo de referencia para el texto:**
> La Figura 3 muestra la página de inicio de la plataforma tal como la ve un usuario autenticado. En la parte superior se encuentra el bloque hero con el nombre de la aplicación y accesos directos a las secciones principales. A continuación, el feed personalizado muestra las publicaciones más recientes de los usuarios que sigue, ordenadas cronológicamente de más reciente a más antigua.

---

## FIGURA 4 — Buscador de libros (BooksPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de la página `/books` con resultados de búsqueda visibles (por ejemplo, búsqueda de "dune").

**Pie de figura:**
> Figura 4: Buscador de libros integrado con la Google Books API. Los resultados se muestran en tarjetas con portada, autores y año de publicación.

**Párrafo de referencia para el texto:**
> La Figura 4 ilustra la funcionalidad de búsqueda de libros. El buscador consulta en tiempo real la Google Books API a través del backend, mostrando los resultados como tarjetas con portada, título, autores y año. Los usuarios autenticados pueden añadir cualquier libro a sus estanterías directamente desde el resultado, sin necesidad de navegar a la página de detalle.

---

## FIGURA 5 — Detalle de un libro (BookDetailPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de `/books/:externalId` mostrando portada, metadatos, sinopsis, formulario de reseña y valoraciones de la comunidad.

**Pie de figura:**
> Figura 5: Página de detalle de un libro. Muestra los metadatos completos del volumen, las valoraciones de la comunidad con puntuación media y el formulario de reseña para usuarios autenticados.

**Párrafo de referencia para el texto:**
> Como se observa en la Figura 5, la página de detalle de un libro presenta toda la información disponible del volumen: portada ampliada, metadatos (editorial, fecha, páginas, idioma, ISBNs), sinopsis completa y la sección de valoraciones de la comunidad con la puntuación media, el número de reseñas y las opiniones individuales de otros usuarios.

---

## FIGURA 6 — Mis estanterías (ShelvesPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de `/shelves` mostrando el sidebar con las estanterías y el panel principal con los libros de la estantería seleccionada. Idealmente con el tracker de lectura visible en la parte superior.

**Pie de figura:**
> Figura 6: Página de gestión de estanterías. El panel lateral lista las estanterías del usuario; el panel principal muestra los libros de la estantería seleccionada con sus estados de lectura. En la parte superior se muestra el tracker de lectura activa.

**Párrafo de referencia para el texto:**
> La Figura 6 muestra la página de estanterías, que es la sección central de la biblioteca personal del usuario. El layout se divide en un sidebar de navegación entre estanterías y un panel principal donde se gestionan los libros. En la parte superior del panel se encuentra el tracker de lectura activa, que muestra el progreso de los libros que el usuario está leyendo actualmente con una barra visual y controles de actualización.

---

## FIGURA 7 — Clubs de lectura (ClubsPage y ClubDetailPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura del listado de clubs (`/clubs`) o de la página de detalle de un club (`/clubs/:id`) con las pestañas Chats, Miembros y Solicitudes.

**Pie de figura:**
> Figura 7: Página de detalle de un club de lectura. Se muestran las tres pestañas de navegación interna (Chats, Miembros, Solicitudes) y el panel con el libro del mes activo.

**Párrafo de referencia para el texto:**
> La Figura 7 muestra la página de detalle de un club de lectura. La información del club (nombre, descripción, visibilidad, número de miembros y libro del mes) se presenta en la cabecera. El contenido se organiza en tres pestañas: Chats, con los hilos de debate disponibles para los miembros; Miembros, con la lista de participantes y sus roles; y Solicitudes, visible únicamente para los administradores del club en clubes privados.

---

## FIGURA 8 — Mi perfil (ProfilePage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de `/profile` mostrando el avatar, nombre, bio, contadores de seguidores/seguidos y las secciones de edición.

**Pie de figura:**
> Figura 8: Página de perfil propio. Muestra la información personal del usuario, los contadores de seguidores y seguidos, la sección de publicaciones propias y las opciones de configuración de privacidad.

**Párrafo de referencia para el texto:**
> La Figura 8 ilustra la página de perfil del usuario autenticado. Desde esta sección el usuario puede editar su información personal (nombre visible, biografía y avatar), consultar y gestionar su lista de seguidores y seguidos, configurar las opciones de privacidad de la cuenta y publicar nuevas entradas en su perfil con imagen adjunta.

---

## FIGURA 9 — Diseño responsive (versión móvil)

**Dónde insertar:** Sección "Diseño de Interfaces" → subsección "Diseño responsive".

**Imagen a insertar:** Dos capturas en paralelo: la misma página (por ejemplo, clubs o home) en escritorio (1280px) y en móvil (375px), mostrando la adaptación del layout.

**Pie de figura:**
> Figura 9: Comparativa del diseño responsive de TFGdaw. La imagen izquierda muestra la vista en escritorio (1280px); la imagen derecha muestra la adaptación a dispositivo móvil (375px) con menú hamburguesa y layout de columna única.

**Párrafo de referencia para el texto:**
> La Figura 9 ilustra la adaptación del diseño a diferentes tamaños de pantalla. En la vista de escritorio se aprovecha el ancho disponible con layouts de múltiples columnas y el sidebar de navegación siempre visible. En la vista móvil, la barra de navegación se contrae en un menú hamburguesa, los layouts pasan a una única columna y el tamaño de los elementos interactivos aumenta para facilitar la interacción táctil. Este comportamiento se implementa exclusivamente con media queries CSS, sin dependencia de ningún framework externo.

---

## FIGURA 10 — Panel de administración (AdminPage)

**Dónde insertar:** Sección "Diseño de pantallas" o "Manual de administración".

**Imagen a insertar:** Captura de `/admin` mostrando las estadísticas globales y las secciones de gestión de usuarios, clubs y publicaciones.

**Pie de figura:**
> Figura 10: Panel de administración de la plataforma. Muestra las estadísticas globales del sistema (usuarios, libros, clubs, publicaciones y reseñas) y las herramientas de gestión de usuarios y contenido.

**Párrafo de referencia para el texto:**
> La Figura 10 muestra el panel de administración, accesible exclusivamente a los usuarios con rol `ROLE_ADMIN`. La sección de estadísticas ofrece una visión global del estado de la plataforma con los totales de usuarios registrados, libros importados, clubs creados, publicaciones activas y reseñas escritas. Las secciones de gestión permiten al administrador activar o desactivar cuentas de usuario, promover usuarios a administrador, eliminar clubs y moderar publicaciones inapropiadas.

---

## FIGURA 11 — Arquitectura del sistema

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Arquitectura".

**Imagen a insertar:** Diagrama de bloques con las tres capas: Navegador (React SPA) → Backend Symfony (API REST, Controladores, Entidades, BD) → Google Books API. Puede crearse con draw.io en 10 minutos.

**Pie de figura:**
> Figura 11: Arquitectura del sistema TFGdaw. Se representan las tres capas principales: el cliente React en el navegador, el backend Symfony con su API REST y base de datos, y la integración con la Google Books API como servicio externo.

**Párrafo de referencia para el texto:**
> La Figura 11 representa la arquitectura general del sistema. El frontend React se ejecuta en el navegador del usuario como una SPA, comunicándose de forma asíncrona con el backend Symfony mediante peticiones HTTP a la API REST. El backend gestiona la autenticación, la lógica de negocio y el acceso a la base de datos MySQL. Para la búsqueda de libros, el backend actúa como proxy hacia la Google Books API, importando y cacheando los resultados en la base de datos local para reducir la dependencia de servicios externos.

---

## Checklist de figuras para el TFG.docx

| # | Figura | ¿Insertada? |
|---|--------|-------------|
| 1 | Diagrama E/R | ☐ |
| 2 | Mapa de navegación | ☐ |
| 3 | Página de inicio | ☐ |
| 4 | Buscador de libros | ☐ |
| 5 | Detalle de libro | ☐ |
| 6 | Mis estanterías | ☐ |
| 7 | Club de lectura | ☐ |
| 8 | Mi perfil | ☐ |
| 9 | Diseño responsive | ☐ |
| 10 | Panel de administración | ☐ |
| 11 | Arquitectura del sistema | ☐ |
