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

## Mejoras PHP Moderno (Completadas 2026-01-28)
- [x] Configurar Composer con autoloading PSR-4.
- [x] Refactorizar Support.php en clases separadas con namespaces (App\Support\*).
- [x] Refactorizar Database.php a App\Database\Connection.
- [x] Agregar namespaces a Controllers (App\Controllers\*).
- [x] Actualizar router con autoloading y match expressions.
- [x] Configurar PHPUnit para testing.
- [x] Implementar tests unitarios (28 tests).
- [x] Implementar tests de integración (18 tests).
- [x] Configurar Docker para testing (run-tests.sh, docker-compose.test.yml).
- [x] Cambiar a network_mode: host para resolver problemas de red Docker.
- [x] Verificación completa del proyecto.

## Estado Final
Todas las tareas completadas. Proyecto funcional al 100%.
