FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application files
COPY . .

# Note: If scraped_articles.json exists in the repo root or ../scraper/, 
# copy it to storage/app/ before building, or the seeder will create sample articles

# Run post-install scripts
RUN composer dump-autoload --optimize

# Create storage directories if they don't exist and set permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database && \
    chmod -R 755 storage bootstrap/cache database

# Generate app key if not exists (will be overridden by env var if set)
RUN php artisan key:generate --force || true

# Don't cache config here - it will be done at runtime after database is created
# This ensures the database path is correct

# Copy startup script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port (Render will set PORT env var)
EXPOSE $PORT

# Start PHP built-in server with database initialization
CMD ["/usr/local/bin/start.sh"]

