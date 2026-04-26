# 31 — Manual de Usuario

Este manual describe paso a paso cómo utilizar la plataforma **TFGdaw** desde el punto de vista del usuario final. Está orientado a personas sin conocimientos técnicos y cubre todas las funcionalidades disponibles.

---

## Índice

1. [Introducción a la aplicación](#1-introducción-a-la-aplicación)
2. [Requisitos para el uso](#2-requisitos-para-el-uso)
3. [Registro e inicio de sesión](#3-registro-e-inicio-de-sesión)
4. [Navegación general](#4-navegación-general)
5. [Mi perfil](#5-mi-perfil)
6. [Búsqueda de libros](#6-búsqueda-de-libros)
7. [Mis estanterías](#7-mis-estanterías)
8. [Tracker de lectura](#8-tracker-de-lectura)
9. [Reseñas de libros](#9-reseñas-de-libros)
10. [Clubs de lectura](#10-clubs-de-lectura)
11. [Chats de club](#11-chats-de-club)
12. [Red social: publicaciones y seguimientos](#12-red-social-publicaciones-y-seguimientos)
13. [Notificaciones](#13-notificaciones)
14. [Configuración de privacidad](#14-configuración-de-privacidad)
15. [Preguntas frecuentes](#15-preguntas-frecuentes)

---

## 1. Introducción a la aplicación

TFGdaw es una plataforma web para lectores que combina la gestión personal de libros con funcionalidades de red social y clubs de lectura. Con ella se puede:

- Organizar los libros leídos, en lectura y pendientes en estanterías personalizadas.
- Llevar un registro del progreso de lectura de cada libro.
- Descubrir nuevos títulos buscando en un catálogo de millones de libros.
- Publicar reseñas y ver las valoraciones de la comunidad.
- Seguir a otros lectores y ver sus publicaciones en un feed personalizado.
- Unirse a clubs de lectura y participar en debates organizados por capítulos o temas.

La aplicación funciona directamente en el navegador; no es necesario instalar nada en el dispositivo.

---

## 2. Requisitos para el uso

Para utilizar la plataforma se necesita:

- Un navegador web actualizado: Google Chrome 110+, Mozilla Firefox 110+, Microsoft Edge 110+ o Safari 16+.
- Conexión a Internet.
- Una dirección de correo electrónico válida para el registro.

La aplicación es **responsive**: se adapta a pantallas de ordenador, tablet y móvil. No existe versión nativa de aplicación móvil; se accede siempre desde el navegador.

---

## 3. Registro e inicio de sesión

### 3.1 Crear una cuenta nueva

1. Acceder a la página de inicio de la aplicación.
2. Hacer clic en el botón **"Registrarse"** de la barra de navegación o en el hero de la página principal.
3. Completar el formulario de registro:
   - **Email**: dirección de correo electrónico. Debe ser única en el sistema.
   - **Contraseña**: mínimo 6 caracteres. Se recomienda usar una combinación de letras, números y símbolos.
   - **Confirmar contraseña**: escribir la misma contraseña para verificar que no haya errores tipográficos.
4. Hacer clic en **"Crear cuenta"**.
5. Si todos los datos son correctos, la cuenta se creará automáticamente y se redirigirá a la página de inicio de sesión.

> **Nota:** El nombre de usuario visible (el que verán los demás) se genera automáticamente a partir del email. Podrá cambiarse en cualquier momento desde el perfil.

**Errores frecuentes en el registro:**

| Error mostrado | Causa | Solución |
|----------------|-------|----------|
| "Este email ya está registrado" | La dirección ya tiene cuenta | Usar otro email o recuperar la contraseña |
| "La contraseña debe tener al menos 6 caracteres" | Contraseña demasiado corta | Introducir una contraseña más larga |
| "Las contraseñas no coinciden" | Error tipográfico en la confirmación | Volver a escribir la contraseña con cuidado |

---

### 3.2 Iniciar sesión

1. Hacer clic en **"Iniciar sesión"** en la barra de navegación.
2. Introducir el **email** y la **contraseña** registrados.
3. Hacer clic en **"Entrar"**.
4. Tras un inicio de sesión correcto, se redirigirá a la página principal con el feed de publicaciones visible.

La sesión se mantiene activa aunque se cierre el navegador o se recargue la página. No es necesario volver a iniciar sesión salvo que se cierre la sesión manualmente o transcurra un tiempo prolongado de inactividad.

---

### 3.3 Cerrar sesión

1. En la barra de navegación, hacer clic sobre el icono de usuario o el nombre visible.
2. Seleccionar **"Cerrar sesión"** en el menú desplegable.
3. La sesión quedará cerrada y se redirigirá a la página de inicio.

> Al cerrar sesión se eliminan los datos de autenticación del navegador. Será necesario volver a introducir las credenciales para acceder a contenido privado.

---

## 4. Navegación general

La barra de navegación superior está presente en todas las páginas y ofrece acceso directo a las secciones principales:

| Elemento | Destino | Disponible para |
|----------|---------|-----------------|
| Logotipo / nombre de la app | Página de inicio (`/`) | Todos |
| "Libros" | Buscador de libros (`/books`) | Todos |
| "Clubs" | Listado de clubs (`/clubs`) | Todos |
| "Mis estanterías" | Gestión de estanterías (`/shelves`) | Usuarios con sesión |
| Icono de usuario | Perfil y opciones de cuenta | Usuarios con sesión |
| "Registrarse" / "Iniciar sesión" | Formularios de acceso | Visitantes |

En **dispositivos móviles y tablets**, la barra de navegación se contrae en un menú hamburguesa (icono de tres líneas horizontales). Al pulsarlo se despliegan todas las opciones de navegación.

---

## 5. Mi perfil

El perfil es la sección personal donde se gestiona la información de la cuenta, las publicaciones propias y la configuración de privacidad.

Para acceder: hacer clic en el icono de usuario de la barra de navegación y seleccionar **"Mi perfil"**, o navegar directamente a `/profile`.

---

### 5.1 Editar información personal

En la sección **"Información personal"** se puede:

- **Nombre visible**: el nombre que verán los demás usuarios. Puede contener letras, números y guiones bajos. Debe ser único en la plataforma.
- **Biografía**: texto libre de hasta 255 caracteres para presentarse. Aparece en el perfil público.

Para guardar los cambios, hacer clic en **"Guardar cambios"** después de editar los campos deseados.

---

### 5.2 Cambiar el avatar

El avatar es la imagen que representa al usuario en publicaciones, comentarios y en el perfil.

1. En la sección de perfil, situar el cursor sobre la imagen de avatar actual.
2. Aparecerá un icono de cámara superpuesto.
3. Hacer clic sobre él para abrir el selector de archivos.
4. Seleccionar una imagen del dispositivo (formatos admitidos: JPG, PNG, GIF, WEBP; tamaño recomendado: al menos 200×200 píxeles).
5. La imagen se subirá y actualizará automáticamente.

Si no se ha subido ningún avatar, se muestra una imagen generada automáticamente con las iniciales del nombre visible.

---

### 5.3 Cambiar la contraseña

1. En el perfil, localizar la sección **"Cambiar contraseña"**.
2. Introducir la **contraseña actual** para confirmar la identidad.
3. Introducir la **nueva contraseña** (mínimo 6 caracteres).
4. Confirmar la nueva contraseña.
5. Hacer clic en **"Actualizar contraseña"**.

Si la contraseña actual no es correcta, se mostrará un mensaje de error y no se realizará el cambio.

---

### 5.4 Ver seguidores y seguidos

En la parte superior del perfil se muestran los contadores de **Seguidores** y **Siguiendo**. Al hacer clic sobre cualquiera de ellos se abre un modal con la lista de usuarios correspondiente.

Desde la lista de **seguidores** propios es posible eliminar a un seguidor haciendo clic en el botón con el icono de cruz (✕) que aparece junto a su nombre. El seguidor desaparecerá de la lista sin que reciba ninguna notificación.

---

## 6. Búsqueda de libros

La búsqueda de libros permite explorar el catálogo de Google Books directamente desde la plataforma. No es necesario tener sesión iniciada para buscar.

Para acceder: hacer clic en **"Libros"** en la barra de navegación.

---

### 6.1 Realizar una búsqueda

1. Escribir el término de búsqueda en el campo de texto (título, autor, ISBN, etc.).
2. Los resultados aparecen automáticamente mientras se escribe, sin necesidad de pulsar ningún botón.
3. Cada resultado muestra: portada del libro, título, autores, año de publicación y un extracto de la descripción.
4. Hacer clic sobre cualquier tarjeta de resultado para ver el detalle completo del libro.

---

### 6.2 Ver el detalle de un libro

La página de detalle muestra:

- **Portada** en tamaño ampliado.
- **Metadatos completos**: título, subtítulo, autores, editorial, fecha de publicación, número de páginas, idioma, categorías e ISBNs.
- **Sinopsis** del libro.
- **Valoración de la comunidad**: puntuación media con estrellas y número de reseñas.
- **Reseñas individuales** de otros usuarios de la plataforma.

Si se tiene sesión iniciada, aparecen también los botones de acción:

- **"+ Añadir a estantería"**: permite añadir el libro a una de las estanterías propias.
- **"Estoy leyendo"**: activa el tracker de lectura activa para este libro.

---

### 6.3 Añadir un libro a una estantería desde la búsqueda

1. En la página de detalle del libro, hacer clic en **"+ Añadir a estantería"**.
2. Se abre un panel lateral con la lista de estanterías disponibles.
3. Seleccionar la estantería deseada.
4. Opcionalmente, elegir el estado de lectura: **Quiero leer**, **Leyendo** o **Leído**.
5. Hacer clic en **"Añadir"**.

El libro quedará guardado en la estantería seleccionada y podrá consultarse en cualquier momento desde la sección "Mis estanterías".

---

## 7. Mis estanterías

Las estanterías son colecciones personales de libros organizadas por el propio usuario. Pueden representar listas temáticas, estados de lectura, géneros favoritos o cualquier otra categoría que el usuario desee.

Para acceder: hacer clic en **"Mis estanterías"** en la barra de navegación. Requiere sesión iniciada.

---

### 7.1 Crear una estantería

1. En la página de estanterías, hacer clic en el botón **"Nueva estantería"** (visible en el panel lateral o en la parte superior de la página).
2. Introducir el nombre deseado para la estantería.
3. Hacer clic en **"Crear"** o pulsar Enter.

La nueva estantería aparecerá en el listado del panel lateral y estará lista para añadir libros.

---

### 7.2 Renombrar o eliminar una estantería

Para **renombrar**: 
1. Situar el cursor sobre el nombre de la estantería en el panel lateral.
2. Aparecerá un icono de lápiz. Hacer clic sobre él.
3. Editar el nombre directamente en el campo de texto.
4. Pulsar Enter para confirmar.

Para **eliminar**:
1. Situar el cursor sobre el nombre de la estantería.
2. Hacer clic en el icono de papelera que aparece.
3. Confirmar la eliminación en el diálogo que se muestra.

> **Advertencia:** al eliminar una estantería se eliminan también todos los libros que contiene. Esta acción no se puede deshacer.

---

### 7.3 Gestionar los libros de una estantería

Al seleccionar una estantería en el panel lateral, el panel principal muestra todos sus libros. Para cada libro se puede:

- **Cambiar el estado de lectura**: usar el selector desplegable que aparece bajo la portada del libro (Quiero leer / Leyendo / Leído).
- **Mover a otra estantería**: abrir el menú contextual del libro (icono de tres puntos) y seleccionar "Mover a…". Se selecciona la estantería destino en el submenú.
- **Eliminar de la estantería**: abrir el menú contextual y seleccionar "Quitar de la estantería". El libro no se elimina del sistema, solo de esa estantería.

---

## 8. Tracker de lectura

El tracker de lectura permite registrar el avance en un libro que se está leyendo actualmente, ya sea por número de páginas o por porcentaje completado.

---

### 8.1 Iniciar el seguimiento de un libro

Desde la página de detalle de cualquier libro, hacer clic en **"Estoy leyendo"**. El libro se añadirá al panel de lectura activa.

También se puede activar desde la página de estanterías: al cambiar el estado de un libro a "Leyendo", se ofrece la opción de activar el tracker para ese libro.

---

### 8.2 Actualizar el progreso

En la página **"Mis estanterías"**, el panel de **lectura activa** aparece en la parte superior. Para cada libro en seguimiento se muestra:

- **Portada y título** del libro, con enlace a su página de detalle.
- **Barra de progreso visual** que indica el porcentaje completado.
- **Selector de modo**: cambiar entre modo "Páginas" y modo "Porcentaje".
- **Campo de entrada**: introducir la página actual (modo páginas) o el porcentaje completado (modo porcentaje).

Para actualizar:
1. Seleccionar el modo deseado (páginas o porcentaje).
2. Introducir el valor actual.
3. Hacer clic en **"Actualizar"** o pulsar Enter.

La barra de progreso se actualizará de forma inmediata.

---

### 8.3 Finalizar el seguimiento

Cuando se termine el libro o se quiera dejar de registrar el progreso:
1. En la tarjeta del libro en el panel de lectura activa, hacer clic en el botón con el icono de papelera.
2. El libro desaparecerá del panel de lectura activa, pero seguirá en la estantería correspondiente.

---

## 9. Reseñas de libros

Las reseñas permiten compartir la opinión sobre un libro y ver las valoraciones de otros lectores de la plataforma.

---

### 9.1 Publicar una reseña

1. Navegar a la página de detalle del libro (buscándolo en el buscador o accediendo desde la estantería).
2. En la sección **"Valoraciones de la comunidad"**, localizar el formulario de reseña (solo visible para usuarios con sesión iniciada).
3. Seleccionar una puntuación del **1 al 5 estrellas** haciendo clic sobre las estrellas del selector interactivo.
4. Opcionalmente, escribir un comentario en el campo de texto.
5. Hacer clic en **"Publicar reseña"**.

La reseña aparecerá de forma inmediata en la sección de valoraciones del libro. Las estadísticas (puntuación media y número de reseñas) se actualizarán automáticamente.

> Solo se puede publicar una reseña por libro. Si ya existe una reseña propia, se mostrará en modo lectura con las opciones de **editar** y **eliminar**.

---

### 9.2 Editar o eliminar una reseña

- **Editar**: en la reseña propia, hacer clic en el botón **"Editar"**. Se abrirá de nuevo el formulario con los valores actuales. Modificar lo deseado y hacer clic en **"Guardar"**.
- **Eliminar**: hacer clic en el botón **"Eliminar"** de la reseña propia. Se pedirá confirmación antes de borrarla definitivamente.

---

## 10. Clubs de lectura

Los clubs de lectura son grupos de usuarios que leen un libro en común y debaten sobre él. Pueden ser públicos (acceso libre) o privados (acceso bajo solicitud).

Para acceder al listado de clubs: hacer clic en **"Clubs"** en la barra de navegación.

---

### 10.1 Explorar clubs

En la página de clubs se muestra el listado de todos los clubs disponibles. Para cada club se indica:
- Nombre y descripción breve.
- Tipo: **Público** o **Privado** (indicado con un icono de candado en los privados).
- Número de miembros.
- Libro del mes activo (si tiene uno asignado).
- Rol del usuario en el club (Administrador / Miembro / ninguno).

Se puede buscar un club por nombre utilizando el campo de búsqueda en la parte superior de la lista.

---

### 10.2 Unirse a un club

**Club público:**
1. En el listado de clubs, localizar el club deseado y hacer clic en **"Unirse"**.
2. El acceso es inmediato: el usuario pasa a ser miembro del club.

**Club privado:**
1. Hacer clic en **"Solicitar acceso"** en el club deseado.
2. Se enviará una solicitud al administrador del club.
3. El botón pasará a mostrar **"Solicitud enviada"** mientras se espera la respuesta.
4. Si la solicitud es aprobada, el usuario recibirá una notificación y podrá acceder al club.
5. Si es rechazada, la solicitud desaparecerá y se podrá volver a intentar.

---

### 10.3 Ver el detalle de un club

Al hacer clic sobre un club se accede a su página de detalle. Contiene tres pestañas:

**Pestaña "Chats"** (visible solo para miembros):
- Lista de hilos de debate del club.
- Cada hilo muestra el título, si está abierto o cerrado y el número de mensajes.
- Hacer clic sobre un hilo para abrirlo y leer o enviar mensajes.

**Pestaña "Miembros"**:
- Lista de todos los miembros del club con su rol (Administrador / Miembro) y fecha de ingreso.
- En clubs públicos es visible para todos. En clubs privados, solo para miembros.

**Pestaña "Solicitudes"** (solo para administradores de clubs privados):
- Lista de solicitudes de ingreso pendientes.
- Cada solicitud muestra el nombre del usuario solicitante y la fecha.
- Los administradores pueden **aprobar** o **rechazar** cada solicitud con los botones correspondientes.

---

### 10.4 Crear un club

1. En la página de clubs, hacer clic en el botón **"+ Nuevo club"** (solo visible para usuarios con sesión).
2. En el formulario que aparece, completar:
   - **Nombre del club** (obligatorio).
   - **Descripción** (opcional): texto libre que describe el propósito del club.
   - **Visibilidad**: seleccionar **Público** (cualquiera puede unirse directamente) o **Privado** (requiere aprobación).
3. Hacer clic en **"Crear club"**.

El creador del club se convierte automáticamente en su administrador.

---

### 10.5 Administrar un club propio

Como **administrador de un club**, se tienen acceso a las siguientes opciones adicionales en la página de detalle:

**Libro del mes:**
- En la sección de información del club, hacer clic en **"Establecer libro del mes"**.
- Se abre un buscador de libros integrado. Buscar y seleccionar el libro deseado.
- Opcionalmente, establecer fechas de inicio y fin de la lectura.
- El libro del mes aparecerá destacado en la portada del club para todos los miembros.

**Gestión de miembros** (pestaña "Miembros"):
- Al lado de cada miembro aparece un icono de expulsión. Hacer clic para expulsar al miembro del club.

**Abandonar el club:**
- Un administrador no puede abandonar el club si hay otros miembros. Primero debe transferir el rol de administrador a otro miembro o expulsar a todos.

---

## 11. Chats de club

Los chats son hilos de debate organizados dentro de un club. Solo los administradores del club pueden crear y gestionar los hilos; cualquier miembro puede participar en ellos.

---

### 11.1 Leer mensajes de un hilo

1. Acceder al club y hacer clic en la pestaña **"Chats"**.
2. Hacer clic sobre el título del hilo que se quiere leer.
3. Se mostrará el listado de mensajes en orden cronológico (más antiguo arriba, más reciente abajo).
4. Los mensajes paginados se cargan de más antiguos a más recientes.

Los hilos marcados como **cerrados** (con icono de candado) se pueden leer pero no se pueden enviar mensajes nuevos en ellos.

---

### 11.2 Enviar un mensaje

1. Al final del hilo abierto, localizar el campo de texto con el texto "Escribe un mensaje…".
2. Escribir el mensaje.
3. Pulsar **Enter** o hacer clic en el botón de envío (icono de avión de papel).

El mensaje aparecerá inmediatamente en el hilo con el nombre y avatar del usuario.

---

### 11.3 Eliminar un mensaje propio

Al situar el cursor sobre un mensaje propio aparece un icono de papelera. Hacer clic sobre él para eliminar el mensaje. No se pide confirmación; la eliminación es inmediata.

Los **administradores del club** pueden eliminar cualquier mensaje del hilo, no solo los propios.

---

### 11.4 Crear un hilo (solo administradores)

1. En la pestaña "Chats" del club, hacer clic en el botón **"+ Nuevo hilo"**.
2. Introducir el título del hilo (por ejemplo: "Capítulos 1-5", "Personajes principales", "Opinión final").
3. Hacer clic en **"Crear"**.

El hilo aparecerá en la lista con estado "Abierto" y ya estará disponible para que los miembros envíen mensajes.

---

## 12. Red social: publicaciones y seguimientos

La parte social de la plataforma permite seguir a otros lectores y compartir publicaciones relacionadas con la lectura.

---

### 12.1 Seguir a un usuario

Para seguir a otro usuario:
1. Acceder a su perfil haciendo clic en su nombre o avatar (desde un post, un comentario, la lista de miembros de un club, etc.), o buscar el perfil directamente en `/users/{id}`.
2. En la cabecera del perfil, hacer clic en el botón **"Seguir"**.

- Si el perfil es **público**: el seguimiento se acepta de forma inmediata. El botón cambia a **"Siguiendo"**.
- Si el perfil es **privado**: se envía una solicitud. El botón pasa a mostrar **"Solicitud enviada"**. El usuario recibirá una notificación y podrá aceptar o rechazar la solicitud.

Para **dejar de seguir** a alguien, hacer clic en el botón **"Siguiendo"** y confirmar en el diálogo que aparece.

---

### 12.2 Publicar una entrada

1. Acceder al perfil propio (`/profile`).
2. En la sección **"Mis publicaciones"**, localizar el formulario de nueva publicación.
3. Hacer clic en el área de imagen o en el icono de cámara para seleccionar una imagen del dispositivo (formatos admitidos: JPG, PNG, GIF, WEBP).
4. Una vez seleccionada, aparecerá una previsualización de la imagen.
5. Opcionalmente, escribir una descripción en el campo de texto.
6. Hacer clic en **"Publicar"**.

La publicación aparecerá en la sección de publicaciones del perfil y en el feed de los usuarios que siguen al autor.

---

### 12.3 Interactuar con publicaciones

Las publicaciones aparecen en el **feed de la página de inicio** (solo para usuarios con sesión) y en los **perfiles de usuario**.

Para cada publicación se puede:

- **Dar "me gusta"**: hacer clic en el icono de corazón. El contador se actualizará de forma inmediata. Volver a hacer clic quita el like.
- **Ver comentarios**: hacer clic en el icono de comentario o en el contador de comentarios. Se despliegan los comentarios existentes.
- **Comentar**: en la sección de comentarios desplegada, escribir en el campo de texto y pulsar Enter o hacer clic en enviar.
- **Eliminar un comentario propio**: hacer clic en el icono de papelera que aparece junto al comentario (solo visible en los comentarios propios).
- **Eliminar la publicación** (solo el autor): hacer clic en el icono de papelera que aparece en la esquina de la publicación.

---

### 12.4 El feed de publicaciones

La página de inicio muestra el **feed personalizado**: las publicaciones más recientes de los usuarios seguidos y las propias, ordenadas de más reciente a más antigua.

Si el feed está vacío (por ejemplo, en una cuenta nueva sin seguidos), se mostrará un mensaje con sugerencia de explorar el buscador de libros y comenzar a seguir a otros lectores.

---

## 13. Notificaciones

El sistema de notificaciones informa al usuario de eventos relevantes que han ocurrido mientras no estaba conectado.

Las notificaciones se reciben por las siguientes acciones:

| Evento | Descripción |
|--------|-------------|
| Alguien empieza a seguirte | Solo en cuentas públicas |
| Solicitud de seguimiento recibida | Solo en cuentas privadas |
| Tu solicitud de seguimiento fue aceptada | — |
| Alguien da like a tu publicación | — |
| Alguien comenta tu publicación | — |
| Solicitud de ingreso a tu club | Solo para administradores |
| Tu solicitud de ingreso fue aprobada | — |
| Tu solicitud de ingreso fue rechazada | — |

Las notificaciones no leídas se indican con un punto o contador en el icono de la campana de la barra de navegación. Al hacer clic se despliega el panel de notificaciones con las entradas más recientes. Las notificaciones se marcan como leídas al acceder al panel.

---

## 14. Configuración de privacidad

La configuración de privacidad controla qué información es visible para otros usuarios de la plataforma. Se accede desde **Mi perfil → Privacidad**.

---

### 14.1 Perfil privado

Al activar la opción **"Perfil privado"**:

- Los usuarios que no te siguen no podrán ver tus estanterías ni tu participación en clubs.
- Cualquier nuevo usuario que intente seguirte deberá enviar una solicitud que deberás aprobar manualmente desde las notificaciones.
- Los usuarios que ya te seguían antes de activar la privacidad siguen viéndote sin necesidad de reconfirmación.
- Tu nombre, avatar y biografía siguen siendo visibles para todos.

Para **aceptar o rechazar solicitudes de seguimiento** pendientes:
1. Acceder al panel de notificaciones.
2. Localizar las notificaciones de tipo "Solicitud de seguimiento".
3. Hacer clic en **"Aceptar"** o **"Rechazar"** según corresponda.

---

### 14.2 Visibilidad de estanterías y clubs

De forma independiente al modo privado, se puede controlar:

- **"Estanterías públicas"**: si está activo, cualquier usuario puede ver las estanterías desde el perfil público. Si está desactivado, solo los seguidores aceptados pueden verlas.
- **"Clubes públicos"**: igual que el anterior, pero para la participación en clubs.

Estos ajustes se guardan automáticamente al cambiar el estado del toggle.

---

## 15. Preguntas frecuentes

**¿Puedo tener un libro en varias estanterías a la vez?**
Sí. Un mismo libro puede estar en varias estanterías diferentes (por ejemplo, en "Ciencia Ficción" y en "Favoritos" simultáneamente). Lo que no es posible es tener el mismo libro dos veces en la misma estantería.

**¿Qué pasa si borro una estantería?**
Se eliminan todos los libros que contiene. El libro en sí no desaparece del sistema ni de otras estanterías; solo se elimina la asociación con esa estantería concreta.

**¿Puedo cambiar mi email?**
Actualmente no está disponible el cambio de email desde el perfil. Para solucionar esto es necesario contactar con el administrador de la plataforma.

**¿Los libros provienen del catálogo de Google Books?**
Sí. El buscador consulta directamente el catálogo de Google Books. Los libros se guardan en la base de datos local de la plataforma la primera vez que algún usuario los añade a una estantería, escribe una reseña o los establece como libro del mes de un club.

**¿Puedo usar la plataforma sin registrarme?**
Se puede explorar el buscador de libros, ver los clubs y los perfiles públicos sin necesidad de cuenta. Para gestionar estanterías, publicar, unirse a clubs o participar en los chats es necesario registrarse.

**¿Qué formatos de imagen admite la plataforma para publicaciones y avatares?**
Se admiten los formatos JPG, JPEG, PNG, GIF y WEBP.

**¿Cómo cancelo una solicitud de seguimiento que envié?**
Acceder al perfil del usuario al que se envió la solicitud y hacer clic en el botón **"Solicitud enviada"**. Se mostrará la opción de cancelar la solicitud.

**¿Puedo abandonar un club del que soy administrador?**
Si eres el único administrador del club y hay otros miembros, no podrás abandonarlo directamente. Primero debes transferir el rol de administrador a otro miembro (contactando con el administrador de la plataforma si no hay otra opción) o expulsar a todos los miembros y luego abandonarlo.
