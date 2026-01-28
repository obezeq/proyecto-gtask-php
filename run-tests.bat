@echo off
REM Script para ejecutar tests de GTask PHP usando Docker
REM Uso: run-tests.bat

echo === Ejecutando tests de GTask PHP ===
echo.

REM Construir y levantar contenedores de test
echo Levantando contenedores de test...
docker compose -f docker-compose.test.yml up -d --build
if %ERRORLEVEL% neq 0 goto :error

REM Esperar a que la BD este lista
echo Esperando a que PostgreSQL este listo...
timeout /t 5 /nobreak >nul

REM Instalar dependencias de Composer
echo Instalando dependencias de Composer...
docker compose -f docker-compose.test.yml exec -T php-test composer install --no-interaction --prefer-dist
if %ERRORLEVEL% neq 0 goto :error

REM Ejecutar tests
echo.
echo === Ejecutando PHPUnit ===
echo.
docker compose -f docker-compose.test.yml exec -T php-test ./vendor/bin/phpunit --colors=always
set TEST_RESULT=%ERRORLEVEL%

REM Limpiar contenedores
echo.
echo Limpiando contenedores de test...
docker compose -f docker-compose.test.yml down -v

echo.
if %TEST_RESULT% equ 0 (
    echo === TODOS LOS TESTS PASARON ===
) else (
    echo === ALGUNOS TESTS FALLARON ===
)

exit /b %TEST_RESULT%

:error
echo.
echo === ERROR: Fallo al ejecutar los tests ===
docker compose -f docker-compose.test.yml down -v 2>nul
exit /b 1
