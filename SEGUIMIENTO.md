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

- 2026-01-28: Retomé el proyecto. Quería mejorar la estructura asi que configuré Composer con autoloading PSR-4.
- 2026-01-28: Separé el archivo `Support.php` en varias clases, cada una con su namespace. Quedó mucho mas organizado.
- 2026-01-28: Hice lo mismo con `Database.php`, ahora está en `App\Database\Connection`.
- 2026-01-28: Añadí namespaces a los controllers para que todo siga el mismo patron.
- 2026-01-28: Tuve que actualizar el `index.php` para que use el autoloader de Composer. Aproveché para usar match en vez de if/else.
- 2026-01-28: Instalé PHPUnit para hacer tests. Me costó un poco configurarlo pero al final funcionó.
- 2026-01-28: Escribí tests unitarios para las validaciones, en total 28.
- 2026-01-28: También hice tests de integración para probar la API completa, 18 más.
- 2026-01-28: Todos los tests pasan (46 en total). Me quedo tranquilo de que no he roto nada.
- 2026-01-28: Tuve problemas con la red de Docker, los contenedores no se comunicaban. Al final lo solucioné usando `network_mode: host`.
- 2026-01-28: Por eso tuve que cambiar nginx.conf para que apunte a `127.0.0.1:9000` en vez de `php:9000`.
- 2026-01-28: Creé un script `run-tests.sh` para ejecutar los tests facilmente.
- 2026-01-28: Probé todo: API, tests, Docker, cliente web. Todo funciona bien, proyecto terminado.
