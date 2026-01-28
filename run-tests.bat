@echo off
setlocal enabledelayedexpansion

REM Script para ejecutar tests de GTask PHP usando Docker
REM Uso: run-tests.bat

echo === Ejecutando tests de GTask PHP ===
echo.

REM Verificar que Docker esta disponible
docker --version >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo ERROR: Docker no esta instalado o no esta en el PATH
    exit /b 1
)

REM Construir y levantar contenedores de test
echo Levantando contenedores de test...
call docker compose -f docker-compose.test.yml up -d --build
if !ERRORLEVEL! neq 0 (
    echo ERROR: No se pudieron levantar los contenedores
    goto :cleanup
)

REM Esperar a que la BD este lista
echo Esperando a que PostgreSQL este listo...
timeout /t 5 /nobreak >nul

REM Instalar dependencias de Composer
echo Instalando dependencias de Composer...
call docker compose -f docker-compose.test.yml exec -T php-test composer install --no-interaction --prefer-dist
if !ERRORLEVEL! neq 0 (
    echo ERROR: No se pudieron instalar las dependencias
    goto :cleanup
)

REM Ejecutar tests
echo.
echo === Ejecutando PHPUnit ===
echo.
call docker compose -f docker-compose.test.yml exec -T php-test vendor/bin/phpunit --colors=always
set TEST_RESULT=!ERRORLEVEL!

:cleanup
REM Limpiar contenedores
echo.
echo Limpiando contenedores de test...
call docker compose -f docker-compose.test.yml down -v >nul 2>&1

echo.
if !TEST_RESULT! equ 0 (
    echo === TODOS LOS TESTS PASARON ===
) else (
    echo === ALGUNOS TESTS FALLARON ===
)

endlocal
exit /b %TEST_RESULT%
