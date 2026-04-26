# 19 — Modelo de privacidad

TFGdaw tiene un sistema de privacidad con tres dimensiones independientes: la cuenta de usuario, las secciones del perfil y la visibilidad de los clubes. Este documento describe cada dimensión, los valores posibles y cómo afectan a las respuestas de la API.

---

## 1. Visión general

```
Usuario
├── isPrivate         → controla quién puede seguirte y ver tu contenido
├── shelvesPublic     → controla si tus estanterías son visibles en tu perfil
└── clubsPublic       → controla si tus clubes son visibles en tu perfil

Club
└── visibility        → controla si el club aparece en listados y cómo se une
```

Cada flag es independiente: un usuario puede tener cuenta pública pero estanterías privadas, o cuenta privada pero clubes públicos.

---

## 2. Flag `isPrivate` — privacidad de la cuenta

### 2.1 Definición en la entidad

```php
// User.php
#[ORM\Column]
private bool $isPrivate = false;
```

El valor por defecto es `false` (cuenta pública). El usuario puede cambiarlo desde el endpoint `PATCH /api/profile`.

### 2.2 Efecto en el sistema de follows

Este flag es el que más impacto tiene en la funcionalidad. Modifica el flujo completo del sistema de seguimiento:

| Situación | Cuenta pública (`isPrivate = false`) | Cuenta privada (`isPrivate = true`) |
|-----------|--------------------------------------|-------------------------------------|
| Alguien intenta seguirte | Follow directo con `status = accepted` | Follow con `status = pending` (solicitud) |
| Notificación generada | `TYPE_FOLLOW` | `TYPE_FOLLOW_REQUEST` |
| El nuevo seguidor ve tus posts | Inmediatamente | Solo tras aprobación |

**Código en `FollowApiController`:**

```php
$status = $target->isPrivate()
    ? Follow::STATUS_PENDING
    : Follow::STATUS_ACCEPTED;

$follow = new Follow($me, $target, $status);
$this->em->persist($follow);

if ($status === Follow::STATUS_ACCEPTED) {
    $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW));
} else {
    $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW_REQUEST, null, null, $follow->getId()));
}
```

### 2.3 Efecto en los posts

La visibilidad de los posts de cuentas privadas **la gestiona el frontend**, no el backend. El endpoint `GET /api/users/{id}/posts` devuelve siempre los posts independientemente de si el perfil es privado.

El frontend recibe el campo `followStatus` en la respuesta del perfil y aplica la lógica:

```
si perfil.isPrivate == true && followStatus != "accepted"
    → no mostrar posts
```

> Esta decisión de diseño simplifica el backend pero delega responsabilidad al cliente. Una implementación más robusta añadiría el filtro en el backend.

### 2.4 Efecto en el perfil público

`GET /api/users/{id}` siempre devuelve los datos básicos del perfil (nombre, bio, avatar, contadores de seguidores), independientemente de si la cuenta es privada. Lo que varía es el acceso al contenido.

---

## 3. Flag `shelvesPublic` — visibilidad de estanterías

### 3.1 Definición en la entidad

```php
// User.php
#[ORM\Column]
private bool $shelvesPublic = true;
```

Por defecto `true` (estanterías visibles). El usuario puede ocultarlas con `PATCH /api/profile`.

### 3.2 Efecto en el perfil público

En `GET /api/users/{id}`, el backend comprueba el flag antes de incluir las estanterías:

```php
// UserApiController.php
'shelves' => $user->isShelvesPublic()
    ? array_map(fn($shelf) => [
        'id'    => $shelf->getId(),
        'name'  => $shelf->getName(),
        'books' => array_map(fn($sb) => $this->serializeBook($sb->getBook()), $shelf->getBooks()->toArray()),
    ], $user->getShelves()->toArray())
    : null,
```

Si `shelvesPublic = false`, el campo `shelves` vale `null` en la respuesta. Esto permite al frontend distinguir entre "el usuario no tiene estanterías" (`[]`) y "el usuario tiene estanterías pero no son visibles" (`null`).

### 3.3 Efecto en el perfil propio

`GET /api/profile` devuelve el perfil completo del usuario autenticado, incluyendo siempre sus propias estanterías (independientemente del flag), ya que es su propio perfil.

---

## 4. Flag `clubsPublic` — visibilidad de clubes en el perfil

### 4.1 Definición en la entidad

```php
// User.php
#[ORM\Column]
private bool $clubsPublic = true;
```

Por defecto `true`. Controla si la lista de clubes a los que pertenece el usuario aparece en su perfil público.

### 4.2 Efecto en el perfil público

Mismo patrón que `shelvesPublic`:

```php
// UserApiController.php
'clubs' => $user->isClubsPublic()
    ? array_map(fn($m) => [
        'id'         => $m->getClub()->getId(),
        'name'       => $m->getClub()->getName(),
        'visibility' => $m->getClub()->getVisibility(),
        'role'       => $m->getRole(),
    ], $user->getClubMemberships()->toArray())
    : null,
```

Si `clubsPublic = false`, el campo `clubs` vale `null`. Si `clubsPublic = true`, devuelve solo los clubes donde el usuario es miembro activo.

