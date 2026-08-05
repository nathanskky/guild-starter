# Begin with a base PHP Docker image
FROM php:8.5-apache

# Add PHP extension installation helper
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install desired PHP extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions intl gd xdebug oci8 pdo_mysql ldap zip

# Copy the Apache virtual host configuration file into the image
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf

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
COPY ./auth_cas.conf /etc/apache2/mods-available/auth_cas.conf
RUN a2enmod auth_cas

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