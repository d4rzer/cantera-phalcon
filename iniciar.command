#!/bin/sh
set -eu
cd "$(dirname "$0")"
sh ./iniciar.sh
printf '\nPresiona Enter para cerrar esta ventana.\n'
read respuesta
