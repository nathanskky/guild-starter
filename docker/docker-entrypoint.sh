#!/bin/sh
set -e

envsubst '$APP_HTTPS_PORT' < /etc/apache2/sites-available/000-default.conf.template > /etc/apache2/sites-available/000-default.conf
envsubst '$APP_HTTPS_PORT' < /etc/apache2/mods-available/auth_cas.conf.template > /etc/apache2/mods-available/auth_cas.conf

exec docker-php-entrypoint "$@"
