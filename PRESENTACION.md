# Guion de Presentación — TFG DAW
> Duración estimada: ~3 minutos

---

## 1. Introducción (30 seg)

Buenos días/tardes. Mi proyecto de fin de grado es una **aplicación web de clubs de lectura**, una plataforma social pensada para que los lectores puedan organizarse, compartir libros y leer en comunidad.

La llamé... *(nombre de la app si lo tiene)* y el objetivo era construir algo completo, tanto técnicamente como en experiencia de usuario: que no pareciera un proyecto de clase, sino una aplicación real.

---

## 2. ¿Qué problema resuelve? (30 seg)

Los lectores habituales suelen tener sus libros dispersos en notas, hojas de cálculo o aplicaciones genéricas. Y cuando quieren leer en grupo no tienen una herramienta que lo facilite todo en un solo sitio.

Esta app centraliza **la gestión personal de libros** con **la parte social de los clubs**, de forma que puedes llevar el seguimiento de lo que lees y a la vez participar en una comunidad.

---

## 3. Funcionalidades principales (60 seg)

La aplicación tiene varias áreas clave:

- **Biblioteca personal:** puedes buscar libros a través de la API de Google Books, añadirlos a estanterías personalizadas y registrar tu progreso de lectura.

- **Clubs de lectura:** puedes crear clubs, unirte a los existentes, votar el libro del mes y chatear con los miembros en tiempo real mediante un chat de mensajes.

- **Red social:** la app incluye un feed, seguimiento entre usuarios —con soporte de cuentas privadas y solicitudes de seguimiento—, publicaciones, likes y comentarios.

- **Perfiles:** cada usuario tiene un perfil público con sus libros y actividad, y un perfil privado con sus estadísticas.

- **Panel de administración:** para gestionar usuarios y contenido de la plataforma.

---

## 4. Stack técnico (30 seg)

Técnicamente el proyecto está dividido en dos partes:

- El **frontend** está hecho con **React + TypeScript** usando Vite. La interfaz tiene un sistema de diseño propio con variables CSS, sin librerías de componentes externas.

- El **backend** es una **API REST con Symfony 7.4** (PHP), con autenticación por sesión y cookie. El ORM utilizado es **Doctrine**, que mapea las entidades PHP directamente a tablas de base de datos.

- La **base de datos** es **MySQL / MariaDB**, con 16 entidades y sus relaciones gestionadas mediante migraciones de Doctrine. El entorno local corre en **Docker**.

- Para el **despliegue** se usa **Railway**, que permite alojar tanto el backend Symfony como la base de datos MySQL en la nube con configuración mínima, haciendo la aplicación accesible públicamente.

---

## 5. Cierre (30 seg)

En resumen, este proyecto me ha permitido aplicar de forma integrada todo lo aprendido durante el ciclo: arquitectura cliente-servidor, diseño de APIs, gestión de estado en el frontend, autenticación, y construcción de una interfaz profesional.

El resultado es una aplicación funcional, completa y con una identidad visual propia. Estoy disponible para cualquier pregunta.

---

> **Tiempo total estimado hablando a ritmo normal:** ~3 minutos
