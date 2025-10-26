# Following line is for non-zero downtime deployment
php artisan down

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "" | sudo -S service php8.4-fpm reload

php artisan env:decrypt --env=production --key="$LARAVEL_ENV_ENCRYPTION_KEY" --force
mv .env.production .env

php artisan cache:clear

php artisan config:clear
php artisan config:cache

php artisan route:clear
php artisan route:cache

php artisan view:cache
php artisan view:clear

php artisan migrate --force

export NODE_OPTIONS=--max-old-space-size=4096

npm install
npm run build

php artisan vite:publish

# Following line is for non-zero downtime deployment
php artisan up

# Cache
php artisan optimize
php artisan event:cache

## Stop SSR and run with zero downtime and SSR enabled
# php artisan inertia:stop-ssr

# Prepare Storage
rm -rf ./public/storage
php artisan storage:link

# php artisan queue:restart

echo "🚀 Application deployed!"