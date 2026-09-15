# Begin with a base PHP Docker image
FROM php:8.5-apache

# Add PHP extension installation helper
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install desired PHP extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions intl gd xdebug oci8 pdo_mysql ldap zip

# The Apache virtual host config and the CAS module config are bind-mounted at container start
# (see docker-compose.yml) and rendered by docker-entrypoint.sh, so the port they reference can
# change without rebuilding the image; the files installed below by their respective packages
# are overwritten at container start with rendered content from the bind-mounted templates.

# Enable the Apache rewrite module
RUN a2enmod rewrite

# Enable SSL
RUN set -eux; \
    apt-get update; \
    apt-get install -y ssl-cert; \
    a2enmod ssl; \
    a2ensite default-ssl; \
    rm -rf /var/lib/apt/lists/*

# Enable Apereo CAS module for Apache
RUN apt-get update && \
    apt-get install -y libapache2-mod-auth-cas
RUN a2enmod auth_cas

# Install envsubst, used by docker-entrypoint.sh to render the port into Apache config at startup
RUN apt-get update && \
    apt-get install -y gettext-base

# Copy the entrypoint script that renders the bind-mounted config templates before Apache starts
COPY ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy xdebug configuration into the image
COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Open port 80 and 443 for Apache
EXPOSE 80 443

# Install git, which is needed by composer
RUN apt-get update && \
    apt-get install -y git

# Make composer available
# TODO: verify this is still needed, as well as whether the command itself is correct
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Link sh to bash, so bash is used when opening the CLI from Docker Desktop
# (This is a bit of a hack though, and not strictly necessary)
RUN ln -sf /bin/bash /bin/sh

# Set the default working directory when opening an interactive shell
WORKDIR /var/www

# Render the bind-mounted config templates, then hand off to the base image's normal entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]