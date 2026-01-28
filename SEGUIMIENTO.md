# Seguimiento

- 2026-01-26: Revisé `docker-compose.yml`, estructura en `app/`, `app/src/primer.php`, `app/public/index.php` y `init.sql`.
- 2026-01-26: Creé `INSTRUCCIONES.md`, `SEGUIMIENTO.md`, `PENDIENTE.md` para trazar la tarea.

- 2026-01-26: Revisé `nginx.conf`, `php.ini` y `app/nginx.conf` (duplicado).
- 2026-01-26: Revisé `composer.json` (vacío) y confirmé que `public/index.php` solo muestra `phpinfo()`.
- 2026-01-26: Actualicé `docker-compose.yml` para Postgres 17 y eliminé la IP fija + config IPAM.
- 2026-01-26: Decisiones confirmadas: Postgres 17, sin IP fija, API separada `/api`, auth con email único y `password_hash`, y campos extra en tareas (`priority`, `updated_at`, `completed_at`).
- 2026-01-26: Al retomar, empezar por Dockerfile de PHP con `pdo_pgsql` y mini-MVC + API `/api` + auth/CRUD.

- 2026-01-26: Creé `Dockerfile` para PHP con `pdo_pgsql`/`pgsql` y actualicé `docker-compose.yml` para construir la imagen y pasar variables de entorno.
- 2026-01-26: Añadí `app/config/config.php`, `app/src/Database.php`, `app/src/Support.php` y controladores de auth/tareas con rutas JSON en `app/public/index.php`.
- 2026-01-26: Actualicé `init.sql` con `priority`, `updated_at` y `completed_at` en tareas.
- 2026-01-26: Siguiente paso: revisar si hay que ajustar `nginx.conf`/`php.ini`, levantar Docker y probar endpoints `/api`.

- 2026-01-26: Intenté `docker compose build`/`up --build`, pero el build falló por problemas DNS al descargar índices de paquetes (Debian/Alpine) dentro del build.
- 2026-01-26: Dejé `Dockerfile` en base Debian con `Acquire::ForceIPv4`/`Retries` para mejorar la estabilidad; falta levantar Docker y probar endpoints.
- 2026-01-26: Ejecuté `docker compose build` y `docker compose up -d`; corregí permisos 600 en `app/` e `init.sql` a 644 para evitar 403 y fallo de init de Postgres.
- 2026-01-26: Arranqué `postgres-container` y apliqué `init.sql` manualmente; verifiqué endpoints `/api` (registro, me, tareas) con respuesta OK.
- 2026-01-26: Nota: hay un Postgres 15 de otro proyecto que se levanta al reiniciar el ordenador; recordar pararlo para evitar conflictos con el Postgres de este proyecto.
- 2026-01-26: Añadí documentacion de estructura (`ESTRUCTURA_PROYECTO.md`) y ejemplos de API (`EJEMPLOS_API.md`).
- 2026-01-26: Añadí cliente web basico en `/` con vista HTML y assets (`app/public/views/app.php`, `app/public/assets/*`).
- 2026-01-26: Documenté los pasos completos en `PASOS_DESARROLLO.md`.
- 2026-01-26: Añadí checklist de entrega en `CHECKLIST_ENTREGA.md`.
- 2026-01-26: Añadí manual de usuario (`MANUAL_USUARIO.md`) y manual de despliegue (`MANUAL_DESPLIEGUE.md`).

- 2026-01-28: Configuré Composer con autoloading PSR-4 para `App\` y `Tests\`.
- 2026-01-28: Refactoricé `Support.php` en clases separadas con namespaces: `App\Support\Response`, `App\Support\Request`, `App\Support\Auth`, `App\Support\Cors`.
- 2026-01-28: Refactoricé `Database.php` a `App\Database\Connection` con namespace PSR-4.
- 2026-01-28: Agregué namespaces a Controllers (`App\Controllers\AuthController`, `App\Controllers\TaskController`).
- 2026-01-28: Actualicé `index.php` con autoloading de Composer y match expressions para routing.
- 2026-01-28: Configuré PHPUnit 11 con `phpunit.xml` y estructura de tests.
- 2026-01-28: Implementé 28 tests unitarios para validaciones de auth y tareas.
- 2026-01-28: Implementé 18 tests de integración para API de auth y tareas.
- 2026-01-28: Total: 46 tests, 118 assertions - todos pasan.
- 2026-01-28: Cambié a `network_mode: host` en Docker para resolver problemas de red entre contenedores.
- 2026-01-28: Actualicé `nginx.conf` para usar `127.0.0.1:9000` en lugar de `php:9000`.
- 2026-01-28: Creé `run-tests.sh` y `docker-compose.test.yml` para ejecutar tests en Docker.
- 2026-01-28: Verificación completa: API, tests, Docker, cliente web - todo funcionando al 100%.
