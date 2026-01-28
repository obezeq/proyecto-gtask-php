# Analisis del backend PHP sin framework - Proyecto GTask

**Modulo:** Desarrollo Web en Entorno Servidor (DWES)
**Alumno:** Ezequiel Ortega
**Fecha:** 28/01/2026

---

## Parte A - Mapa de arquitectura

Para entender como funciona el backend he ido mirando fichero por fichero empezando por `index.php` que es donde arranca todo. Basicamente cuando el navegador o cualquier cliente manda una peticion HTTP, pasa por lo siguiente:

```
Cliente (navegador, curl, etc)
    |
    v
Nginx (hace de proxy inverso)
    |
    v
PHP-FPM ejecuta index.php
    |
    +---> session_start()           --> arranca la sesion
    +---> apply_cors()              --> pone las cabeceras CORS
    +---> config.php                --> carga la config de la BD
    +---> new Database($config)     --> conecta a PostgreSQL con PDO
    +---> Parsea metodo y ruta
    |
    v
Mira que ruta es y la manda al controlador que toca
    |
    +---> AuthController  --> para registro, login, logout y /me
    +---> TaskController  --> para todo lo de tareas (CRUD)
    |
    v
El controlador hace la consulta SQL con PDO
    |
    v
Devuelve la respuesta en JSON al cliente
```

Los ficheros que participan en este flujo son:

| Fichero | Para que sirve |
|---------|---------------|
| `app/public/index.php` | Es el punto de entrada. Arranca la sesion, pone CORS, conecta a la BD, crea los controladores y enruta todo |
| `app/src/Support.php` | Tiene funciones que se usan por todos lados: las de respuesta JSON, leer el body, manejar sesiones y CORS |
| `app/src/Database.php` | Crea la conexion PDO a PostgreSQL |
| `app/config/config.php` | Tiene la config de la BD con las variables de entorno de Docker |
| `app/src/Controllers/AuthController.php` | Maneja registro, login, logout y ver perfil |
| `app/src/Controllers/TaskController.php` | Maneja crear, listar, ver, editar y borrar tareas |

Sobre el formato de las respuestas, todas van en JSON. La funcion `json_response()` de `Support.php` (linea 17) pone la cabecera `Content-Type: application/json`, el codigo HTTP y hace el `json_encode`. Asi queda siempre todo uniforme.

Por ejemplo un login correcto devuelve algo asi:
```json
{
    "message": "Login correcto.",
    "user": { "id": 1, "name": "Juan", "email": "juan@example.com" }
}
```

Y si hay error:
```json
{ "error": "Credenciales invalidas." }
```

---

## Parte B - Conexion a base de datos

### Donde se crea

La conexion se crea en `index.php`, en las lineas 23 a 25. Primero carga el fichero de configuracion, luego crea un objeto `Database` y saca la conexion PDO:

```php
$config = require __DIR__ . '/../config/config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();
```

### Como se construye el DSN

Esto lo he visto en `Database.php`, lineas 17-22. Usa `sprintf()` para armar el string del DSN:

```php
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    $config['host'],
    $config['port'],
    $config['name']
);
```

O sea que al final queda algo como `pgsql:host=db;port=5432;dbname=app`. El `pgsql` indica que es PostgreSQL.

### De donde salen los datos de configuracion

En `config/config.php` (lineas 3-12) se leen con `getenv()` las variables de entorno que vienen del contenedor Docker. Si alguna no esta definida, usa un valor por defecto:

```php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'app',
        'user' => getenv('DB_USER') ?: 'user',
        'password' => getenv('DB_PASSWORD') ?: 'password',
    ],
];
```

Estas variables las define el `docker-compose.yml` del proyecto.

### Que pasa si falla la conexion

En `Database.php` (lineas 24-37) hay un `try/catch`. Si no puede conectar, captura la `PDOException` y la relanza con un mensaje mas claro:

```php
try {
    $this->pdo = new PDO($dsn, $config['user'], $config['password'], [...]);
} catch (PDOException $e) {
    throw new PDOException('Error de conexion: ' . $e->getMessage());
}
```

Tambien me he fijado que configura tres atributos de PDO importantes:

- `ERRMODE_EXCEPTION`: para que los errores SQL lancen excepciones y no pasen de largo
- `FETCH_ASSOC`: para que los resultados vengan como arrays asociativos en vez de numericos
- `EMULATE_PREPARES = false`: esto hace que las sentencias preparadas se hagan de verdad en el servidor de PostgreSQL, no las simule PHP. Es importante para evitar inyecciones SQL

---

