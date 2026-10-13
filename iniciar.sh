#!/bin/sh
set -eu
cd "$(dirname "$0")"
if ! command -v docker >/dev/null 2>&1; then
  echo 'Primero instala e inicia Docker Desktop. Consulta README.md.'
  exit 1
fi
if [ ! -f .env ]; then cp .env.example .env; fi
docker compose up --build -d
echo 'Cantera se está iniciando. Abre http://localhost:8080 (o APP_PORT si lo cambiaste).'
echo 'Estado: docker compose ps | Registros: docker compose logs -f app'
