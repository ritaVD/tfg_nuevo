#!/bin/sh
set -e

mkdir -p public/uploads/avatars public/uploads/posts

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:create-admin || true
php bin/console cache:warmup --no-debug --env=prod

chown -R www-data:www-data var/ public/uploads/

exec /usr/bin/supervisord -c /etc/supervisord.conf
