# Pendiente

## Tareas Iniciales (Completadas)
- [x] Validar necesidades de IP fija en red Docker y confirmar versión de Postgres requerida.
- [x] Definir alcance funcional: autenticación, CRUD de tareas, roles, vistas, etc.
- [x] Alcance confirmado: registro/login, autorización por usuario (no tocar tareas ajenas), tareas con campos `title`, `description`, `status`, `due_date`, `priority`, `updated_at`, `completed_at`.
- [x] API sin UI: rutas separadas `/api/...` con JSON.
- [x] Credenciales: email único y contraseña hasheada con `password_hash`.
- [x] Diseñar estructura base (routing, controllers, modelos, vistas) dentro de `app/`.
- [x] Implementar conexión a BD (Postgres) y configuración de entorno.
- [x] Implementar scripts PHP para login/registro y CRUD de tareas.
- [x] Ajustar `nginx.conf`/`php.ini` si hace falta. (Ajustado nginx.conf para usar 127.0.0.1:9000)

- [x] Confirmar si se debe crear imagen PHP con extensiones `pdo_pgsql`/`pgsql` (necesarias para Postgres).
- [x] Decidir si eliminar red con IP fija y usar DNS de Docker por nombre de servicio.
- [x] Crear Dockerfile para PHP con extensiones `pdo_pgsql`/`pgsql` y actualizar `docker-compose.yml` para usarlo.
- [x] Definir endpoints/acciones sin UI (p. ej. JSON) además de HTML simple.
- [x] Levantar Docker y probar endpoints `/api` (registro/login/tareas).

## Mejoras (28 enero)
- [x] Composer PSR-4
- [x] Support.php separado en clases
- [x] Database.php con namespace
- [x] Namespaces en controllers
- [x] Router con match
- [x] PHPUnit
- [x] Tests unitarios
- [x] Tests integracion
- [x] Script para tests
- [x] Arreglar red docker (network_mode host)

## Estado
Hecho, todo funciona y los tests pasan.
