FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip

WORKDIR /var/www/html

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --optimize-autoloader --no-dev

COPY .env.example .env

RUN php artisan key:generate

EXPOSE 80
