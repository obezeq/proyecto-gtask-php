#!/bin/bash

# Script para ejecutar tests de GTask PHP usando Docker
# Uso: ./run-tests.sh

set -e

echo "=== Ejecutando tests de GTask PHP ==="
echo ""

# Construir y levantar contenedores de test
echo "Levantando contenedores de test..."
docker compose -f docker-compose.test.yml up -d --build

# Esperar a que la BD este lista
echo "Esperando a que PostgreSQL este listo..."
sleep 5

# Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
docker compose -f docker-compose.test.yml exec -T php-test composer install --no-interaction --prefer-dist

# Ejecutar tests
echo ""
echo "=== Ejecutando PHPUnit ==="
echo ""
docker compose -f docker-compose.test.yml exec -T php-test ./vendor/bin/phpunit --colors=always

# Capturar codigo de salida
EXIT_CODE=$?

# Limpiar contenedores
echo ""
echo "Limpiando contenedores de test..."
docker compose -f docker-compose.test.yml down -v

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "=== TODOS LOS TESTS PASARON ==="
else
    echo "=== ALGUNOS TESTS FALLARON ==="
fi

exit $EXIT_CODE
