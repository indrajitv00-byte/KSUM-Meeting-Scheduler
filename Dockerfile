# Base build stage
FROM composer:2 as vendor
WORKDIR /app
COPY composer.json composer.lock /app/
RUN composer install --no-dev --prefer-dist --no-scripts --no-progress --no-interaction --ignore-platform-reqs

# Node.js stage for building assets
FROM node:20 as frontend
WORKDIR /app
COPY package.json package-lock.json /app/
COPY --from=vendor /app/vendor /app/vendor
RUN npm install
COPY . .
RUN npm run build

# Production image
FROM php:8.5-fpm

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libonig-dev libzip-dev libxml2-dev unzip curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath xml ctype zip exif intl opcache pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Enable Opcache optimizations
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini

# Create a non-root user
RUN groupadd -g 1000 laravel && useradd -m -u 1000 -g laravel -s /bin/bash laravel

# Set working directory
WORKDIR /var/www

# Copy application source with correct ownership
COPY --chown=1000:1000 . .
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=frontend /app/public /var/www/public

# Ensure storage and cache directories exist and have correct permissions
RUN mkdir -p /var/www/storage /var/www/storage/app \
      /var/www/storage/framework /var/www/storage/logs \
      /var/www/storage/framework/cache /var/www/storage/framework/views \
      /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    chown -R 1000:1000 /var/www/storage /var/www/bootstrap/cache /var/www/public

# Configure PHP-FPM to run as non-root user
RUN sed -i 's/user = www-data/user = laravel/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = laravel/' /usr/local/etc/php-fpm.d/www.conf

# Use non-root user
USER laravel

# Expose port
EXPOSE 9000

CMD ["sh", "-c", "exec php-fpm"]
