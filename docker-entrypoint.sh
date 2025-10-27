#!/bin/sh

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
export PGPASSWORD=$DB_PASSWORD
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

echo "Executing seeders"
php artisan db:seed --force

echo "Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan view:cache

echo "Processing queued jobs..."
php artisan queue:work --once --tries=3 --timeout=30

echo "Starting Laravel application..."
exec "$@"
