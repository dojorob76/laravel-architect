FROM php:8.4-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    postgresql-dev \
    sqlite-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions required for Laravel and database testing
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip \
    pcntl \
    intl \
    mbstring

# Install and enable SQLite (usually included in PHP but ensure PDO SQLite)
RUN docker-php-ext-install pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Create non-root user for running commands
RUN addgroup -g 1000 laravel && \
    adduser -D -u 1000 -G laravel laravel

# Copy composer files first for better caching
COPY --chown=laravel:laravel composer.json composer.lock* ./

# Switch to non-root user
USER laravel

# Install dependencies (will be overridden by volume in development)
RUN composer install --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs || true

# Copy application files
COPY --chown=laravel:laravel . .

# Generate autoloader
RUN composer dump-autoload --optimize || true

# Default command
CMD ["php", "-v"]
