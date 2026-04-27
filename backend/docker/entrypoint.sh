#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console cache:warmup --no-debug --env=prod

chown -R www-data:www-data var/

exec /usr/bin/supervisord -c /etc/supervisord.conf
