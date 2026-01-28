# Estructura del proyecto GTask

Este proyecto es una API REST en PHP 8.4 con PostgreSQL 17, empaquetada en Docker.
Utiliza autoloading PSR-4 con Composer y namespaces para una organizacion moderna del codigo.
Incluye un cliente web basico y tests con PHPUnit.

## Raiz del proyecto
- Dockerfile: construye la imagen PHP con extensiones pdo_pgsql, pgsql y Composer.
- docker-compose.yml: levanta los contenedores de PHP, Nginx y Postgres (network_mode: host).
- nginx.conf: config de Nginx para servir /public y pasar PHP a PHP-FPM.
- init.sql: crea las tablas `users` y `tasks` en la base de datos.
- run-tests.sh: script para ejecutar los tests PHPUnit en el contenedor.
- INSTRUCCIONES.md / SEGUIMIENTO.md / PENDIENTE.md: seguimiento del trabajo.

## Carpeta app/

### app/public/
- index.php: punto de entrada. Usa autoloading PSR-4 y match expressions para routing.
- views/app.php: HTML del cliente web.
- assets/style.css: estilos minimos para que sea usable.
- assets/app.js: logica del cliente (fetch a /api con cookies de sesion).

### app/src/ (Namespace: App\)
- Support/ (Namespace: App\Support)
  - Response.php: respuestas JSON con metodos json() y error().
  - Request.php: lectura del cuerpo JSON de la peticion.
  - Auth.php: gestion de sesion (login, logout, verificacion).
  - Cors.php: cabeceras CORS y manejo de OPTIONS.
- Database/ (Namespace: App\Database)
  - Connection.php: conexion PDO a PostgreSQL con patron singleton.
- Controllers/ (Namespace: App\Controllers)
  - AuthController.php: registro, login, logout, /api/me.
  - TaskController.php: CRUD de tareas con validaciones.

### app/config/
- config.php: lee las variables de entorno (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD).

### app/tests/ (Namespace: Tests\)
- Unit/
  - Support/ResponseTest.php: tests de respuestas JSON.
  - Controllers/AuthControllerTest.php: tests de validacion de auth.
  - Controllers/TaskControllerTest.php: tests de validacion de tareas.
- Feature/
  - TestCase.php: clase base con transacciones y helpers.
  - AuthApiTest.php: tests de integracion de autenticacion.
  - TaskApiTest.php: tests de integracion de tareas.

### app/composer.json
Configura autoloading PSR-4 y PHPUnit 11.

### app/phpunit.xml
Configuracion de PHPUnit con suites Unit y Feature.

## Flujo principal
1) Nginx recibe la peticion.
2) Si es un archivo estatico (assets), lo sirve directamente.
3) Si no, reenvia a app/public/index.php.
4) index.php carga el autoloader de Composer y usa match expressions para routing.
5) Los controladores usan PDO (inyectado) para consultar la base de datos.
6) Las respuestas son JSON (o la vista HTML en /).

## Conexion a la base de datos
- Los parametros se obtienen desde variables de entorno del contenedor.
- Si no existen, se usan valores por defecto (host 127.0.0.1, puerto 5432, etc.).
- App\Database\Connection construye el DSN y crea el objeto PDO.

## Donde llegan los datos del cliente
- En la API, el cliente envia JSON en el cuerpo de la peticion.
- App\Support\Request::json() lee `php://input` y lo convierte a array.
- Los controladores leen esos campos y los validan antes de ejecutar SQL.

## Tests
- 46 tests en total (28 unitarios + 18 de integracion)
- 118 assertions
- Ejecutar con: `./run-tests.sh` o `docker exec php-container ./vendor/bin/phpunit`
