#!/bin/bash
set -e

# Set database path explicitly
export DB_CONNECTION=sqlite
export DB_DATABASE=/var/www/database/database.sqlite

# Create database directory if it doesn't exist
mkdir -p /var/www/database
chmod 755 /var/www/database

# Create SQLite database file if it doesn't exist
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
    chmod 664 /var/www/database/database.sqlite
    echo "✓ Created database file: /var/www/database/database.sqlite"
else
    echo "✓ Database file already exists: /var/www/database/database.sqlite"
fi

# Clear config cache to ensure fresh database path
echo "Clearing config cache..."
php artisan config:clear || true
php artisan cache:clear || true

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

echo "✓ Migrations completed successfully"

# Seed database if empty
echo "Seeding database with articles..."
php artisan db:seed --force --class=ArticleSeeder || echo "Seeder completed (articles may already exist)"

# Start the server
echo "Starting Laravel server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