---

## 5. `visibility` del club — acceso al club

Este campo pertenece a la entidad `Club`, no al usuario, y controla cómo se puede unir alguien al club y qué información se ve.

### 5.1 Definición en la entidad

```php
// Club.php
#[ORM\Column(length: 20)]
private string $visibility = 'public';
```

Valores posibles: `'public'` y `'private'`.

### 5.2 Diferencias entre public y private

| Aspecto | `public` | `private` |
|---------|----------|-----------|
| Aparece en `GET /api/clubs` | Sí | Solo si eres miembro |
| Unirse | Inmediato (`joined`) | Requiere aprobación del admin |
| Notificación al admin | No | `TYPE_CLUB_REQUEST` |
| Ver miembros (`GET /api/clubs/{id}/members`) | Sí (solo miembros) | Solo si eres miembro |
| Ver hilos de debate | Solo miembros | Solo miembros |

### 5.3 Flujo de unión a un club privado

```
POST /api/clubs/{id}/join (club privado)
         │
         ▼
ClubJoinRequest(status: pending) creado
         │
         ▼
Notificación TYPE_CLUB_REQUEST → admin del club
         │                           │
         │            (admin acepta) │
         ▼                           ▼
  ClubMember creado           ClubJoinRequest.status = approved
  role: 'member'              Notificación TYPE_CLUB_REQUEST_ACCEPTED → solicitante
```

**Código en `ClubApiController.join()`:**

```php
if ($club->getVisibility() === 'private') {
    // Comprobar si ya hay solicitud pendiente
    $existing = $joinRequestRepo->findOneBy(['club' => $club, 'user' => $me]);
    if ($existing) {
        return $this->json(['status' => 'already_requested'], 409);
    }
    $request = new ClubJoinRequest($me, $club);
    $this->em->persist($request);

    // Notificar al admin
    $admin = $memberRepo->findOneBy(['club' => $club, 'role' => 'admin']);
    if ($admin) {
        $this->em->persist(new Notification(
            $admin->getUser(), $me,
            Notification::TYPE_CLUB_REQUEST,
            null, $club, $request->getId()
        ));
    }
    $this->em->flush();
    return $this->json(['status' => 'requested']);
}
```

---

## 6. Combinaciones de privacidad y comportamientos resultantes

### 6.1 Usuario A visita el perfil del usuario B

| Condición | Datos devueltos |
|-----------|----------------|
| B tiene cuenta pública | Perfil completo + estanterías (si `shelvesPublic`) + clubes (si `clubsPublic`) |
| B tiene cuenta privada, A no le sigue | Datos básicos (nombre, bio, avatar, contadores). Posts no visibles en el frontend |
| B tiene cuenta privada, A le sigue (accepted) | Igual que cuenta pública |
| B oculta estanterías (`shelvesPublic = false`) | `shelves: null` en la respuesta |
| B oculta clubes (`clubsPublic = false`) | `clubs: null` en la respuesta |

### 6.2 Datos siempre visibles independientemente de la privacidad

Independientemente de la configuración del usuario, estos datos son siempre públicos:
- `displayName`
- `bio`
- `avatar`
- Número de seguidores (`followers`)
- Número de seguidos (`following`)

### 6.3 Campo `followStatus` en el perfil público

`GET /api/users/{id}` incluye el estado del follow entre el visitante y el propietario del perfil:

```json
{
  "followStatus": "none" | "pending" | "accepted",
  "isFollowing": true | false
}
```

| `followStatus` | Significado |
|----------------|-------------|
| `"none"` | No hay relación de seguimiento |
| `"pending"` | Solicitud enviada pero pendiente de aceptación |
| `"accepted"` | El usuario visitante sigue al propietario del perfil |

El frontend usa estos valores para mostrar el botón correcto: "Seguir", "Solicitud enviada", o "Siguiendo".

---

## 7. Modificar la configuración de privacidad

```
PATCH /api/profile
Content-Type: application/json

{
  "isPrivate": true,
  "shelvesPublic": false,
  "clubsPublic": true
}
```

Todos los campos son opcionales: se pueden cambiar uno o varios en la misma petición. El endpoint ignora los campos no enviados (no los resetea).

**Lógica del controlador:**

```php
if (isset($data['isPrivate'])) {
    $me->setIsPrivate((bool) $data['isPrivate']);
}
if (isset($data['shelvesPublic'])) {
    $me->setShelvesPublic((bool) $data['shelvesPublic']);
}
if (isset($data['clubsPublic'])) {
    $me->setClubsPublic((bool) $data['clubsPublic']);
}
$this->em->flush();
```

---

## 8. Tabla resumen de flags de privacidad

| Flag | Entidad | Valor por defecto | Controla |
|------|---------|-------------------|---------|
| `isPrivate` | `User` | `false` | Requiere aprobación para follows; posts no visibles sin seguimiento |
| `shelvesPublic` | `User` | `true` | Estanterías visibles en perfil público |
| `clubsPublic` | `User` | `true` | Clubes visibles en perfil público |
| `visibility` | `Club` | `'public'` | Aparece en listados; requiere solicitud para unirse si es `'private'` |
