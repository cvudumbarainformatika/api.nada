FROM php:8.3-fpm

# Gunakan user host machine
ARG user=laravel
ARG uid=1000
ARG gid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    libbrotli-dev \
    pkg-config \
    # Tambahkan dependencies untuk GD (baru tgl 22 april 2025)
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
# RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring exif pcntl bcmath zip

# Install Redis Extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Swoole (dengan opsi yang lebih sederhana)
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user
RUN groupadd -g $gid $user && \
    useradd -u $uid -g $user -s /bin/bash -m $user

# Set working directory
WORKDIR /var/www

# Create necessary directories
RUN mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache \
    /etc/supervisor/conf.d

# Copy supervisor configuration
COPY docker-compose/supervisor/websockets.conf /etc/supervisor/conf.d/

# Set permissions
RUN chown -R $user:$user /var/www && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    chown -R $user:$user /etc/supervisor/conf.d

# Copy and set permissions for entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER root

ENTRYPOINT ["docker-entrypoint.sh"]
