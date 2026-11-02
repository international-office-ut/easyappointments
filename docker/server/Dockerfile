FROM php:8.3-apache

WORKDIR "/var/www/html"

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg-dev \
        libpng-dev \
        libonig-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mbstring intl gettext mysqli pdo_mysql

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN a2enmod rewrite
RUN a2enmod headers
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
