# TFGdaw — Plataforma de Clubes de Lectura

Aplicación web full-stack de clubes de lectura compuesta por un **backend API REST** en Symfony 7.4 y un **frontend SPA** en React 18 + TypeScript. Los usuarios pueden gestionar estanterías personales, hacer seguimiento de su lectura activa, dejar reseñas de libros, seguir a otros lectores, unirse a clubs, debatir libros por capítulos y descubrir nuevos títulos a través de la Google Books API.

---

## Tabla de contenidos

### Análisis del proyecto
1. [Introducción](#introducción)
2. [Objetivos](#objetivos)
3. [Funciones y rendimientos deseados](#funciones-y-rendimientos-deseados)
4. [Planteamiento y evaluación de soluciones](#planteamiento-y-evaluación-de-soluciones)
5. [Justificación de la solución elegida](#justificación-de-la-solución-elegida)
6. [Recursos del proyecto](#recursos-del-proyecto)
7. [Planificación temporal](#planificación-temporal)

### Diseño e implementación
8. [Tipos de usuarios y operaciones](#tipos-de-usuarios-y-operaciones)
9. [Mapa de navegación](#mapa-de-navegación)

### Backend
10. [Stack tecnológico](#stack-tecnológico)
11. [Instalación y puesta en marcha](#instalación-y-puesta-en-marcha)
12. [Variables de entorno](#variables-de-entorno)
13. [Modelo de datos](#modelo-de-datos)
14. [Autenticación y roles](#autenticación-y-roles)
15. [Referencia de la API](#referencia-de-la-api)
    - [Autenticación](#autenticación)
    - [Libros — búsqueda externa](#libros--búsqueda-externa)
    - [Estanterías](#estanterías)
    - [Clubs de lectura](#clubs-de-lectura)
    - [Chats de club](#chats-de-club)
    - [Perfil de usuario](#perfil-de-usuario)
    - [Seguimientos de usuarios](#seguimientos-de-usuarios)
    - [Reseñas de libros](#reseñas-de-libros)
    - [Progreso de lectura](#progreso-de-lectura)
16. [Integración con Google Books API](#integración-con-google-books-api)
17. [Convenciones de respuesta](#convenciones-de-respuesta)

### Frontend
18. [Stack frontend](#stack-frontend)
19. [Estructura del proyecto frontend](#estructura-del-proyecto-frontend)
20. [Instalación del frontend](#instalación-del-frontend)
21. [Arquitectura frontend](#arquitectura-frontend)
22. [Sistema de diseño](#sistema-de-diseño)
23. [Páginas implementadas](#páginas-implementadas)
24. [Comunicación con la API](#comunicación-con-la-api)

### Verificación y cierre
25. [Fase de pruebas](#fase-de-pruebas)
26. [Conclusiones finales](#conclusiones-finales)
27. [Bibliografía](#bibliografía)

---

## Introducción

En los últimos años, el hábito de la lectura ha experimentado un resurgimiento notable impulsado por las redes sociales y las comunidades de lectores en línea. Plataformas como Goodreads o StoryGraph han demostrado que existe una demanda real de herramientas digitales que permitan no solo llevar un registro de las lecturas personales, sino también conectar con otros lectores, participar en debates y formar grupos de lectura.

Sin embargo, estas plataformas mayoritarias presentan limitaciones relevantes: interfaces poco intuitivas, ausencia de funcionalidades comunitarias en tiempo real, o falta de un componente social integrado comparable al de las redes sociales convencionales. Además, al tratarse de servicios externos de terceros, no permiten al desarrollador estudiar ni controlar su arquitectura interna.

El presente proyecto, **TFGdaw**, nace con el objetivo de construir desde cero una plataforma web de clubes de lectura que integre en un mismo sistema tres ámbitos que habitualmente se encuentran dispersos:

1. **Biblioteca personal**: gestión de estanterías de libros, seguimiento del progreso de lectura y publicación de reseñas.
2. **Clubes de lectura**: formación de grupos, asignación del libro del mes y debate estructurado mediante hilos de chat organizados por temas o capítulos.
3. **Red social**: seguimiento entre usuarios, publicaciones con imagen, likes, comentarios y feed personalizado.

Desde el punto de vista técnico, el proyecto constituye una aplicación web full-stack de complejidad media-alta, desarrollada con tecnologías del ciclo DAW: **PHP/Symfony** en el backend, **React/TypeScript** en el frontend y **MySQL/MariaDB** como sistema gestor de base de datos. La arquitectura sigue el patrón **MVC** (Modelo-Vista-Controlador), con el backend actuando como API REST que consume un frontend SPA desacoplado.

La aplicación se despliega mediante **Docker**, lo que garantiza la reproducibilidad del entorno tanto en desarrollo como en producción. El frontend, compilado con Vite, se integra en el mismo servidor que la API, permitiendo un despliegue unificado.

---

## Objetivos

El objetivo general es desarrollar una aplicación web completa, funcional y desplegable que permita a los usuarios gestionar su actividad lectora y conectar con otros lectores.

Los objetivos específicos son:

- Implementar un sistema de autenticación seguro basado en sesiones de servidor con cookies.
- Diseñar y desarrollar una base de datos relacional que cubra todas las entidades del dominio (usuarios, libros, estanterías, clubs, publicaciones, seguimientos).
- Exponer los datos mediante una API REST documentada, con control de acceso por roles.
- Integrar la Google Books API como fuente de datos de libros, con caché local para reducir llamadas externas.
- Desarrollar un frontend React con diseño responsive, accesible y coherente visualmente.
- Implementar funcionalidades sociales: feed de publicaciones, sistema de follows con cuentas privadas, likes y comentarios.
- Implementar clubes de lectura con gestión de miembros, libro del mes y chats organizados por hilos.
- Desplegar la aplicación mediante Docker con configuración para entornos de desarrollo y producción.

---

## Funciones y rendimientos deseados

Una vez implementado, el sistema ofrecerá los siguientes servicios:

**Gestión de cuenta y perfil:**
- Registro de nuevos usuarios con generación automática de nombre visible.
- Login y logout con persistencia de sesión mediante cookie.
- Edición de perfil: nombre visible, biografía, avatar.
- Configuración de privacidad: perfil privado, visibilidad de estanterías y participación en clubs.
- Cambio de contraseña con verificación de la contraseña actual.

**Biblioteca personal:**
- Creación, edición y eliminación de estanterías personalizadas.
- Búsqueda de libros en el catálogo de Google Books con filtros avanzados (título, autor, ISBN, género, editorial, idioma).
- Añadir libros a estanterías con estado de lectura (quiero leer / leyendo / leído).
- Tracker de lectura activa con progreso por páginas o porcentaje.
- Reseñas con puntuación (1-5 estrellas) y texto opcional, con estadísticas de la comunidad.

**Red social:**
- Sistema de seguimiento entre usuarios (follows), con flujo de solicitud para cuentas privadas.
- Feed de publicaciones con las entradas de los usuarios seguidos y las propias.
- Publicaciones con imagen y descripción, likes y comentarios.

**Clubes de lectura:**
- Creación y gestión de clubs públicos o privados.
- Incorporación directa en clubs públicos; solicitud de acceso en clubs privados.
- Gestión de solicitudes de ingreso por parte de los administradores.
- Asignación del libro del mes con fechas de inicio y fin.
- Hilos de debate (chats) organizados por temas, gestionados por los administradores del club.

**Administración:**
- Panel de administración para usuarios con rol `ROLE_ADMIN`.
- Estadísticas generales: totales de usuarios, libros, clubs, publicaciones, reseñas y progreso.
- Gestión de usuarios (activar/desactivar, promover a administrador), clubs (eliminar) y publicaciones (eliminar contenido inapropiado).

---

## Planteamiento y evaluación de soluciones

Antes de iniciar el desarrollo se realizó un análisis comparativo de las principales alternativas tecnológicas disponibles para cada capa de la aplicación. Los criterios de evaluación empleados fueron:

- **Adecuación al ciclo DAW**: coherencia con los módulos cursados (Entornos Servidor, Entornos Cliente, Diseño de Interfaces, Bases de Datos).
- **Madurez y estabilidad**: tiempo que lleva la tecnología en el mercado y mantenimiento activo.
- **Facilidad de mantenimiento**: curva de aprendizaje, documentación disponible y tamaño de la comunidad.
- **Productividad en desarrollo individual**: herramientas de scaffolding, convenciones y velocidad de desarrollo.
- **Seguridad**: mecanismos integrados de autenticación, autorización y protección contra vulnerabilidades comunes.

---

### Backend

Para el servidor se evaluaron cuatro frameworks:

| Alternativa | Ventajas | Desventajas | Valoración |
|-------------|----------|-------------|-----------|
| **Symfony (PHP)** | Framework maduro con más de 15 años de historia, ORM Doctrine con migraciones, sistema de seguridad con firewalls y roles, inyección de dependencias, amplia documentación y conocimiento previo de la alumna | Configuración inicial más extensa que otros frameworks | ⭐⭐⭐⭐⭐ |
| Laravel (PHP) | Sintaxis expresiva, scaffolding rápido con Artisan, Eloquent ORM | Convenciones muy rígidas que dificultan la personalización, menor control arquitectónico | ⭐⭐⭐ |
| Express.js (Node.js) | Ligero y flexible, misma tecnología que el frontend (JavaScript), alto rendimiento | Sin ORM oficial, sin sistema de seguridad integrado, requiere configurar manualmente cada capa | ⭐⭐ |
| Django (Python) | ORM muy potente, panel de administración automático | Requiere dominar Python, fuera del alcance de los módulos del ciclo DAW | ⭐⭐ |

---

### Frontend

Para la capa de presentación se valoraron tres enfoques:

| Alternativa | Ventajas | Desventajas | Valoración |
|-------------|----------|-------------|-----------|
| **React + TypeScript** | Componentización, tipado estático que previene errores en tiempo de desarrollo, ecosistema muy amplio (Router, Context API), gran adopción en el mercado laboral, conocimiento previo de la alumna | Requiere configuración inicial (bundler, router) y comprensión del ciclo de vida de componentes | ⭐⭐⭐⭐⭐ |
| Vue.js | Curva de aprendizaje más suave, sintaxis más familiar para quienes vienen de HTML | Menor adopción en el mercado laboral, ecosistema más reducido | ⭐⭐⭐ |
| HTML/CSS/JavaScript vanilla | Sin dependencias externas, control total sobre el código | No escala bien: sin gestión de estado, sin enrutamiento, sin reutilización de componentes | ⭐⭐ |
| Angular | Framework muy completo con todo integrado (HTTP client, formularios reactivos, inyección de dependencias) | Demasiado verboso para un proyecto individual, curva de aprendizaje muy elevada | ⭐⭐ |

---

### Framework CSS / Sistema visual

| Alternativa | Ventajas | Desventajas | Valoración |
|-------------|----------|-------------|-----------|
| **CSS nativo con variables** | Control total del diseño, sin dependencias, demuestra dominio de CSS3, resultado visual diferenciado | Más tiempo de desarrollo inicial para construir el sistema de diseño | ⭐⭐⭐⭐⭐ |
| Bootstrap 5 | Muy rápido para prototipar, componentes probados, responsive out-of-the-box | Aspecto genérico y reconocible, difícil de personalizar sin sobrescribir muchos estilos | ⭐⭐⭐ |
| Tailwind CSS | Utilitario, flexible, fácil de hacer responsive | Clases extensas en el HTML, requiere configuración del build, curva de aprendizaje | ⭐⭐⭐ |

---

### Base de datos

| Alternativa | Ventajas | Desventajas | Valoración |
|-------------|----------|-------------|-----------|
| **MySQL / MariaDB** | Sistema relacional maduro y ampliamente soportado, integración nativa perfecta con Doctrine ORM, fácil de dockerizar, cumple con los criterios de BD relacional del ciclo | Sin ventajas específicas para datos no estructurados, irrelevante dado el modelo de datos del proyecto | ⭐⭐⭐⭐⭐ |
| PostgreSQL | Mayor potencia para consultas complejas y tipos de datos avanzados | Mayor complejidad de configuración, menos familiar para la alumna | ⭐⭐⭐⭐ |
| MongoDB | Esquema flexible, muy adecuado para datos no estructurados | Base de datos no relacional: no cumple los criterios del módulo de BD del ciclo, ORM mal adaptado a documentos | ⭐ |

---

## Justificación de la solución elegida

### Backend: Symfony 7 + Doctrine ORM

Se eligió Symfony como framework de backend por ser la opción más sólida y completa para el perfil técnico del proyecto. Symfony implementa de forma nativa el patrón **MVC** (Modelo-Vista-Controlador): los controladores en `src/Controller/` gestionan la lógica de las peticiones HTTP, las entidades Doctrine en `src/Entity/` representan el modelo de datos, y las plantillas Twig (o en este caso las respuestas JSON) conforman la vista. Esta separación de responsabilidades facilita el mantenimiento, las pruebas y la escalabilidad del código.

El bundle `symfony/security-bundle` proporciona un sistema de autenticación y autorización maduro con muy poco código personalizado: firewalls, proveedores de usuarios, hashing de contraseñas con el algoritmo óptimo disponible (bcrypt/argon2id) y control de acceso por roles mediante anotaciones o configuración YAML. Implementar un nivel equivalente de seguridad desde cero con Express.js, por ejemplo, requeriría integrar y mantener múltiples librerías de terceros.

Doctrine ORM permite trabajar con la base de datos de forma completamente orientada a objetos. Las entidades PHP se mapean a tablas SQL mediante atributos PHP 8, y las consultas se escriben en DQL (Doctrine Query Language), que es independiente del motor de base de datos. El sistema de migraciones genera automáticamente los scripts SQL a partir de los cambios en las entidades, lo que garantiza que el esquema de la base de datos esté siempre sincronizado con el modelo de datos del código.

### Frontend: React 18 + TypeScript

React fue elegido como librería de frontend por su modelo de componentes reutilizables, que permite dividir la interfaz en piezas independientes con su propio estado y ciclo de vida. Este enfoque es especialmente adecuado para una SPA con múltiples vistas interactivas como la plataforma TFGdaw, donde componentes como `PostCard`, `Navbar` o `PrivateRoute` se reutilizan en distintos contextos sin duplicar código.

La incorporación de **TypeScript** añade tipado estático sobre JavaScript, lo que permite detectar errores de tipo en tiempo de compilación en lugar de en tiempo de ejecución. En un proyecto con una API REST que devuelve estructuras JSON complejas, TypeScript facilita enormemente el desarrollo al autocompletar propiedades de los objetos y señalar incompatibilidades de tipo antes de que lleguen al navegador.

**Vite** se utiliza como bundler y servidor de desarrollo por su velocidad de compilación (basada en ES modules nativos) y su configuración mínima. Su sistema de proxy permite redirigir las peticiones a `/api/*` al backend de Symfony durante el desarrollo sin necesidad de configurar CORS en el servidor, simulando fielmente el comportamiento del entorno de producción.

### Sistema de diseño: CSS nativo con variables

Se optó deliberadamente por no usar ningún framework CSS externo (Bootstrap, Tailwind, etc.) para demostrar el dominio de CSS3 en su nivel más fundamental. El sistema de diseño se construyó íntegramente mediante **variables CSS nativas** (custom properties definidas en `:root`), lo que permite mantener una paleta de colores, tipografías y espaciados coherentes en toda la aplicación modificando un único punto. El diseño responsive se implementa con **media queries** y unidades relativas (`rem`, `%`, `vw`), sin depender de ninguna cuadrícula externa.

### Comunicación frontend-backend: sesiones PHP + fetch

Se eligió el sistema de **sesiones PHP con cookies** en lugar de tokens JWT por dos razones. En primer lugar, Symfony Security lo integra de forma nativa sin configuración adicional. En segundo lugar, las sesiones basadas en cookie son intrínsecamente más seguras contra ataques XSS, ya que la cookie puede marcarse como `HttpOnly` y el token de sesión nunca queda expuesto en el JavaScript de la página. El frontend realiza todas las peticiones con `credentials: 'include'` para que el navegador envíe automáticamente la cookie en cada petición cross-origin.

### Base de datos: MySQL / MariaDB

MySQL/MariaDB fue seleccionado por ser el sistema gestor de bases de datos relacionales más extendido en entornos PHP y por su integración nativa y perfectamente documentada con Doctrine ORM. El modelo de datos del proyecto, con 16 entidades y múltiples relaciones de cardinalidad 1:N y N:M, se adapta perfectamente al modelo relacional. MariaDB, fork compatible de MySQL, aporta mejoras de rendimiento y es el motor usado en el entorno de desarrollo con Docker.

### Despliegue: Docker + Docker Compose

Docker garantiza que el entorno de ejecución sea idéntico en todos los equipos y en producción, eliminando el clásico problema de "en mi máquina funciona". Docker Compose orquesta los servicios (PHP-FPM, Nginx, base de datos) con un único comando, lo que facilita tanto el desarrollo en equipo como el despliegue en un servidor.

---

## Recursos del proyecto

### Recursos humanos

El proyecto ha sido desarrollado íntegramente por una sola persona, la alumna Rita Victoria Domínguez, estudiante de segundo curso del ciclo formativo de Grado Superior en Desarrollo de Aplicaciones Web (DAW). Las tareas de análisis, diseño, implementación, pruebas y documentación han recaído sobre la misma persona.

### Recursos hardware

| Recurso | Descripción |
|---------|-------------|
| Ordenador de desarrollo | PC con Windows 11, procesador Intel Core i5/i7, 16 GB RAM, SSD |
| Servidor de desarrollo | Localhost (Symfony CLI + Vite) |
| Servidor de producción | Máquina virtual o VPS con Docker (opcional para despliegue) |

### Recursos software

| Software | Versión | Uso |
|----------|---------|-----|
| PHP | ≥ 8.2 | Lenguaje del backend |
| Symfony CLI | 5.x | Servidor de desarrollo y gestión de comandos |
| Composer | 2.x | Gestor de dependencias PHP |
| Node.js | ≥ 18 | Entorno de ejecución del frontend |
| npm | ≥ 9 | Gestor de paquetes JavaScript |
| MariaDB / MySQL | 10.4+ | Base de datos relacional |
| Docker Desktop | — | Contenedores para desarrollo y producción |
| Visual Studio Code | — | Editor de código |
| Git | — | Control de versiones |
| Postman | — | Prueba manual de endpoints de la API |
| Google Chrome | — | Navegador principal para pruebas |
| Google Books API | v1 | Fuente de datos de libros |

---

## Planificación temporal

El desarrollo del proyecto se organizó siguiendo una metodología **iterativa e incremental**: en lugar de completar todas las capas de la aplicación de forma paralela desde el inicio, se optó por desarrollar módulos funcionales completos uno a uno (backend + frontend juntos), de modo que al finalizar cada fase existía una parte de la aplicación plenamente operativa y verificable.

Esta estrategia permite detectar y corregir errores de integración entre el backend y el frontend de forma temprana, antes de que se acumulen dependencias entre módulos. Cada iteración termina con un conjunto de funcionalidades que pueden probarse de extremo a extremo.

Las fases se distribuyeron a lo largo del tercer trimestre del curso escolar, con una dedicación estimada de entre 3 y 5 horas diarias:

| Semana | Fase | Tareas principales | Entregable al finalizar |
|--------|------|--------------------|------------------------|
| 1 | Análisis | Definición de requisitos, casos de uso, elección de tecnologías | Documento de requisitos, decisión de stack |
| 2 | Diseño | Modelo E/R, diseño de tablas, bocetos de pantallas (wireframes) | Diagrama E/R, esquema de BD, wireframes |
| 3-4 | Infraestructura | Configuración de Symfony, Doctrine, Docker, Vite, React Router, proxy de desarrollo | Repositorio con estructura base, Docker funcional, hello world de API y frontend |
| 5-6 | Autenticación y perfil | Registro, login, logout, `AuthContext`, perfil, avatar, privacidad, cambio de contraseña | Flujo completo de autenticación funcional |
| 7-8 | Estanterías y libros | CRUD estanterías, integración Google Books API, búsqueda, importación, estados de lectura, tracker, reseñas | Módulo de biblioteca personal completo |
| 9-10 | Clubs de lectura | CRUD clubs, membresías, solicitudes de ingreso, libro del mes, hilos de chat y mensajes | Módulo de clubes completo |
| 11-12 | Sistema social | Follows (público y privado), publicaciones, likes, comentarios, feed, notificaciones | Red social operativa |
| 13 | Panel de administración | Estadísticas globales, gestión de usuarios, clubs y publicaciones | Panel admin completo |
| 14 | Pruebas y ajustes | Batería de pruebas funcionales, corrección de errores, ajustes de responsive y accesibilidad | Aplicación estable y verificada |
| 15 | Documentación | Redacción de documentación técnica, manual de usuario, conclusiones y bibliografía | Documentación completa entregable |

> La semana 14 actuó como colchón para absorber la deuda técnica acumulada y resolver los problemas de integración detectados durante las pruebas. La semana 15 se dedicó íntegramente a documentación, sin añadir nuevas funcionalidades.

---

## Tipos de usuarios y operaciones

El sistema contempla tres perfiles de usuario con niveles de acceso diferenciados:

---

### Usuario no autenticado (visitante)

Accede a la plataforma sin haberse registrado o sin sesión activa.

| Operación | Disponible |
|-----------|-----------|
| Ver la página de inicio | Sí |
| Buscar libros (Google Books) | Sí |
| Ver listado de clubs | Sí |
| Ver detalle de un club (público) | Sí |
| Ver perfil público de un usuario | Sí |
| Registrarse | Sí |
| Iniciar sesión | Sí |
| Gestionar estanterías | No |
| Publicar, dar like o comentar | No |
| Unirse a clubs | No |
| Ver chats de clubs | No |
| Acceder al panel de administración | No |

---

### Usuario registrado (`ROLE_USER`)

Usuario con cuenta creada y sesión activa.

**Perfil y cuenta:**
- Ver y editar su perfil (nombre visible, biografía, avatar).
- Configurar la privacidad (perfil privado, visibilidad de estanterías y clubs).
- Cambiar la contraseña.
- Cerrar sesión.

**Biblioteca personal:**
- Crear, renombrar y eliminar estanterías.
- Buscar libros y añadirlos a sus estanterías con estado de lectura.
- Actualizar el estado de lectura de un libro (quiero leer / leyendo / leído).
- Mover libros entre estanterías.
- Activar y actualizar el tracker de lectura activa (páginas o porcentaje).
- Publicar, editar y eliminar reseñas de libros.

**Red social:**
- Seguir y dejar de seguir a otros usuarios.
- Enviar solicitudes de seguimiento a cuentas privadas.
- Ver el feed de publicaciones de los usuarios que sigue.
- Crear publicaciones con imagen y descripción.
- Dar like y comentar publicaciones.
- Eliminar sus propias publicaciones y comentarios.
- Eliminar seguidores de su propia lista.

**Clubs:**
- Ver y buscar clubs.
- Unirse a clubs públicos (acceso inmediato).
- Solicitar unirse a clubs privados (requiere aprobación del administrador del club).
- Abandonar un club.
- Ver miembros y chats de clubs a los que pertenece.
- Enviar mensajes en hilos de chat abiertos.
- Eliminar sus propios mensajes de chat.

**Como administrador de un club propio:**
- Crear y eliminar clubs.
- Editar el nombre, descripción y visibilidad del club.
- Aprobar o rechazar solicitudes de ingreso.
- Expulsar miembros.
- Establecer el libro del mes (con fechas de inicio y fin).
- Crear, editar y eliminar hilos de chat.
- Eliminar cualquier mensaje dentro de sus chats.

---

### Administrador de la plataforma (`ROLE_ADMIN`)

Usuario con rol de superadministrador, asignado manualmente desde la base de datos o por otro `ROLE_ADMIN`.

Hereda todas las capacidades de `ROLE_USER` y además puede:

| Operación | Descripción |
|-----------|-------------|
| Ver estadísticas globales | Totales de usuarios, libros, clubs, publicaciones y reseñas |
| Listar todos los usuarios | Con datos de email, nombre, estado y roles |
| Activar / desactivar cuentas | Bloquear el acceso a un usuario sin eliminar su cuenta |
| Promover a administrador | Asignar `ROLE_ADMIN` a cualquier usuario |
| Eliminar cualquier club | Independientemente de quién sea el propietario |
| Eliminar cualquier publicación | Para moderar contenido inapropiado |
| Acceder a `/api/admin/*` | Todos los endpoints del panel de administración |

---

## Mapa de navegación

El siguiente esquema muestra la estructura de rutas del frontend y las condiciones de acceso a cada una:

```
/  (HomePage)
│   Acceso: público
│   ├── Si autenticado: muestra feed de publicaciones
│   └── Si no autenticado: muestra solo hero + features
│
├── /login  (LoginPage)
│   Acceso: público (redirige a "/" si ya autenticado)
│
├── /register  (RegisterPage)
│   Acceso: público (redirige a "/" si ya autenticado)
│
├── /books  (BooksPage)
│   Acceso: público
│   └── /books/:externalId  (BookDetailPage)
│       Acceso: público
│       ├── Si autenticado: botones añadir a estantería, iniciar tracking, formulario de reseña
│       └── Si no autenticado: solo lectura (portada, sinopsis, valoraciones)
│
├── /clubs  (ClubsPage)
│   Acceso: público
│   └── /clubs/:id  (ClubDetailPage)
│       Acceso: público (detalle básico)
│       ├── Tab "Chats": solo miembros del club
│       ├── Tab "Miembros": público para clubs públicos, solo miembros para clubs privados
│       └── Tab "Solicitudes": solo administradores del club (clubs privados)
│
├── /shelves  (ShelvesPage)  ← RUTA PRIVADA
│   Acceso: requiere ROLE_USER
│   ├── Sidebar: lista de estanterías propias
│   └── Panel: libros de la estantería seleccionada + tracker de lectura
│
├── /profile  (ProfilePage)  ← RUTA PRIVADA
│   Acceso: requiere ROLE_USER
│   ├── Sección: datos personales (editar nombre, bio, avatar)
│   ├── Sección: seguidores / seguidos (modales)
│   ├── Sección: privacidad
│   ├── Sección: publicaciones propias + formulario de nueva publicación
│   └── Sección: cambiar contraseña
│
├── /users/:id  (PublicProfilePage)
│   Acceso: público
│   ├── Si autenticado: botón seguir/dejar de seguir
│   ├── Si el perfil es privado y no se sigue: estanterías y clubs ocultos
│   └── Si es el propio usuario: redirige a /profile
│
└── /admin  (AdminPage)  ← RUTA PRIVADA
    Acceso: requiere ROLE_ADMIN
    ├── Estadísticas generales
    ├── Gestión de usuarios
    ├── Gestión de clubs
    └── Gestión de publicaciones
```

**Reglas de navegación:**
- Las rutas privadas (`/shelves`, `/profile`, `/admin`) están protegidas por el componente `PrivateRoute`. Si el usuario no está autenticado, se redirige automáticamente a `/login` guardando la ruta de origen para volver tras el login.
- `/admin` requiere adicionalmente `ROLE_ADMIN`; un usuario con solo `ROLE_USER` recibe un error `403` en los endpoints y es redirigido.
- Cualquier URL no reconocida por React Router muestra una página 404 genérica.

---

---

## Stack tecnológico

| Tecnología | Versión | Uso |
|---|---|---|
| PHP | ≥ 8.2 | Lenguaje principal |
| Symfony | 7.4 | Framework |
| Doctrine ORM | 3.x | Mapeo objeto-relacional |
| Doctrine Migrations | 3.x | Control de esquema de BD |
| MariaDB / MySQL | 10.4+ | Base de datos relacional |
| symfony/http-client | 7.4 | Llamadas a Google Books API |
| symfony/security-bundle | 7.4 | Autenticación y autorización |

---

## Instalación y puesta en marcha

```bash
# 1. Clonar el repositorio
git clone <url-del-repo>
cd TFGdaw

# 2. Instalar dependencias
composer install

# 3. Configurar variables de entorno
cp .env .env.local
# Editar .env.local con los valores reales

# 4. Crear la base de datos y ejecutar migraciones
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Iniciar el servidor de desarrollo
symfony server:start
# o
php -S localhost:8000 -t public/
```

---

## Variables de entorno

| Variable | Ejemplo | Descripción |
|---|---|---|
| `DATABASE_URL` | `mysql://root@127.0.0.1:3306/tfgdaw?serverVersion=mariadb-10.4.28` | Cadena de conexión a la base de datos |
| `APP_SECRET` | `abc123...` | Clave secreta de Symfony |
| `GOOGLE_BOOKS_API_KEY` | `AIzaSy...` | Clave de la Google Books API |
| `MAILER_DSN` | `null://null` | Configuración del mailer |

---

## Modelo de datos

### Diagrama de entidades

```
User
 ├── Shelf (1:N)
 │    └── ShelfBook (1:N)  ──→  Book
 ├── ClubMember (1:N)  ──→  Club
 ├── ClubJoinRequest (1:N)  ──→  Club
 ├── ClubChatMessage (1:N)  ──→  ClubChat
 ├── Follow (1:N as follower)
 ├── Follow (1:N as following)
 ├── BookReview (1:N)  ──→  Book
 └── ReadingProgress (1:N)  ──→  Book

Club
 ├── ClubMember (1:N)
 ├── ClubJoinRequest (1:N)
 ├── ClubChat (1:N)
 │    └── ClubChatMessage (1:N)
 └── currentBook (N:1)  ──→  Book
```

### Entidades principales

#### `User`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `email` | string | Identificador único de login |
| `roles` | array | Lista de roles (`ROLE_USER`, `ROLE_ADMIN`) |
| `displayName` | string\|null | Nombre público |
| `bio` | string\|null | Biografía del usuario |
| `avatar` | string\|null | Nombre del archivo de avatar subido |
| `isPrivate` | bool | Si el perfil es privado (los no-seguidores no ven sus datos) |
| `shelvesPublic` | bool | Si las estanterías son visibles públicamente |
| `clubsPublic` | bool | Si los clubs son visibles públicamente |

#### `Book`
Representa un libro importado desde Google Books y almacenado localmente para evitar llamadas repetidas a la API.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria interna |
| `externalSource` | string | Siempre `"google_books"` |
| `externalId` | string | ID del volumen en Google Books (ej: `zyTCAlFPjgYC`) |
| `title` | string | Título |
| `authors` | array | Lista de autores |
| `isbn10` / `isbn13` | string\|null | ISBNs |
| `coverUrl` | string\|null | URL de la portada |
| `description` | text\|null | Sinopsis |
| `publisher` | string\|null | Editorial |
| `publishedDate` | string\|null | Fecha de publicación |
| `language` | string\|null | Código de idioma (`es`, `en`…) |
| `pageCount` | int\|null | Número de páginas |
| `categories` | array | Categorías/géneros |

#### `Shelf`
Estantería personal del usuario.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `user` | User | Propietario |
| `name` | string | Nombre de la estantería |
| `orderIndex` | int | Orden de visualización |

#### `ShelfBook`
Relación entre una estantería y un libro, con estado de lectura.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `shelf` | Shelf | Estantería |
| `book` | Book | Libro |
| `status` | string | `want_to_read` \| `reading` \| `read` |
| `orderIndex` | int | Orden dentro de la estantería |
| `addedAt` | DateTimeImmutable | Fecha de incorporación |

> La combinación `(shelf_id, book_id)` tiene restricción `UNIQUE`.

#### `Club`
Club de lectura.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `owner` | User | Usuario que creó el club |
| `name` | string | Nombre del club |
| `description` | text\|null | Descripción |
| `visibility` | string | `public` \| `private` |
| `currentBook` | Book\|null | Libro del mes activo |
| `currentBookSince` | DateTimeImmutable\|null | Fecha en que se estableció el libro actual |

#### `ClubMember`
Membresía de un usuario en un club.

| Campo | Tipo | Descripción |
|---|---|---|
| `role` | string | `admin` \| `member` |
| `joinedAt` | DateTimeImmutable | Fecha de ingreso |

> La combinación `(club_id, user_id)` tiene restricción `UNIQUE`.

#### `ClubJoinRequest`
Solicitud de ingreso a un club privado.

| Campo | Tipo | Descripción |
|---|---|---|
| `status` | string | `pending` \| `approved` \| `rejected` |
| `requestedAt` | DateTimeImmutable | Fecha de solicitud |
| `resolvedAt` | DateTimeImmutable\|null | Fecha de resolución |
| `resolvedBy` | User\|null | Admin que resolvió la solicitud |

#### `ClubChat`
Hilo de conversación dentro de un club (ej: "Capítulos 1-5", "Impresiones finales").

| Campo | Tipo | Descripción |
|---|---|---|
| `title` | string | Título del hilo |
| `isOpen` | bool | Si se pueden enviar nuevos mensajes |
| `createdBy` | User | Quién creó el hilo |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `closedAt` | DateTimeImmutable\|null | Fecha de cierre |

#### `ClubChatMessage`
Mensaje dentro de un hilo de chat.

| Campo | Tipo | Descripción |
|---|---|---|
| `content` | text | Contenido del mensaje |
| `user` | User | Autor |
| `createdAt` | DateTimeImmutable | Fecha de envío |

> Índice compuesto `(chat_id, created_at)` para consultas paginadas eficientes.

#### `Follow`
Relación de seguimiento entre usuarios.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `follower` | User | Usuario que sigue |
| `following` | User | Usuario seguido |
| `status` | string | `pending` \| `accepted` |
| `createdAt` | DateTimeImmutable | Fecha de solicitud/seguimiento |

> La combinación `(follower_id, following_id)` tiene restricción `UNIQUE`.

#### `BookReview`
Reseña de un libro por un usuario.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `user` | User | Autor de la reseña |
| `book` | Book | Libro reseñado |
| `rating` | int | Puntuación de 1 a 5 |
| `content` | text\|null | Texto de la reseña (opcional) |
| `createdAt` | DateTimeImmutable | Fecha de publicación |

> La combinación `(user_id, book_id)` tiene restricción `UNIQUE`.

#### `ReadingProgress`
Seguimiento de lectura activa de un libro por un usuario.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | Clave primaria |
| `user` | User | Lector |
| `book` | Book | Libro en progreso |
| `mode` | string | `pages` \| `percent` |
| `currentPage` | int\|null | Página actual (modo `pages`) |
| `totalPages` | int\|null | Total de páginas override (si el libro no tiene `pageCount`) |
| `percent` | int\|null | Porcentaje 0-100 (modo `percent`) |
| `startedAt` | DateTimeImmutable | Fecha de inicio |
| `updatedAt` | DateTimeImmutable | Última actualización |

> La combinación `(user_id, book_id)` tiene restricción `UNIQUE`.

---

## Autenticación y roles

La autenticación se gestiona mediante **sesiones** con un `LoginFormAuthenticator` personalizado. No se usa JWT.

| Rol | Descripción |
|---|---|
| `ROLE_USER` | Cualquier usuario registrado. Se asigna automáticamente a todos. |
| `ROLE_ADMIN` | Administrador de la web. Puede crear chats en cualquier club y gestionar el libro del mes. |

Los endpoints protegidos devuelven `401 Unauthorized` si el usuario no está autenticado, y `403 Forbidden` si no tiene permisos suficientes.

---

## Referencia de la API

Todas las rutas de la API tienen el prefijo `/api` y responden con `Content-Type: application/json`.

---

### Autenticación

Endpoints JSON para el flujo de autenticación del frontend SPA. Las sesiones se mantienen mediante cookies de sesión (`credentials: include`).

---

#### `POST /api/login` — Iniciar sesión

Manejado por el `json_login` de Symfony Security. No requiere controlador propio.

**Body JSON:**
```json
{ "email": "usuario@email.com", "password": "contraseña" }
```

**Respuesta `200`:**
```json
{
  "id": 3,
  "email": "usuario@email.com",
  "displayName": "María García",
  "avatar": null,
  "roles": ["ROLE_USER"]
}
```

**Errores:**
- `401` — credenciales incorrectas.

---

#### `GET /api/auth/me` — Usuario actual

Devuelve los datos del usuario autenticado. El frontend lo usa al iniciar para restaurar la sesión activa.

**Respuesta `200`:** mismo esquema que el login.

**Errores:**
- `401` — no autenticado.

---

#### `POST /api/auth/register` — Registrar usuario

Crea una nueva cuenta. El usuario queda marcado como verificado automáticamente (`isVerified: true`).

**Body JSON:**
```json
{ "email": "nuevo@email.com", "password": "micontraseña" }
```

| Campo | Validación |
|---|---|
| `email` | Obligatorio, formato email válido, único en el sistema |
| `password` | Obligatorio, mínimo 6 caracteres |

**Respuesta `201`:**
```json
{ "id": 7, "email": "nuevo@email.com" }
```

**Errores:**
- `400` — datos inválidos o contraseña demasiado corta.
- `409` — ya existe una cuenta con ese email.

---

#### `POST /api/auth/logout` — Cerrar sesión

Invalida la sesión del servidor.

**Respuesta `200`:**
```json
{ "status": "logged_out" }
```

---

### Libros — búsqueda externa

Proxy hacia la **Google Books API**. Los libros no se guardan en la base de datos hasta que un usuario los añade a una estantería, inicia un seguimiento de lectura, publica una reseña o se establecen como libro del mes de un club.

---

#### `GET /api/books/search` — Buscar libros

Búsqueda avanzada con múltiples filtros.

**Query params:**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `q` | string | Búsqueda general de texto libre |
| `title` | string | Filtro por título (`intitle:`) |
| `author` | string | Filtro por autor (`inauthor:`) |
| `isbn` | string | Búsqueda por ISBN-10 o ISBN-13 |
| `subject` | string | Filtro por categoría/género |
| `publisher` | string | Filtro por editorial |
| `page` | int | Página (default: `1`) |
| `limit` | int | Resultados por página (default: `20`, máx: `40`) |
| `orderBy` | string | `relevance` (default) \| `newest` |
| `lang` | string | Código de idioma (`es`, `en`…) |
| `printType` | string | `all` (default) \| `books` \| `magazines` |
| `filter` | string | `partial` \| `full` \| `free-ebooks` \| `paid-ebooks` \| `ebooks` |

> Al menos uno de `q`, `title`, `author`, `isbn`, `subject` o `publisher` es obligatorio.

**Respuesta `200`:**
```json
{
  "page": 1,
  "limit": 20,
  "totalItems": 347,
  "results": [
    {
      "externalId": "zyTCAlFPjgYC",
      "title": "Dune",
      "subtitle": null,
      "authors": ["Frank Herbert"],
      "publisher": "Hodder & Stoughton",
      "publishedDate": "2010-09-07",
      "categories": ["Fiction"],
      "language": "en",
      "description": "...",
      "pageCount": 896,
      "averageRating": 4.5,
      "ratingsCount": 1200,
      "thumbnail": "https://books.google.com/...",
      "previewLink": "https://...",
      "infoLink": "https://...",
      "isbn10": "0340960191",
      "isbn13": "9780340960196"
    }
  ]
}
```

---

#### `GET /api/books/{externalId}` — Detalle de un libro

Obtiene la información completa de un volumen de Google Books por su ID.

**Parámetros de ruta:**

| Parámetro | Descripción |
|---|---|
| `externalId` | ID del volumen en Google Books (ej: `zyTCAlFPjgYC`) |

**Respuesta `200`:** mismo esquema que un elemento de `results` en la búsqueda.

**Errores:**
- `404` — el libro no existe en Google Books.
- `502` — error al contactar con Google Books API.

---

### Estanterías

Las estanterías son colecciones personales de libros de un usuario. Requieren autenticación (`ROLE_USER`) en todos los endpoints excepto donde se indique.

---

#### `GET /api/shelves` — Listar estanterías

Devuelve todas las estanterías del usuario autenticado.

**Respuesta `200`:**
```json
[
  { "id": 1, "name": "Leídos" },
  { "id": 2, "name": "Quiero leer" }
]
```

---

#### `GET /api/shelves/full` — Estanterías con libros

Devuelve todas las estanterías del usuario incluyendo sus libros. Útil para cargar el perfil completo de una vez.

**Respuesta `200`:**
```json
[
  {
    "id": 1,
    "name": "Leídos",
    "books": [
      {
        "shelfBookId": 5,
        "status": "read",
        "orderIndex": 0,
        "addedAt": "2026-03-30T10:00:00+00:00",
        "book": {
          "id": 12,
          "externalId": "zyTCAlFPjgYC",
          "title": "Dune",
          "authors": ["Frank Herbert"],
          "coverUrl": "https://...",
          "publishedDate": "2010-09-07",
          "pageCount": 896,
          "language": "en",
          "isbn10": "0340960191",
          "isbn13": "9780340960196",
          "publisher": "Hodder & Stoughton",
          "description": "...",
          "categories": ["Fiction"]
        }
      }
    ]
  }
]
```

---

#### `POST /api/shelves` — Crear estantería

**Body JSON:**
```json
{ "name": "Favoritos" }
```

**Respuesta `201`:**
```json
{ "id": 3, "name": "Favoritos" }
```

---

#### `PATCH /api/shelves/{id}` — Renombrar estantería

**Body JSON:**
```json
{ "name": "Nuevo nombre" }
```

**Respuesta `200`:**
```json
{ "id": 1, "name": "Nuevo nombre" }
```

---

#### `DELETE /api/shelves/{id}` — Eliminar estantería

Elimina la estantería y todas sus entradas de libros (cascade).

**Respuesta `204`:** sin cuerpo.

---

#### `GET /api/shelves/{id}/books` — Libros de una estantería

**Respuesta `200`:** array de objetos `ShelfBook` (mismo formato que el campo `books` de `/api/shelves/full`).

---

#### `POST /api/shelves/{id}/books` — Añadir libro a estantería

Si el libro con el `externalId` indicado no existe en la base de datos, se importa automáticamente desde Google Books.

**Body JSON:**
```json
{
  "externalId": "zyTCAlFPjgYC",
  "status": "want_to_read"
}
```

| Campo | Obligatorio | Valores |
|---|---|---|
| `externalId` | Sí | ID de Google Books |
| `status` | No | `want_to_read` (default) \| `reading` \| `read` |

**Respuesta `201`:** objeto `ShelfBook` con el libro incluido.

**Errores:**
- `404` — el `externalId` no existe en Google Books.
- `409` — el libro ya está en esa estantería.

---

#### `PATCH /api/shelves/{id}/books/{bookId}` — Actualizar estado de lectura

`bookId` es el `shelfBookId` (no el `id` del libro).

**Body JSON:**
```json
{ "status": "reading" }
```

**Respuesta `200`:** objeto `ShelfBook` actualizado.

---

#### `POST /api/shelves/{id}/books/{bookId}/move` — Mover libro a otra estantería

**Body JSON:**
```json
{ "targetShelfId": 3 }
```

**Respuesta `200`:**
```json
{
  "shelfBookId": 5,
  "targetShelfId": 3,
  "status": "reading",
  "book": { ... }
}
```

**Errores:**
- `400` — la estantería destino es la misma que la origen.
- `409` — el libro ya existe en la estantería destino.

---

#### `DELETE /api/shelves/{id}/books/{bookId}` — Quitar libro de estantería

**Respuesta `204`:** sin cuerpo.

---

### Clubs de lectura

Un club de lectura es una comunidad de usuarios que leen un libro en común. Cada club puede tener un **libro del mes** y múltiples **hilos de chat** para discutirlo.

---

#### `GET /api/clubs` — Listar clubs

Devuelve todos los clubs (públicos y privados). No requiere autenticación.

**Respuesta `200`:**
```json
[
  {
    "id": 1,
    "name": "Amantes de la Ciencia Ficción",
    "description": "Club para lectores de sci-fi",
    "visibility": "public",
    "memberCount": 42,
    "userRole": "member",
    "hasPendingRequest": false,
    "owner": {
      "id": 3,
      "displayName": "María García",
      "email": "maria@example.com"
    },
    "currentBook": {
      "id": 12,
      "externalId": "zyTCAlFPjgYC",
      "title": "Dune",
      "authors": ["Frank Herbert"],
      "coverUrl": "https://...",
      "publishedDate": "2010-09-07",
      "since": "2026-03-01T00:00:00+00:00"
    }
  }
]
```

> `currentBook` es `null` si el club no tiene libro del mes asignado. `userRole` es `"admin"`, `"member"` o `null` si el usuario no es miembro o no está autenticado. `hasPendingRequest` indica si el usuario autenticado tiene una solicitud pendiente en ese club (solo relevante en clubs privados).

---

#### `POST /api/clubs` — Crear club

Requiere `ROLE_USER`. El creador se convierte automáticamente en `admin` del club.

**Body JSON:**
```json
{
  "name": "Mi Club de Lectura",
  "description": "Descripción opcional",
  "visibility": "public"
}
```

| Campo | Obligatorio | Valores |
|---|---|---|
| `name` | Sí | — |
| `description` | No | — |
| `visibility` | No | `public` (default) \| `private` |

**Respuesta `201`:**
```json
{ "id": 5, "name": "Mi Club de Lectura", "visibility": "public" }
```

---

#### `GET /api/clubs/{id}` — Detalle de un club

Incluye el rol del usuario autenticado en el club.

**Respuesta `200`:**
```json
{
  "id": 1,
  "name": "Amantes de la Ciencia Ficción",
  "description": "...",
  "visibility": "public",
  "memberCount": 42,
  "userRole": "admin",
  "hasPendingRequest": false,
  "owner": {
    "id": 3,
    "displayName": "María García",
    "email": "maria@example.com"
  },
  "currentBook": { ... }
}
```

> `userRole` es `"admin"`, `"member"` o `null` si el usuario no es miembro o no está autenticado.

---

#### `PATCH /api/clubs/{id}` — Editar club

Requiere ser `admin` del club.

**Body JSON** (todos los campos son opcionales):
```json
{
  "name": "Nuevo nombre",
  "description": "Nueva descripción",
  "visibility": "private"
}
```

**Respuesta `200`:** datos actualizados del club.

---

#### `DELETE /api/clubs/{id}` — Eliminar club

Requiere ser `admin` del club. Elimina el club y todos sus datos en cascade (miembros, solicitudes, chats, mensajes).

**Respuesta `204`:** sin cuerpo.

---

#### `POST /api/clubs/{id}/join` — Unirse a un club

Requiere `ROLE_USER`.

- **Club público:** el usuario se añade directamente como `member`.
- **Club privado:** se crea una solicitud de ingreso con estado `pending`.

**Respuesta `200`:**
```json
{ "status": "joined", "role": "member" }
```
```json
{ "status": "requested" }
```
```json
{ "status": "already_member", "role": "member" }
```
```json
{ "status": "already_requested", "requestStatus": "pending" }
```

---

#### `DELETE /api/clubs/{id}/leave` — Abandonar club

Requiere `ROLE_USER` y ser miembro del club.

> Un `admin` no puede abandonar el club si hay otros miembros. Debe transferir el rol primero.

**Respuesta `204`:** sin cuerpo.

---

#### `GET /api/clubs/{id}/members` — Lista de miembros

Los clubs privados solo muestran sus miembros a otros miembros del mismo club.

**Respuesta `200`:**
```json
[
  {
    "id": 7,
    "role": "admin",
    "joinedAt": "2026-01-15T10:00:00+00:00",
    "user": {
      "id": 3,
      "displayName": "María García",
      "avatar": "abc123.jpg"
    }
  }
]
```

---

#### `DELETE /api/clubs/{id}/members/{memberId}` — Expulsar miembro

Requiere ser `admin` del club. `memberId` es el `id` de la entrada `ClubMember`, no el `id` del usuario.

> Un admin no puede expulsarse a sí mismo; debe usar `/leave`.

**Respuesta `204`:** sin cuerpo.

---

#### `GET /api/clubs/{id}/requests` — Ver solicitudes pendientes

Requiere ser `admin` del club.

**Respuesta `200`:**
```json
[
  {
    "id": 2,
    "status": "pending",
    "requestedAt": "2026-03-28T18:00:00+00:00",
    "user": {
      "id": 8,
      "displayName": "Pedro López",
      "avatar": null
    }
  }
]
```

---

#### `POST /api/clubs/{id}/requests/{requestId}/approve` — Aceptar solicitud

Requiere ser `admin` del club. Cambia el estado a `approved` y crea la entrada `ClubMember` para el usuario.

**Respuesta `200`:**
```json
{ "status": "approved" }
```

---

#### `POST /api/clubs/{id}/requests/{requestId}/reject` — Rechazar solicitud

Requiere ser `admin` del club. Cambia el estado a `rejected`.

**Respuesta `200`:**
```json
{ "status": "rejected" }
```

---

#### `PUT /api/clubs/{id}/current-book` — Establecer libro del mes

Requiere ser `admin` del club o tener `ROLE_ADMIN`. Si el libro no existe en la base de datos local, se importa automáticamente desde Google Books.

**Body JSON:**
```json
{ "externalId": "zyTCAlFPjgYC" }
```

**Respuesta `200`:**
```json
{
  "id": 12,
  "externalId": "zyTCAlFPjgYC",
  "title": "Dune",
  "authors": ["Frank Herbert"],
  "coverUrl": "https://...",
  "publishedDate": "2010-09-07",
  "since": "2026-03-30T10:00:00+00:00"
}
```

---

#### `DELETE /api/clubs/{id}/current-book` — Quitar libro del mes

Requiere ser `admin` del club o tener `ROLE_ADMIN`.

**Respuesta `204`:** sin cuerpo.

---

### Chats de club

Los hilos de chat permiten organizar las discusiones de un club por temas (ej: por capítulos, por semanas…).

**Quién puede crear hilos:** solo `admin` del club o usuarios con `ROLE_ADMIN`.
**Quién puede enviar mensajes:** cualquier miembro del club, siempre que el hilo esté abierto (`isOpen: true`).

---

#### `GET /api/clubs/{clubId}/chats` — Listar hilos

Los clubs privados solo muestran sus hilos a sus miembros.

**Respuesta `200`:**
```json
[
  {
    "id": 1,
    "title": "Capítulos 1-10: Primeras impresiones",
    "isOpen": true,
    "messageCount": 23,
    "createdAt": "2026-03-01T09:00:00+00:00",
    "closedAt": null,
    "createdBy": {
      "id": 3,
      "displayName": "María García",
      "avatar": "abc123.jpg"
    }
  }
]
```

---

#### `POST /api/clubs/{clubId}/chats` — Crear hilo

Requiere ser `admin` del club o tener `ROLE_ADMIN`.

**Body JSON:**
```json
{ "title": "Capítulos 11-20: El giro argumental" }
```

**Respuesta `201`:** objeto `ClubChat` serializado.

---

#### `GET /api/clubs/{clubId}/chats/{chatId}` — Detalle de un hilo

**Respuesta `200`:** objeto `ClubChat` serializado.

---

#### `PATCH /api/clubs/{clubId}/chats/{chatId}` — Editar hilo

Pueden editar el hilo el creador o un `admin` del club.

**Body JSON** (campos opcionales):
```json
{
  "title": "Título corregido",
  "isOpen": false
}
```

> Al cerrar un hilo (`isOpen: false`) se registra `closedAt` automáticamente. Al reabrirlo, `closedAt` se pone a `null`.

**Respuesta `200`:** objeto `ClubChat` actualizado.

---

#### `DELETE /api/clubs/{clubId}/chats/{chatId}` — Eliminar hilo

Requiere ser `admin` del club. Elimina el hilo y todos sus mensajes en cascade.

**Respuesta `204`:** sin cuerpo.

---

#### `GET /api/clubs/{clubId}/chats/{chatId}/messages` — Listar mensajes

Mensajes ordenados de más antiguo a más reciente. Soporta paginación.

Los clubs privados solo muestran mensajes a sus miembros.

**Query params:**

| Parámetro | Default | Máximo |
|---|---|---|
| `page` | `1` | — |
| `limit` | `50` | `100` |

**Respuesta `200`:**
```json
{
  "page": 1,
  "limit": 50,
  "total": 87,
  "messages": [
    {
      "id": 1,
      "content": "Me ha parecido increíble la descripción de Arrakis.",
      "createdAt": "2026-03-10T11:30:00+00:00",
      "user": {
        "id": 5,
        "displayName": "Carlos Ruiz",
        "avatar": null
      }
    }
  ]
}
```

---

#### `POST /api/clubs/{clubId}/chats/{chatId}/messages` — Enviar mensaje

Requiere `ROLE_USER` y ser miembro del club. El hilo debe estar abierto (`isOpen: true`).

**Body JSON:**
```json
{ "content": "¡Totalmente de acuerdo! La ambientación es magnífica." }
```

**Respuesta `201`:** objeto `ClubChatMessage` serializado.

**Errores:**
- `400` — el hilo está cerrado.
- `403` — el usuario no es miembro del club.

---

#### `DELETE /api/clubs/{clubId}/chats/{chatId}/messages/{messageId}` — Borrar mensaje

Puede borrar el mensaje su autor o un `admin` del club.

**Respuesta `204`:** sin cuerpo.

---

### Perfil de usuario

---

#### `GET /api/profile` — Obtener perfil propio

Requiere `ROLE_USER`.

**Respuesta `200`:**
```json
{
  "id": 3,
  "email": "maria@example.com",
  "displayName": "María García",
  "bio": "Lectora empedernida.",
  "avatar": "abc123.jpg",
  "isPrivate": false,
  "followers": 25,
  "following": 10,
  "shelves": [
    { "id": 1, "name": "Leídos" }
  ],
  "clubs": [
    { "id": 1, "name": "Amantes de la Sci-Fi", "visibility": "public", "role": "admin" }
  ],
  "privacy": {
    "isPrivate": false,
    "shelvesPublic": true,
    "clubsPublic": false
  }
}
```

---

#### `PUT /api/profile` — Editar perfil

Requiere `ROLE_USER`.

**Body JSON** (campos opcionales):
```json
{
  "displayName": "María G.",
  "bio": "Nueva bio"
}
```

**Respuesta `200`:** perfil completo actualizado.

---

#### `POST /api/profile/avatar` — Subir avatar

Requiere `ROLE_USER`. Enviar como `multipart/form-data` con el campo `avatar`.

**Respuesta `200`:**
```json
{ "avatar": "nuevo_archivo.jpg" }
```

---

#### `PUT /api/profile/password` — Cambiar contraseña

Requiere `ROLE_USER`.

**Body JSON:**
```json
{
  "currentPassword": "contraseña_actual",
  "newPassword": "nueva_contraseña"
}
```

> La nueva contraseña debe tener al menos 6 caracteres.

**Respuesta `200`:**
```json
{ "status": "password_updated" }
```

---

#### `PUT /api/profile/privacy` — Actualizar privacidad

Requiere `ROLE_USER`.

**Body JSON** (campos opcionales):
```json
{
  "isPrivate": true,
  "shelvesPublic": false,
  "clubsPublic": true
}
```

**Respuesta `200`:**
```json
{ "isPrivate": true, "shelvesPublic": false, "clubsPublic": true }
```

---

#### `GET /api/users/{id}` — Perfil público de otro usuario

No requiere autenticación. Respeta la configuración de privacidad del usuario.

**Respuesta `200`:**
```json
{
  "id": 5,
  "displayName": "Carlos Ruiz",
  "bio": "...",
  "avatar": null,
  "isPrivate": false,
  "followers": 12,
  "following": 8,
  "followStatus": "none",
  "shelves": [ ... ],
  "clubs": [ ... ]
}
```

> `followStatus` puede ser `"none"`, `"pending"` (solicitud enviada por el autenticado) o `"accepted"` (ya lo sigue). Es `"none"` si el usuario no está autenticado. `shelves` y `clubs` son `null` si el perfil es privado y el visitante no sigue al usuario.

---

### Seguimientos de usuarios

---

#### `GET /api/users/{id}/followers` — Lista de seguidores de un usuario

Devuelve los usuarios que siguen al usuario con el `id` indicado.

**Respuesta `200`:**
```json
[
  {
    "id": 3,
    "displayName": "María García",
    "avatar": "abc123.jpg"
  }
]
```

---

#### `GET /api/users/{id}/following` — Lista de usuarios seguidos

Devuelve los usuarios a los que sigue el usuario con el `id` indicado.

**Respuesta `200`:** mismo esquema que `/followers`.

---

#### `POST /api/users/{id}/follow` — Seguir o solicitar seguir

Requiere `ROLE_USER`. No puede seguirse a uno mismo.

- Si el perfil del usuario es **público**: el seguimiento se acepta directamente (`status: accepted`).
- Si el perfil es **privado**: se crea una solicitud pendiente (`status: pending`).

**Respuesta `200`:**
```json
{ "status": "accepted" }
```
o
```json
{ "status": "pending" }
```

**Errores:**
- `409` — ya sigues a ese usuario o ya tienes una solicitud pendiente.

---

#### `DELETE /api/users/{id}/follow` — Dejar de seguir / cancelar solicitud

Requiere `ROLE_USER`. Elimina el registro `Follow` (aceptado o pendiente).

**Respuesta `204`:** sin cuerpo.

**Errores:**
- `404` — no existe el seguimiento.

---

### Reseñas de libros

---

#### `GET /api/books/{externalId}/reviews` — Listar reseñas de un libro

No requiere autenticación. Si el libro no existe en la base de datos local, devuelve una respuesta vacía sin error.

**Respuesta `200`:**
```json
{
  "stats": {
    "average": 4.2,
    "count": 15
  },
  "myRating": {
    "id": 7,
    "rating": 5,
    "content": "Absolutamente imprescindible."
  },
  "reviews": [
    {
      "id": 7,
      "rating": 5,
      "content": "Absolutamente imprescindible.",
      "createdAt": "2026-04-01T18:00:00+00:00",
      "user": {
        "id": 3,
        "displayName": "María García",
        "avatar": "abc123.jpg"
      }
    }
  ]
}
```

> `myRating` es `null` si el usuario no está autenticado o no ha dejado reseña. `stats.average` es `null` si no hay reseñas aún.

---

#### `POST /api/books/{externalId}/reviews` — Crear o actualizar reseña propia

Requiere `ROLE_USER`. Si el usuario ya tiene una reseña para ese libro, la actualiza (upsert). Si el libro no existe en la BD local, se importa automáticamente desde Google Books.

**Body JSON:**
```json
{ "rating": 5, "content": "Texto de la reseña (opcional)" }
```

`rating` es obligatorio, entre 1 y 5. `content` es opcional.

**Respuesta `201`:**
```json
{
  "review": { ... },
  "stats": { "average": 4.3, "count": 16 }
}
```

**Errores:**
- `400` — rating inválido.
- `404` — el `externalId` no existe en Google Books.

---

#### `DELETE /api/books/{externalId}/reviews` — Eliminar reseña propia

Requiere `ROLE_USER`. Solo elimina la reseña del usuario autenticado.

**Respuesta `200`:**
```json
{ "stats": { "average": 4.1, "count": 14 } }
```

**Errores:**
- `404` — el libro no existe o el usuario no tiene reseña.

---

### Progreso de lectura

---

#### `GET /api/reading-progress` — Libros en progreso

Requiere `ROLE_USER`. Devuelve los libros que el usuario está leyendo actualmente, ordenados por última actualización.

**Respuesta `200`:**
```json
[
  {
    "id": 1,
    "mode": "pages",
    "currentPage": 230,
    "totalPages": 896,
    "percent": null,
    "computed": 25,
    "startedAt": "2026-03-15T00:00:00+00:00",
    "updatedAt": "2026-04-01T20:00:00+00:00",
    "book": {
      "id": 12,
      "externalId": "zyTCAlFPjgYC",
      "title": "Dune",
      "authors": ["Frank Herbert"],
      "coverUrl": "https://...",
      "pageCount": 896
    }
  }
]
```

> `computed` es el porcentaje calculado en el servidor (0-100), independientemente del `mode`.

---

#### `POST /api/reading-progress` — Añadir libro al seguimiento

Requiere `ROLE_USER`. Si el libro no está en la BD local, se importa desde Google Books. Si ya existe un registro para ese libro, devuelve el existente con `200` (idempotente).

**Body JSON:**
```json
{ "externalId": "zyTCAlFPjgYC", "mode": "percent" }
```

`mode` puede ser `pages` o `percent` (default: `percent`). Opcionalmente puede incluirse `totalPages` si se conoce de antemano y el libro no tiene `pageCount`.

**Respuesta `201`** (o `200` si ya existía): objeto `ReadingProgress` creado o existente.

---

#### `PATCH /api/reading-progress/{id}` — Actualizar progreso

Requiere `ROLE_USER`. Solo el propietario puede actualizar su registro.

**Body JSON** (todos los campos opcionales):
```json
{
  "mode": "pages",
  "currentPage": 350,
  "totalPages": 896,
  "percent": null
}
```

**Respuesta `200`:** objeto `ReadingProgress` actualizado.

---

#### `DELETE /api/reading-progress/{id}` — Quitar libro del seguimiento

Requiere `ROLE_USER`.

**Respuesta `204`:** sin cuerpo.

---

## Integración con Google Books API

La clave de la API se configura en `.env` bajo `GOOGLE_BOOKS_API_KEY`.

### Flujo de importación de libros

El sistema evita llamadas redundantes a Google Books mediante una caché local en la tabla `book`:

```
Frontend solicita añadir libro con externalId "zyTCAlFPjgYC"
        │
        ▼
¿Existe en BD con externalSource = "google_books"?
        │
   NO ──┤──► GET googleapis.com/books/v1/volumes/zyTCAlFPjgYC
        │              │
        │         Guardar en tabla `book`
        │
   SÍ ──┤
        │
        ▼
Crear ShelfBook / ReadingProgress / BookReview / asignar como currentBook del club
```

Este mecanismo se aplica en:
- `POST /api/shelves/{id}/books` — añadir libro a estantería.
- `PUT /api/clubs/{id}/current-book` — establecer libro del mes.
- `POST /api/reading-progress` — cuando se añade un libro al seguimiento de lectura.
- `POST /api/books/{externalId}/reviews` — cuando se crea la primera reseña de un libro no importado aún.

---

## Convenciones de respuesta

### Códigos de estado

| Código | Significado |
|---|---|
| `200 OK` | Operación exitosa |
| `201 Created` | Recurso creado correctamente |
| `204 No Content` | Operación exitosa sin cuerpo de respuesta (DELETE) |
| `400 Bad Request` | Datos de entrada inválidos |
| `401 Unauthorized` | No autenticado |
| `403 Forbidden` | Autenticado pero sin permisos suficientes |
| `404 Not Found` | Recurso no encontrado |
| `409 Conflict` | Conflicto con el estado actual (ej: libro duplicado en estantería) |
| `502 Bad Gateway` | Error al contactar con Google Books API |

### Formato de error

Todos los errores devuelven un objeto JSON con la clave `error`:

```json
{ "error": "Descripción del error en español" }
```

### Fechas

Todas las fechas se devuelven en formato **ISO 8601 / RFC 3339**:
```
2026-03-30T10:00:00+00:00
```

---

---

# Frontend

SPA (Single Page Application) desarrollada con **React 18** y **TypeScript**, que consume la API REST del backend Symfony. Ubicada en la carpeta `frontend/` del repositorio.

---

## Stack frontend

| Tecnología | Versión | Uso |
|---|---|---|
| React | 18.3 | Librería de interfaz de usuario |
| TypeScript | 5.6 | Tipado estático |
| Vite | 5.4 | Bundler y servidor de desarrollo |
| React Router DOM | 6.28 | Enrutamiento client-side |
| CSS custom con variables CSS | — | Sistema de diseño propio (sin frameworks externos) |

> No se usa ningún framework de componentes UI externo (MUI, shadcn, Tailwind, etc.) para mantener el control total del diseño y minimizar dependencias. Todo el sistema visual se implementa mediante variables CSS nativas y clases utilitarias propias definidas en `frontend/src/index.css`.

---

## Estructura del proyecto frontend

```
frontend/
├── index.html
├── package.json
├── vite.config.ts
├── tsconfig.json
└── src/
    ├── main.tsx
    ├── App.tsx
    ├── index.css               # Sistema de diseño completo con variables CSS y utilidades
    │
    ├── context/
    │   └── AuthContext.tsx
    │
    ├── components/
    │   ├── Navbar.tsx
    │   ├── Footer.tsx
    │   ├── Spinner.tsx
    │   └── PrivateRoute.tsx
    │
    ├── api/
    │   ├── client.ts
    │   ├── auth.ts
    │   ├── books.ts
    │   ├── shelves.ts
    │   ├── clubs.ts
    │   ├── chats.ts
    │   ├── profile.ts
    │   ├── reviews.ts          # Reseñas de libros
    │   └── readingProgress.ts  # Seguimiento de lectura
    │
    └── pages/
        ├── HomePage.tsx
        ├── LoginPage.tsx
        ├── RegisterPage.tsx
        ├── BooksPage.tsx
        ├── BookDetailPage.tsx  # Detalle interno de libro con reseñas
        ├── ClubsPage.tsx
        ├── ClubDetailPage.tsx
        ├── ShelvesPage.tsx
        ├── ProfilePage.tsx
        └── PublicProfilePage.tsx
```

---

## Instalación del frontend

### Prerrequisitos

- Node.js ≥ 18
- El backend Symfony corriendo en `http://localhost:8000`

### Pasos

```bash
# Desde la raíz del repositorio
cd frontend

# Instalar dependencias
npm install

# Iniciar el servidor de desarrollo
npm run dev
# → http://localhost:5173
```

### Comandos disponibles

| Comando | Descripción |
|---|---|
| `npm run dev` | Servidor de desarrollo con HMR en `localhost:5173` |
| `npm run build` | Compilación de producción (TypeScript + Vite) en `dist/` |
| `npm run preview` | Previsualizar el build de producción localmente |

---

## Arquitectura frontend

### Proxy de desarrollo

En lugar de configurar CORS en el backend, Vite actúa como proxy: todas las peticiones a `/api/*` se redirigen transparentemente al backend en `http://localhost:8000`. Esto elimina los problemas de CORS en desarrollo y simula el comportamiento del despliegue en producción.

```
Navegador → localhost:5173/api/... → Vite proxy → localhost:8000/api/...
```

Configurado en `vite.config.ts`:

```ts
server: {
  proxy: {
    '/api': { target: 'http://localhost:8000', changeOrigin: true }
  }
}
```

### Gestión de sesión

La autenticación usa **sesiones de servidor** (no JWT). El navegador almacena la cookie de sesión automáticamente. Todas las peticiones al backend se realizan con `credentials: 'include'` para que la cookie se envíe en cada request.

### Estado global de autenticación

`AuthContext` centraliza el estado del usuario en toda la aplicación:

```
App init
  └─► GET /api/auth/me
        ├─ 200 → setUser(data)   [sesión activa]
        └─ 401 → setUser(null)   [no autenticado]
```

El contexto expone:

| Propiedad / Método | Tipo | Descripción |
|---|---|---|
| `user` | `User \| null` | Datos del usuario autenticado |
| `loading` | `boolean` | `true` mientras se verifica la sesión al inicio |
| `login(email, password)` | `Promise<void>` | Llama a `/api/login` y actualiza el estado |
| `logout()` | `Promise<void>` | Llama a `/api/auth/logout` y limpia el estado |
| `setUser(user)` | `void` | Actualización manual (p.ej. tras editar el perfil) |

### Rutas protegidas

`PrivateRoute` envuelve cualquier página que requiera autenticación. Si el usuario no está logado, redirige a `/login` guardando la ruta original para volver tras el login:

```tsx
<Route path="/shelves" element={
  <PrivateRoute><ShelvesPage /></PrivateRoute>
} />
```

### Cliente HTTP

`src/api/client.ts` centraliza todas las llamadas fetch con un wrapper tipado:

```ts
// Ejemplo de uso
const books = await api.get<Book[]>('/shelves/1/books')
await api.post('/shelves', { name: 'Favoritos' })
await api.delete('/shelves/1/books/5')
```

- Añade automáticamente `Content-Type: application/json` y `credentials: include`
- Lanza un `Error` con el mensaje del campo `error` del JSON de respuesta
- Maneja `204 No Content` devolviendo `undefined` sin intentar parsear el cuerpo

---

## Sistema de diseño

El sistema de diseño se implementa completamente mediante **variables CSS nativas** definidas en `:root` en `frontend/src/index.css`. No se usa ningún framework CSS externo.

### Paleta de colores

| Variable | Valor | Uso |
|---|---|---|
| `--color-primary` | `#7c5cbf` | Color principal (púrpura) — botones primarios, títulos |
| `--color-primary-dark` | `#6246a3` | Estado hover del primario |
| `--color-accent` | `#e07b54` | Acento cálido (coral) — badges, highlights |
| `--color-accent-dark` | `#c9653d` | Estado hover del acento |
| `--color-bg` | `#faf7f4` | Fondo general (tono papel cálido) |
| `--color-surface` | `#ffffff` | Fondo de tarjetas y modales |
| `--color-border` | `#e8e0f0` | Bordes sutiles |
| `--color-text` | `#2d2640` | Texto principal |
| `--color-text-muted` | `#9085a0` | Texto secundario/placeholder |
| `--color-danger` | `#e05454` | Acciones destructivas, errores |
| `--color-success` | `#4caf7d` | Confirmaciones, éxito |

### Componentes CSS globales

Clases utilitarias definidas en `index.css` y disponibles en todo el JSX:

#### Botones

| Clase | Descripción |
|---|---|
| `btn btn-primary` | Botón principal púrpura |
| `btn btn-secondary` | Botón secundario con borde |
| `btn btn-accent` | Botón de acento coral |
| `btn btn-danger` | Botón de acción destructiva |
| `btn btn-ghost` | Botón transparente |
| `btn-sm` / `btn-lg` | Modificadores de tamaño |

#### Formularios

```tsx
<label className="form-label" htmlFor="email">Email</label>
<input id="email" type="email" className="form-control" />
```

Clases disponibles: `.form-control`, `.form-control-sm`, `.form-label`, `.form-group`.

#### Tarjetas

```tsx
<div className="card">
  <div className="card-header">Título</div>
  <div className="card-body">Contenido</div>
  <div className="card-footer">Pie</div>
</div>
```

#### Badges

| Clase | Descripción |
|---|---|
| `badge badge-primary` | Badge púrpura |
| `badge badge-accent` | Badge coral |
| `badge badge-neutral` | Badge neutro |
| `badge badge-danger` | Badge de alerta |

#### Layout y estado

| Clase | Descripción |
|---|---|
| `page-content` | Contenedor con `max-width` y padding responsive |
| `loading-state` | Contenedor para estado de carga |
| `empty-state` | Contenedor para estado vacío |

#### Alertas

```tsx
<div className="alert alert-danger">Mensaje de error</div>
<div className="alert alert-success">Operación correcta</div>
```

---

## Páginas implementadas

### `/` — Home

Landing page pública con sección hero y tres tarjetas de características.

- El **hero** adapta los botones de acción según el estado de autenticación: si el usuario está logado muestra accesos directos a clubs y estanterías; si no, muestra registro y exploración.
- Las **feature cards** enlazan directamente a las secciones principales de la app con efecto hover animado.

### `/login` — Inicio de sesión

Formulario centrado con card flotante.

- Tras un login exitoso, redirige automáticamente a la ruta que el usuario intentaba visitar (comportamiento `from` de React Router), o a `/` si llegó directamente.
- Los errores de credenciales se muestran inline sin recargar la página.

### `/register` — Registro

Formulario de creación de cuenta con validación client-side previa al envío:

| Validación | Dónde |
|---|---|
| Contraseñas coinciden | Cliente (antes del fetch) |
| Mínimo 6 caracteres | Cliente + servidor |
| Email con formato válido | Servidor |
| Email único | Servidor (devuelve `409`) |

Tras un registro exitoso redirige a `/login`.

### `/books` — Buscador de libros

Búsqueda en tiempo real contra `GET /api/books/search`, que a su vez consulta la Google Books API.

- Resultados presentados como tarjetas con portada, autores, año y descripción recortada. Cada tarjeta enlaza a la página interna del libro (`/books/:externalId`).
- Usuarios autenticados pueden **añadir cualquier libro a una estantería** directamente desde el resultado: seleccionan la estantería y el estado (`quiero leer / leyendo / leído`) con un mini-formulario inline que se despliega sin cambiar de página.
- Si el usuario no está logado, el botón redirige a `/login` preservando la ruta de retorno.

### `/books/:externalId` — Detalle interno de libro

Página de detalle completo de un libro con toda la información disponible.

- **Hero**: portada (160px), título, subtítulo, autores, pills de metadatos (editorial, fecha, páginas, idioma, categorías, ISBNs).
- **Botones de acción**: `+ Estantería` (abre drawer lateral para elegir o crear estantería) y `Estoy leyendo` (añade el libro al tracker de lectura activa con estado visual si ya está en seguimiento).
- **Sinopsis**: descripción del libro renderizada como HTML (los libros de Google Books pueden incluir etiquetas `<p>`, `<b>`, etc.).
- **Valoraciones de la comunidad**: sección estilo Amazon con puntuación global prominente (número grande + estrellas + contador) seguida de las reseñas individuales. Usuarios autenticados ven un formulario interactivo de reseña (selector de estrellas con hover + textarea); si ya tienen reseña publicada, se muestra en modo lectura con opciones de editar y eliminar.

### `/clubs` — Clubs de lectura

Listado de todos los clubs con búsqueda por nombre.

- Muestra tipo (público/privado), número de miembros y libro del mes activo.
- El rol del usuario en cada club se muestra como badge (Administrador / Miembro).
- Botón **"+ Nuevo club"** (solo para usuarios autenticados) abre un modal con campos: nombre, descripción opcional y toggle público/privado.

### `/clubs/:id` — Detalle del club

Vista completa de un club con tres tabs:

| Tab | Visible para | Contenido |
|---|---|---|
| Chats | Todos | Hilos de discusión colapsables con mensajes paginados |
| Miembros | Todos | Lista con roles; admin puede expulsar |
| Solicitudes | Admin + club privado | Aprobar o rechazar peticiones de acceso |

**Panel de libro del mes** (solo admin): buscador de Google Books integrado que permite establecer o cambiar el libro mensual del club sin salir de la página.

Los hilos de chat muestran si están abiertos o cerrados. Solo los admins del club (o `ROLE_ADMIN`) pueden crear y eliminar hilos. Cualquier miembro puede enviar mensajes en hilos abiertos; admins y el propio autor pueden borrar sus mensajes.

### `/shelves` *(ruta privada)* — Mis estanterías

Gestión completa de estanterías con layout sidebar + panel principal.

- **Tracker de lectura activa**: encima de las estanterías se muestra un panel colapsable que lista todos los libros que el usuario está leyendo actualmente. Cada tarjeta incluye: portada, título enlazado a `/books/:externalId`, autores, barra de progreso visual, selector de modo (páginas / porcentaje), inputs de actualización y botón de quitar del seguimiento.
- **Sidebar**: navega entre estanterías, muestra contador de libros, inline rename al pasar el ratón, botón de eliminar (no disponible en la estantería por defecto).
- **Panel**: lista de libros con portada, cambio de estado de lectura con dropdown inline, menú contextual para mover el libro a otra estantería o eliminarlo.
- Formulario de nueva estantería visible con un click sin abandonar la vista.

### `/profile` *(ruta privada)* — Mi perfil

Panel de configuración personal dividido en varias secciones:

| Sección | Funcionalidad |
|---|---|
| Información personal | Editar nombre visible y biografía; avatar con upload al pasar el ratón |
| Seguidores/Seguidos | Contadores clicables que abren un modal con lista de usuarios |
| Privacidad | Toggle "Perfil privado" (`isPrivate`) — los usuarios no seguidores ven el perfil pero no sus estanterías ni clubs. Toggles independientes para hacer públicas/privadas estanterías y participación en clubs |
| Cambiar contraseña | Verificación de contraseña actual + nueva con confirmación |
| Sesión | Botón de cierre de sesión |

### `/users/:id` — Perfil público

Página de perfil de cualquier usuario de la plataforma.

- Muestra avatar, nombre, bio, contadores de seguidores y seguidos (clicables: abre modal con lista de usuarios).
- Botón de seguimiento con tres estados: `Seguir` / `Solicitud enviada` / `Siguiendo`.
- Si el perfil es privado y el visitante no sigue al usuario, las secciones de estanterías y clubs aparecen ocultas.
- Si el propio usuario visita su propio `id`, se redirige automáticamente a `/profile`.

---

## Comunicación con la API

Cada módulo tiene su propio archivo en `src/api/` que usa el cliente base:

```
src/api/
├── client.ts          — wrapper fetch genérico con credentials: 'include' y manejo de errores
├── auth.ts            — login, register, logout, me
├── books.ts           — search, get (detalle)
├── shelves.ts         — CRUD estanterías y libros (add, status, move, remove)
├── clubs.ts           — CRUD clubs, join/leave, members, requests, currentBook
├── chats.ts           — hilos y mensajes (list, create, send, delete)
├── profile.ts         — perfil, avatar, privacidad (isPrivate incluido), contraseña, followers, following
├── reviews.ts         — reseñas de libros (list, upsert, delete)
└── readingProgress.ts — seguimiento de lectura (list, add, update, delete)
```

### Ejemplo de flujo completo

```
Usuario escribe en buscador
        │
        ▼
BooksPage.tsx llama a booksApi.search({ q: "dune" })
        │
        ▼
api.get<SearchResult>('/books/search?q=dune')
        │
        ▼              ← Vite proxy →
GET /api/books/search?q=dune   →   Symfony BookExternalApiController
        │
        ▼
Google Books API  →  normalizar  →  { page, totalItems, results[] }
        │
        ▼
Renderizar tarjetas de resultado en React
```

---

**Páginas frontend nuevas o actualizadas:** `/books/:externalId` (detalle de libro con reseñas), `/users/:id` (perfil público con seguimiento), tracker de lectura en `/shelves`, y secciones de seguidores y privacidad ampliada en `/profile`.

---

## 9. Sistema de Publicaciones (Posts)

La versión más reciente de la plataforma incorpora un sistema de publicaciones de estilo Instagram: los usuarios pueden subir fotos con descripción, dar "me gusta" a las publicaciones ajenas y comentarlas. Además, la página de inicio muestra un feed personalizado con las publicaciones de las personas a las que el usuario sigue.

Esta funcionalidad se apoya en tres nuevas entidades de base de datos, un controlador REST dedicado, tres nuevos repositorios, un módulo de API frontend y un componente React reutilizable.

---

### 9.1 Corrección del registro: generación automática de `displayName`

Antes de documentar las publicaciones conviene mencionar una corrección que se aplicó al flujo de registro. La columna `display_name` de la tabla `user` tiene restricción `NOT NULL` y `UNIQUE`, pero el controlador de registro (`AuthApiController::register`) no la asignaba, lo que producía un error 500 de MySQL al intentar crear el usuario.

**Solución aplicada en `src/Controller/Api/AuthApiController.php`:**

```php
// Generar un displayName único a partir del prefijo del e-mail
$base = strstr($email, '@', true);
$base = preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'usuario';

$candidate = $base;
$suffix = 1;
while ($em->getRepository(User::class)->findOneBy(['displayName' => $candidate])) {
    $candidate = $base . $suffix;
    $suffix++;
}
$user->setDisplayName($candidate);
```

El algoritmo:
1. Extrae la parte local del e-mail (antes de `@`).
2. Elimina caracteres no alfanuméricos ni guión bajo.
3. Si el nombre ya está en uso, añade un sufijo numérico incremental (`maria`, `maria1`, `maria2`, …).

El usuario puede cambiar su `displayName` en cualquier momento desde el perfil.

---

### 9.2 Entidades de base de datos

#### `Post`

Representa una publicación individual. Archivo: `src/Entity/Post.php`.

| Campo | Tipo Doctrine | Columna SQL | Notas |
|---|---|---|---|
| `id` | `int` (PK, autoincrement) | `id` | Identificador único |
| `user` | `ManyToOne → User` | `user_id` | Autor. CASCADE remove en User |
| `imagePath` | `string(255)` | `image_path` | Nombre del archivo guardado en `public/uploads/posts/` |
| `description` | `text` (nullable) | `description` | Texto opcional de la publicación |
| `createdAt` | `DateTimeImmutable` | `created_at` | Fecha/hora de creación |
| `likes` | `OneToMany → PostLike` | — | Colección de likes (orphanRemoval) |
| `comments` | `OneToMany → PostComment` | — | Colección de comentarios (orphanRemoval) |

El constructor inicializa `createdAt` con `new \DateTimeImmutable()` y ambas colecciones con `new ArrayCollection()`.

#### `PostLike`

Registra que un usuario ha dado "me gusta" a una publicación. Archivo: `src/Entity/PostLike.php`.

| Campo | Tipo Doctrine | Columna SQL | Notas |
|---|---|---|---|
| `id` | `int` (PK) | `id` | — |
| `post` | `ManyToOne → Post` | `post_id` | CASCADE remove en Post |
| `user` | `ManyToOne → User` | `user_id` | Quién dio like |
| `createdAt` | `DateTimeImmutable` | `created_at` | — |

Restricción de unicidad `uq_post_like` sobre `(post_id, user_id)` para evitar likes duplicados. Se define mediante la anotación `#[ORM\UniqueConstraint]` a nivel de clase.

#### `PostComment`

Almacena un comentario sobre una publicación. Archivo: `src/Entity/PostComment.php`.

| Campo | Tipo Doctrine | Columna SQL | Notas |
|---|---|---|---|
| `id` | `int` (PK) | `id` | — |
| `post` | `ManyToOne → Post` | `post_id` | CASCADE remove en Post |
| `user` | `ManyToOne → User` | `user_id` | Autor del comentario |
| `content` | `text` | `content` | Contenido del comentario |
| `createdAt` | `DateTimeImmutable` | `created_at` | — |

---

### 9.3 Repositorios

#### `PostRepository`

Ubicación: `src/Repository/PostRepository.php`.

| Método | Descripción |
|---|---|
| `findByUser(User $user, int $limit = 30): array` | Devuelve las últimas publicaciones del usuario ordenadas por `createdAt DESC` |
| `findFeed(User $me, int $limit = 40): array` | Devuelve las publicaciones propias más las de usuarios a los que `$me` sigue con estado `accepted`, usando LEFT JOIN sobre la entidad `Follow` |

El método `findFeed` evita el problema N+1 resolviendo el join directamente en DQL:

```php
return $this->createQueryBuilder('p')
    ->leftJoin(Follow::class, 'f', 'WITH',
        'f.following = p.user AND f.follower = :me AND f.status = :status')
    ->where('p.user = :me OR f.id IS NOT NULL')
    ->setParameter('me', $me)
    ->setParameter('status', 'accepted')
    ->orderBy('p.createdAt', 'DESC')
    ->setMaxResults($limit)
    ->getQuery()
    ->getResult();
```

#### `PostLikeRepository`

Ubicación: `src/Repository/PostLikeRepository.php`.

| Método | Descripción |
|---|---|
| `findByPostAndUser(Post $post, User $user): ?PostLike` | Busca un like existente para comprobar si el usuario ya dio like |
| `countByPost(Post $post): int` | Cuenta el total de likes de una publicación |

#### `PostCommentRepository`

Ubicación: `src/Repository/PostCommentRepository.php`.

| Método | Descripción |
|---|---|
| `findByPost(Post $post): array` | Devuelve todos los comentarios de una publicación ordenados por `createdAt ASC` |

---

### 9.4 API de publicaciones

Todos los endpoints están implementados en `src/Controller/Api/PostApiController.php` con el prefijo de ruta `/api`.

#### Formato de respuesta — Post

Todos los endpoints que devuelven publicaciones utilizan el siguiente objeto JSON:

```json
{
  "id": 12,
  "imagePath": "550e8400-e29b-41d4-a716-446655440000.jpg",
  "description": "Acabo de terminar este libro increíble.",
  "createdAt": "2026-03-28T17:45:00+00:00",
  "likes": 7,
  "liked": true,
  "commentCount": 3,
  "user": {
    "id": 4,
    "displayName": "maria",
    "avatar": null
  }
}
```

- `liked`: `true` si el usuario autenticado ya dio like, `false` en caso contrario. Siempre `false` para usuarios no autenticados.
- `imagePath`: nombre del fichero, se accede en el cliente con `/uploads/posts/{imagePath}`.

#### Formato de respuesta — Comentario

```json
{
  "id": 5,
  "content": "¡Me encantó también!",
  "createdAt": "2026-03-28T18:10:00+00:00",
  "user": {
    "id": 9,
    "displayName": "juan",
    "avatar": "avatars/juan.jpg"
  }
}
```

---

#### `GET /api/posts` — Feed del usuario autenticado

Devuelve las publicaciones propias del usuario autenticado más las de los usuarios que sigue (con estado `accepted`), ordenadas de más reciente a más antigua. Requiere sesión activa.

**Respuesta 200:**
```json
[
  { ...post },
  { ...post }
]
```

**Respuesta 401** si no hay sesión.

---

#### `GET /api/users/{id}/posts` — Publicaciones de un usuario

Devuelve las publicaciones públicas de cualquier usuario identificado por `{id}`. No requiere autenticación, aunque el campo `liked` siempre será `false` si no hay sesión.

**Parámetros de ruta:**
| Nombre | Tipo | Descripción |
|---|---|---|
| `id` | `int` | ID del usuario cuyas publicaciones se quieren obtener |

**Respuesta 200:** array de objetos Post.
**Respuesta 404** si el usuario no existe.

---

#### `POST /api/posts` — Crear publicación

Crea una nueva publicación con imagen adjunta. Requiere sesión activa.

La petición debe enviarse como `multipart/form-data`, **no** como JSON.

**Campos del formulario:**
| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `image` | `file` | Sí | Imagen de la publicación. Extensiones permitidas: `jpg`, `jpeg`, `png`, `gif`, `webp` |
| `description` | `string` | No | Texto descriptivo de la publicación |

El servidor genera un nombre de fichero UUID v4 para evitar colisiones y lo guarda en `public/uploads/posts/`. La ruta relativa se almacena en `Post::imagePath`.

**Respuesta 201:** objeto Post creado.

**Respuesta 400** si no se envió imagen o el tipo de fichero no está permitido:
```json
{ "error": "Tipo de fichero no permitido. Usa jpg, png, gif o webp." }
```

**Respuesta 401** si no hay sesión.

**Ejemplo de llamada desde el frontend (FormData):**
```typescript
const fd = new FormData()
fd.append('image', imageFile)
fd.append('description', 'Mi lectura del mes')

const res = await fetch('/api/posts', {
  method: 'POST',
  credentials: 'include',
  body: fd,           // NO establecer Content-Type manualmente
})
```

> **Nota:** El wrapper `apiFetch` establece `Content-Type: application/json` de forma fija, por lo que `postsApi.create` usa `fetch` nativo para esta llamada.

---

#### `DELETE /api/posts/{id}` — Eliminar publicación

Elimina la publicación indicada. Solo el autor puede eliminarla.

**Parámetros de ruta:**
| Nombre | Tipo | Descripción |
|---|---|---|
| `id` | `int` | ID de la publicación |

Además de borrar el registro de la base de datos, el servidor elimina el fichero de imagen de `public/uploads/posts/` con `unlink()`.

**Respuesta 204** (sin contenido) si la eliminación fue correcta.
**Respuesta 403** si el usuario autenticado no es el autor.
**Respuesta 404** si la publicación no existe.

---

#### `POST /api/posts/{id}/like` — Dar o quitar "me gusta"

Alterna el estado del like del usuario autenticado sobre la publicación indicada. Si ya tenía like, lo elimina; si no lo tenía, lo crea.

**Respuesta 200:**
```json
{ "liked": true, "likes": 8 }
```

- `liked`: estado resultante del like tras la operación.
- `likes`: recuento total actualizado.

**Respuesta 401** si no hay sesión.

---

#### `GET /api/posts/{id}/comments` — Listar comentarios

Devuelve todos los comentarios de una publicación ordenados cronológicamente (más antiguo primero).

**Respuesta 200:** array de objetos Comentario.

---

#### `POST /api/posts/{id}/comments` — Añadir comentario

Añade un comentario a la publicación. Requiere sesión activa.

**Cuerpo JSON:**
```json
{ "content": "¡Gran elección de lectura!" }
```

**Respuesta 201:** objeto Comentario creado.
**Respuesta 400** si el campo `content` está vacío.
**Respuesta 401** si no hay sesión.

---

#### `DELETE /api/posts/{id}/comments/{commentId}` — Eliminar comentario

Elimina un comentario. Pueden hacerlo el autor del comentario o el autor de la publicación.

**Respuesta 204** si la eliminación fue correcta.
**Respuesta 403** si el usuario no tiene permiso.
**Respuesta 404** si el comentario no existe.

---

### 9.5 Endpoint: Eliminar seguidor

**`DELETE /api/users/{id}/followers`**

Implementado en `src/Controller/Api/FollowApiController.php`.

Permite al usuario autenticado **eliminar a otra persona de su lista de seguidores**. El parámetro `{id}` identifica al seguidor que se desea expulsar (no al usuario que ejecuta la acción).

**Lógica:**
1. Localiza el registro `Follow` donde `follower_id = {id}` y `following_id = {usuario autenticado}`.
2. Si no existe, devuelve 404.
3. Si existe, lo elimina del repositorio y persiste el cambio.

**Respuesta 200:**
```json
{ "followersCount": 4 }
```

El campo `followersCount` refleja el número actualizado de seguidores del usuario autenticado tras la eliminación.

**Respuesta 401** si no hay sesión.
**Respuesta 404** si el registro de seguimiento no existe (esa persona no te sigue).

---

## 10. Módulos API en el frontend

La carpeta `frontend/src/api/` contiene un módulo TypeScript por cada dominio de la aplicación. Cada módulo exporta un objeto con funciones que encapsulan las llamadas HTTP y los tipos de datos correspondientes.

| Fichero | Dominio | Descripción |
|---|---|---|
| `api.ts` | Base | Función `apiFetch` genérica con `credentials: 'include'` y manejo de errores |
| `auth.ts` | Autenticación | `login`, `logout`, `me`, `updateProfile` |
| `books.ts` | Libros | `search`, `detail`, `importBook` |
| `shelves.ts` | Estanterías | CRUD de estanterías y gestión de libros en ellas |
| `bookReviews.ts` | Reseñas | `list`, `create`, `delete` por libro |
| `readingProgress.ts` | Tracker | `list`, `add`, `update`, `delete` del progreso de lectura |
| `clubs.ts` | Clubs | CRUD de clubs, chats, mensajes y membresías |
| `follow.ts` | Seguimiento | `follow`, `unfollow`, `followers`, `following`, `removeFollower` |
| `posts.ts` | Publicaciones | `feed`, `byUser`, `create`, `delete`, `like`, `comments`, `addComment`, `deleteComment` |

### Módulo `posts.ts`

Ubicación: `frontend/src/api/posts.ts`.

**Interfaces exportadas:**

```typescript
export interface PostUser {
  id: number
  displayName: string
  avatar: string | null
}

export interface Post {
  id: number
  imagePath: string
  description: string | null
  createdAt: string
  likes: number
  liked: boolean
  commentCount: number
  user: PostUser
}

export interface PostComment {
  id: number
  content: string
  createdAt: string
  user: PostUser
}
```

**Funciones exportadas:**

```typescript
export const postsApi = {
  feed: () =>
    apiFetch<Post[]>('/posts'),

  byUser: (userId: number) =>
    apiFetch<Post[]>(`/users/${userId}/posts`),

  create: async (image: File, description: string): Promise<Post> => {
    const fd = new FormData()
    fd.append('image', image)
    fd.append('description', description)
    const res = await fetch('/api/posts', {
      method: 'POST',
      credentials: 'include',
      body: fd,
    })
    if (!res.ok) throw new Error(await res.text())
    return res.json()
  },

  delete: (postId: number) =>
    apiFetch<void>(`/posts/${postId}`, 'DELETE'),

  like: (postId: number) =>
    apiFetch<{ liked: boolean; likes: number }>(`/posts/${postId}/like`, 'POST'),

  comments: (postId: number) =>
    apiFetch<PostComment[]>(`/posts/${postId}/comments`),

  addComment: (postId: number, content: string) =>
    apiFetch<PostComment>(`/posts/${postId}/comments`, 'POST', { content }),

  deleteComment: (postId: number, commentId: number) =>
    apiFetch<void>(`/posts/${postId}/comments/${commentId}`, 'DELETE'),
}
```

---

## 11. Componentes React compartidos

La carpeta `frontend/src/components/` agrupa los componentes reutilizables que se utilizan en más de una página.

| Componente | Descripción |
|---|---|
| `Spinner.tsx` | Indicador de carga animado, acepta `size` como prop |
| `PostCard.tsx` | Tarjeta completa de publicación con imagen, likes y comentarios |

### `PostCard.tsx`

Ubicación: `frontend/src/components/PostCard.tsx`.

Renderiza una publicación con toda su interactividad. Es el componente central del sistema de publicaciones y se reutiliza en `HomePage`, `ProfilePage` y `PublicProfilePage`.

**Props:**

```typescript
{
  post: Post          // Datos iniciales de la publicación
  meId: number | null // ID del usuario autenticado, o null si no hay sesión
  onDelete?: (id: number) => void  // Si se provee, aparece botón de eliminar (solo para el autor)
}
```

**Comportamiento:**

- Mantiene una copia local del post en estado (`useState`) para aplicar actualizaciones optimistas al dar like o al añadir/eliminar comentarios sin necesidad de recargar desde el servidor.
- Al hacer clic en el icono de corazón se llama a `postsApi.like(post.id)` y se actualiza inmediatamente el recuento y el estado del like en la UI.
- Los comentarios se cargan de forma diferida: solo se hace la petición a `postsApi.comments(post.id)` la primera vez que el usuario expande la sección de comentarios.
- El botón de eliminar publicación (`<Trash2>`) solo se muestra cuando se cumplan dos condiciones: se haya pasado `onDelete` como prop Y `meId === post.user.id`.
- Cualquier usuario autenticado (`meId !== null`) puede eliminar sus propios comentarios. El autor de la publicación puede eliminar cualquier comentario.
- El avatar del autor se genera con DiceBear Initials (`https://api.dicebear.com/7.x/initials/svg?seed=…`) si el usuario no tiene avatar propio.

**Diagrama de flujo interno:**

```
PostCard recibe { post, meId, onDelete }
         │
         ├── handleLike()
         │      └── postsApi.like(id) → actualiza { liked, likes } en estado local
         │
         ├── handleToggleComments()
         │      └── si primera vez → postsApi.comments(id) → guarda en estado
         │
         ├── handleSendComment(e)
         │      └── postsApi.addComment(id, text) → añade al array local + ++commentCount
         │
         ├── handleDeleteComment(commentId)
         │      └── postsApi.deleteComment(id, commentId) → filtra array local + --commentCount
         │
         └── handleDeletePost()
                └── postsApi.delete(id) → llama onDelete(id) para que el padre lo retire del listado
```

---

## 12. Páginas frontend actualizadas

### `ProfilePage.tsx` — Perfil propio

Ruta: `/profile`.

Además de la edición de datos personales, gestión de estanterías y listas de seguidores/siguiendo, la página incorpora:

**Sección "Mis publicaciones":**
- Lista las publicaciones del usuario autenticado usando `postsApi.byUser(user.id)`.
- Incluye un formulario de creación de nuevas publicaciones:
  - Un área de clic que activa un `<input type="file">` oculto. Al seleccionar una imagen muestra una previsualización (`URL.createObjectURL`).
  - Un `<textarea>` para la descripción opcional.
  - Botón "Publicar" que llama a `postsApi.create(file, description)` con `multipart/form-data`.
  - Al publicar con éxito, el nuevo post se añade al inicio del listado sin recargar la página.
- Utiliza el componente `<PostCard>` pasando `onDelete` para que el usuario pueda borrar sus propias publicaciones desde esta vista.

**Sección "Seguidores" (modal):**
- Muestra la lista de seguidores del usuario.
- Junto a cada seguidor aparece un botón con icono `×` que llama a `DELETE /api/users/{id}/followers`.
- Al eliminar, el seguidor desaparece de la lista y el contador se actualiza de forma inmediata en el estado local.

---

### `PublicProfilePage.tsx` — Perfil público

Ruta: `/users/:id`.

La página de perfil público de cualquier usuario muestra, antes de la sección de estanterías:

**Sección "Publicaciones":**
- Carga `postsApi.byUser(profileId)` en paralelo con el resto de los datos del perfil.
- Renderiza las publicaciones con `<PostCard>` sin `onDelete` (nadie puede borrar desde el perfil ajeno excepto el propio autor, cuyo caso se gestiona dentro del componente).
- Si el usuario no tiene publicaciones, muestra un estado vacío con el texto "Este usuario aún no ha publicado nada."

---

### `HomePage.tsx` — Página de inicio

Ruta: `/`.

La página de inicio consta de tres bloques:

1. **Hero** — título, descripción y llamadas a la acción (visible para todos).
2. **Feed de publicaciones** — visible únicamente para usuarios autenticados.
3. **Sección de características** — cards con las tres funciones principales (visible para todos).

**Feed de publicaciones:**
- Se carga al montar el componente si hay sesión activa: `postsApi.feed()`.
- Muestra las publicaciones de las personas que sigue el usuario autenticado, más las propias, ordenadas de más reciente a más antigua.
- Mientras carga, muestra un `<Spinner>`.
- Si la carga termina con un array vacío, muestra un estado vacío con el mensaje "Tu feed está vacío" y un enlace a `/books` para descubrir libros y seguir a otros lectores.
- Las publicaciones se renderizan con `<PostCard meId={user.id} post={p} onDelete={...}>`, donde `onDelete` filtra el post del array local si el usuario lo elimina desde el feed.

**Flujo de carga del feed:**

```
Usuario autenticado carga "/"
         │
         ▼
useEffect detecta { user } → postsApi.feed()
         │
         ▼
GET /api/posts  →  PostApiController::feed()
         │
         ▼
PostRepository::findFeed($me) → DQL JOIN sobre Follow
         │
         ▼
Array de Post serializados → React renderiza PostCard × N
```

---

## 13. Diagrama de entidades (actualizado)

```
User ──────────────────────────────────────────────────────────┐
 │                                                              │
 ├─< Shelf >─< ShelfBook >──────────────────────── Book        │
 │                                                              │
 ├─< BookReview >─────────────────────────────────── Book      │
 │                                                              │
 ├─< ReadingProgress >────────────────────────────── Book      │
 │                                                              │
 ├─< Follow >── User (siguiendo)                               │
 │                                                              │
 ├─< ClubMember >─< Club >─< Chat >─< ChatMessage >            │
 │                                                              │
 └─< Post >──────────────────────────────────────── (autor)   │
        │                                                       │
        ├─< PostLike >── User                                   │
        │                                                       │
        └─< PostComment >── User                               │
                                                               │
└──────────────────────────────────────────────────────────────┘
```

**Cardinalidades relevantes:**
- `User → Post`: uno a muchos. Un usuario puede tener cero o muchas publicaciones.
- `Post → PostLike`: uno a muchos con unicidad `(post_id, user_id)`. Cada usuario solo puede dar like una vez por publicación.
- `Post → PostComment`: uno a muchos sin límite. Un post puede tener cualquier número de comentarios.
- `Follow`: relación reflexiva sobre `User`. El campo `status` puede ser `pending` o `accepted` (perfiles privados).

---

## 14. Resumen del flujo completo de publicaciones

A continuación se muestra el ciclo de vida completo de una publicación, desde que el usuario la crea hasta que aparece en el feed de sus seguidores.

```
1. CREAR PUBLICACIÓN
   ─────────────────
   Usuario en /profile selecciona imagen y escribe descripción
            │
            ▼
   postsApi.create(file, description)
            │  [multipart/form-data]
            ▼
   POST /api/posts  →  PostApiController::create()
            │
            ├── Validar extensión (jpg/jpeg/png/gif/webp)
            ├── Generar nombre UUID + guardar en public/uploads/posts/
            ├── new Post(user, imagePath, description)
            └── $em->flush() → devolver Post serializado (201)

2. VER EN EL FEED
   ──────────────
   Seguidor autenticado carga /
            │
            ▼
   postsApi.feed()
            │
            ▼
   GET /api/posts  →  PostRepository::findFeed($me)
            │  [DQL JOIN sobre Follow WHERE status=accepted]
            ▼
   Array de Posts propios + de seguidos → React renderiza PostCard

3. DAR LIKE
   ─────────
   Usuario hace clic en ♥ en PostCard
            │
            ▼
   postsApi.like(postId)
            │
            ▼
   POST /api/posts/{id}/like
            │
            ├── ¿Existe PostLike(post, user)? → eliminar (unlike)
            └── No existe → new PostLike(post, user) → insertar
            │
            ▼
   { liked: bool, likes: int }  →  actualización optimista en PostCard

4. COMENTAR
   ─────────
   Usuario escribe en el formulario de comentarios de PostCard
            │
            ▼
   postsApi.addComment(postId, content)
            │
            ▼
   POST /api/posts/{id}/comments
            │
            └── new PostComment(post, user, content) → flush
            │
            ▼
   Comentario serializado → se añade al array local + ++commentCount

5. ELIMINAR PUBLICACIÓN
   ─────────────────────
   Autor hace clic en icono de papelera en PostCard
            │
            ▼
   postsApi.delete(postId)
            │
            ▼
   DELETE /api/posts/{id}
            │
            ├── unlink(public/uploads/posts/{imagePath})
            └── $em->remove($post) → flush
            │
            ▼
   204 No Content → onDelete(id) → post filtrado del array en React
```

---

## Fase de pruebas

Se realizó una batería de pruebas funcionales manuales sobre los flujos principales de la aplicación, ejecutadas con el servidor en modo desarrollo y verificadas con las herramientas de red del navegador (DevTools → Network) y Postman para los endpoints de la API.

---

### Pruebas de autenticación

| ID | Descripción | Entrada | Resultado esperado | Resultado obtenido |
|----|-------------|---------|-------------------|--------------------|
| P-01 | Registro con datos válidos | email único, contraseña ≥ 6 caracteres | 201 Created, usuario creado | ✅ Correcto |
| P-02 | Registro con email duplicado | email ya existente en BD | 409 Conflict, mensaje de error | ✅ Correcto |
| P-03 | Registro con contraseña corta | contraseña de 3 caracteres | 400 Bad Request | ✅ Correcto |
| P-04 | Login con credenciales correctas | email y contraseña válidos | 200 OK, cookie de sesión establecida | ✅ Correcto |
| P-05 | Login con contraseña incorrecta | contraseña errónea | 401 Unauthorized, mensaje genérico | ✅ Correcto |
| P-06 | Acceso a endpoint protegido sin sesión | GET /api/profile sin cookie | 401 Unauthorized | ✅ Correcto |
| P-07 | Logout invalida la sesión | POST /api/auth/logout + petición posterior | Cookie eliminada, 401 en siguiente petición | ✅ Correcto |

---

### Pruebas de estanterías y libros

| ID | Descripción | Entrada | Resultado esperado | Resultado obtenido |
|----|-------------|---------|-------------------|--------------------|
| P-08 | Crear estantería | nombre "Favoritos" | 201, estantería creada y visible en sidebar | ✅ Correcto |
| P-09 | Añadir libro a estantería | externalId válido de Google Books | 201, libro importado a BD local y añadido | ✅ Correcto |
| P-10 | Añadir libro duplicado a misma estantería | mismo externalId dos veces | 409 Conflict | ✅ Correcto |
| P-11 | Mover libro a otra estantería | targetShelfId diferente | 200, libro aparece en la nueva estantería | ✅ Correcto |
| P-12 | Eliminar estantería con libros | DELETE /api/shelves/{id} | 204, estantería y sus entradas eliminadas | ✅ Correcto |
| P-13 | Buscar libro por título | q=dune | Array de resultados con portadas y metadatos | ✅ Correcto |
| P-14 | Buscar libro inexistente | q=zzzzzzzzzzzzz | Array vacío, sin error | ✅ Correcto |
| P-15 | Actualizar tracker de lectura | currentPage=150, totalPages=400 | 200, computed=37 | ✅ Correcto |

---

### Pruebas de clubs

| ID | Descripción | Entrada | Resultado esperado | Resultado obtenido |
|----|-------------|---------|-------------------|--------------------|
| P-16 | Crear club público | nombre y visibility=public | 201, usuario pasa a ser admin | ✅ Correcto |
| P-17 | Unirse a club público | POST /api/clubs/{id}/join | 200, status=joined | ✅ Correcto |
| P-18 | Solicitar unirse a club privado | POST /api/clubs/{id}/join en club privado | 200, status=requested | ✅ Correcto |
| P-19 | Aprobar solicitud de ingreso | POST …/requests/{id}/approve (admin) | 200, status=approved, usuario pasa a member | ✅ Correcto |
| P-20 | Rechazar solicitud | POST …/requests/{id}/reject (admin) | 200, status=rejected | ✅ Correcto |
| P-21 | Intentar crear hilo sin ser admin | POST /api/clubs/{id}/chats (ROLE_USER no admin) | 403 Forbidden | ✅ Correcto |
| P-22 | Enviar mensaje en hilo abierto | content no vacío, usuario miembro | 201, mensaje visible en el chat | ✅ Correcto |
| P-23 | Enviar mensaje en hilo cerrado | hilo con isOpen=false | 400 Bad Request | ✅ Correcto |

---

### Pruebas del sistema social

| ID | Descripción | Entrada | Resultado esperado | Resultado obtenido |
|----|-------------|---------|-------------------|--------------------|
| P-24 | Seguir a usuario público | POST /api/users/{id}/follow | 200, status=accepted | ✅ Correcto |
| P-25 | Seguir a usuario privado | POST /api/users/{id}/follow (perfil privado) | 200, status=pending | ✅ Correcto |
| P-26 | Crear publicación con imagen | multipart/form-data con fichero jpg | 201, imagePath almacenado, imagen accesible | ✅ Correcto |
| P-27 | Crear publicación con tipo no permitido | fichero .pdf | 400, mensaje de error de tipo | ✅ Correcto |
| P-28 | Like y unlike en publicación | POST /api/posts/{id}/like dos veces | Primera vez liked=true, segunda liked=false | ✅ Correcto |
| P-29 | Feed muestra solo seguidos | GET /api/posts con seguidos y no seguidos | Solo aparecen publicaciones propias + seguidos | ✅ Correcto |

---

### Pruebas de privacidad y control de acceso

| ID | Descripción | Entrada | Resultado esperado | Resultado obtenido |
|----|-------------|---------|-------------------|--------------------|
| P-30 | Perfil privado oculta datos a no seguidores | GET /api/users/{id} desde cuenta sin follow | shelves y clubs son null | ✅ Correcto |
| P-31 | Usuario no puede editar estantería ajena | PUT /api/shelves/{id_ajeno} | 404 (no revela que existe) | ✅ Correcto |
| P-32 | Acceso al panel admin sin ROLE_ADMIN | GET /api/admin/stats con ROLE_USER | 403 Forbidden | ✅ Correcto |
| P-33 | Admin puede eliminar cualquier publicación | DELETE /api/posts/{id} con ROLE_ADMIN | 204 aunque no sea el autor | ✅ Correcto |

---

### Pruebas de responsive design

| ID | Descripción | Dispositivo | Resultado esperado | Resultado obtenido |
|----|-------------|-------------|-------------------|--------------------|
| P-34 | Navbar en móvil | 375px de ancho | Menú hamburguesa, sin desbordamiento | ✅ Correcto |
| P-35 | Página de clubs en tablet | 768px de ancho | Grid de 2 columnas, cards legibles | ✅ Correcto |
| P-36 | Página de detalle de libro en móvil | 375px de ancho | Layout vertical, portada centrada | ✅ Correcto |
| P-37 | Formulario de login en móvil | 375px de ancho | Card ocupa el 90% del ancho, sin scroll horizontal | ✅ Correcto |

---

## Conclusiones finales

### Grado de cumplimiento de los objetivos

El proyecto TFGdaw ha alcanzado todos los objetivos planteados en la fase de análisis. A continuación se detalla el grado de cumplimiento de cada uno:

**Autenticación y seguridad.** Se ha implementado un sistema completo de registro, inicio y cierre de sesión basado en sesiones PHP gestionadas por Symfony Security. Las contraseñas se almacenan con hashing bcrypt/argon2id; ningún valor sensible queda expuesto en texto plano ni en los datos de sesión. El acceso a los recursos está protegido tanto por roles (`ROLE_USER`, `ROLE_ADMIN`) como por comprobaciones de propiedad en cada controlador. Se ha configurado HTTPS mediante certificado SSL para el entorno de producción, y se ha documentado la configuración tanto para Apache como para Nginx con Let's Encrypt.

**Base de datos.** Se ha diseñado e implementado una base de datos relacional con 16 entidades, sus relaciones, índices y restricciones de integridad referencial. El esquema se gestiona mediante migraciones de Doctrine, lo que permite reproducir el estado exacto de la base de datos en cualquier entorno. El diagrama E/R y el paso a tablas completo están documentados en el fichero `32-paso-a-tablas.md`.

**API REST.** El backend expone más de 50 endpoints REST documentados, organizados en controladores por dominio funcional. Todas las respuestas siguen un formato JSON homogéneo con los códigos de estado HTTP apropiados. La API ha sido verificada con Postman durante la fase de pruebas.

**Integración con Google Books API.** La búsqueda de libros consume la API de Google Books con soporte para filtros avanzados (título, autor, ISBN, editorial, género, idioma). Los libros se importan automáticamente a la base de datos local la primera vez que un usuario los utiliza, evitando llamadas repetidas a la API externa para el mismo volumen.

**Frontend React.** Se ha desarrollado una SPA con React 18 y TypeScript que cubre todas las funcionalidades del sistema con una interfaz responsive, coherente y accesible. El diseño se adapta a pantallas de móvil (375px), tablet (768px) y escritorio (1280px+). El sistema de diseño se construyó íntegramente con CSS nativo y variables personalizadas, sin dependencias de frameworks externos.

**Sistema social.** El módulo de red social implementa el ciclo completo de follows (públicos e instantáneos, o privados con flujo de solicitud), publicaciones con imagen, likes, comentarios y feed personalizado. Los datos de la interfaz se actualizan de forma optimista para proporcionar una experiencia fluida sin recargas de página.

**Clubs de lectura.** Los clubs implementan un flujo completo: creación con visibilidad configurable, gestión de membresías, flujo de solicitudes para clubs privados, asignación del libro del mes con fechas y hilos de debate organizados por temas. El control de permisos dentro de cada club (admin vs. member) está implementado a nivel de controlador.

**Panel de administración.** El panel admin proporciona estadísticas globales de la plataforma y herramientas de gestión de usuarios, clubs y publicaciones, accesible exclusivamente a los usuarios con `ROLE_ADMIN`.

**Despliegue.** La aplicación puede desplegarse con un único comando (`docker compose up`) tanto en desarrollo como en producción. La documentación de instalación cubre todos los pasos necesarios, incluyendo la configuración de HTTPS.

---

### Relación con los módulos del ciclo DAW

El proyecto cubre contenidos de todos los módulos principales del ciclo:

| Módulo | Contenidos aplicados |
|--------|---------------------|
| Diseño de Interfaces | HTML5 semántico, CSS3 con variables, responsive design, accesibilidad WAI-A, sistema de diseño propio |
| Entornos Cliente | React + TypeScript, Fetch API asíncrona, validación client-side, gestión de estado con Context API |
| Entornos Servidor | Symfony MVC, API REST, gestión de sesiones y cookies, encriptación, generación dinámica de JSON |
| Bases de Datos | Diseño E/R, paso a tablas, Doctrine ORM, migraciones, consultas DQL avanzadas, restricciones de integridad |
| Instalación y Administración | Docker, configuración de servidor web (Apache/Nginx), HTTPS con Let's Encrypt, restricción de acceso por roles |

---

### Dificultades encontradas

**Cookies de sesión en peticiones cross-origin.** Durante el desarrollo, el frontend corre en `localhost:5173` y el backend en `localhost:8000`. Conseguir que la cookie de sesión se enviara correctamente en peticiones cross-origin requirió configurar `credentials: 'include'` en todas las peticiones del frontend y las cabeceras `Access-Control-Allow-Credentials` y `Access-Control-Allow-Origin` en el servidor. El problema se resolvió definitivamente configurando Vite como proxy de desarrollo, que redirige las peticiones a `/api/*` al backend en el mismo proceso, eliminando el problema de CORS por completo.

**Serialización de entidades con relaciones circulares.** Doctrine ORM carga las entidades con referencias circulares entre ellas (por ejemplo, `Club` contiene una colección de `ClubMember`, cada `ClubMember` contiene un `User`, y `User` contiene a su vez sus membresías). Intentar serializar estas entidades directamente con `json_encode` produce recursión infinita. La solución adoptada fue la serialización manual en cada controlador, extrayendo únicamente los campos necesarios para cada respuesta.

**Diseño del modelo de privacidad.** El sistema de privacidad resultó más complejo de lo previsto al combinar tres niveles independientes (`isPrivate`, `shelvesPublic`, `clubsPublic`) con el estado del seguimiento (`pending`/`accepted`). La implementación requirió cuidado especial en los repositorios para que las consultas tuvieran en cuenta el estado del seguimiento del usuario que realiza la petición.

**Subida de imágenes con `multipart/form-data`.** El cliente HTTP genérico (`apiFetch`) establece `Content-Type: application/json` de forma fija, lo que es incompatible con la subida de ficheros. Para el endpoint `POST /api/posts`, fue necesario usar `fetch` nativo sin cabeceras predefinidas, permitiendo que el navegador establezca automáticamente el `Content-Type: multipart/form-data` con el boundary correcto.

**Problema N+1 en el feed.** La primera implementación del feed de publicaciones producía N consultas adicionales a la base de datos para cargar el autor de cada post (una por publicación). Este problema, conocido como N+1 queries, se resolvió reescribiendo la consulta del repositorio con un `JOIN FETCH` en DQL, que recupera los autores de todas las publicaciones en una única consulta.

---

### Propuestas de mejora y ampliaciones futuras

El proyecto, en su estado actual, constituye una plataforma funcional y completa. No obstante, existen diversas líneas de mejora y ampliación que podrían abordarse en futuras versiones:

- **Notificaciones en tiempo real** mediante WebSockets o Server-Sent Events. Actualmente las notificaciones se recuperan bajo demanda al acceder al panel; una implementación en tiempo real mejoraría significativamente la experiencia de usuario en los chats de club.

- **Verificación de email obligatoria.** La infraestructura ya está implementada con `SymfonyCastsVerifyEmailBundle`, pero se desactivó durante el desarrollo para simplificar el flujo de pruebas. Activarla añadiría una capa de seguridad contra registros con emails falsos.

- **Tests automatizados.** El proyecto carece de tests unitarios e integración automatizados. La prioridad más alta sería añadir tests de integración para los controladores de la API con PHPUnit, usando una base de datos SQLite en memoria para las pruebas. En el frontend, Playwright permitiría cubrir los flujos de usuario más críticos.

- **Paginación del feed y de las estanterías.** Actualmente el feed y las estanterías cargan todos los resultados de una vez. Para cuentas con muchas publicaciones o libros, sería necesario implementar paginación con scroll infinito o botón de "cargar más".

- **Búsqueda de usuarios.** No existe actualmente ningún mecanismo para buscar usuarios por nombre en la plataforma. Añadir un buscador en la barra de navegación mejoraría el descubrimiento de otros lectores.

- **Modo oscuro.** El sistema de diseño con variables CSS nativas está preparado para implementar un tema oscuro con un mínimo de cambios: bastaría con redefinir las variables de color en una clase `.dark` en `:root` y añadir un toggle en la barra de navegación.

- **Exportación de la biblioteca personal.** Ofrecer al usuario la posibilidad de exportar sus estanterías y progreso de lectura en formato CSV o JSON facilitaría la portabilidad de sus datos.

- **Estadísticas personales.** Un panel de estadísticas para cada usuario (libros leídos por mes, géneros más leídos, evolución del tracker) añadiría valor a la plataforma como herramienta de seguimiento de hábitos lectores.

---

## Bibliografía

### Documentación oficial

- Symfony Documentation. *The Symfony Framework*. Versión 7.x. Disponible en: https://symfony.com/doc/current/index.html
- Doctrine ORM Documentation. *Doctrine ORM 3.x*. Disponible en: https://www.doctrine-project.org/projects/doctrine-orm/en/3.0/index.html
- React Documentation. *React 18*. Disponible en: https://react.dev/
- TypeScript Documentation. *TypeScript 5.x Handbook*. Disponible en: https://www.typescriptlang.org/docs/
- Vite Documentation. *Vite 5.x Guide*. Disponible en: https://vitejs.dev/guide/
- React Router Documentation. *React Router DOM v6*. Disponible en: https://reactrouter.com/en/main
- Google Books API. *Google Books APIs Developer's Guide*. Disponible en: https://developers.google.com/books/docs/overview
- Docker Documentation. *Docker Compose*. Disponible en: https://docs.docker.com/compose/

### Referencias web

- MDN Web Docs. *Fetch API*. Mozilla. Disponible en: https://developer.mozilla.org/es/docs/Web/API/Fetch_API
- MDN Web Docs. *Using CSS custom properties (variables)*. Mozilla. Disponible en: https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties
- MDN Web Docs. *Responsive design*. Mozilla. Disponible en: https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Responsive_Design
- W3C. *Web Content Accessibility Guidelines (WCAG) 2.1*. World Wide Web Consortium. Disponible en: https://www.w3.org/TR/WCAG21/
- OWASP. *OWASP Top Ten*. Open Web Application Security Project. Disponible en: https://owasp.org/www-project-top-ten/

### Recursos adicionales consultados

- Symfony Cast. *Symfonycasts — PHP & Symfony Tutorials*. Disponible en: https://symfonycasts.com/
- Stack Overflow. Consultas sobre problemas específicos de Doctrine, Symfony Security y React. Disponible en: https://stackoverflow.com/
- DiceBear. *DiceBear Avatars — Initials style*. Utilizado para generar avatares por defecto. Disponible en: https://www.dicebear.com/