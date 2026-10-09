#!/bin/sh
set -e

if [ "$APP_ENV" = "dev" ] && [ -f bin/console ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
fi

exec "$@"
