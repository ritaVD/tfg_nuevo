# 34 — Usabilidad

La usabilidad de una aplicación web mide el grado en que un sistema permite a sus usuarios alcanzar sus objetivos de forma eficaz, eficiente y satisfactoria. Este documento acredita el cumplimiento del criterio de usabilidad del ciclo DAW, describiendo los principios aplicados durante el diseño e implementación de TFGdaw.

El marco de referencia utilizado son las **10 heurísticas de usabilidad de Jakob Nielsen**, el estándar más extendido en la evaluación de interfaces de usuario.

---

## 1. Visibilidad del estado del sistema

El sistema mantiene informado al usuario en todo momento sobre lo que está ocurriendo, mediante retroalimentación apropiada y en tiempo razonable.

**Aplicación en TFGdaw:**

- El componente `<Spinner>` se muestra durante todas las operaciones asíncronas: carga del feed, búsqueda de libros, envío de formularios. El usuario nunca se queda mirando una pantalla en blanco sin saber si la aplicación está procesando su acción.
- Los botones de envío de formulario se desactivan (`disabled`) mientras la petición está en curso, impidiendo envíos duplicados y señalando visualmente que se está procesando la acción.
- La barra de progreso del tracker de lectura se actualiza visualmente de forma inmediata al guardar un nuevo valor, proporcionando retroalimentación instantánea.
- El botón de like cambia de estado (vacío → relleno) en el momento del clic, antes incluso de recibir confirmación del servidor (actualización optimista).
- Las notificaciones no leídas se indican con un contador visible en el icono de la campana, manteniéndose al día sin que el usuario tenga que navegar a ninguna sección específica.

---

## 2. Coincidencia entre el sistema y el mundo real

El sistema usa el lenguaje y los conceptos familiares para el usuario, siguiendo las convenciones del mundo real.

**Aplicación en TFGdaw:**

- La metáfora de **estantería** es directamente reconocible para cualquier lector: se organiza en estanterías igual que en casa, con libros que se mueven entre ellas.
- Los estados de lectura usan términos naturales en español: *"Quiero leer"*, *"Leyendo"*, *"Leído"*, en lugar de identificadores técnicos como `WANT_TO_READ`.
- El sistema de reseñas con **estrellas del 1 al 5** es un patrón conocido por el usuario de plataformas como Amazon o Google Maps, eliminando la curva de aprendizaje.
- Los iconos utilizados son semánticamente universales: corazón para like, papelera para eliminar, lápiz para editar, candado para privacidad. Ningún icono requiere explicación.
- Los mensajes de error están escritos en español natural y describen el problema de forma comprensible, sin exponer mensajes técnicos del servidor al usuario.

---

## 3. Control y libertad del usuario

Los usuarios a menudo eligen funciones por error y necesitan una "salida de emergencia" claramente marcada.

**Aplicación en TFGdaw:**

- Todos los modales (formulario de nuevo club, lista de seguidores, panel de añadir libro a estantería) se pueden cerrar haciendo clic fuera del área del modal o pulsando el botón ✕ en la esquina superior, sin efectos secundarios.
- Las operaciones destructivas (eliminar estantería, eliminar publicación, eliminar comentario) muestran un diálogo de confirmación antes de ejecutarse, evitando borrados accidentales.
- El botón "Abandonar club" está oculto detrás de un menú contextual para evitar que el usuario lo active sin intención.
- La navegación mediante el botón "atrás" del navegador funciona correctamente en toda la aplicación gracias a React Router, que gestiona el historial de navegación de forma coherente.
- Si un formulario falla al enviarse, los datos introducidos por el usuario se conservan en los campos; el usuario no necesita volver a escribir todo desde el principio.

---

## 4. Consistencia y estándares

Los usuarios no deben tener que preguntarse si distintas palabras, situaciones o acciones significan lo mismo.

**Aplicación en TFGdaw:**

