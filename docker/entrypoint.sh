#!/bin/sh
set -eu
mkdir -p /var/www/html/var/cache /var/www/html/var/session /var/www/html/var/log /var/www/html/public/uploads/players
chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads
php /var/www/html/scripts/migrate.php
php /var/www/html/scripts/seed.php
exec "$@"
