# Use the official PHP 8.1 image with an Apache server
FROM php:8.1-apache

# Install system dependencies required by Moodle's PHP extensions
# git, zip/unzip are useful utilities for managing Moodle code and plugins
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    git \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install the required PHP extensions for Moodle/IOMAD
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql \
    zip \
    intl \
    soap \
    opcache

# Enable Apache modules required by Moodle
RUN a2enmod rewrite headers

# Copy our custom PHP configuration to override defaults
# This sets memory limits, upload sizes, etc., as defined in our Knowledge Base
COPY __refactor/config/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set the working directory
WORKDIR /var/www/html
