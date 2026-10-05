#!/bin/sh
# Arranque del contenedor: clave de la app, espera de la base, migraciones y seed demo.
set -e
cd /var/www/html

# APP_KEY: si no viene por variable de entorno, se genera una vez y se guarda
# en el volumen de storage (lo comparten la app y el worker de la cola).
if [ -z "$APP_KEY" ]; then
    if [ ! -f storage/app/.app_key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/.app_key
    fi
    APP_KEY="$(cat storage/app/.app_key)"
    export APP_KEY
fi

echo "Esperando a la base de datos ${DB_HOST}:${DB_PORT}..."
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
    sleep 2
done

if [ "$(id -u)" = "0" ]; then
    mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        php artisan migrate --force

        # Datos demo solo si la base está vacía
        USUARIOS=$(php -r '$p = new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo $p->query("SELECT COUNT(*) FROM users")->fetchColumn();')
        if [ "${SEED_DEMO:-false}" = "true" ] && [ "$USUARIOS" = "0" ]; then
            php artisan db:seed --force
        fi
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    chown -R www-data:www-data storage bootstrap/cache
fi

exec "$@"