- El **sistema de diseño** centralizado en `tokens.css` garantiza que los colores, tipografías, tamaños y espaciados son idénticos en toda la aplicación. El color primario púrpura siempre identifica acciones principales; el rojo siempre identifica acciones destructivas; el verde siempre confirma éxito.
- Los botones de acción principal usan siempre la clase `btn btn-primary` y los botones de cancelar o salir usan `btn btn-ghost`, manteniendo un patrón coherente en todos los formularios.
- Las respuestas de la API siempre devuelven el mismo formato de error (`{ "error": "..." }`), lo que permite que el frontend los gestione de forma uniforme en todos los módulos.
- El patrón de tarjeta (`card` con `card-header`, `card-body`, `card-footer`) se reutiliza en toda la aplicación para presentar contenido agrupado, desde clubs hasta publicaciones o resultados de búsqueda.
- Los textos de los botones son consistentes: siempre "Guardar cambios" para actualizar datos de perfil, siempre "Cancelar" para descartar, siempre "Eliminar" para borrar.

---

## 5. Prevención de errores

Mejor que los buenos mensajes de error es un diseño cuidadoso que evite que los problemas ocurran.

**Aplicación en TFGdaw:**

- La búsqueda de libros no se ejecuta hasta que el usuario ha escrito al menos 2 caracteres, evitando resultados vacíos o demasiado amplios.
- Al añadir un libro a una estantería, si ese libro ya está en la estantería seleccionada, el sistema detecta el duplicado antes de enviar la petición (el servidor devuelve 409 y se muestra un mensaje informativo en lugar de un error genérico).
- El campo de contraseña muestra en tiempo real si se cumplen los requisitos mínimos (longitud ≥ 6 caracteres) antes de que el usuario intente enviar el formulario.
- En el formulario de registro, la confirmación de contraseña se valida instantáneamente al escribir, sin esperar al envío.
- Los hilos de chat cerrados muestran visualmente que están bloqueados (icono de candado, campo de texto desactivado) antes de que el usuario intente escribir, evitando el frustración de escribir un mensaje que no se puede enviar.
- Las imágenes subidas son validadas en el cliente por tipo MIME antes de enviar la petición al servidor.

---

## 6. Reconocimiento antes que recuerdo

Minimizar la carga de memoria del usuario haciendo visibles los objetos, acciones y opciones.

**Aplicación en TFGdaw:**

- El estado de cada libro en las estanterías (quiero leer / leyendo / leído) se muestra visualmente con un badge de color directamente sobre la portada, sin necesidad de abrir ningún menú.
- El rol del usuario en cada club (Administrador / Miembro) se muestra como badge en el listado de clubs y en el detalle, evitando que el usuario tenga que recordar en cuáles tiene permisos de gestión.
- El botón "Siguiendo" / "Seguir" / "Solicitud enviada" en el perfil de otro usuario refleja el estado actual de la relación de seguimiento sin que el usuario deba recordarlo.
- La estantería seleccionada en la barra lateral aparece visualmente resaltada, orientando al usuario sobre qué contenido está viendo en el panel principal.
- El libro del mes activo del club se muestra en la cabecera del club con portada y título, sin necesidad de navegar a ninguna sección adicional.

---

## 7. Flexibilidad y eficiencia de uso

Los aceleradores, invisibles para el usuario novel, pueden acelerar la interacción del usuario experto.

**Aplicación en TFGdaw:**

- Los formularios de búsqueda y envío de mensajes en el chat responden a la tecla Enter, permitiendo al usuario enviar sin usar el ratón.
- El formulario de nueva publicación muestra una previsualización de la imagen seleccionada inmediatamente, sin necesidad de confirmar primero.
- La sección de comentarios en `PostCard` se carga de forma diferida: los usuarios que no quieren ver comentarios nunca pagan el coste de esa petición adicional.
- Al renombrar una estantería, el campo de edición se activa directamente sobre el nombre, sin abrir ningún modal separado.
- El buscador de libros aplica los resultados mientras el usuario escribe (debounced), sin necesidad de pulsar ningún botón de búsqueda.

---

## 8. Diseño estético y minimalista

Los diálogos no deben contener información irrelevante o que rara vez sea necesaria.

