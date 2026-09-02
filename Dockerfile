# Stage 1: Build Stage for Composer Dependencies
FROM composer:latest AS composer_builder
WORKDIR /app
COPY composer.json composer.lock ./
# If composer.lock doesn't exist, remove it from the COPY command above
RUN composer install --no-dev --ignore-platform-reqs --no-scripts --no-autoloader

# Stage 2: Final Production Image
FROM php:8.1-apache

# Install only necessary system dependencies and clean up in the same layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install mysqli pdo_mysql zip gd \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy your code into the container
COPY . .

# Copy vendor from the build stage
COPY --from=composer_builder /app/vendor /var/www/html/vendor

# Use the custom php.ini
COPY php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Create necessary directories and set permissions
RUN mkdir -p sessions uploads content includes && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 sessions uploads content includes

# Ensure .htaccess is applied correctly if the docker-specific one exists
RUN if [ -f htaccess_docker.htaccess ]; then cp htaccess_docker.htaccess .htaccess; fi
