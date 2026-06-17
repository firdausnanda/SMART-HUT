# ==========================================
# Stage 1: Build Frontend Assets with Vite
# ==========================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package descriptors and lockfile
COPY package*.json ./

# Install npm dependencies
RUN npm ci

# Copy codebase and compile assets
COPY . .
RUN npm run build

# ==========================================
# Stage 2: Install Composer Dependencies
# ==========================================
FROM composer:2.6 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install composer dependencies ignoring local platform requirements (since extensions are installed in the final stage)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Copy the rest of the application
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --no-dev --optimize

# ==========================================
# Stage 3: Production Runtime Environment
# ==========================================
FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    bash

# Install PHP extensions (with temporary build dependencies to keep image small)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip bcmath opcache \
    && apk del .build-deps

# Configure PHP Opcache for production
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Configure Nginx to run as www-data to match PHP-FPM and prevent permission issues
RUN sed -i 's/user nginx;/user www-data;/' /etc/nginx/nginx.conf \
    && mkdir -p /var/log/supervisor /var/run /var/log/nginx \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Copy application files from composer-builder stage
COPY --from=composer-builder --chown=www-data:www-data /app /var/www/html

# Copy built frontend assets from node-builder stage
COPY --from=node-builder --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Copy configurations
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Fix line endings (CRLF to LF) for the entrypoint and make it executable
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && sed -i -e 's/\r$//' /usr/local/bin/entrypoint.sh

# Adjust storage and bootstrap folder permissions
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose HTTP port
EXPOSE 80

# Run entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
