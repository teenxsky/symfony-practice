FROM php:8.2-fpm-alpine

RUN apk update && apk add --no-cache \
    libzip-dev \
    libpq-dev \
    bash

RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql

RUN docker-php-ext-install \
    zip \
    pdo \
    pdo_pgsql \
    pgsql

COPY ./.docker/dev/php/entrypoint.sh /usr/local/entrypoint.sh
RUN chmod +x /usr/local/entrypoint.sh
ENTRYPOINT ["sh", "/usr/local/entrypoint.sh"]

WORKDIR /var/www/app

CMD [ "php-fpm", "--fpm-config", "/usr/local/etc/php-fpm.conf" ]