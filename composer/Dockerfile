FROM php:7.4-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli

RUN apt-get update \
    && apt-get install -y zip libzip-dev zip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

RUN apt-get update
RUN apt-get install -y bash git libzip-dev openssh-client zip unzip dos2unix