## Parte C - Enrutado y gestion de peticiones

### Como sabe que metodo HTTP se esta usando

En `index.php`, linea 32:

```php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
```

Lee de `$_SERVER['REQUEST_METHOD']` que puede ser GET, POST, PUT, PATCH o DELETE. Si por algun motivo no existe, pone GET por defecto.

### Como procesa la ruta

En las lineas 33-34 extrae la ruta limpia:

```php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';
```

`parse_url` quita los query strings (tipo `?page=1`) y `rtrim` quita la barra del final para que `/api/tasks/` y `/api/tasks` sean lo mismo.

Luego en las lineas 43-50 trocea la ruta por las barras:

```php
$segments = explode('/', trim($path, '/'));
$resource = $segments[1] ?? '';    // ej: "tasks" o "login"
$id = $segments[2] ?? null;        // ej: "5" o null
```

Si la ruta no empieza por `api`, directamente devuelve 404.

### Como decide que controlador ejecutar

Usa la estructura `match` de PHP 8. Hay dos bloques:

**Rutas publicas** (lineas 53-59): compara el recurso y el metodo. Si coincide con register+POST, login+POST, logout+POST o me+GET, llama al metodo correspondiente del `AuthController`.

**Rutas de tareas** (lineas 62-74): si el recurso es `tasks`, primero llama a `require_auth()` para comprobar que hay sesion. Si no hay, corta con un 401. Si hay, usa otro `match` mirando el metodo HTTP y si hay ID o no para saber que accion hacer.

Si nada coincide, llega a la linea 77 y devuelve `"Ruta no encontrada."` con un 404.

### Tabla de endpoints

| Endpoint | Metodo | Auth | Controller::metodo |
|----------|--------|------|--------------------|
| `/` | GET | No | Sirve la vista `app.php` |
| `/api/register` | POST | No | `AuthController::register()` |
| `/api/login` | POST | No | `AuthController::login()` |
| `/api/logout` | POST | No* | `AuthController::logout()` |
| `/api/me` | GET | Si | `AuthController::me()` |
| `/api/tasks` | GET | Si | `TaskController::index()` |
| `/api/tasks` | POST | Si | `TaskController::create()` |
| `/api/tasks/{id}` | GET | Si | `TaskController::show()` |
| `/api/tasks/{id}` | PUT | Si | `TaskController::update()` |
| `/api/tasks/{id}` | PATCH | Si | `TaskController::update()` |
| `/api/tasks/{id}` | DELETE | Si | `TaskController::delete()` |

*Nota: `logout` esta en el bloque de rutas publicas del router (linea 56) pero al llamar a `destroy_session()` destruye la sesion que hubiera. Y `me` aunque tambien esta en ese bloque, internamente llama a `require_auth()` asi que si no estas logueado te devuelve 401.

---

## Parte D - Validacion y control de tipos

He revisado las validaciones que hay tanto en `AuthController` como en `TaskController`. Primero, ambos controladores definen constantes para los limites:

En `TaskController.php` (lineas 10-14):
```php
private const VALID_STATUSES = ['pending', 'completed'];
private const MAX_TITLE_LENGTH = 200;
private const MAX_DESCRIPTION_LENGTH = 1000;
private const MIN_PRIORITY = 0;
private const MAX_PRIORITY = 5;
```

En `AuthController.php` (lineas 10-11):
```php
private const MAX_NAME_LENGTH = 100;
private const MIN_PASSWORD_LENGTH = 6;
```

### Validaciones del registro (AuthController, lineas 95-109)

| Campo | Que valida | HTTP |
|-------|-----------|------|
| `name` | Que no este vacio | 422 |
| `name` | Que no pase de 100 caracteres | 422 |
| `email` | Que no este vacio | 422 |
| `email` | Que tenga formato de email valido (usa `filter_var` con `FILTER_VALIDATE_EMAIL`) | 422 |
| `email` | Que no exista ya en la BD | 409 |
| `password` | Que no este vacio | 422 |
| `password` | Que tenga minimo 6 caracteres | 422 |

### Validaciones del login (AuthController, lineas 111-119)

| Campo | Que valida | HTTP |
|-------|-----------|------|
| `email` | Que no este vacio | 422 |
| `email` | Formato valido | 422 |
| `password` | Que no este vacio | 422 |
| Credenciales | Que el email exista y la password coincida (con `password_verify`) | 401 |

### Validaciones al crear tarea (TaskController, lineas 138-163)

