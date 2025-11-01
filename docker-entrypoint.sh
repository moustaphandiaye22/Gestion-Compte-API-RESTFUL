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

echo "Installing Passport and creating personal access client..."
php artisan passport:install --force

echo "Executing seeders"
php artisan db:seed --force

echo "Creating personal access client for production..."
CLIENT_ID=$(php artisan tinker --execute="
use Illuminate\Support\Facades\DB;

// Supprimer les anciens clients
DB::table('oauth_personal_access_clients')->delete();
DB::table('oauth_clients')->where('personal_access_client', 1)->delete();

// Créer le nouveau client
\$clientId = DB::table('oauth_clients')->insertGetId([
    'user_id' => null,
    'name' => 'Laravel Personal Access Client',
    'secret' => null,
    'provider' => null,
    'redirect' => 'http://localhost',
    'personal_access_client' => 1,
    'password_client' => 0,
    'revoked' => 0,
    'created_at' => now(),
    'updated_at' => now(),
]);

DB::table('oauth_personal_access_clients')->insert([
    'client_id' => \$clientId,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo \$clientId;
" 2>/dev/null)

echo "Personal Access Client créé avec ID: $CLIENT_ID"
echo "Ajoutez cette variable à vos variables d'environnement Render:"
echo "PASSPORT_PERSONAL_ACCESS_CLIENT_ID=$CLIENT_ID"

echo "Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan view:cache

echo "Processing queued jobs..."
php artisan queue:work --once --tries=3 --timeout=30

echo "Starting Laravel application..."
exec "$@"
