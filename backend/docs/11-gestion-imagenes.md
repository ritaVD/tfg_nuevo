# 11 — Gestión de imágenes y archivos

El backend gestiona dos tipos de archivos subidos por los usuarios: imágenes de publicaciones (posts) y avatares de perfil. Ambos se almacenan en el sistema de archivos local del servidor.

---

## 1. Estructura de directorios

```
backend/public/
├── uploads/
│   ├── posts/      ← Imágenes de publicaciones
│   └── avatars/    ← Fotos de perfil de usuarios
└── app/            ← Frontend React compilado (no gestión de usuario)
```

Estos directorios están dentro de `public/`, por lo que son accesibles directamente desde el navegador:
- `http://localhost:8000/uploads/posts/post_abc123.jpg`
- `http://localhost:8000/uploads/avatars/6716a3b.jpg`

---

## 2. Subida de imágenes de publicaciones

**Endpoint:** `POST /api/posts`  
**Tipo de petición:** `multipart/form-data`

### Validaciones

```php
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext     = strtolower($file->guessExtension() ?? '');

if (!in_array($ext, $allowed, true)) {
    return $this->json(['error' => 'Formato de imagen no permitido'], 400);
}
```

El método `guessExtension()` detecta el tipo real del archivo por su contenido (no solo por la extensión del nombre), lo que previene que un usuario suba un archivo `.exe` renombrado como `.jpg`.

### Generación de nombre único

```php
$filename = uniqid('post_', true) . '.' . $ext;
```

`uniqid('post_', true)` genera un nombre como `post_6716a3b4e5f12.3456789012`. El segundo argumento `true` añade entropía adicional basada en microsegundos, minimizando colisiones.

### Guardado

```php
$uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
$file->move($uploadDir, $filename);
```

Solo el nombre del archivo se guarda en la entidad `Post` (campo `imagePath`), no la ruta completa. El frontend construye la URL completa prefijando `/uploads/posts/`.

---

## 3. Subida de avatares de perfil

**Endpoint:** `POST /api/profile/avatar`  
**Tipo de petición:** `multipart/form-data`  
**Campo:** `avatar`

```php
$filename = uniqid() . '.' . $file->guessExtension();
$file->move(
    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
    $filename
);
$user->setAvatar($filename);
$this->em->flush();
```

A diferencia de los posts, los avatares no tienen validación de extensión explícita en el código actual. El campo `avatar` de `User` almacena solo el nombre del archivo.

---

## 4. Eliminación de imágenes al borrar un post

Cuando se elimina una publicación, el archivo físico también se borra del disco:

```php
$imgPath = $this->getParameter('kernel.project_dir')
         . '/public/uploads/posts/'
         . $post->getImagePath();

if (file_exists($imgPath)) {
    @unlink($imgPath);
}

$this->em->remove($post);
$this->em->flush();
```

El operador `@` antes de `unlink()` suprime posibles errores si el archivo ya no existe (por ejemplo, si fue eliminado manualmente del servidor). La entidad se borra de BD siempre, independientemente de si el archivo existía.

> **Importante:** Esta misma lógica se ejecuta también cuando un **admin** elimina un post desde el panel de administración (`AdminApiController`).

---

## 5. Acceso a las imágenes desde el frontend

El frontend recibe el nombre del archivo en las respuestas JSON:

```json
{
  "id": 42,
  "imagePath": "post_6716a3b4e5f12.jpg",
  "user": {
    "avatar": "6716c001b2345.jpg"
  }
}
```

Y construye la URL completa para mostrar la imagen:

```javascript
// Post
`/uploads/posts/${post.imagePath}`

// Avatar
`/uploads/avatars/${user.avatar}`
```

En desarrollo, Vite tiene configurado un proxy que redirige `/uploads/` al servidor Symfony en `localhost:8000`, por lo que las imágenes se sirven correctamente aunque el frontend corra en `localhost:5173`.

---

## 6. Consideraciones de producción

En el entorno actual, las imágenes se almacenan en el disco del servidor. Esto es suficiente para un TFG, pero en una aplicación real con múltiples servidores o despliegue en la nube habría que considerar:

- **Almacenamiento en la nube:** Amazon S3, Google Cloud Storage o Azure Blob Storage.
- **CDN:** Para servir imágenes más rápido desde ubicaciones cercanas al usuario.
- **Límite de tamaño:** Configurar `upload_max_filesize` y `post_max_size` en `php.ini`.
- **Procesamiento:** Redimensionar y comprimir imágenes al subir (con librerías como Intervention Image o Imagine).

---

## 7. Configuración de PHP para subidas

El tamaño máximo de archivo permitido se controla en la configuración de PHP (`php.ini`), no en Symfony. Los valores clave son:

```ini
upload_max_filesize = 10M   ; tamaño máximo de un archivo individual
post_max_size = 12M          ; tamaño máximo del body completo (debe ser > upload_max_filesize)
max_file_uploads = 20        ; número máximo de archivos por petición
```

Para cambiar estos valores en el entorno de desarrollo con Symfony CLI:
```bash
# En symfony.lock o en un archivo .php.ini del proyecto
upload_max_filesize=10M
post_max_size=12M
```