| Campo | Que valida | HTTP |
|-------|-----------|------|
| `title` | Obligatorio, no puede estar vacio | 422 |
| `title` | Maximo 200 caracteres | 422 |
| `description` | Es opcional, pero si viene no puede pasar de 1000 caracteres | 422 |
| `status` | Solo puede ser `pending` o `completed` | 422 |
| `priority` | Tiene que ser un entero entre 0 y 5 | 422 |
| `due_date` | Es opcional, pero si viene tiene que ser formato `YYYY-MM-DD` | 422 |

Para las fechas usa un metodo privado `isValidDate` (lineas 220-224) que crea un `DateTimeImmutable` con el formato y comprueba que al reconvertirla a string salga igual. Asi rechaza cosas como `2025-13-01` o `2025-02-30` que no son fechas reales.

### Validaciones al actualizar tarea (TaskController, lineas 165-218)

Cuando actualizas una tarea solo se validan los campos que mandes. Si no mandas `title` no lo valida, pero si lo mandas tiene las mismas reglas que al crear. Ademas si pasas el `status` a `completed`, automaticamente se rellena `completed_at` con la fecha actual, y si lo vuelves a poner en `pending` se pone a NULL.

### Codigos HTTP que usa la API

| Codigo | Que significa | Cuando se usa |
|--------|--------------|--------------|
| 400 | Bad Request | Cuando el JSON del body esta mal formado (`Support.php`, linea 48) |
| 401 | Unauthorized | Si no estas autenticado o las credenciales son incorrectas |
| 404 | Not Found | Ruta que no existe o tarea que no se encuentra |
| 409 | Conflict | Email que ya esta registrado en la BD |
| 422 | Unprocessable Entity | Cualquier dato que no pase las validaciones |

### Ejemplos de datos invalidos

**Registro sin nombre:**
```
POST /api/register
{ "email": "juan@example.com", "password": "123456" }

--> HTTP 422
{ "error": "Nombre, email y contrasena son obligatorios." }
```

**Contrasena muy corta:**
```
POST /api/register
{ "name": "Juan", "email": "juan@example.com", "password": "123" }

--> HTTP 422
{ "error": "La contrasena debe tener al menos 6 caracteres." }
```

**Email mal escrito:**
```
POST /api/register
{ "name": "Juan", "email": "esto-no-es-email", "password": "123456" }

--> HTTP 422
{ "error": "El email no es valido." }
```

**Prioridad fuera de rango:**
```
POST /api/tasks
{ "title": "Estudiar", "priority": 10 }

--> HTTP 422
{ "error": "La prioridad debe estar entre 0 y 5." }
```

**Fecha con formato incorrecto:**
```
POST /api/tasks
{ "title": "Estudiar", "due_date": "01/12/2025" }

--> HTTP 422
{ "error": "La fecha limite debe tener formato YYYY-MM-DD." }
```

**Estado inventado:**
```
POST /api/tasks
{ "title": "Estudiar", "status": "en_progreso" }

--> HTTP 422
{ "error": "El estado no es valido." }
```

---

## Parte E - Autenticacion y control de acceso

### Como funciona la sesion

Lo primero que hace `index.php` (linea 17) es llamar a `session_start()`. Esto hace que PHP cree o recupere una sesion, que se identifica con una cookie llamada `PHPSESSID` que el navegador manda automaticamente.

Las funciones para manejar la sesion estan todas en `Support.php`:

- `set_session_user($user)` (linea 72): guarda los datos del usuario en `$_SESSION['user']`
- `get_session_user()` (linea 80): devuelve los datos del usuario o null si no hay sesion
- `require_auth()` (linea 61): comprueba que haya usuario en sesion, si no devuelve 401
- `destroy_session()` (linea 88): vacia `$_SESSION` y llama a `session_destroy()`

### Que se guarda en `$_SESSION`

Cuando te registras o haces login, se guarda esto en `$_SESSION['user']`:

```php
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Juan',
    'email' => 'juan@example.com'
];
```

Esto lo hace `AuthController` al llamar a `set_session_user()`:
- En `register()`, linea 45, despues de insertar el usuario en la BD
- En `login()`, linea 73, despues de verificar que la contrasena es correcta

Una cosa que me parece bien es que la contrasena no se guarda nunca en la sesion, solo el id, nombre y email.

### Como se protegen los endpoints

En `index.php` (linea 63), cuando la ruta es `/api/tasks`, lo primero que hace es:

```php
$user = require_auth();
$userId = (int) $user['id'];
```

Si `$_SESSION['user']` esta vacio, `require_auth()` corta la ejecucion devolviendo:
```json
{ "error": "No autenticado." }
```
Con codigo 401. Y ya no se ejecuta nada mas.

