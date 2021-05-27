FROM php:8.0-cli-alpine

ENV PHP_APP_ROOT=/usr/src/app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions \
    ast \
    pcntl \
    sodium \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache \
    git \
    openssh-client

RUN ssh-keyscan gitlab.com >> /etc/ssh/ssh_known_hosts \
    ssh-keyscan github.com >> /etc/ssh/ssh_known_hosts

WORKDIR ${PHP_APP_ROOT}