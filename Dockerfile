ARG PHP_VERSION
FROM php:${PHP_VERSION}-cli

WORKDIR /module

ENV PHP_IDE_CONFIG 'serverName=DockerApp'
ENV XDEBUG_MODE debug
ENV XDEBUG_TRIGGER 1
ENV COMPOSER_ALLOW_XDEBUG 1

RUN apt-get update && apt-get install -y \
    # Required for wait-for-health
    curl jq \
    # Required for Mutagen termination
    procps \
    # Required by the Composer plugin
    git unzip locales \
    && docker-php-ext-install bcmath \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ARG COMPOSER_VERSION
RUN composer self-update $COMPOSER_VERSION
RUN useradd -rm -d /home/developer -s /bin/bash -g root -G sudo -u 1001 developer
RUN chown -R developer:root /module
RUN echo 'export LANGUAGE=en_US.UTF-8' >> /home/developer/.bashrc
RUN echo 'export LANG=en_US.UTF-8' >> /home/developer/.bashrc
RUN echo 'export LC_ALL=en_US.UTF-8' >> /home/developer/.bashrc
RUN touch /var/log/xdebug.log && chmod 775 /var/log/xdebug.log
RUN echo 'LC_ALL=en_US.UTF-8' >> /etc/environment
RUN echo 'en_US.UTF-8 UTF-8' >> /etc/locale.gen
RUN echo 'LANG=en_US.UTF-8' > /etc/locale.conf
RUN locale-gen en_US.UTF-8

ADD ./.devcontainer/files/php.ini /usr/local/etc/php
USER developer
