FROM php:8.1-apache

RUN apt-get update \
    && apt-get install -yq curl libzip-dev imagemagick locales unzip libxslt-dev openssl build-essential libssl-dev libicu-dev wget libfreetype6-dev libjpeg62-turbo-dev libpng-dev cron \
    && apt-get upgrade -yq \
	&& pecl install redis

RUN apt-get install -y locales locales-all

# PHP modules
RUN docker-php-ext-install intl \
    && docker-php-ext-install xsl \
    && docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

