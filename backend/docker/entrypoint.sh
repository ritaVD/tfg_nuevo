#!/bin/sh
set -e

php bin/console cache:warmup --no-debug --env=prod

chown -R www-data:www-data var/

exec /usr/bin/supervisord -c /etc/supervisord.conf
