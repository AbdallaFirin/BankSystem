#!/bin/bash
set -e

echo "────────────────────────────────────────"
echo "  Gobaad Bank — Container Startup"
echo "────────────────────────────────────────"

cd /var/www/html

# ── 1. Wait for MySQL ─────────────────────────────────────────────────────────
echo "⏳  Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
until php -r "
new PDO(
    'mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-BankSystem}',
    '${DB_USERNAME:-root}',
    '${DB_PASSWORD:-}'
);
" 2>/dev/null; do
    sleep 2
    echo "    MySQL not ready yet — retrying..."
done
echo "✓  MySQL is ready."

# ── 2. Composer install ───────────────────────────────────────────────────────
if [ ! -f vendor/autoload.php ]; then
    echo "📦  Running composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "✓  Composer done."
else
    echo "✓  Vendor already installed."
fi

# ── 3. NPM install + build ────────────────────────────────────────────────────
if [ ! -d public/build ]; then
    echo "🔨  Running npm install && npm run build..."
    npm ci --prefer-offline 2>/dev/null || npm install
    npm run build
    echo "✓  Frontend assets built."
else
    echo "✓  Frontend assets already built."
fi

# ── 4. Generate APP_KEY if missing ───────────────────────────────────────────
if grep -q "APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑  Generating application key..."
    php artisan key:generate --force
fi

# ── 5. Storage symlink ────────────────────────────────────────────────────────
if [ ! -L public/storage ]; then
    echo "🔗  Creating storage symlink..."
    php artisan storage:link
fi

# ── 6. Set permissions ────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ── 7. Run migrations + seed (seed only on first run) ────────────────────────
echo "🗄️   Running migrations..."
php artisan migrate --force --no-interaction

# Seed only when the roles table is empty (i.e. fresh database)
ROLES_COUNT=$(php artisan tinker --execute="echo App\Models\Role::count();" 2>/dev/null | tr -d '\n\r ')
if [ "$ROLES_COUNT" = "0" ] || [ -z "$ROLES_COUNT" ]; then
    echo "🌱  Seeding database (first run)..."
    php artisan db:seed --force --no-interaction
    echo "✓  Database seeded."
else
    echo "✓  Database already seeded (${ROLES_COUNT} roles found)."
fi

# ── 8. Clear caches ──────────────────────────────────────────────────────────
php artisan config:clear
php artisan view:clear
php artisan cache:clear

echo "────────────────────────────────────────"
echo "  ✅  App ready — starting php-fpm"
echo "────────────────────────────────────────"

exec "$@"