**Aplicación en TFGdaw:**

- El listado de clubs muestra solo la información esencial para tomar la decisión de unirse: nombre, tipo, número de miembros y libro activo. Los detalles completos están disponibles en la página de detalle, no en el listado.
- Las tarjetas de resultado de búsqueda de libros muestran portada, título, autores y año. La descripción completa y todos los metadatos están en la página de detalle.
- Los mensajes de error son breves y directos, sin terminología técnica ni códigos de estado HTTP visibles para el usuario.
- La barra de navegación expone únicamente las secciones principales, sin submenús complejos. Las acciones secundarias (cambiar contraseña, configurar privacidad) están agrupadas en el perfil.
- El feed de la página de inicio no incluye anuncios, sugerencias algorítmicas ni contenido no solicitado; muestra exclusivamente las publicaciones de las personas que el usuario ha decidido seguir.

---

## 9. Ayuda a los usuarios a reconocer, diagnosticar y recuperarse de los errores

Los mensajes de error deben expresarse en lenguaje sencillo, indicar con precisión el problema y sugerir una solución.

**Aplicación en TFGdaw:**

| Situación de error | Mensaje mostrado | Acción sugerida |
|-------------------|------------------|-----------------|
| Email ya registrado | "Este email ya está en uso" | — (el usuario sabe qué hacer) |
| Contraseña incorrecta en login | "Credenciales incorrectas" | — (genérico por seguridad) |
| Libro ya en la estantería | "Este libro ya está en esta estantería" | El selector cambia automáticamente a otra estantería disponible |
| Hilo de chat cerrado | "Este hilo está cerrado" | El campo de texto se desactiva visualmente antes de intentar escribir |
| Error de red en petición | "No se pudo completar la operación. Inténtalo de nuevo." | Botón de reintentar visible |
| Imagen con formato no válido | "Formato no admitido. Usa JPG, PNG, GIF o WEBP." | El selector de ficheros filtra ya por extensión |

Los errores de validación en formularios aparecen **bajo el campo afectado**, no como un mensaje global en la parte superior del formulario, lo que permite al usuario identificar y corregir el problema de un vistazo.

---

## 10. Ayuda y documentación

Aunque es mejor si el sistema puede ser utilizado sin documentación, puede ser necesario proporcionar ayuda.

**Aplicación en TFGdaw:**

- Los placeholders de los campos de formulario indican el formato esperado directamente en el campo (por ejemplo, "mínimo 6 caracteres" en el campo de contraseña).
- Los estados vacíos no muestran una pantalla en blanco, sino un mensaje contextual con instrucciones sobre qué hacer: *"Tu feed está vacío. Sigue a otros lectores para ver sus publicaciones"*, *"No tienes estanterías. Crea una para empezar a organizar tus libros"*.
- El manual de usuario completo está disponible en `docs/31-manual-usuario.md` y cubre todas las funcionalidades paso a paso.
- Los tooltips en los iconos de acción menos obvios (como el botón de eliminar seguidor) muestran una descripción breve al pasar el cursor.

---

## Evaluación general de usabilidad

La interfaz de TFGdaw fue diseñada priorizando la claridad y la reducción de la carga cognitiva del usuario. Las decisiones de diseño más relevantes en este sentido son:

- **Una sola columna de atención en móvil**: en pantallas pequeñas, el contenido se presenta en una única columna sin elementos que compitan por la atención visual.
- **Acciones contextuales**: los botones de editar, eliminar o mover aparecen únicamente al interactuar con el elemento al que pertenecen (hover en escritorio, tap en móvil), evitando que la interfaz esté sobrecargada de controles permanentemente visibles.
- **Jerarquía visual clara**: los elementos de acción principal (botones primarios) tienen mayor peso visual que los secundarios (botones fantasma), guiando al usuario hacia las acciones más frecuentes.
- **Tiempo de respuesta percibido**: las actualizaciones optimistas (like, comentarios) hacen que la aplicación se perciba como más rápida de lo que técnicamente es, mejorando la satisfacción del usuario sin necesidad de infraestructura adicional.
