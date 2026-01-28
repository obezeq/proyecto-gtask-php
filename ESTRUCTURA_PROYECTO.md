# Estructura del proyecto GTask

Proyecto de API REST con PHP 8.4 y PostgreSQL 17, dockerizado.
Hay un cliente web basico y tests con PHPUnit.

## Raiz del proyecto
- Dockerfile: construye la imagen PHP con extensiones pdo_pgsql, pgsql y Composer.
- docker-compose.yml: levanta los contenedores de PHP, Nginx y Postgres (network_mode: host).
- nginx.conf: config de Nginx para servir /public y pasar PHP a PHP-FPM.
- init.sql: crea las tablas `users` y `tasks` en la base de datos.
- run-tests.sh: script para ejecutar los tests PHPUnit en el contenedor.
- INSTRUCCIONES.md / SEGUIMIENTO.md / PENDIENTE.md: seguimiento del trabajo.

## Carpeta app/

### app/public/
- index.php: punto de entrada. Incluye los archivos necesarios y usa match expressions para routing.
- views/app.php: HTML del cliente web.
- assets/style.css: estilos minimos para que sea usable.
- assets/app.js: logica del cliente (fetch a /api con cookies de sesion).

### app/src/
- Support.php: funciones de utilidad (json_response, json_error, get_json_body, sesion, CORS).
- Database.php: clase Database para la conexion PDO a PostgreSQL.
- Controllers/
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
Configura autoloading para tests y PHPUnit 11.

### app/phpunit.xml
Configuracion de PHPUnit con suites Unit y Feature.

## Flujo principal
1) Nginx recibe la peticion.
2) Si es un archivo estatico (assets), lo sirve directamente.
3) Si no, reenvia a app/public/index.php.
4) index.php incluye Support.php, Database.php y los controladores, luego usa match expressions para routing.
5) Los controladores usan PDO (inyectado) para consultar la base de datos.
6) Las respuestas son JSON (o la vista HTML en /).

## Conexion a la base de datos
- Los parametros se obtienen desde variables de entorno del contenedor.
- Si no existen, se usan valores por defecto (host 127.0.0.1, puerto 5432, etc.).
- La clase Database construye el DSN y crea el objeto PDO.

## Donde llegan los datos del cliente
- En la API, el cliente envia JSON en el cuerpo de la peticion.
- La funcion get_json_body() en Support.php lee `php://input` y lo convierte a array.
- Los controladores leen esos campos y los validan antes de ejecutar SQL.

## Tests
46 tests (28 unitarios, 18 integracion).
Ejecutar con `./run-tests.sh` o `docker exec php-container ./vendor/bin/phpunit`