El endpoint `/api/me` tambien esta protegido porque aunque en el router esta en la seccion publica (linea 57), el metodo `me()` del `AuthController` (linea 91) llama a `require_auth()` internamente.

### Como se evita acceder a tareas de otro usuario

Esto lo he visto en `TaskController.php`. Todas las consultas SQL llevan un `WHERE user_id = :user_id` con el ID del usuario autenticado:

- Listar tareas (linea 30): `WHERE user_id = :user_id`
- Ver una tarea (linea 126): `WHERE id = :id AND user_id = :user_id`
- Actualizar (linea 96): `WHERE id = :id AND user_id = :user_id`
- Eliminar (linea 112): `WHERE id = :id AND user_id = :user_id`

Asi que si por ejemplo yo estoy logueado como usuario 1 e intento hacer `GET /api/tasks/5` pero esa tarea es del usuario 2, la query no devuelve nada y me sale un 404 como si no existiera. Ni siquiera puedo saber si la tarea existe para otro usuario.

El `$userId` viene de la sesion (se saca en `index.php`, linea 64), no de la peticion del cliente, asi que no se puede manipular.

---

## Parte F - CORS, respuestas y errores

### Cabeceras CORS

Esto esta en `apply_cors()` de `Support.php` (lineas 101-114). Se ejecuta al principio de cada peticion desde `index.php` linea 20.

Las cabeceras que pone son:

| Cabecera | Valor | Para que |
|----------|-------|---------|
| `Access-Control-Allow-Origin` | El origen de la peticion o `*` | Para que un frontend desde otro dominio pueda hacer peticiones |
| `Access-Control-Allow-Methods` | `GET, POST, PUT, PATCH, DELETE, OPTIONS` | Los metodos que acepta |
| `Access-Control-Allow-Headers` | `Content-Type` | Las cabeceras que puede mandar el cliente |
| `Access-Control-Allow-Credentials` | `true` | Para que se envien las cookies de sesion |

### Peticiones OPTIONS (preflight)

Cuando el navegador va a hacer una peticion que no es "simple" (por ejemplo un POST con JSON), primero manda un OPTIONS para preguntar si puede. En `apply_cors()` (lineas 110-113) se detecta y se responde con 204 (sin cuerpo) y las cabeceras CORS:

```php
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
```

### Formato de las respuestas

Todas las respuestas pasan por `json_response()` (linea 17 de `Support.php`), que siempre hace lo mismo: poner el codigo HTTP, la cabecera `Content-Type: application/json` y hacer el `json_encode` del array.

Para errores hay `json_error()` (linea 28) que es un wrapper que llama a `json_response` con la estructura `{"error": "mensaje"}`.

Asi todos los errores tienen siempre la misma pinta:
```json
{ "error": "Mensaje de lo que ha fallado." }
```

Esto esta bien porque el frontend solo tiene que mirar si hay una clave `error` en la respuesta para saber si algo fue mal.

---

## Parte G - Propuestas de mejora

### 1. Usar tokens JWT en vez de sesiones

Ahora mismo la autenticacion funciona con sesiones PHP (`$_SESSION`). Esto va bien para un proyecto asi pero tiene algunos problemas si se quiere escalar:

- Las sesiones se guardan en ficheros del servidor. Si tuvieras varios servidores detras de un balanceador de carga, las sesiones no se comparten entre ellos (a no ser que configures algo extra como Redis).
- Para apps moviles o APIs que consumen otros servicios las cookies no son lo mas comodo.

La mejora seria usar JWT (JSON Web Tokens). Funcionaria asi: al hacer login el servidor genera un token firmado con el ID del usuario y una fecha de caducidad, se lo manda al cliente, y el cliente lo manda en cada peticion con la cabecera `Authorization: Bearer <token>`. El servidor solo tiene que verificar la firma, sin necesidad de guardar nada.

### 2. Paginacion en el listado de tareas

El endpoint `GET /api/tasks` ahora mismo devuelve todas las tareas del usuario de golpe (linea 28 de `TaskController.php`). Si un usuario tiene muchas tareas esto puede ser lento y gastar mucha memoria.

La mejora seria meter paginacion con parametros tipo `GET /api/tasks?page=1&per_page=20`. En la query SQL se usaria `LIMIT` y `OFFSET`, y en la respuesta se incluirian datos como el total de tareas, la pagina actual y cuantas paginas hay en total. Tambien se podrian meter filtros como `?status=pending` o `?priority=3` para que el usuario pueda buscar tareas concretas sin cargar todo.
