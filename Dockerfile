FROM php:8.3-cli-alpine

# create a guest local user
ARG user=localuser
ARG group=localgroup
ARG uid=1000
ARG gid=1000
RUN addgroup -g ${gid} -S ${group} \
    && adduser -u ${uid} -G ${group} -S ${user}

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=0 \
    COMPOSER_HOME=/home/${user}/.composer \
    PATH=/opt/project:/opt/project/vendor/bin:/home/${user}/.composer/vendor/bin:$PATH

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        opcache \
        xdebug \
    && cp -f "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && php -m

WORKDIR /opt/project

# Switch to user
USER ${uid}
