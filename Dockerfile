FROM php:7.3.25-fpm

WORKDIR /module

ENV PHP_IDE_CONFIG 'serverName=DockerApp'
RUN apt-get update && apt-get install -y \
    # Required for wait-for-health
    curl jq \
    # Required for Mutagen termination
    procps \
    # Required by the Composer plugin
    git unzip \
    && docker-php-ext-install bcmath \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer self-update 1.10.20

ENV XDEBUG_MODE debug
ENV XDEBUG_TRIGGER 1
ADD ./.devcontainer/files/php.ini /usr/local/etc/php

RUN useradd -rm -d /home/ubuntu -s /bin/bash -g root -G sudo -u 1001 developer
RUN chown -R developer:root /module
USER developer
