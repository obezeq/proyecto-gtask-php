# GTask - API de Gestion de Tareas

Backend PHP para gestion de tareas con autenticacion de usuarios.

## Requisitos

- Docker y Docker Compose

## Instalacion

```bash
docker compose build
docker compose up -d
```

La aplicacion estara disponible en `http://localhost`.

## Estructura

```
app/
  public/          # Punto de entrada y assets
  src/             # Codigo fuente (Support.php, Database.php, Controllers/)
  config/          # Configuracion de base de datos
  tests/           # Tests unitarios y de integracion
```

## API Endpoints

### Autenticacion

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/api/register` | POST | Registro de usuario |
| `/api/login` | POST | Inicio de sesion |
| `/api/logout` | POST | Cierre de sesion |
| `/api/me` | GET | Usuario actual |

### Tareas (requieren autenticacion)

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/api/tasks` | GET | Listar tareas |
| `/api/tasks` | POST | Crear tarea |
| `/api/tasks/{id}` | GET | Ver tarea |
| `/api/tasks/{id}` | PATCH | Actualizar tarea |
| `/api/tasks/{id}` | DELETE | Eliminar tarea |

## Ejemplos de uso

Registro:
```bash
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Usuario","email":"user@example.com","password":"123456"}'
```

Login:
```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"email":"user@example.com","password":"123456"}'
```

Crear tarea:
```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"title":"Mi tarea","description":"Descripcion","priority":2}'
```

Listar tareas:
```bash
curl http://localhost/api/tasks -b cookies.txt
```

## Tests

Ejecutar tests con PHPUnit:

```bash
./run-tests.sh
```

O directamente:

```bash
docker exec php-container sh -c "cd /var/www/html && ./vendor/bin/phpunit"
```

## Cliente Web

Accede a `http://localhost` para usar la interfaz web.
