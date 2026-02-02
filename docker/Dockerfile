FROM php:8.2-fpm

# Set Environment Variables
#ENV DEBIAN_FRONTEND=noninteractive
#ARG USER
#ARG UID=1000
#ARG GID=$UID

# Установка зависимостей, как было ранее
RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
            ca-certificates \
            git \
            curl \
            default-mysql-client \
            libmemcached-dev \
            libz-dev \
            libpq-dev \
            libjpeg-dev \
            libpng-dev \
            libfreetype6-dev \
            libssl-dev \
            libwebp-dev \
            libxpm-dev \
            libmcrypt-dev \
            libonig-dev \
            libzip-dev \
            zip \
            unzip; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    docker-php-ext-configure gd --with-jpeg --with-webp --with-xpm --with-freetype; \
    docker-php-ext-install pdo_mysql pdo_pgsql gd pcntl zip;

RUN apt-get update && apt-get install -y libpcre2-dev && \
    pecl install redis && \
    docker-php-ext-enable redis

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN update-ca-certificates

RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

COPY ./start.sh /var/www/
RUN chmod +x /var/www/start.sh

RUN git config --global --add safe.directory /var/www/html

WORKDIR /var/www/html

COPY ./laravel.ini /usr/local/etc/php/conf.d/custom.ini

ENTRYPOINT ["/var/www/start.sh"]
CMD ["php-fpm"]
