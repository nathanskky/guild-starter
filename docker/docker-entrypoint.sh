#!/bin/sh
set -e

# Render the bind-mounted Apache templates with the current APP_HTTPS_PORT, so changing the port
# needs only a restart through a start script, not an image rebuild.
envsubst '$APP_HTTPS_PORT' < /etc/apache2/sites-available/000-default.conf.template > /etc/apache2/sites-available/000-default.conf
envsubst '$APP_HTTPS_PORT' < /etc/apache2/mods-available/auth_cas.conf.template > /etc/apache2/mods-available/auth_cas.conf

# Install dependencies into the bind-mounted project. A failure is reported but does not stop the
# container, so Apache still starts and `docker compose exec app composer install` can be retried.
composer install --working-dir=/var/www --no-interaction --no-progress \
    || echo "docker-entrypoint.sh: composer install failed; see the output above" >&2

# Create or update the database tables. Like the install, a failure is reported but does not stop
# the container; rerun it with `docker compose exec app composer migrate`.
composer migrate --working-dir=/var/www --no-interaction \
    || echo "docker-entrypoint.sh: composer migrate failed; see the output above" >&2

exec docker-php-entrypoint "$@"
